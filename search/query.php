<meta charset="utf-8" />

<?php
   set_time_limit(0);

ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once('Solr/Service.php');

$solr = new Apache_Solr_Service( 'localhost', '8983', '/solr' );

if ( !$solr->ping() ) {
  echo 'Solr service not responding.';
  exit;
} else {
  echo "Success\n";
}


$offset = 0;
$limit = 100000;

$queries = array(
		 // 40.1156,-75.6945
		 //"all_text:the AND location:[39,-77 TO 42,-73]"
		 $_GET['q']
		 //'all_text:detailing AND city_id:5 AND -expired:1'
                 //'-expired:1 AND all_text:burger'
                 //'all_text: '.$_GET['q']
                 );

foreach ( $queries as $query ) {
  $response = $solr->search( $query, $offset, $limit );

  if ( $response->getHttpStatus() == 200 ) {
    // print_r( $response->getRawResponse() );                                                                                            

    if ( $response->response->numFound > 0 ) {
      echo "$query <br />";

      foreach ( $response->response->docs as $doc ) {
        echo "$doc->id <a href='$doc->url' target=_blank>$doc->title</a> $doc->expired<br />";
      }

      echo '<br />';
    }
  }
  else {
    echo $response->getHttpStatusMessage();
  }
}


?>


