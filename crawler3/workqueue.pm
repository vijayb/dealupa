#!/usr/bin/perl -w
# Copyright (c) 2011, All Rights Reserved
# Author: Vijay Boyapati (vijayb@gmail.com) September, 2011
#
{
    package workqueue;
    
    use strict;
    use warnings;
    use getargs;
    use DBI;
    use Net::Address::IP::Local;
    use Term::ANSIColor;
    use IO::Handle;
    
    # Force autoflush on std/stderr, so we log
    # what happens if a process dies
    STDERR->autoflush(1);
    STDOUT->autoflush(1);

    use constant {
        # index of status field in worker's table:
	WORKERS_TABLE_STATUS_INDEX => 4
    };
    
    my ($server, $database, $user, $password, $work_id, $company_id,
	$force_work);
    getargs::getBasicArgs(\$server, \$database, \$user, \$password,
			  \$work_id, \$company_id, \$force_work);

    my @workqueue_db_params = ("DBI:mysql:$database;host=$server;mysql_ssl=1",
			       $user, $password,
			       {RaiseError => 1, AutoCommit => 1});

    my ($ip, $pid);
    $pid = $$;
    $ip = Net::Address::IP::Local->public;
    if (!defined($ip) || $ip eq "127.0.0.1") {
	die "Error: Unable to determine IP address for this process.\n";
    }
    

    # Global workqueue handler for sigint (ctrl-c) abort by user:
    my $current_work_id = 0;
    $SIG{'INT'} = "workqueue::cleanup";
    sub cleanup {
	if ($current_work_id > 0) {
	    abortedWork($current_work_id);
	}
	retireWorker();
	exit();
    }
	
    # Global workqueue variables:
    my $do_work_function_ref;
    my $work_type;
    my $num_tasks;
    my $delay_between_tasks;
    my $delay_after_tasks;
    my $request_shutdown = 0;
	
    sub registerWorker {
	unless ($#_ == 4) { die "Error: Incorrect usage of registerWorker.\n"; }
	
	$do_work_function_ref = shift;
	$work_type = shift;
	$num_tasks = shift;
	$delay_between_tasks = shift;
	$delay_after_tasks = shift;
	
	print "registering\n";
	my $workqueue_dbh =
	    DBI->connect_cached(@workqueue_db_params) || return 0;
	
	my $sth =
	    $workqueue_dbh->prepare("select * from Workers where ip='$ip' ".
				    "and pid='$pid'");
	$sth->execute() or return 0;
	my @result = $sth->fetchrow_array();
	
        if ($#result == -1) {
	    # If worker doesn't exist:
	    $workqueue_dbh->do("insert into Workers ".
			       "(ip, pid, type, spawned, heartbeat, ".
			       "status, latest_work_id) values ".
			       "('$ip', '$pid', '$work_type', ".
			       "UTC_TIMESTAMP(), UTC_TIMESTAMP(), ".
			       "'1', NULL)") or return 0;
	} elsif (defined($result[WORKERS_TABLE_STATUS_INDEX]) &&
		 $result[WORKERS_TABLE_STATUS_INDEX] == 0) {
	    # If worker exists but has previously shut down:
	    $workqueue_dbh->do("update Workers set type='$work_type', ".
			       "status='1', spawned=UTC_TIMESTAMP(), ".
			       "heartbeat=UTC_TIMESTAMP(), ".
			       "latest_work_id=NULL") or return 0;
	} else {# Worker already exists and is running:
	    return 0;
	}
	
	$sth->finish();
	return 1;
    }
    
    # Set status of worker to 0 (shut down)
    sub retireWorker {
	print "Retiring\n";
	my $workqueue_dbh =
	    DBI->connect_cached(@workqueue_db_params);
	
	$workqueue_dbh->do("update Workers set status='0' where ip='$ip' ".
			   "and pid='$pid'") || return 0;
        $workqueue_dbh->disconnect();
	return 1;
    }

    sub updateWorkerHeartBeat {
	my $workqueue_dbh =
	    DBI->connect_cached(@workqueue_db_params) || return 0;

	$workqueue_dbh->do("update Workers set heartbeat=UTC_TIMESTAMP() ".
			   "where ip='$ip' and pid='$pid'");
    }

    sub requestShutdown {
	$request_shutdown = 1;
    }
    
    sub shutdownRequested {
	if ($request_shutdown) { return 1; }

	my $workqueue_dbh =
	    DBI->connect_cached(@workqueue_db_params) || return 0;
	my $sql = "select force_shutdown from Workers where ".
	    "pid='$pid' and ip='$ip' and force_shutdown='1'";
	my $sth = $workqueue_dbh->prepare($sql);
	$sth->execute() || return 0;
	
	if ($sth->fetchrow_array()) {
	    # For the sql above, at most 1 row should be returned. If it's
	    # returned that means a manual shutdown of this worker has been
	    # requested
	    return 1;
	}
	return 0;
    }
    
    sub run {
	while (1) {
	    my $workqueue_dbh =
		DBI->connect_cached(@workqueue_db_params);
	    
	    if (!$workqueue_dbh) {
		print "Can't connect to workqueue database. Sleeping... ";
		sleep($delay_after_tasks);
		next;
	    }
	    
	    updateWorkerHeartBeat();

	    my @work_array;
	    my $count = workqueue::getAllWork(\@work_array);
	    print "Got $count pieces of work of type $work_type. $ip:$pid\n";
	    
	    foreach my $work_ref (@work_array) {
		$current_work_id = ${$work_ref}{"id"};
		workqueue::doingWork($current_work_id);
		print color 'yellow';
		print "Doing work with id : $current_work_id [".
		    ${$work_ref}{"work"}."]...\n";
		print color 'white';

		# default status for each piece of work:
		my $status = 3;
		my $status_message = "Status unknown";  
		
		my $output_server = ${$work_ref}{"output_server"};
		my $output_database = ${$work_ref}{"output_database"};
		my $output_dbh =
		    DBI->connect_cached("DBI:mysql:$output_database;".
					"host=$output_server;mysql_ssl=1",
					$user, $password,
					{RaiseError => 1,
					 #don't seem to need this because
					 #data is already utf8 encode(?)
					 #mysql_enable_utf8 => 1,
					 AutoCommit => 1});
		
		if (!$output_dbh) {
		    print "Can't connect to $output_server:$output_database ".
			"Sleeping... ";
		    $status = 2;
		    $status_message =
			"Can't connect to $output_server:$output_database";
		} else {
		    &{$do_work_function_ref}($work_ref,
					     $workqueue_dbh,
					     $output_dbh,
					     \$status,
					     \$status_message);
		}
		
		workqueue::completedWork($current_work_id,
					 $status, $status_message);
		print color 'yellow';
		print "Completed! Sleeping... ".time()."\n";
		print color 'white';
		$current_work_id = 0;
		sleep($delay_between_tasks);
	    }
	    print "Finished $count pieces of work. Sleeping... ".time()."\n";

	    if (shutdownRequested()) {
		print "Manual shutdown requested. Halting ...\n";
		retireWorker();
		exit();
	    }
	    sleep($delay_after_tasks);
	}
    }
    
    sub addWork {
        unless ($#_ == 6 || $#_ == 7) { die "Incorrect usage of addWork\n"; }	
	my $work = shift;
	my $type = shift;
	my $company_id = shift;
	my $frequency = shift;
	my $output_server = shift;
	my $output_database = shift;
	my $force_update = shift;
	my $work_info_ref;
	if (@_) { $work_info_ref = shift; }
	
	my $workqueue_dbh =
	    DBI->connect_cached(@workqueue_db_params) || return 0;	
	
	my $sql = "insert into WorkQueue (work, type, company_id,".
	    "frequency, output_server, output_database, created) ".
	    "values (?,?,?,?,?,?,UTC_TIMESTAMP()) ".
	    "on duplicate key update ";
	
     	my @work_params = ($work, $type, $company_id,
			   $frequency, $output_server, 
			   $output_database);
	
        if ($force_update) {
	    $sql = $sql."frequency=?, output_server=?, output_database=?";
	    push(@work_params, $frequency);
	    push(@work_params, $output_server);
	    push(@work_params, $output_database);			
	} else {
	    $sql = $sql."work=work";
	}
	
	my $sth = $workqueue_dbh->prepare($sql);
	for (my $i=0; $i <= $#work_params; $i++) {
	    $sth->bind_param($i+1, $work_params[$i]);
	}
	
	$sth->execute() || return 0;
	$sth->finish();
	
	
	# If we were passed a reference for work info, then we store
	# in it the fields from the work that was just inserted. Importantly
	# we look up the work row in the workqueue, because if it existed
	# prior to our addition of work above, then we want the values
	# that are actually on the work queue, which may or may not be the
	# same as the ones we tried to insert above.
	if (defined($work_info_ref)) {
	    $sql = "select id, company_id, frequency, output_server, ".
		"output_database from WorkQueue where work=? ".
		" and type=?";
	    $sth = $workqueue_dbh->prepare($sql);
	    $sth->bind_param(1, $work);
	    $sth->bind_param(2, $type);
	    $sth->execute() || return 0;
	    
	    if (my @work_info = $sth->fetchrow_array()) {
		${$work_info_ref}{"work"} = $work;
		${$work_info_ref}{"type"} = $type;
		${$work_info_ref}{"id"} = $work_info[0];
		${$work_info_ref}{"company_id"} = $work_info[1];
		${$work_info_ref}{"frequency"} = $work_info[2];
		${$work_info_ref}{"output_server"} = $work_info[3];
		${$work_info_ref}{"output_database"} = $work_info[4]; 		
	    }
	}
	return 1;
    }
    
    
    
    
    sub getAllWork {
        print "Attempting to get $num_tasks pieces of work\n";
	
        unless (@_) { die "Incorrect usage of getAllWork\n"; }
        my $work_array_ref = shift;
        
	# We don't use the stardard workqueue params
	# when connecting here, because we don't want to
	# commit until the end.
        my $dbh = DBI->connect("DBI:mysql:$database;host=$server;mysql_ssl=1",
			       $user, $password,
			       {RaiseError => 1, AutoCommit => 0}) || return 0;
	
        # Get jobs that have either not been started, or have previously
        # been completed and are old enough that we should do them again (this
        # only applies for jobs whose frequency is > 0)
	#
	# Because we have no auto commit in the connection (see above)
	# and because we're using select ... for update, rows selected by
	# query below will be locked until we get to the commit at the bottom
	# of the function. This is what we want. We don't want multiple
	# workers grabbing the same piece of work.
	my $work_filter = "";
	if (defined($work_id)) {
	    $work_filter = $work_filter."id='$work_id' and ";
	}
	if (defined($company_id)) {
	    $work_filter = $work_filter."company_id='$company_id' and ";
	}		
	
	my $frequency = "frequency";
	if ($force_work) {
	    $frequency = 0;
	}

        my $sql = "select id, work, type, company_id, frequency, ".
	    "output_server, output_database, ".
	    "TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), created)) from WorkQueue ".
	    "where type='$work_type' and $work_filter ((started is null) or ".
	    "((frequency > 0 or $force_work = 1) and completed is not null and ".
	    "(TIME_TO_SEC(TIMEDIFF(UTC_TIMESTAMP(), completed))) > ".
	    "$frequency)) order by completed ASC limit $num_tasks for update";

        my $sth = $dbh->prepare($sql);
        $sth->execute() || return 0;
        my @work_info;
        my $work_count = 0;
	
        while (@work_info = $sth->fetchrow_array()) {
            my %hash;
            $hash{"id"} = $work_info[0];
            $hash{"work"} = $work_info[1];
            $hash{"type"} = $work_info[2];
            $hash{"company_id"} = $work_info[3];
            $hash{"frequency"} = $work_info[4];
            $hash{"output_server"} = $work_info[5];
            $hash{"output_database"} = $work_info[6];          
            $hash{"age_seconds"} = $work_info[7];

            push(@{$work_array_ref}, \%hash);
            # Importantly, we set started to the current time and 
	    # completed to null, so no other workers will attempt
	    # to do this task (see sql above to understand why
	    # workers will not select this row in the workqueue
	    # when started is not null and complete is null)
            $sql = "update WorkQueue set started=UTC_TIMESTAMP(), ".
		"completed=null, worker_ip='$ip', worker_pid='$pid', ".
		"status=null, status_message=null ".
		"WHERE id=$work_info[0]";
            $dbh->do($sql);
            $work_count++;
        }
        
        $sth->finish();
        $dbh->commit();
        $dbh->disconnect();
        return $work_count;
    }
    
    
    sub doingWork {
        unless ($#_ == 0) { die "Incorrect usage of doingWork\n"; }
        my $id = shift;
	my $workqueue_dbh =
	    DBI->connect_cached(@workqueue_db_params) || return 0;	
	
        my $sql = "update Workers set latest_work_id='$id' ".
	    "where ip='$ip' and pid='$pid'";                  
        $workqueue_dbh->do($sql);
    }
    
    sub abortedWork {
        unless ($#_ == 0) { die "Incorrect usage of abortedWork\n"; }
        my $id = shift;
        print "Aborting work $id\n";
	my $workqueue_dbh =
	    DBI->connect_cached(@workqueue_db_params) || return 0;
	
        my $sql = "update WorkQueue set status='2', ".
	    "status_message='User aborted', completed=UTC_TIMESTAMP() ".
	    "where id='$id'";                  
        $workqueue_dbh->do($sql);
    }
    
    sub completedWork {
        unless ($#_ == 2) { die "Incorrect usage of getAllWork\n"; }
        my $id = shift;
        my $status = shift;
        my $status_message = shift;
	
	my $workqueue_dbh =
	    DBI->connect_cached(@workqueue_db_params) || return 0;
	
        my $sql = "update WorkQueue set status=?, status_message=?, ".
	    "completed=UTC_TIMESTAMP() where id=?";
	my $sth = $workqueue_dbh->prepare($sql);
	$sth->bind_param(1, $status);
	$sth->bind_param(2, $status_message);
	$sth->bind_param(3, $id);
	$sth->execute() || return 0;

	return 1;
    }
    
    
    sub updateFrequency {
	unless ($#_ == 1) { die "Incorrect usage of updateFrequency\n"; }
	my $id = shift;
	my $frequency = shift;
	
	my $workqueue_dbh =
	    DBI->connect_cached(@workqueue_db_params) || return 0;
	
        my $sql = "update WorkQueue set frequency=? where id=?";
	my $sth = $workqueue_dbh->prepare($sql);
	$sth->bind_param(1, $frequency);
	$sth->bind_param(2, $id);
	$sth->execute() || return 0;				
	
	return 1;
    }
    

    sub server {
	return $server;
    }

    sub database {
	return $database;
    }
    sub user {
        return $user;
    }
    
    sub password {
        return $password;
    }

    sub ip {
	return $ip;
    }

    sub pid {
	return $pid;
    }
    
    1;
}
