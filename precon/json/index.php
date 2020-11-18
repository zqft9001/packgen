<?php

//defines database interactions
include('../../db_defs.php');

//defines pack rarities
include('../../packgendefs.php');

//defines card functions
include('../../cardfunctions.php');

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


if(isset($gclean["search"])){

	$search = $gclean["search"];

	if(!preg_match('/^[a-zA-Z0-9 ]+$/', $search)){
		echo "illegal search string";
		exit;
	}


	$alldecks = scandir("./");

	$search = explode(" ", $search." .j");

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

	foreach($decks as $deck){
		echo preg_replace("/\.j.*$/", "", $deck)."\n";
	}
	exit;
}

$name = $gclean["name"];

$name = str_replace(" ", "_", $name);

if(!preg_match('/^[a-zA-Z0-9_]+$/', $name)){
	echo "illegal deck name";
	exit;
}

if(isset($gclean["delete"])){
	if(unlink(__DIR__."/".$name.".jason")){
		echo "deleted ".$name;
		exit;
	} else {
		echo "delete failed";
		exit;
	}
}

if(!isset($gclean["name"])){
	echo "specify a name for the deck";
	exit;
}

$pack = null;

if(isset($gclean["url"])){

	$ch = curl_init($gclean["url"]);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$out = curl_exec($ch);

	curl_close($ch);

	if(preg_match("<!DOCTYPE html>", $out)){
		echo "Invalid Format";
		exit;
	}

	$cardnames = null;
	$cardsetnum = null;
	$section = null;

	$lines = null;
	preg_match_all("/(.*[^\r\n])[\r\n]*/", $out, $lines);

	foreach($lines[1] as $line){

		$line = preg_replace("/\s?\/{1,2}\s?/", " // ", $line);

		$numname = null;

		$setcn = null;

		if($line != "" and preg_match("/([0-9]+)\s([^[].*)/", $line, $numname) == 1){

			for($i = 0; $i < $numname[1]; $i++){

				$cardnames[] = [ "name" => $numname[2], "note" => $section ];

			}

		}elseif($line != "" and preg_match("/([0-9]+)\s\[[A-Za-z0-9]{3}\]\s([^[].*)/", $line, $numname) == 1){

			for($i = 0; $i < $numname[1]; $i++){

				$cardnames[] = [ "name" => $numname[2], "note" => $section ];

			}

		}elseif($line != "" and preg_match("/([0-9]+).*\[(.*):(.*)\]/", $line, $setcn) == 1){

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

	if(isset($cardnames)){

		foreach($cardnames as $cardname){

			$cnd = null;

			$cnd["name"] = $cardname["name"];
			$card = fuzzyget($cnd)[0];
			$card["note"] = $setcn["note"];
			$card["count"] = 1;
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
			$card["count"] = 1;
			$pack[] = $card;
		}

	}

}elseif(isset($gclean["uuid"])){
	foreach(explode(",", $gclean["uuid"]) as $item){
		$card = null;

		$card["uuid"] = $item;
		$card["count"] = 1;
		$pack[] = $card;
	}

}else{
	echo "No deck information provided";
	exit;
}

$json = ["data"=>["mainBoard"=>$pack]];

$text = json_encode($json);

$return = file_put_contents(__DIR__."/".$name.".jason", $text);

if($return === false){
	echo "Write failed";
} else {
	echo "Wrote deck to ".$name;
}

?>
