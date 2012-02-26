#!/usr/bin/php  
<?php  
$host = "127.0.0.1";  
$port = 11211;  
$lookupKey = "";  
$limit = 10000;  
  
$time = time();  
  
foreach ($argv as $key => $arg) {  
  switch ($arg) {  
  case '-h':  
    $host = $argv[$key + 1];  
    break;  
  case '-p':  
    $port = $argv[$key + 1];  
    break;  
  case '-s':  
    $lookupKey = $argv[$key + 1];  
    break;  
  case '-l':  
    $limit = $argv[$key + 1];  
  }  
}  
  
$memcache = memcache_connect($host, $port);  
  
$list = array();  
$allSlabs = $memcache->getExtendedStats('slabs');  
$items = $memcache->getExtendedStats('items');  
  
foreach ($allSlabs as $server => $slabs) {  
  foreach ($slabs as $slabId => $slabMeta) {  
    if (!is_numeric($slabId)) {  
      continue;  
    }  
  
    $cdump = $memcache->getExtendedStats('cachedump', (int)$slabId, $limit);  
  
    foreach ($cdump as $server => $entries) {  
      if (!$entries) {  
	continue;  
      }  
  
      foreach($entries as $eName => $eData) {  
	$list[$eName] = array(  
			      'key' => $eName,  
			      'slabId' => $slabId,  
			      'size' => $eData[0],  
                    'age' => $eData[1]  
				);  
      }  
    }  
  }  
}  
  
ksort($list);  
  
if (!empty($lookupKey)) {  
  echo "Searching for keys that contain: '{$lookupKey}'\n";  
  foreach ($list as $row) {  
    if (strpos($row['key'], $lookupKey) !== FALSE) {  
      echo "Key: {$row['key']}, size: {$row['size']}b, age: ", ($time - $row['age']), "s, slab id: {$row['slabId']}\n";  
    }  
  }  
} else {  
  echo "Printing out all keys\n";  
  foreach ($list as $row) {  
    echo "Key: {$row['key']}, size: {$row['size']}b, age: ", ($time - $row['age']), "s, slab id: {$row['slabId']}\n";  
  }  
} 
