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

$pack = gettokens($gclean);

if(count($pack) <= 0){
	exit;
}

printJSON($pack,$gclean["back"],$gclean["face"],$ipos, $irot, $iscl, $gclean["note"]);

?>
