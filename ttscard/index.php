<?php

//defines database interactions
include('../db_defs.php');

//defines pack rarities
include('../packgendefs.php');

//defines card functions
include('../cardfunctions.php');

//makes the file output as plain text instead of html
header('Content-type: text/plain');

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 */


$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

foreach($_GET as $key=>$value){
	$gclean[$key] = $conn->escape_string($value);
}

//card search conditions
$cnd = array();
//card set keyrune (string)
$cnd["set"] = $gclean["set"];
//card rarity in set (string)
$cnd["rarity"] = $item;
//timeshifted flag (1 or 0)
$cnd["timeshifted"] = 0;
//frameEffect (string)
$cnd["frameEffect"] = null;
//no frameEffect flag (1 or 0)
$cnd["noframeEffect"] = 0;
//card type (string)
$cnd["type"] = null;
//echoing SQL (string)
$cnd["sql"] = $gclean["sql"];
//set basic (string)
$cnd["basic"] = null;
//card name (string)
$cnd["name"] = $gclean["name"];
//collector's number (string)
$cnd["cn"] = $gclean["cardnumber"];
//maximum collector's number (string)
$cnd["max cn"] = null;
//card colors (string)
$cnd["colors"] = null;
//card color identity (string)
$cnd["colorIDs"] = null;
//colorless (1 or 0)
$cnd["colorless"] = 0;
//colorIDless (1 or 0)
$cnd["colorIDless"] = 0;


$card = getcard($cnd);

if (strlen($card["name"]) > 0){
	$pack[] = $card;
}else{
	//fuzzy if initial search fails
	$cnd["fuzzy"]="yes";
	$card = getcard($cnd);
	
	//double fail defaults to island
	if(strlen($card["name"]) > 0){
		$pack[] = $card;
	} else {
		$cnd = NULL;
		$cnd["name"] = "Island";
		$pack[] = getcard($cnd);
	}
}

printJSON($pack,$gclean["back"],$gclean["face"]);

?>
