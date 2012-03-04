<?php

$ip= $_SERVER['REMOTE_ADDR'];
echo "IP: $ip\n";
$request = "http://www.geobytes.com/IpLocator.htm?GetLocation&template=php3.txt&IpAddress=$ip";

echo "$request\n";

$tags = get_meta_tags($request);
echo "[",$tags['city'], "]\n";


?>