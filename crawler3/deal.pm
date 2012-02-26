#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July-September, 2011
#
{
    package deal;
    use strict;
    use warnings;
    use crawlerutils;
    use Encode;

    sub new {
        my $self = {};

        $self->{url} = undef;
	$self->{affiliate_url} = undef;

        $self->{company_id} = undef;
        $self->{category_id} = undef;

        $self->{deadline} = undef; # when deal ends
        $self->{expires} = undef; # when deal voucher expires
        $self->{expired} = undef; # whether deal has expired
        $self->{upcoming} = undef; # whether deal is yet to begin
        $self->{title} = undef;
        $self->{subtitle} = undef;
        $self->{text} = undef;
        $self->{fine_print} = undef;
        $self->{price} = undef;
        $self->{value} = undef;
        $self->{num_purchased} = undef;
        $self->{fb_likes} = undef;
        $self->{fb_shares} = undef;

        $self->{image_urls} = ();

        $self->{name} = undef; # business name
        $self->{website} = undef;
        $self->{addresses} = ();
        $self->{phone} = undef;

        bless($self);
        return $self;
    }
    
    sub url {
        my $self = shift;
        if (@_) { $self->{url} = shift; }
        return $self->{url};
    }

    sub affiliate_url {
        my $self = shift;
        if (@_) { $self->{affiliate_url} = shift; }
        return $self->{affiliate_url};
    }


    sub company_id {
        my $self = shift;
        if (@_) { $self->{company_id} = shift; }
        return $self->{company_id};
    }

 
    sub category_id {
        my $self = shift;
        if (@_) { $self->{category_id} = shift; }
        return $self->{category_id};
    }

    sub title {
        my $self = shift;
        if (@_) { $self->{title} = shift; }
        return $self->{title};
    }

    sub subtitle {
        my $self = shift;
        if (@_) { $self->{subtitle} = shift; }
        return $self->{subtitle};
    }

    sub price {
        my $self = shift;
        if (@_) { $self->{price} = shift; }
        return $self->{price};
    }

    sub value {
        my $self = shift;
        if (@_) { $self->{value} = shift; }
        return $self->{value};
    }

    sub text {
        my $self = shift;
        if (@_) { $self->{text} = shift; }
        return $self->{text};
    }

    sub fine_print {
        my $self = shift;
        if (@_) { $self->{fine_print} = shift; }
        return $self->{fine_print};
    }

    sub num_purchased {
        my $self = shift;
        if (@_) { $self->{num_purchased} = shift; }
        return $self->{num_purchased};
    }

    sub fb_likes {
        my $self = shift;
        if (@_) { $self->{fb_likes} = shift; }
        return $self->{fb_likes};
    }

    sub fb_shares {
        my $self = shift;
        if (@_) { $self->{fb_shares} = shift; }
        return $self->{fb_shares};
    }

    sub deadline {
        my $self = shift;
        if (@_) { $self->{deadline} = shift; }
        return $self->{deadline};
    }

    sub expires {
        my $self = shift;
        if (@_) { $self->{expires} = shift; }
        return $self->{expires};
    }

    sub expired {
        my $self = shift;
        if (@_) { $self->{expired} = shift; }
        return $self->{expired};
    }

    sub upcoming {
        my $self = shift;
        if (@_) { $self->{upcoming} = shift; }
        return $self->{upcoming};
    }


    sub image_urls {
	my $self = shift;
        if (@_) { 
            my $image_url = shift;
            ${$self->{image_urls}}{$image_url} = 1;
        }
        return \%{$self->{image_urls}};
    }


    sub name {
        my $self = shift;
        if (@_) { $self->{name} = shift; }
        return $self->{name};
    }

    sub website {
        my $self = shift;
        if (@_) { $self->{website} = shift; }
        return $self->{website};
    }

    sub addresses {
        my $self = shift;
        if (@_) { 
            my $address = shift;
            ${$self->{addresses}}{$address} = 1;
        }
        return \%{$self->{addresses}};
    }


    sub phone {
        my $self = shift;
        if (@_) { $self->{phone} = shift; }
        return $self->{phone};
    }




    sub cleanUp {
        my $self = shift;

	# Outputting to stdout requires
	# using ":utf8";
	#my @chars = split("", $self->{title});
	#binmode STDOUT, ":utf8";
	#foreach my $char (@chars) {
	#    print "[$char]\n";
	#}

        if (defined($self->{title})) {
            $self->{title} =~ s/^\s+//;
            $self->{title} =~ s/\s+$//;
            $self->{title} =~ s/\s+/ /g;
        }
        if (defined($self->{subtitle})) {
            $self->{subtitle} =~ s/^\s+//;
            $self->{subtitle} =~ s/\s+$//;
            $self->{subtitle} =~ s/\s+/ /g;
        }
        if (defined($self->{price})) {
            $self->{price} =~ s/^\s+//;
            $self->{price} =~ s/\s+$//;
        }
        if (defined($self->{value})) {
            $self->{value} =~ s/^\s+//;
            $self->{value} =~ s/\s+$//;
        }
        if (defined($self->{text})) {
            $self->{text} =~ s/^\s+//;
            $self->{text} =~ s/\s+$//;
        }
        if (defined($self->{fine_print})) {
            $self->{fine_print} =~ s/^\s+//;
            $self->{fine_print} =~ s/\s+$//;
        }
        if (defined($self->{expires})) {
            $self->{expires} =~ s/^\s+//;
            $self->{expires} =~ s/\s+$//;
        }
        if (defined($self->{deadline})) {
            $self->{deadline} =~ s/^\s+//;
            $self->{deadline} =~ s/\s+$//;
        }
        if (defined($self->{name})) {
            $self->{name} =~ s/^\s+//;
            $self->{name} =~ s/\s+$//;
        }
        if (defined($self->{website})) {
            $self->{website} =~ s/^\s+//;
            $self->{website} =~ s/\s+$//;
        }
        if (defined($self->{phone})) {
            $self->{phone} =~ s/^\s+//;
            $self->{phone} =~ s/\s+$//;
        }

        foreach my $address (keys %{$self->{addresses}}) {
            $address =~ s/^\s+//;
            $address =~ s/\s+$//;
            $address =~ s/\s+/ /g;
        }
        foreach my $image_url (keys %{$self->{image_urls}}) {
            $image_url =~ s/^\s+//;
            $image_url =~ s/\s+$//;
            $image_url =~ s/\s+/ /g;
        }
    }


    sub check_for_extraction_error {
        my $self = shift;
	my @errors;

        if (!defined($self->url()) ||
            $self->{url} !~ /^http[s]?:\/\/.*/) {
            push(@errors, "url");
        }

        if (!defined($self->{company_id}) ||
            $self->{company_id} !~ /^[0-9]+$/) {
            push(@errors, "company_id");
        }

        if (!defined($self->{title}) || length($self->title) < 4) {
            push(@errors, "title");
        } 
        
        if (!defined($self->{price}) ||
            $self->{price} !~ /^[0-9]*\.?[0-9]+$/) {
            push(@errors, "price");
        }

        if (!defined($self->{value}) ||
            $self->{value} !~ /^[0-9]*\.?[0-9]+$/) {
            push(@errors, "value");
        }
        
        if (!defined($self->{num_purchased}) ||
            $self->{num_purchased} !~ /^-?[0-9]+$/) {
            push(@errors, "num purchased");
        }
        
        if (!defined($self->{expired}) || !$self->{expired} ||
            !defined($self->{upcoming}) || !$self->{upcoming}) {
            if (!defined($self->{deadline}) ||
		!crawlerutils::validDatetime($self->{deadline}))
            {
                push(@errors, "deadline");
            }
        }

        if (!defined($self->{expires}) ||
            !crawlerutils::validDatetime($self->{expires}))
        {
            push(@errors, "expires");
        }
        
        
        if (!defined($self->{text}) || length($self->{text}) < 20) {
            push(@errors, "text");
        } 

        if (!defined($self->{fine_print}) || length($self->{fine_print}) < 20) {
            push(@errors, "fine print");
        }


        if (!defined($self->{name}) || length($self->name) < 3) {
            push(@errors, "name");
        } 

        if (!defined($self->website()) ||
            $self->{website} !~ /^http[s]?:\/\/.*/) {
            push(@errors, "website");
        }

        if (!defined($self->{category_id}) ||
            $self->{category_id} !~ /^[0-9]+$/) {
            push(@errors, "category_id");
        }

        # TODO: get rid of the != 3 check. just a hack because
        # buywithme (company_id == 3) never has phone numbers.
        if ($self->{company_id} != 3 &&
            (!defined($self->{phone})  ||
             $self->{phone} !~
             /[0-9]{3}[^0-9]{0,2}[0-9]{3}[^0-9]{0,2}[0-9]{4}/)) {
            push(@errors, "phone");
        }
        
	if (scalar(keys(%{$self->{image_urls}})) == 0) {
            push(@errors, "image_urls");
        }

        if (scalar(keys(%{$self->{addresses}})) == 0) {
            push(@errors, "addresses");
        }

        return @errors;
    }


    1;
}

