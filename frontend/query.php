<meta charset="utf-8" />

<?php
   set_time_limit(0);

ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once('Solr/Service.php');

$solr = new Apache_Solr_Service( '10.182.130.173', '8983', '/solr' );

if ( !$solr->ping() ) {
  echo 'Solr service not responding.';
  exit;
} else {
  echo "Success\n";
}


$offset = 0;
$limit = 100000;

$query = $_GET['q'];

$response = $solr->search( $query, $offset, $limit );

if ( $response->getHttpStatus() == 200 ) {

	if ( $response->response->numFound > 0 ) {
	  echo "$query <br />";

	  foreach ( $response->response->docs as $doc ) {
		echo "$doc->id <a href='$doc->url' target=_blank>$doc->title</a> $doc->deadline<br />";
	  }

	  echo '<br />';
	}
}
else {
	echo $response->getHttpStatusMessage();
}


?>


