#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) July-September, 2011
#
{
    package getargs;
    
    use strict;
    use warnings;
    use Getopt::Long qw(GetOptionsFromArray);
    
    sub getBasicArgs {
	if ($#_ != 8) {
	    die "Incorrect number of arguments in getBasicArgs.\n"; 
	}
	my $server_ref = shift;
	my $database_ref = shift;
	my $user_ref = shift;
	my $password_ref = shift;
	my $aws_access_key_ref = shift;
	my $aws_secret_key_ref = shift;
        my $work_id_ref = shift;
        my $company_id_ref = shift;
	my $force_work_ref = shift;
	
	my @args = @ARGV; # To avoid GetOptions deleting @ARGV
	my $result = GetOptionsFromArray(\@args,
					 "server=s" => $server_ref,
					 "database=s" => $database_ref,
					 "user=s" => $user_ref,
					 "password=s" => $password_ref,
					 "aws_access_key=s" =>
					 $aws_access_key_ref,
					 "aws_secret_key=s" =>
					 $aws_secret_key_ref,
					 "work_id=i" => $work_id_ref,        
					 "company_id=i" => $company_id_ref,
					 "force_work" => $force_work_ref);
	
	if (!defined($$server_ref) || !defined($$database_ref) ||
	    !defined($$user_ref) || !defined($$password_ref)) {
	    die "WorkQueue server, database, ".
		"user or user password not specified. Use options ".
		"--server, --database, --user and --password\n"; 
	}
	
	if (!defined($$force_work_ref)) {
	    $$force_work_ref = 0;
	}
    }
    
    1;
}
