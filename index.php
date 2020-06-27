<?php

//defines database interactions
include('db_defs.php');

//defines pack rarities
include('packgendefs.php');

//defines card functions
include('cardfunctions.php');

//makes the file output as plain text istead of html
header('Content-type: text/plain');

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 */

$debug = $_GET["debug"];
//debug help

$help = $_GET["help"];
//gets pack information if "yes" or "only"

$set = $_GET["set"];
//Keyrune of set
//Grabs a random pack by default

$ptype = $_GET["ptype"];
//type of pack.
// ori - original rarity (default)
// r - oops all rares
// cur/curm - "standard" 15 card pack, curm has mythics
// cu - only commons and uncommons
// c - all commons
// u - all uncommons
// m - all mythics

$lands = $_GET["lands"];
//attempt lands in pack

$dupesflag = $_GET["dupes"];
//sets packs to allow or disallow duplicated. defaults to no duplicate cards.

if($dupesflag == "yes"){
	$nodupe = False;
} else {
	$nodupe = True;
}

$custom = $_GET["custom"];
//wacky packs
//kami - all legendary cards, rarity ignored, set ignored, 15 card pack.
//color - grabs only cards of a specific color (from set if specified)

//define array for adding cards to a pack
$pack = array();

//setup connection
$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

//escape string to prevent shenanigans
$search = $conn->escape_string($set);

//grabs a random keyrune if not provided. always one with a boosterV3.

if ($search == ""){
	$search = $conn->query("select randrune();")->fetch_assoc()["randrune()"];	
}

//get set info
$sql = "SELECT * FROM sets where sets.keyruneCode like '%".$search."%' and sets.boosterV3 is not null;";

$result = $conn->query($sql);

//it's not me, it's the query that is wrong
if ($result->num_rows < 1){
	$rarity = $stdrarity;
}

//Get booster distribution and keyrunecode
while ($row = $result->fetch_assoc()){

	If($row["boosterV3"] != NULL){
	$rarity = json_decode(str_replace("'", '"', $row["boosterV3"]));
	$set = $row["keyruneCode"];
}
}

//override rarity string

switch($ptype){
case "ori":
	break;
case "m":
	$rarity = $mrarity;
	break;
case "r":
	$rarity = $rrarity;
	break;
case "curm":
	$rarity = $curmrarity;
	break;
case "cur":
	$rarity = $currarity;
	break;
case "cu":
	$rarity = $curarity;
	break;
case "c":
	$rarity = $crarity;
	break;
case "u":
	$rarity = $urarity;
	break;
default:
	break;
}

//break for help only

if($help == "yes" or $help == "only"){
	print_r($set);
	echo "\n";
	print_r($rarity);
	if($help == "only"){
		exit;
	}
}


//Science occurs. We have a list of what rarity we need, now we need to pull every card of that rarity and grab one. Several times.

//To Fix:
//Unsets
//modern masters


//Broken for sets with sub-rarity, but MTGJSON does not recognize their authority
//Broken for marketing cards
//Broken for snow land slots
//Broken for token slots
//Broken for full art print slots

$futurecount = 0;
$contraptioncount = 0;

