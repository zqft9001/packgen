<?php

//consume and db
include('../consume.php');

//defines pack rarities
include('../packgendefs.php');

//defines card functions
include('../cardfunctions.php');

//makes the file output as plain text instead of html
header('Content-type: text/plain');

$alldecks = scandir("./json");

$decks = null;
$uuids = null;
$deckname = null;

//Jumpstart Deck Handler

function jumpstartdeck($jumpstart){

	global $alldecks, $decks, $uuids, $deckname;	
	
	foreach($alldecks as $deck){
		if(preg_match("/.*".$jumpstart.".*/", $deck)){
			$decks[] = $deck;
		}
	}

	shuffle($decks);

	for($i = 0; $i < 2; $i++){

		$deckname = $deckname.$decks[$i];

		$json = json_decode(file_get_contents("./json/".$decks[$i]), true)["data"]["mainBoard"];

		foreach($json as $card){
			for($j = 0; $j < $card["count"]; $j++){	

				$uuids[] = array( "id" => $card["uuid"]);

			}
		}

	}
}
//Jumpstart overrides

if(isset($gclean["JMP"])){
	jumpstartdeck("JMP");
}

if(isset($gclean["TLE"])){
	jumpstartdeck("TLE");
}

if(isset($gclean["J22"])){
	jumpstartdeck("J22");
}

if(isset($gclean["J25"])){
	jumpstartdeck("J25");
}

//Precon and user uploaded decks


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
		foreach($fulljson["commander"] as $cm){
			$json[] = $cm + array("note" => "CMDR");
		}
	}

	if(count($fulljson["sideBoard"])>0){
		foreach($fulljson["sideBoard"] as $sb){
			$json[] = $sb + array("note" => "Sideboard");
		}
	}

	foreach($json as $card){
		for($j = 0; $j < $card["count"]; $j++){	
			if(isset($card["image"])){
				$uuids[] = array("id" => $card["uuid"], "image" => $card["image"]);
			} else {
				$uuids[] = array("id" => $card["uuid"], "note" => $card["note"]);
			}

		}
	}


}

//Spit out deck

$pack = null;

foreach($uuids as $uuid){

	$card = null;

	$card = fuzzyget($uuid["id"], "id")[0];
	$card["cutsheet"] = "Deck: ".$deckname;
	if(isset($uuid["image"])){$card["image"] = $uuid["image"];}
	if(isset($uuid["note"])){$card["note"] = $uuid["note"];}
	$pack[] = $card;
}

if(count($pack) <= 0){

	$pack = fuzzyget($gclean["search"], "No matching decks for search");

}

printJSON($pack, $gclean["back"], null, $ipos, $irot, $iscl, $gclean["note"], $gclean["GUID"]);

?>
