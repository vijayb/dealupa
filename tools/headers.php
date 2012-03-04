<?php


foreach (getallheaders() as $name => $value) {

	if (strcmp($name, "X-Cluster-Client-Ip") == 0) {

	   echo "$value\n";
}


}

?>