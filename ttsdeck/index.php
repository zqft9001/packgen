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
$cnd["set"] = null;
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
$cnd["name"] = null;
//collector's number (string)
$cnd["cn"] = null;
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
$cnd["allprints"] = null;

$deck = null;

if(isset($gclean["cards"])){
	foreach(explode(";", $gclean["cards"]) as $cardname){

		$cnd["name"] = $cardname;

		$card = getcard($cnd);

		if (count($card) > 0){
			$deck[] = $card;
		}else{
			//fuzzy if initial search fails
			$cnd["fuzzy"]="yes";
			$card = getcard($cnd);

			//double fail gets nothing
			if(count($card) > 0){
				$deck[] = $card;
			}
		}
		$cnd["fuzzy"] = null;
	}
}elseif(isset($gclean["cardnumber"]) and isset($gclean["set"])){
	$numbers = explode(";", $gclean["cardnumber"]);
	$sets = explode(";", $gclean["set"]);

	for($i = 0; $i < count($sets); $i = $i + 1){

		$cnd["set"] = $sets[$i];
		$cnd["cn"] = $numbers[$i];

		$card = getcard($cnd);

		if (count($card) > 0){
			$deck[] = $card;
		}else{
			//fuzzy if initial search fails
			$cnd["fuzzy"]="yes";
			$card = getcard($cnd);

			//double fail gets nothing
			if(count($card) > 0){
				$deck[] = $card;
			}
		}
		$cnd["fuzzy"] = null;


	}

}

if(isset($gclean["deckcheck"])){
	print_r($deck);
}

printJSON($deck,$gclean["back"],$gclean["face"]);

?>
