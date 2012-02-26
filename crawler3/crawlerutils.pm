#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July-September, 2011
#
{
    package crawlerutils;
    use strict;
    use warnings;

    use Date::Calc qw(Date_to_Time);

    sub gmtNow {
        my $offset = 0;
        if (@_) {
            if ($_[0] =~ /^(-?[0-9]+)$/) {
            $offset = $1 + 0;
            } else {
            die "Incorrect usage of gmtNow.\n";
            }
        }
        my @utc_time = gmtime(time()+$offset);
        return sprintf("%d-%02d-%02d %02d:%02d:%02d",1900+$utc_time[5],
                   $utc_time[4]+1, $utc_time[3], $utc_time[2],
                   $utc_time[1], $utc_time[0]);
    }


    sub diffDatetimesInSeconds {
        if ($#_ != 1 || !validDatetime($_[0]) || !validDatetime($_[1])) {
            die "Incorrect usage of diffDatetimesInSeconds.\n";

        }
        
        my ($year1,$year2,$month1,$month2,$day1,$day2,$hour1,$hour2,
            $minute1,$minute2,$second1,$second2);
        
        if ($_[0] =~ /^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/)
        {
            $year1 = $1+0;
            $month1 = $2+0;
            $day1 = $3+0;
            $hour1 = $4+0;
            $minute1 = $5+0;
            $second1 = $6+0;
        } else {
            die "Failure in diffDatetimesInSeconds.\n";
        }

        if ($_[1] =~ /^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/)
        {
            $year2 = $1+0;
            $month2 = $2+0;
            $day2 = $3+0;
            $hour2 = $4+0;
            $minute2 = $5+0;
            $second2 = $6+0;
        } else {
            die "Failure in diffDatetimesInSeconds.\n";
        }

        return Date_to_Time($year1,$month1,$day1,$hour1,$minute1,$second1) -
            Date_to_Time($year2,$month2,$day2,$hour2,$minute2,$second2);
    }
    
    sub validDatetime {
        if (@_) {
            # must be in format: YYYY-MM-DD HH:MM:SS
            return $_[0] =~
            /^[0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{2}:[0-9]{2}:[0-9]{2}$/
        }

        return 0;
    }

    1;
}
