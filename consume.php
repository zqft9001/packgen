<?php

//defines database interactions
include('db_defs.php');

//setup connection
$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

$gclean = null;

//escape all variables passed by get or post
foreach ($_REQUEST as $key => $value){
	$gclean[$key]=$conn->escape_string($value);
}

$pclean = null;

//put requests aren't cleaned as they are assumed to be json when used, they are converted from json instead
$pclean = file_get_contents( 'php://input', 'r' );
$pclean = json_decode($pclean, true);


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

?>
