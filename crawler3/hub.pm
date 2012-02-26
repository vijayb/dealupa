#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July-September, 2011
#
{
    package hub;
    use strict;
    use warnings;

    sub new {
        my $self = {};

        $self->{url} = undef;
	$self->{redirect_url} = undef;
        $self->{company_id} = undef;
        $self->{category_id} = undef;
        $self->{use_cookie} = undef;
        $self->{use_password} = undef;
        $self->{post_form} = ();
	
        bless($self);
        return $self;
    }

    sub url {
        my $self = shift;
        if (@_) { $self->{url} = shift; }
        return $self->{url};
    }

    sub redirect_url {
        my $self = shift;
        if (@_) { $self->{redirect_url} = shift; }
        return $self->{redirect_url};
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

    sub use_cookie {
        my $self = shift;
        if (@_) { $self->{use_cookie} = shift; }
        return $self->{use_cookie};
    }

    sub use_password {
        my $self = shift;
        if (@_) { $self->{use_password} = shift; }
        return $self->{use_password};
    }


    sub post_form {
        my $self = shift;
        if (@_) { 
            # Post string comes as white space separated key/value pairs
            # for post form
            my $string = shift;
            my @post_values = split(/\s+/, $string);
            
            if ($#post_values >= 1 && (($#post_values+1)%2) == 0) {
                for (my $i=0; $i <= $#post_values; $i+=2) {
                    ${$self->{post_form}}{$post_values[$i]} =
                    $post_values[$i+1];
                }
            }
        }

        return \%{$self->{post_form}};
    }

    sub has_post_form {
        my $self = shift;
        return scalar(keys(%{$self->{post_form}})) > 0;
    }


    1;
}
