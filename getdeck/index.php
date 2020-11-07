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

$ch = curl_init($gclean["url"]);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$out = curl_exec($ch);

curl_close($ch);

if(preg_match("<!DOCTYPE html>", $out)){
	echo "Invalid Format";
	exit;
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

$cardnames = null;
$cardsetnum = null;
$section = null;

$lines = null;
preg_match_all("/(.*[^\r\n])[\r\n]*/", $out, $lines);

foreach($lines[1] as $line){

	$line = str_replace(" / ", " // ", $line);

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