foreach( $rarity as $item ){

	$cnd = array();
	$cnd["set"] = $set;
	$cnd["rarity"] = $item;
	$cnd["timeshifted"] = 0;
	$cnd["frameEffect"] = null;
	$cnd["noframeEffect"] = 0;
	$cnd["type"] = null;
	$cnd["sql"] = $debug;
	$cnd["basic"] = null;
	$cnd["name"] = null;

	if($cnd["rarity"] == "marketing"){
		//eventually we'll put the phone cards in here. Somehow.
		continue;
	}

	//Double sided cards are a nightmare nightmare nightmare nightmare nightmare
	if($set == "SOI" or $set == "ISD" or $set == "EMN"){

		if( $cnd["rarity"] == ['common', 'double faced rare', 'double faced mythic rare']){
			if(rand(1,20) == 1){
				if(rand(1,8) == 1){
					$cnd["rarity"] = "mythic";
				} else {
					$cnd["rarity"] = "rare";
				}
				$cnd["frameEffect"] = "dfc";
			} else {
				$cnd["rarity"] = "common";
			}

		}

		if( $cnd["rarity"] == ['double faced common', 'double faced uncommon'] ){
			$cnd["rarity"] = raritygenerate("cu");
			$cnd["frameEffect"] = "dfc";
		}

		if( $cnd["rarity"] == "double faced" ){

			$cnd["frameEffect"] = "dfc";
			$cnd["rarity"] = raritygenerate("curm");
		}

		if( $cnd["rarity"] == ['land', 'checklist']){
			$cnd["rarity"] = "land";
		}

		if($cnd["frameEffect"] == null){
			$cnd["noframeEffect"] = 1;
		}


	}

	//foil handling for masters sets.
	if( $cnd["rarity"] == ['foil mythic rare', 'foil rare', 'foil uncommon', 'foil common'] ){
		$cnd["rarity"] = raritygenerate("curm");
	}

	//mythic is 1 out of every 8 rares if it's just those two.
	if( $cnd["rarity"] == ['rare', 'mythic rare']){
		if(rand(1,8) == 1){
			$cnd["rarity"] = "mythic";
		} else {
			$cnd["rarity"] = "rare";
		}
	}

	//1:4 rare to uncommon if it's just those two
	if( $cnd["rarity"] == ['rare', 'uncommon']){
		if(rand(1,4) == 4){
			$cnd["rarity"] = "rare";
		} else {
			$cnd["rarity"] = "uncommon";
		}
	}


	//identify if rarity is timeshifted or colorshifted, pull word timeshifted out after setting flag.

	//time spiral has array rarities and weird rules about how many shifted cards can be in a pack.
	//This "solves" those.
	if( is_array($cnd["rarity"]) and $set == "PLC"){
		if(rand(1,4) == 1){
			$cnd["rarity"] = $cnd["rarity"][0];
		} else {
			$cnd["rarity"] = $cnd["rarity"][1];
		}
	}

	if( is_array($cnd["rarity"]) and $set == "FUT" ){
		$isfuture = rand(1, 2) == 1;
		if($isfuture and $futurecount < 8){
			$cnd["rarity"] = $cnd["rarity"][1];
			$futurecount = $futurecount + 1;
		} else {
			$cnd["rarity"] = $cnd["rarity"][0];
		}
	}

	if(substr($cnd["rarity"], 0, 11) == "timeshifted"){
		$cnd["timeshifted"] = 1;
		$cnd["rarity"] = str_replace('timeshifted ', '', $cnd["rarity"]);
	}

	if($set == "PLC" and $cnd["timeshifted"] == 1){
		$cnd["frameEffect"] = "colorshifted";
		$cnd["timeshifted"] = 0;
	}

	//GET THAT PURP
	if($set == "TSP" and $cnd["rarity"] == "purple"){
		$cnd["rarity"] = "rare";
		$cnd["set"] = "TSB";
	}

	//Dragon's maze land override
	if($set == "DGM" and $cnd["rarity"] == "land"){

		$card = dgmland($cnd, $shocks, $gates);

		while(in_array($card, $pack, true) && $nodupe){
			$card = dgmland($cnd, $shocks, $gates);
		}

		if( $help == "yes" ){
			echo "DGM Land Slot Override - ".$card["name"];
			echo "\n";
		}

		if(strlen($card["name"])>0){
			$pack[] = $card;
		}

		continue;
	}

	//try to add basics in land slots if enabled (some sets don't include basics, so may fail)
	if($lands == "yes" and $cnd["rarity"] == "land"){
		$cnd["basic"] = "yes";
		$cnd["rarity"] = "common";

		$card = getcard($cnd);

		while(in_array($card, $pack, true) && $nodupe){
			$card = getcard($cnd);
		}

		if( $help == "yes" ){
			echo "basic land - ".$card["name"];
			echo "\n";
		}

		if(strlen($card["name"])>0){
			$pack[] = $card;
		}

		continue;

	}

	//grab draft matters cards for conspiracy 1, grab other cards for conspiracy 1
	//this is significantly less elegant than CN2, but there's no frameEffect to use in CNS. 
	if($cnd["set"] == "CNS"){

		if($cnd["rarity"] == "draft-matters"){
			$cnd["rarity"] =  raritygenerate("curm");
			$card = getcard($cnd);

			while((in_array($card, $pack, true) && $nodupe) or ($card["type"] != "Conspiracy" and !in_array($card["number"], range(53, 65)))){
				$card = getcard($cnd);			
			}
		} else {

			$card = getcard($cnd);
			while((in_array($card, $pack, true) && $nodupe) or ($card["type"] == "Conspiracy" or in_array($card["number"], range(53, 65)))){
				$card = getcard($cnd);
			}
		}

		if( $help == "yes" ){
			echo $cnd["rarity"]." - ".$card["name"];
			echo "\n";
		}

		if(strlen($card["name"])>0){
			$pack[] = $card;
		}

		continue;
	}

	//Grab draft matters cards for conspiracy 2, grab other cards for conspiracy 2
	if($cnd["set"] == "CN2"){

		if($cnd["rarity"] == "draft-matters"){
			$cnd["rarity"] =  raritygenerate("curm");
			$cnd["frameEffect"] = "draft";
			$card = getcard($cnd);

			while(in_array($card, $pack, true) && $nodupe){
				$card = getcard($cnd);			
			}
		} else {
			$cnd["frameEffect"] = null;
			$cnd["noframeEffect"] = 1;
			$card = getcard($cnd);
			while(in_array($card, $pack, true) && $nodupe){
				$card = getcard($cnd);
			}
		}

		if( $help == "yes" ){
			echo $cnd["rarity"]." - ".$card["name"];
			echo "\n";
		}

		if(strlen($card["name"])>0){
			$pack[] = $card;
		}

		continue;
	}

	//Grab contraptions and other cards for Unsanctioned
	//uses $contraptioncount
	if($cnd["set"] == "UST"){

		if($cnd["rarity"] == "common" and $contraptioncount == 0){
			$cnd["rarity"] = raritygenerate("curm");
			$cnd["type"] = "contraption";
			$contraptioncount = 1;
			$card = getcard($cnd);
			while(in_array($card, $pack, true) && $nodupe){
				$card = getcard($cnd);
			}

			if( $help == "yes" ){
				echo $cnd["rarity"]." - ".$card["name"];
				echo "\n";
			}

			if(strlen($card["name"])>0){
				$pack[] = $card;
			}
			continue;
		} 


		if($cnd["rarity"] == "common" and $contraptioncount == 1){
			$cnd["type"] = "contraption";
			$contraptioncount = 2;
			$card = getcard($cnd);
			while(in_array($card, $pack, true) && $nodupe){
				$card = getcard($cnd);
			}
			if( $help == "yes" ){
				echo $cnd["rarity"]." - ".$card["name"];
				echo "\n";
			}

			if(strlen($card["name"])>0){
				$pack[] = $card;
			}
			continue;
		}

		if($cnd["rarity"] == ['land', 'Steamflogger Boss']){
			if(rand(1,121) == 1){
				$cnd["rarity"] = "Steamflogger Boss";
				$cnd["name"] = "Steamflogger Boss";
			} else {
				$cnd["rarity"] = "land";
			}

		}


		$card = getcard($cnd);
		while((in_array($card, $pack, true) && $nodupe ) or $card["type"] == "Artifact — Contraption"){
			$card = getcard($cnd);
		}

		if( $help == "yes" ){
			echo $cnd["rarity"]." - ".$card["name"];
			echo "\n";
		}

		if(strlen($card["name"])>0){
			$pack[] = $card;
		}
		continue;

	}


	//if all else fails, grab a random item if it's an array
	if( is_array($cnd["rarity"]) and $cnd["rarity"] == null ){
		$cnd["rarity"] = $cnd["rarity"][rand(0, count($cnd["rarity"])-1)];
	}

	//default to getting a card with currently set conditions
	$card = getcard($cnd);

	while(in_array($card, $pack, true) && $nodupe){
		$card = getcard($cnd);
	}

	if( $help == "yes" ){
		echo $cnd["rarity"]." - ".$card["name"];
		echo "\n";
	}

	if(strlen($card["name"])>0){
		$pack[] = $card;
	}
}

//we outie
$conn->close();

//print cards if not using help
if($help != "yes" ){
	printcards($pack);
}

?>
