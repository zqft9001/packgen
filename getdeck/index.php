<?php

//consume
include('../consume.php');

//defines pack rarities
include('../packgendefs.php');

//defines card functions
include('../cardfunctions.php');

//defines moxfield api secret
include('../moxapi.php');

//makes the file output as plain text instead of html
header('Content-type: text/plain');

$url = null;
if(isset($gclean["url"])){
	$url = $gclean["url"];
}
$out = "";

//translate JSON verb links to correct API call links. Assumes anything it doesn't catch will break and exits early.
if(isset($gclean["JSON"])){

	$substrs = null;

	//scryfall
	if(preg_match("/.*scryfall.com\/.*\/decks\/([a-zA-Z0-9\-]+)/", $url, $substrs)){
		$url = "https://api.scryfall.com/decks/".$substrs[1]."/export/json/";
	}

	//archidekt
	if(preg_match("/.*archidekt.com\/decks\/([0-9]+)\/.*/", $url, $substrs)){
		$url = "https://archidekt.com/api/decks/".$substrs[1]."/";
	}

	//moxfield
	if(preg_match("/.*moxfield.com\/decks\/(.*)/", $url, $substrs)){
		echo "Moxfield is a work in progress";
		exit;
		$url = "https://api.moxfield.com/v2/decks/all/".$substrs[1]."/";
	}

	if(is_null($substrs)){
	echo "Deck site currently unsupported for JSON verb";
	exit;
	}

} else {
//Translate Deck verb links into correct links. Assumes anything it doesn't catch is already a link to text.
	$substrs = null;
	
	//tappedout
	if(preg_match("/.*tappedout.net\/mtg-decks\/([a-zA-Z0-9\-]+)\/.*/", $url, $substrs)){
		echo "Tappedout is currently blocking requests";
		exit;
		$url = "https://tappedout.net/mtg-decks/".$substrs[1]."/?fmt=txt";
	}
	
	//mtggoldfish.com
	if(preg_match("/.*mtggoldfish.com\/deck\/(.*)/", $url, $substrs)){
		$url = "https://www.mtggoldfish.com/deck/download/".$substrs[1]."/";
	}

	//scryfall
	if(preg_match("/.*scryfall.com\/.*\/decks\/([a-zA-Z0-9\-]+)/", $url, $substrs)){
		$url = "https://api.scryfall.com/decks/".$substrs[1]."/export/text/";
	}

	//archidekt
	if(preg_match("/.*archidekt.com\/decks\/([0-9]+)\/.*/", $url, $substrs)){
		$url = "https://archidekt.com/api/decks/".$substrs[1]."/";
	}

}

if(isset($url)){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if(preg_match("/moxfield/", $url,)){
		echo "Moxfield is a work in progress";
		exit;
		curl_setopt($ch, CURLOPT_USERAGENT, $moxapi);
	} else {
		curl_setopt($ch, CURLOPT_USERAGENT, "GiantweevilDecklistWorker/1.0");
	}

	$out = curl_exec($ch);

	curl_close($ch);
} elseif(isset($pclean["decklist"])){
	$out = $pclean["decklist"];
}

if(preg_match("<!DOCTYPE html>", $out)){
	echo "Got website instead of text response for ".$url;
	exit;
}

$cardnames = null;
$cardsetnum = null;
$section = null;
$cardsuuid = null;

