<?php

//defines database interactions
include('../db_defs.php');

//defines pack rarities
include('../packgendefs.php');

//defines card functions
include('../cardfunctions.php');

//makes the file output as plain text instead of html
header('Content-type: text/plain');

//setup connection
$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}


//escape all variables passed
foreach ($_GET as $key => $value){
	$gclean[$key]=$conn->escape_string($value);
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

$alldecks = scandir("./json");

$decks = null;
$uuids = null;
$deckname = null;

if(isset($gclean["JMP"])){

	foreach($alldecks as $deck){
		if(preg_match("/.*JMP.*/", $deck)){
			$decks[] = $deck;
		}
	}

	shuffle($decks);

	for($i = 0; $i < 2; $i++){

		$deckname = $deckname.$decks[$i];

		$json = json_decode(file_get_contents("./json/".$decks[$i]), true)["data"]["mainBoard"];

		foreach($json as $card){
			for($j = 0; $j < $card["count"]; $j++){	

				$uuids[] = $card["uuid"];

			}
		}

	}

}


if(isset($gclean["search"])){

	$search = explode(" ", $gclean["search"]);

	foreach($search as $find){
		foreach($alldecks as $deck){
			if(strpos(strtolower($deck), strtolower($find)) === False){

			} else {
				$decks[] = $deck;
			}
		}
		$alldecks = $decks;
		$decks = null;

	}

	$decks = $alldecks;


	shuffle($decks);

	$deckname = $decks[0];

	$fulljson = json_decode(file_get_contents("./json/".$decks[0]), true)["data"];

	$json = $fulljson["mainBoard"];

	if(count($fulljson["commander"])>0){
		$json[] = $fulljson["commander"][0];
	}


	foreach($json as $card){
		for($j = 0; $j < $card["count"]; $j++){	

			$uuids[] = $card["uuid"];

		}
	}


}

$pack = null;

foreach($uuids as $uuid){

	$card = fuzzyget($uuid, "id")[0];
	$card["cutsheet"] = "Deck: ".$deckname;
	$pack[] = $card;
}

if(count($pack) <= 0){

	$pack = fuzzyget($gclean["search"], "No matching decks for search");

}

printJSON($pack, $gclean["back"], null, $ipos, $irot, $iscl, $gclean["note"]);

?>
