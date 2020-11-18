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

$ipos = null;
$irot = null;
$iscl = null;

if(isset($gclean["pos"])){

	$in = explode(",", $gclean["pos"]);

	$ipos = [ "x" => $in[0], "y" => $in[1], "z" => $in[2] ];

}

if(isset($gclean["rot"])){

	$in = explode(",", $gclean["rot"]);

	$irot = [ "x" => $in[0], "y" => $in[1], "z" => $in[2] ];

}

if(isset($gclean["scl"])){

	$in = explode(",", $gclean["scl"]);

	$iscl = [ "x" => $in[0], "y" => $in[1], "z" => $in[2] ];

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
$cnd["type"] = $gclean["type"];
//echoing SQL (string)
$cnd["sql"] = $gclean["sql"];
//set basic (string)
$cnd["basic"] = $gclean["basic"];
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
//get allprints
$cnd["allprints"] = $gclean["allprints"];
//get by gatherer
$cnd["multiverseid"] = $gclean["multiverseid"];

$pack = fuzzyget($cnd);

if(isset($gclean["packcheck"])){
	print_r($pack);
}

printJSON($pack,$gclean["back"],$gclean["face"], $ipos, $irot, $iscl, $gclean["note"]);

?>
