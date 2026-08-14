<?php

//consume
include('../consume.php');

//defines pack rarities
include('../packgendefs.php');

//defines card functions
include('../cardfunctions.php');

//makes the file output as plain text instead of html
header('Content-type: text/plain');


$ch = curl_init($gclean["url"]);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, "GiantweevilProxyWorker/1.0");

$out = curl_exec($ch);

curl_close($ch);

if(preg_match("<!DOCTYPE html>", $out)){
	echo "Got website instead of text response";
	exit;
}

$cardnames = null;
$cardsetnum = null;
$section = null;
$cardsuuid = null;

//Handle bare decklists separately from Scryfall JSON
if(isset($gclean["scryfall"])){

	//Scryfall
	$cardsjson = json_decode($out, true)["entries"];
	foreach($cardsjson as $cardsection){
		foreach($cardsection as $cardjson){
			for($i = 1; $i <= $cardjson["count"]; $i++){
				if($cardjson["card_digest"] <> null){
					$sid = $cardjson["card_digest"]["id"];
					$section = $cardjson["section"];
					if($section == "nonlands" or $section == "lands"){
						$section = "";
					}
					$cardsuuid[] = [
						"uuid" => sid2uuid($sid),
						"note" => $section,
					];
				}
			}
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

				$cardnames[] = [ "name" => $numname[2], "note" => $section ];

			}

			//# [setcode] name
		}elseif($line != "" and preg_match("/^([0-9]+)\s\[([A-Za-z0-9].*)\]\s([^[].*)/", $line, $numname) == 1){

			for($i = 0; $i < $numname[1]; $i++){

				$cardnames[] = [ "set" => $numname[2], "name" => $numname[3], "note" => $section ];

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

if(isset($gclean["back"])){
	$back = $gclean["back"];
}

if(isset($gclean["note"])){
	$note = $gclean["note"];
}

printJSON($pack, $back, null, $ipos, $irot, $iscl, $note, $gclean["GUID"]);

?>
