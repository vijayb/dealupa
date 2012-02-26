#!/usr/bin/perl -w
# Copyright (c) 2010, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July, 2010
#
{
    package genericextractor;
    
    use strict;
    use warnings;
    use deal;

    my %stop_words = ( "and" => 1,
		       "the" => 1,
		       "any" => 1,
		       "are" => 1,
		       "but" => 1,
		       "can" => 1,
		       "did" => 1,
		       "get" => 1,
		       "got" => 1,
		       "had" => 1,
		       "has" => 1,
		       "was" => 1,
		       "let" => 1,
		       "not" => 1,
		       "is" => 1,
		       "at" => 1 ); 


    my @state_abbreviations = ('AK', 'AL', 'AR', 'AZ', 'CA', 'CO', 'CT', 'DC',
			       'DE', 'FL', 'GA', 'HI', 'IA', 'ID', 'IL', 'IN',
			       'KS', 'KY', 'LA', 'MA', 'MD', 'ME', 'MI', 'MN',
			       'MO', 'MS', 'MT', 'NC', 'ND', 'NE', 'NH', 'NJ',
			       'NM', 'NV', 'NY', 'OH', 'OK', 'OR', 'PA', 'RI',
			       'SC', 'SD', 'TN', 'TX', 'UT', 'VA', 'VT', 'WA',
			       'WI', 'WV', 'WY', 'AB', 'BC', 'MB', 'NB', 'NL',
			       'NT', 'NS', 'NU', 'ON', 'PE', 'QC', 'SK', 'YT');

    my @state_names = ('ALABAMA', 'ALASKA', 'ARIZONA', 'ARKANSAS',
		       'CALIFORNIA', 'COLORADO', 'CONNECTICUT', 'DELAWARE',
		       'FLORIDA', 'GEORGIA', 'HAWAII', 'IDAHO', 'ILLINOIS',
		       'INDIANA', 'IOWA', 'KANSAS', 'KENTUCKY', 'LOUISIANA',
		       'MAINE', 'MARYLAND', 'MASSACHUSETTS', 'MICHIGAN',
		       'MINNESOTA', 'MISSISSIPPI', 'MISSOURI', 'MONTANA',
		       'NEBRASKA', 'NEVADA', 'NEW HAMPSHIRE', 'NEW JERSEY',
		       'NEW MEXICO', 'NEW YORK', 'NORTH CAROLINA',
		       'NORTH DAKOTA', 'OHIO', 'OKLAHOMA', 'OREGON',
		       'PENNSYLVANIA', 'RHODE ISLAND', 'SOUTH CAROLINA',
		       'SOUTH DAKOTA', 'TENNESSEE', 'TEXAS', 'UTAH', 'VERMONT',
		       'VIRGINIA', 'WASHINGTON', 'WEST VIRGINIA', 'WISCONSIN',
		       'WYOMING', 'ALBERTA', 'BRITISH COLUMBIA', 'MANITOBA',
		       'NEW BRUNSWICK', 'NEWFOUNDLAND',
		       'NEWFOUNDLAND AND LABRADOR', 'NOVA SCOTIA', 'ONTARIO',
		       'PRINCE EDWARD ISLAND', 'QUEBEC', 'SASKATCHEWAN');

    my %states;
    foreach my $state (@state_abbreviations, @state_names) {
	$states{$state} = 1;
    }

    sub isState {
	if (@_) {
	    return defined($states{uc($_[0])});
	}

	return 0;
    }

    sub containsPattern {
	if ($#_ != 1) { die "Incorrect usage of containsPattern\n"; }

	my $deal_content_ref = shift;
	my $pattern = shift;

	my $regex = eval { qr/$pattern/ };
	if (!defined($regex)) {
	    die "Unable to parse regex $pattern in containsPattern\n";
	}

	my @lines = split(/\n/, $$deal_content_ref);
	my $match;
	foreach my $line (@lines) {
	    if ($line =~ /$regex/) {
		return 1;
	    }
	}

	return 0;
    }

    sub extractFirstPatternMatched {
	if ($#_ < 1) { die "Incorrect usage of extractFirstPatternMatched\n"; }

	my $deal_content_ref = shift;
	my $pattern = shift;

	my $regex = eval { qr/$pattern/ };
	if (!defined($regex)) {
	    die "Unable to parse regex $pattern in ".
		"extractFirstPatternMatched\n";
	}

	my @lines = split(/\n/, $$deal_content_ref);
	my $match;
	foreach my $line (@lines) {
	    if ($line =~ /$regex/) {
		$match = $1;
		last;
	    }
	}

	if (defined($match) && @_) {
	    foreach my $filter (@_) {
		$match =~ s/$filter//g;
	    }
	}
	return $match;
    }

    sub extractMPatterns {
	if ($#_ < 2) { die "Incorrect usage of extractMPatterns\n"; }

	my $max_matches = shift;
	my $deal_content_ref = shift;
	my $pattern = shift;

	my $regex = eval { qr/$pattern/ };
	if (!defined($regex)) {
	    die "Unable to parse regex $pattern in extractMPatterns\n";
	}

	my @lines = split(/\n/, $$deal_content_ref);
	my @matches;
	foreach my $line (@lines) {
	    if ($line =~ /$regex/) {
		push(@matches, $1);
	    }
	}

	if (@_) {
	    foreach my $filter (@_) {
		foreach my $match (@matches) {
		    $match =~ s/$filter//g;
		}
	    }
	}

	return @matches;
    }


    sub extractBetweenPatterns {
	extractBetweenPatternsN(-1, @_);
    }

    sub extractBetweenPatternsN {
	if ($#_ < 3) { die "Incorrect usage of extractBetweenPatternsN\n"; }

	my $max_lines = shift;	
	my $deal_content_ref = shift;
	my $start_pattern = shift;
	my $end_pattern = shift;


	my $start_regex = eval { qr/$start_pattern/ };
	my $end_regex = eval { qr/$end_pattern/ };
	if (!defined($start_regex) || !defined($end_regex)) {
	    die "Unable to parse regexs provided to extractBetweenPatterns.\n";
	}

	my @lines = split(/\n/, $$deal_content_ref);
	my $match;
	my $start = 0;
	my $num_lines = 0;
	foreach my $line (@lines) {
	    if ($start && $line =~ /$end_regex/) {
		last;
	    }
	    if ($start) {
		if (!defined($match)) {
		    $match = $line."\n";
		} else {
		    $match = $match.$line."\n";
		}
		$num_lines++;
	    }
	    if ($line =~ /$start_regex/) {
		$start = 1;
	    }

	    if ($max_lines > 0 && $num_lines >= $max_lines) {
		last;
	    }
	}

	if (defined($match) && @_) {
	    foreach my $filter (@_) {
		$match =~ s/$filter//g;
	    }
	}
	return $match;
    }



    sub extractMBetweenPatternsN {
	if ($#_ < 4) { die "Incorrect usage of extractMBetweenPatternsN\n"; }

	my $max_matches = shift;
	my $max_lines = shift;	
	my $deal_content_ref = shift;
	my $start_pattern = shift;
	my $end_pattern = shift;


	my $start_regex = eval { qr/$start_pattern/ };
	my $end_regex = eval { qr/$end_pattern/ };
	if (!defined($start_regex) || !defined($end_regex)) {
	    die "Unable to parse regexs provided to extractMBetweenPatterns.\n";
	}

	my @lines = split(/\n/, $$deal_content_ref);
	my @matches;
	my $match;
	my $start = 0;
	my $num_lines = 0;
	foreach my $line (@lines) {
	    if ($start) {
		if ($line =~ /$end_regex/ || 
		    ($max_lines > 0 && $num_lines >= $max_lines)) {
		    if (defined($match) && @_) {
			foreach my $filter (@_) {
			    $match =~ s/$filter//g;
			}
		    }
		    
		    push(@matches, $match);

		    $start = 0;
		    $num_lines = 0;
		    undef $match;
		}
	    }

	    if ($#matches >= $max_matches - 1) {
		last;
	    }

	    if ($start) {
		if (!defined($match)) {
		    $match = $line;
		} else {
		    $match = $match.$line;
		}
		$num_lines++;
	    }
	    if ($line =~ /$start_regex/ && $start==0) {
		$start = 1;
	    }
	}

	return @matches;
    }


    # Takes two strings representing business names and determines
    # whether they represent the same business.
    sub similarEnough {
	if ($#_ != 1 && $#_ != 2) { die "Incorrect usage of similarEnough\n"; }
	my $s1 = lc($_[0]);
	my $s2 = lc($_[1]);
	my $score_ref;
	# If caller provides a reference to a variable, we put the similarity
	# score of s1 and s2 into the variable.
	if (defined($_[2])) {
	    $score_ref = $_[2];
	}

	my $long;
	my $short;
	
	if (length($s1)> length($s2)) {
	    $long = $s1;
	    $short = $s2;
	} else {
	    $long = $s2;
	    $short = $s1;
	}
	
	# We don't care about non alphanumeric characters when comparing
	# the similarity of two names.
	$long =~ s/[^a-z0-9\s]//g;
	$short =~ s/[^a-z0-9\s]//g;
	
	my $edit_distance = editDistance($short, $long);
	
	my $score = (1.0*$edit_distance)/(1.0*length($long));
	my $threshold = 0.15 + length($long)/100.0;

	if (defined($score_ref)) {
	    $$score_ref = $score;
	}
	
	# If similar enough based on edit distance:
	if ($score <= $threshold) {
	    return 1;
	} 
	
	# Check for substrings:
	if (length($short) >= 5 && $long =~ /$short/i) {
	    return 1;
	}
	
	if (length($short) >= 3 && $long =~ /^$short/i) {
	    return 1;
	}
	
	
	# Now check if most of the words of $short are in $long. This handles
	# cases like "Antoni's Restaurant-Diner & Lounge" and "Antoni's
	# Diner" (all the words in the shorter string are in the longer one).
	my @shortwords = split(/\s/, $short);
	my $shortcount = 0;
	foreach my $shortword (@shortwords) {
	    if (length($shortword) >=3 && !defined($stop_words{$shortword}) &&
		$long =~ /$shortword/i) {
		$shortcount++;
	    }
	}
	if ($#shortwords > 0) {
	    $score =(1.0*$shortcount)/(1.0 + 1.0*$#shortwords);
	    if ($score > 0.7) { return 1; }
	}
	
	
	return 0;
    }


    # Return the Levenshtein distance (also called Edit distance) 
    # between two strings
    #
    # The Levenshtein distance (LD) is a measure of similarity between two
    # strings, denoted here by s1 and s2. The distance is the number of
    # deletions, insertions or substitutions required to transform s1 into
    # s2. The greater the distance, the more different the strings are.
    #
    # The algorithm employs a proximity matrix, which denotes the distances
    # between substrings of the two given strings. Read the embedded comments
    # for more info. If you want a deep understanding of the algorithm, print
    # the matrix for some test strings and study it
    #
    # The beauty of this system is that nothing is magical - the distance
    # is intuitively understandable by humans
    #
    # The distance is named after the Russian scientist Vladimir
    # Levenshtein, who devised the algorithm in 1965
    #
    sub editDistance {
	# $s1 and $s2 are the two strings
	# $len1 and $len2 are their respective lengths
	#
	my ($s1, $s2) = @_;
	my ($len1, $len2) = (length $s1, length $s2);
	
	# If one of the strings is empty, the distance is the length
	# of the other string
	#
	return $len2 if ($len1 == 0);
	return $len1 if ($len2 == 0);
	
	my %mat;
	
	# Init the distance matrix
	#
	# The first row to 0..$len1
	# The first column to 0..$len2
	# The rest to 0
	#
	# The first row and column are initialized so to denote distance
	# from the empty string
	#
	for (my $i = 0; $i <= $len1; ++$i) {
	    for (my $j = 0; $j <= $len2; ++$j) {
		$mat{$i}{$j} = 0;
		$mat{0}{$j} = $j;
	    }
	    
	    $mat{$i}{0} = $i;
	}
	
	# Some char-by-char processing is ahead, so prepare
	# array of chars from the strings
	#
	my @ar1 = split(//, $s1);
	my @ar2 = split(//, $s2);
	
	for (my $i = 1; $i <= $len1; ++$i) {
	    for (my $j = 1; $j <= $len2; ++$j) {
		# Set the cost to 1 iff the ith char of $s1
		# equals the jth of $s2
		# 
		# Denotes a substitution cost. When the char are equal
		# there is no need to substitute, so the cost is 0
		#
		my $cost = ($ar1[$i-1] eq $ar2[$j-1]) ? 0 : 1;
		
		# Cell $mat{$i}{$j} equals the minimum of:
		#
		# - The cell immediately above plus 1
		# - The cell immediately to the left plus 1
		# - The cell diagonally above and to the left plus the cost
		#
		# We can either insert a new char, delete a char or
		# substitute an existing char (with an associated cost)
		#
		$mat{$i}{$j} = min([$mat{$i-1}{$j} + 1,
				    $mat{$i}{$j-1} + 1,
				    $mat{$i-1}{$j-1} + $cost]);
	    }
	}
	
	# Finally, the Levenshtein distance equals the rightmost bottom cell
	# of the matrix
	#
	# Note that $mat{$x}{$y} denotes the distance between the substrings
	# 1..$x and 1..$y
	#
	return $mat{$len1}{$len2};
    }

    # minimal element of a list
    #
    sub min {
	my @list = @{$_[0]};
	my $min = $list[0];
	
	foreach my $i (@list) {
	    $min = $i if ($i < $min);
	}
	
	return $min;
    }

    1;
}