//Handle bare decklists separately from JSON
if(isset($gclean["JSON"])){

	//Scryfall
	if(preg_match("/scryfall/", $url)){
		$cardsjson = json_decode($out, true)["entries"];
		foreach($cardsjson as $cardsection){
			foreach($cardsection as $cardjson){
				for($i = 1; $i <= $cardjson["count"]; $i++){
					if($cardjson["card_digest"] <> null){
						$sid = $cardjson["card_digest"]["id"];
						$section = $cardjson["section"];
						if($section == "nonlands" or $section == "lands"){
							$section = null;
						}
						$cardsuuid[] = [
							"uuid" => sid2uuid($sid),
							"note" => $section,
						];
					}
				}
			}
		}
	}

	//archidekt
	elseif(preg_match("/archidekt/", $url)){
		$cardsjson = json_decode($out, true)["cards"];
		foreach($cardsjson as $cardjson){
			
			//section handling
			if($cardjson["categories"][0] == "Commander"){
				$section = "Commander";
			}elseif($cardjson["categories"][0] == "Maybeboard"){
				continue;
			}elseif($cardjson["categories"][0] == "Sideboard"){
				$section = "Sideboard";
			}else{
				$section = null;
			}
			
			for($i = 1; $i <= $cardjson["quantity"]; $i++){
				$sid = $cardjson["card"]["uid"];
				$cardsuuid[] = [
					"uuid" => sid2uuid($sid),
					"note" => $section,
				];
			}
		}
	} else {
		echo "unsupported url ".$url;
		exit;
	}

}elseif(preg_match("/archidekt/", $url)){
		$cardsjson = json_decode($out, true)["cards"];
		foreach($cardsjson as $cardjson){
			
			//section handling
			if($cardjson["categories"][0] == "Commander"){
				$section = "Commander";
			}elseif($cardjson["categories"][0] == "Maybeboard"){
				continue;
			}elseif($cardjson["categories"][0] == "Sideboard"){
				$section = "Sideboard";
			}else{
				$section = null;
			}
			
			for($i = 1; $i <= $cardjson["quantity"]; $i++){
				$cardnames[] = [
					"name" => $cardjson["card"]["oracleCard"]["name"],
					"note" => $section,
				];
			}
		}

} else {

	$lines = null;
	preg_match_all("/(.*[^\r\n])[\r\n]*/", $out, $lines);

	foreach($lines[1] as $line){

		$line = preg_replace("/\s?\/{1,2}\s?/", " // ", $line);

		$numname = null;

		$setcn = null;


		if($line != "" and preg_match("/[Ss]ideboard.*/", $line, $numname) == 1){
			$section = "Sideboard";

			//#x name (setcode) cn
			//the arena/moxfield paste format
		}elseif($line != "" and preg_match("/^([0-9]+)[Xx]*\s(.*)\s\(([A-Za-z0-9]*)\)\s([A-Za-z0-9\-]*)/", $line, $setcn) == 1){
			for($i = 0; $i < $setcn[1]; $i++){
				$cardsetnum[] = [
					"set" => $setcn[3],
					"cn" => $setcn[4],
					"note" => $section,
				];

			}

			//# name
		} elseif($line != "" and preg_match("/^([0-9]+)[Xx]*\s(.*)/", $line, $numname) == 1 and !str_contains($line, "[")){

			for($i = 0; $i < $numname[1]; $i++){

				$cardnames[] = [ 
					"name" => $numname[2], 
					"note" => $section,
				];

			}

			//# [setcode] name
		}elseif($line != "" and preg_match("/^([0-9]+)\s\[([A-Za-z0-9].*)\]\s([^[].*)/", $line, $numname) == 1){

			for($i = 0; $i < $numname[1]; $i++){

				$cardnames[] = [ 
					"set" => $numname[2], 
					"name" => $numname[3], 
					"note" => $section,
			       	];

			}

			//# name [setcode:cn]
		}elseif($line != "" and preg_match("/^([0-9]+).*\[(.*):(.*)\]/", $line, $setcn) == 1){

			for($i = 0; $i < $setcn[1]; $i++){

				$cardsetnum[] = [
					"set" => $setcn[2],
					"cn" => $setcn[3],
					"note" => $section,
				];

			}
		} elseif($line != ""){
			$section = $line;
		}
	}
}

$pack = null;

if(isset($cardnames)){

	foreach($cardnames as $cardname){

		$cnd = null;

		if(isset($cardname["set"])){
			$cnd["set"] = $cardname["set"];
		}

		$cnd["name"] = $cardname["name"];
		$card = fuzzyget($cnd)[0];
		$card["note"] = $cardname["note"];
		$pack[] = $card;
	}

}

if(isset($cardsetnum)){

	foreach($cardsetnum as $setcn){

		$cnd = null;

		$cnd["set"] = $setcn["set"];
		$cnd["cn"] = $setcn["cn"];
		$card = fuzzyget($cnd)[0];
		$card["note"] = $setcn["note"];
		$pack[] = $card;
	}

}

if(isset($cardsuuid)){
	foreach($cardsuuid as $cuuid){

		$cnd = null;

		$cnd["id"] = $cuuid["uuid"];
		$card = fuzzyget($cnd)[0];
		$card["note"] = $cuuid["note"];
		$pack[] = $card;
	}
}

$back = null;
$note = null;

//sort deck by notes, blank notes first and then everything else
array_multisort(array_column($pack, 'note'), SORT_ASC, $pack);

if(isset($gclean["back"])){
	$back = $gclean["back"];
}

if(isset($gclean["note"])){
	$note = $gclean["note"];
}

printJSON($pack, $back, null, $ipos, $irot, $iscl, $note, $gclean["GUID"]);

?>
