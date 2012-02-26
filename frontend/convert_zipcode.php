<?php

require("db_user.php");

// Start XML file, create parent node

$dom = new DOMDocument("1.0");
$node = $dom->createElement("zips");
$parnode = $dom->appendChild($node);

$zip = mysql_real_escape_string($_GET["zip"]);

$query = "SELECT * FROM Zipcodes WHERE zip=" . $zip;

$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

header("Content-type: text/xml");

// Iterate through the rows, adding XML nodes for each
while ($row = @mysql_fetch_assoc($result)){

	// ADD TO XML DOCUMENT NODE
	$node = $dom->createElement("zip");
	$newnode = $parnode->appendChild($node);

	$newnode->setAttribute("lat", $row['latitude']);
	$newnode->setAttribute("lng", $row['longitude']);
}

echo $dom->saveXML();

?>