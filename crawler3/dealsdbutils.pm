#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) November, 2011
#
{
    package dealsdbutils;
    use strict;
    use warnings;
    use DBI;    
    use Digest::SHA1 qw(sha1 sha1_hex);
    use Cache::Memcached;

    # Used for extracting info from the Facebook JSON response
    # to how many likes/shares a given URL has:
    use JSON::XS;
    my $json_coder = JSON::XS->new->utf8->allow_nonref;
    use deal;

    use constant {
	DUP_EXPIRE_SECONDS => 86400 # 1 day
    };

    # Given a URL, get its ID in the specified database
    # (i.e, the database handle given as the first parameter)
    sub getDealId {
	my $deals_dbh = shift;
	my $deal_url = shift;

	my $deal_id;
	my $deal_url_hash = sha1($deal_url);
	
	# Try find the id for the given deal_url
	my $sql = "select id from Deals where url_hash=?";
	my $sth = $deals_dbh->prepare($sql);
	$sth->bind_param(1, $deal_url_hash);
	$sth->execute() || return 0;
	$sth->bind_col(1, \$deal_id);
	$sth->fetch();

	if (!defined($deal_id)) {
	    return 0;
	}

	return $deal_id;
    }

    sub createDealId {
	my $deals_dbh = shift;
	my $deal_url = shift;
	my $company_id = shift;

	my $deal_url_hash = sha1($deal_url);

	my $sql = "insert into Deals (url, company_id, discovered, ".
	    "last_updated) values (?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP()) ".
	    "on duplicate key update id=id";
	my $sth = $deals_dbh->prepare($sql);
	$sth->bind_param(1, $deal_url);
	$sth->bind_param(2, $company_id);
	$sth->execute() || return 0;
	
	
	return getDealId($deals_dbh, $deal_url);
    }


    # Return whether the given deal (described by $deal_id) is present
    # in the table specified by $table. All the ancillary deals
    # tables (Addresses, Cities, Categories and Images) have deal_id
    # as an index. This method is used to avoid doing reinserts by the
    # crawler, if the deal has already been inserted. The benefit of this
    # is that if the crawler/extractor makes a mistake for a given document
    # it can be manually fixed, without the crawler rewriting over the
    # manual fix later.
    sub inTable {
	my $deals_dbh = shift;
	my $deal_id = shift;
	my $table = shift;
	
	my $sql = "select id from $table where deal_id=$deal_id";
	my $sth = $deals_dbh->prepare($sql);
	$sth->execute();

	if ($sth->fetchrow_array()) { 
	    return 1; 
	}

	return 0;
    }


    # Given a deal ID, get the value of the column with field_name.
    # undef will be return if it doesn't exist
    sub getField {
	my $deals_dbh = shift;
	my $deal_id = shift;
	my $field_name = shift;

	my $field;

	# Try find field for the given deal ID
	my $sql = "select $field_name from Deals where id=?";
	my $sth = $deals_dbh->prepare($sql);
	$sth->bind_param(1, $deal_id);
	$sth->execute() || return 0;
	$sth->bind_col(1, \$field);
	$sth->fetch();

	return $field;
    }


    sub isDup {
	my $deal = shift;
	my $deal_id = shift;
	my $dup_server = shift;

	# $company_ids_array_ref provides the list of companies
	# for which we will search for dups from, in addition
	# to the company_id of the deal itself. If the array is empty
	# then we only look for dups within a single company's set
	# of deals
	my $company_ids_array_ref = shift;

	#print "[$dup_server,$deal_id]\n";
	my $memd = new Cache::Memcached {
	    'servers' => [$dup_server.":11211"],
	};

	my $title;
	my $company_id;
	if (!defined($deal->company_id()) || !defined($deal->title())) {
	    return 0;
	}
	$title = $deal->title();
	$company_id = $deal->company_id();

	if (defined($deal->subtitle())) {
	    $title = $title.$deal->subtitle();
	} elsif (defined($deal->name())) {
	    $title = $title.$deal->name();
	} elsif (defined($deal->website())) {
	    $title = $title.$deal->website();
	} elsif (defined($deal->price())) {
	    $title = $title.$deal->price();
	} elsif (defined($deal->phone())) {
	    $title = $title.$deal->phone();
	}

	# Normalize the title, striping non-alphanumeric characters and
	# conflating runs of whitespace
	$title =~ s/[^A-Za-z0-9\s]//g;
	$title =~ s/^\s*//;
	$title =~ s/\s*$//;
	$title =~ s/\s+/ /g;
	# If we don't have a long enough title, then it's dangerous to
	# use it as a dup lookup string, so just say it's not a dup:
	if (length($title) < 3) {
	    return 0;
	}

	# Memcache only allows keys that are 250 chars long and don't
	# contain control characters, like whitespace. So let's just
	# make the memcache key the SHA1 hash of the company_id+title. Indeud.
	my $dup_hash = sha1_hex($deal->company_id().":$title");
	#print "[$dup_hash]\n";
	
	$memd->add($dup_hash, $deal_id, DUP_EXPIRE_SECONDS);
	my $dup_id = $memd->get($dup_hash);
	#print "[$dup_id]\n";
	if (!defined($dup_id)) {
	    # This should only happen if for some reason we failed
	    # to connect to the dup server
	    #print "Uh oh\n";

	    return -1;
	}

	# Check for dups in other companies:
	foreach my $company_id (@{$company_ids_array_ref}) {
	    my $my_dup_hash = sha1_hex($company_id.":$title");
	    my $my_dup_id = $memd->get($my_dup_hash);
	    
	    if (defined($my_dup_id)) {
		$dup_id = $my_dup_id;
		last;
	    }
	}


	# For convenience we will also create a mapping in memcache
	# from the deal_id to its dup_id. This is useful for the
	# hub_crawler to add city_ids to the canonical deal, rather than
	# its dups. We prepend "dup" to the deal_id to avoid clashing
	# with other tools that might want to put deal_ids into memcache
	$memd->add("dup".$deal_id, $dup_id, DUP_EXPIRE_SECONDS);

	if ($dup_id == $deal_id) {
	    # Since the given deal isn't a dup, we will re-set it in memcache.
	    # The point of resetting it is that its expiration field will
	    # be pushed further out into the future, DUP_EXPIRE_SECONDS from now.
	    # $memd->add will not do a reset if a key is already present, which
	    # is why we need to do a $memd->set here.
	    # If we didn't do this the canonical deal in a set of dups might
	    # expire, even tho it was still recrawlable. 
	    $memd->set($dup_hash, $deal_id, DUP_EXPIRE_SECONDS);
	    #print "not a dup!\n";
	    return 0;
	}
	
	return $dup_id;
    }


    # Return the dup_id for a given deal_id. If there is no dup for the deal
    # then just return the deal_id that was passed in.
    sub findDupId {
	my $deal_id = shift;
	my $dup_server = shift;
	my $memd = new Cache::Memcached {
	    'servers' => [$dup_server.":11211"],
	};

	my $dup_id = $memd->get("dup".$deal_id);

	if (!defined($dup_id)) {
	    return $deal_id;
	}
	
	return $dup_id;
    }



    sub setFBInfo {
	my $deal = shift;
	my $url = shift;
	if (!defined($url)) {
	    $url = $deal->url();
	}
	
	my $response =
	    downloader::getURL("http://api.facebook.com/method/fql.query?".
			       "query=select%20total_count,like_count,comment_count,".
			       "share_count,click_count%20from%20link_stat%20where%20url=".
			       "'$url'&format=json");
	
	my $fb_info;
	if ($response->is_success && defined($response->content()) &&
	    length($response->content()) > 0 && 
	    # Make sure it looks like valid json before passing it to json parser,
	    # because the parser dies ungracefully if it gets non json content:
	    $response->content() !~ /error/ &&
	    $response->content() =~ /^\[\{.*\}\]$/)
	{
	    $fb_info = $response->content();
	} else {
	    return;
	}
	
	my @json = @{$json_coder->decode($fb_info)};
	if (@json) {
	    # Only set the fb_likes/fb_shares fields for a deal
	    # if we correctly extracted them as numbers and their
	    # values are greater than 0.
	    if (defined($json[0]{"like_count"}) && 
		$json[0]{"like_count"} =~ /^[0-9]+$/ &&
		$json[0]{"like_count"} > 0) {
		$deal->fb_likes($json[0]{"like_count"});
	    }
	    
	    if (defined($json[0]{"share_count"}) && 
		$json[0]{"share_count"} =~ /^[0-9]+$/ &&
		$json[0]{"share_count"} > 0) {
		$deal->fb_shares($json[0]{"share_count"});
	    }
	}
    }


    1;
}

