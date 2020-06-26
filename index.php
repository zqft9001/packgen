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

$help = $_GET["help"];
//gets pack information if "yes" or "only"

$set = $_GET["set"];
//Keyrune of set
//Grabs a random pack by default

$ptype = $_GET["ptype"];
//type of pack.
// ori - original rarity (default)
// oar - oops all rares
// std - "standard" 15 card pack
// pau - all commons
// unc - all uncommons

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
//gold - all rare + mythic cards, set ignored, 15 card pack.
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
case "oar":
	$rarity = $oarrarity;
	break;
case "std":
	$rarity = $stdrarity;
	break;
case "pau":
	$rarity = $paurarity;
	break;
case "unc":
	$rarity = $uncrarity;
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
//Conspriacies
//Unsets
//modern masters


//Broken for sets with sub-rarity, but MTGJSON does not recognize their authority
//Broken for sets with draft-matters
//Broken for marketing cards
//Broken for snow land slots
//Broken for token slots
//Broken for full art print slots

$futurecount = 0;

foreach( $rarity as $item ){

	if($item == "marketing"){
		continue;
	}

	$timeshifted = 0;
	$frameeffect = "";

	//Double sided cards are a nightmare nightmare nightmare nightmare nightmare
	if($set == "SOI" or $set == "ISD" or $set == "EMN"){
		$frameeffect = "null";

		if( $item == ['common', 'double faced rare', 'double faced mythic rare']){
			if(rand(1,20) == 1){
				if(rand(1,8) == 1){
					$item = "mythic";
				} else {
					$item = "rare";
				}
				$frameeffect = "dfc";
			} else {
				$item = 'common';
			}

		}

		if( $item == ['double faced common', 'double faced uncommon'] ){
			$item = raritygenerate("cu");
			$frameeffect = "dfc";
		}

		if( $item == "double faced" ){

			$frameeffect = "dfc";
			$item = raritygenerate("curm");
		}


	}

	//foil handling for masters sets.
	if( $item == ['foil mythic rare', 'foil rare', 'foil uncommon', 'foil common'] ){
		$item = raritygenerate("curm");
	}

	//mythic is 1 out of every 8 rares if it's just those two.
	if( $item == ['rare', 'mythic rare']){
		if(rand(1,8) == 1){
			$item = "mythic";
		} else {
			$item = "rare";
		}
	}

	//1:4 rare to uncommon if it's just those two
	if( $item == ['rare', 'uncommon']){
		if(rand(1,4) == 4){
			$item = "rare";
		} else {
			$item = "uncommon";
		}
	}


	//identify if rarity is timeshifted or colorshifted, pull word timeshifted out after setting flag.

	//time spiral has array rarities and weird rules about how many shifted cards can be in a pack.
	//This "solves" those.
	if( is_array($item) and $set == "PLC"){
		if(rand(1,4) == 1){
			$item = $item[0];
		} else {
			$item = $item[1];
		}
	}

	if( is_array($item) and $set == "FUT" ){
		$isfuture = rand(1, 2) == 1;
		if($isfuture and $futurecount < 8){
			$item = $item[1];
			$futurecount = $futurecount + 1;
		} else {
			$item = $item[0];
		}
	}
	
	if(strpos($item, "timeshifted") !== false){
		$timeshifted = 1;
		$item = str_replace('timeshifted ', '', $item);
	}

	if($set == "PLC" and $timeshifted == 1){
		$frameeffect = "colorshifted";
	}

	//GET THAT PURP
	if($set == "TSP" and $timeshifted == 1 and $item = "purple"){
		$item = "rare";
		$set = "TSB";
	}

	//Dragon's maze land override
	if($set == "DGM" and $item == "land"){


		$card = dgmland($shocks, $gates);

		while(in_array($card, $pack, true) && $nodupe){
			$card = dgmland($shocks, $gates);
		}

		if( $help == "yes" ){
			echo "timeshifted: ".$timeshifted."\n";
			echo "frameeffect: ".$frameeffect."\n";
			echo "DGM Land Slot Override - ".$card;
			echo "\n";
		}

		if(strlen($card)>0){
			$pack[] = $card;
		}

		continue;
	}

	//try to add basics in land slots if enabled (some sets don't include basics, so may fail)
	if($lands == "yes" and $item == "land"){
		$card = getbytype($set, "common", "basic");

		while(in_array($card, $pack, true) && $nodupe){
			$card = getbytype($set, "common", "basic");
		}

		if( $help == "yes" ){
			echo "timeshifted: ".$timeshifted."\n";
			echo "frameeffect: ".$frameeffect."\n";
			echo $item." - ".$card;
			echo "\n";
		}

		if(strlen($card)>0){
			$pack[] = $card;
		}

		continue;

	}


	//Grab draft matters cards for conspiracy, grab other cards for conspiracy


	//Grab contraptions and other cards for UST
	

	//if all else fails, grab a random item if it's an array
	if( is_array($item) ){
		$item = $item[rand(0, count($item)-1)];
	}

	//Default to just getting a card. Can't ever grab a basic with this function.
	$card = getcard($set, $item, $timeshifted, $frameeffect);

	while(in_array($card, $pack, true) && $nodupe){
		$card = getcard($set, $item, $timeshifted, $frameeffect);
	}

	if( $help == "yes" ){
		echo "timeshifted: ".$timeshifted."\n";
		echo "frameeffect: ".$frameeffect."\n";
		echo $item." - ".$card;
		echo "\n";
	}

	if(strlen($card)>0){
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
