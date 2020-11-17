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

if(isset($gclean["JMP"])){

	foreach($alldecks as $deck){
		if(preg_match("/.*JMP.*/", $deck)){
			$decks[] = $deck;
		}
	}

	shuffle($decks);


	for($i = 0; $i < 2; $i++){

		$json = json_decode(file_get_contents("./json/".$decks[$i]), true)["data"]["mainBoard"];

		foreach($json as $card){
			echo $card["count"]." [".$card["setCode"].":".$card["number"]."] ".$card["name"]."\n";
		}
	
	}

}


exit;

$pack = null;

foreach($cardnames as $cardname){

	$cnd = null;

	$cnd["name"] = $cardname["name"];
	$card = getcard($cnd);
	if (count($card) > 0){
		$card["note"] = $cardname["note"];
		$pack[] = $card;
	}else{
		//fuzzy if initial search fails
		$cnd["fuzzy"]="yes";
		$card = getcard($cnd);
		//double fail gets nothing
		if(count($card) > 0){
			$card["note"] = $cardname["note"];
			$pack[] = $card;
		} else {
			$card["note"] = "Fail to Find";
		}
	}
}

foreach($cardsetnum as $setcn){

	$cnd = null;

	$cnd["set"] = $setcn["set"];
	$cnd["cn"] = $setcn["cn"];
	$card = getcard($cnd);
	if (count($card) > 0){
		$card["note"] = $setcn["note"];
		$pack[] = $card;
	}else{
		//fuzzy if initial search fails
		$cnd["fuzzy"]="yes";
		$card = getcard($cnd);
		//double fail gets nothing
		if(count($card) > 0){
			$card["note"] = $setcn["note"];
			$pack[] = $card;
		}
	}
}

printJSON($pack, $gclean["back"], null, $ipos, $irot, $iscl, $gclean["note"]);

?>
