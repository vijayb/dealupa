<?php


function getIP($withV6 = true) {
	 if (preg_match('/(?:inet\saddr.)([0-9\.]+)/', `/sbin/ifconfig`, $ip)) {
	 	 return $ip[0];
		 } else {
 		 return "no ip";
}
}

$ip = getIP();
echo "[$ip]\n";



?>