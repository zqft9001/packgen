<?php

//consume and db
include('../consume.php');

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


$pack = gettokens($gclean);

if(count($pack) <= 0){
	exit;
}

printJSON($pack,$gclean["back"],$gclean["face"],$ipos, $irot, $iscl, $gclean["note"], $gclean["GUID"]);

?>
