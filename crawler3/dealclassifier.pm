#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2011
#
# Line format for classifier.dat:
# doc-components   category_id    score   match_pattern   filter_match_patterns
#
# doc-components is a string containing one or more of t,s,u, or b.
# t: title of the document
# s: subtitle of the document
# u: url of the document
# b: text ("body") of the document
#
# category_id: integer corresponding to a row in the Categories table
#
# score: an integer to add if we find match_patter
#
# match_pattern: a regular expression with no whitespace in it
#
# filter_match_patterns: one or more whitespace separated patterns. If we
# match one of these we ignore that we had a match on match_pattern.
#
# Example line:
#
# tsu   1    5    \bfood\b     massage     running
#
# This will give a score of 5 to category 1 if we find the word "food"
# in either the title, subtitle or url of the document, so long as the
# patterns "massage" or "running" aren't found in those document components.
{
    package dealclassifier;
    
    use strict;
    use warnings;
    use deal;

    use constant {
        CLASSIFIER_FILE => "./classifier.dat"
    };

    initializeClassifier();
    my @classifier_lines;

    sub classifyDeal {
        if ($#_ != 1) { die "Incorrect usage of classifyDeal, need 2 ".
                            "arguments\n"; }
        my $deal = shift;
        my $deal_content_ref = shift;

        my %scores;

        foreach my $line (@classifier_lines) {
            my @parts = @{$line};

            my $doc_component;
            #print "[$parts[0]]\n";
            if ($parts[0] eq "t" && defined($deal->title())) {
                $doc_component = $deal->title();
            }
            if ($parts[0] eq "s" && defined($deal->subtitle())) {
                $doc_component = $deal->subtitle();
            }
            if ($parts[0] eq "b" && defined($deal->text())) {
                $doc_component = $deal->text();
            }
            if ($parts[0] eq "u" && defined($deal->url())) {
                $doc_component = $deal->url();
            }

            #print "Check [$parts[3]] in $doc_component\n";

            if (defined($doc_component) && $doc_component =~ m/$parts[3]/i) {
                my $negative_match = 0;
                for (my $i=4; $i<= $#parts; $i++) {
                    if ($doc_component =~ /$parts[$i]/i) {
                    $negative_match = 1;
                    last;
                    }
                }

                if (!$negative_match) {
                    if (defined($scores{$parts[1]})) {
                    $scores{$parts[1]} += $parts[2];
                    } else {
                    $scores{$parts[1]} = $parts[2];
                    }
                }
            }
        }

        foreach my $category (sort {$scores{$b} <=> $scores{$a}} keys %scores) {
            $deal->category_id($category);
            last;
        }
    }


    sub initializeClassifier {
        unless(open(FILE, CLASSIFIER_FILE)) {
            die "Unable to open classifier file [".CLASSIFIER_FILE."]\n";
        }

        while (my $line = <FILE>) {
            chomp($line);

            # skip comments:
            if ($line =~ /^\s*#/) {
                next;
            }
            my @parts = split(/\s+/, $line);

            if ($#parts < 3) {
                die "Malformed line in classifier file [$line]\n".
                    "At least 4 components per line needed.\n";
            }

            my @chars = split(//, $parts[0]);
            
            foreach my $char (@chars) {
                my @classifier_line;
                        
                if ($char ne "t" && $char ne "s" &&
                    $char ne "b" && $char ne "u") {
                    die "Malformed line in classifier file, [$line]\n".
                    "First component must be either t,s,b or u\n";
                }
                push(@classifier_line, $char);
                
                if ($parts[1] !~ /^[1-9][0-9]*$/) {
                    die "Malformed line in classifier file [$line]\n".
                    "Second component must be a category id ".
                    "(postive int)\n";
                }
                push(@classifier_line, $parts[1]);
                
                if ($parts[2] !~ /^-?[0-9]+$/) {
                    die "Malformed line in classifier file [$line]\n".
                    "Second component (score) must be an integer\n";
                }
                push(@classifier_line, $parts[2]);
                
                for (my $i=3; $i <= $#parts; $i++) {
                    my $regex = eval { qr/$parts[$i]/ };
                    if (!defined($regex)) {
                        die "Malformed line in classifier file [$line]\n".
                            "Pattern [$parts[$i]] is not a valid regex\n";
                    }
                    
                    push(@classifier_line, $parts[$i]);
                }

                
                push(@classifier_lines, \@classifier_line);
            }
        }
    }

    1;
}
