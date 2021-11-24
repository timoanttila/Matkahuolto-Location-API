<?php
function head($i = 2, $data = ""){
	$status = ["200 OK", "400 Bad Request", "405 Method Not Allowed"];
	header("Content-Type: application/json");
	header("HTTP/1.1 " . $status[$i]);
	if (!empty($data)) echo json_encode($data, JSON_UNESCAPED_SLASHES);
	die();
}

$method = strtolower($_SERVER['REQUEST_METHOD']);

// Retrieving sent POST information
if($method == "post"){
  $post = file_get_contents('php://input');
  if(empty($post) || !isset($post->street) || !isset($post->postal) || !isset($post->area)) head();
  $post = json_decode($post);
}
else head(2);

$vars =	"<?xml version='1.0' encoding='ISO-8859-1'?><MHSearchOfficesRequest><Login>1234567</Login><Version>1.0</Version><StreetAddress>{$post->street}</StreetAddress><PostalCode>{$post->postal}</PostalCode><City>{$post->area}</City><MaxResults>10</MaxResults><OfficeType></OfficeType></MHSearchOfficesRequest>";

$ch = curl_init("http://map.matkahuolto.fi/map24mh/searchoffices");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
$items = curl_exec($ch);
curl_close($ch);

$items = simplexml_load_string(urldecode($items), "SimpleXMLElement", LIBXML_NOCDATA);

if(isset($items->ErrorMsg))
	head(1, [
		"status" => 400,
		"en" => $item->ErrorMsg
	]);

foreach($items->Office as $item)
	$data[] = [
		"id" => (int)$item->Id,
		"name" => (string)$item->Name,
		"street" => (string)$item->StreetAddress,
		"postal" => (string)$item->PostalCode,
		"area" => (string)$item->City
	];

// Returns the response in json format
head(0, $data);
