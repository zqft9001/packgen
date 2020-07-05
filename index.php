<?php

//defines database interactions
include('db_defs.php');

//defines pack rarities
include('packgendefs.php');

//defines card functions
include('cardfunctions.php');

//makes the file output as plain text instead of html
header('Content-type: text/plain');

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 */

$debug = $_GET["debug"];
//debug help if "yes" (prints SQL, mostly)

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

$images = $_GET["images"];
//Printnice will have images if "yes".

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
	$rarity = $currarity;
}

//Get booster distribution and keyrunecode
while ($row = $result->fetch_assoc()){

	If($row["boosterV3"] != NULL){
	$rarity = json_decode(str_replace("'", '"', $row["boosterV3"]));
	$set = $row["keyruneCode"];
	$basesetsize = $row["baseSetSize"];
	$setfriendly = $row["name"];
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


//help+print options. switches to HTML output if enabled.

if($help == "yes" or $help == "only" or $images == "yes"){

	header('Content-type: text/html');

}

if($help == "yes" or $help == "only"){

	$options["help"] = $help;


	echo "<pre>";

	print_r($setfriendly);
	echo "\n";
	echo "keyrune ";
	print_r($set);
	echo "\n";
	echo "base set size ";
	print_r($basesetsize);
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
//Broken for snow land slots
//Broken for full art print slots
//Broken for ME4 Urza's Land slots

$options = array();
$options["images"] = $images;
$options["customrarity"] = null;

$futurecount = 0;
$contraptioncount = 0;

foreach( $rarity as $item ){

	//Reset relevant card print options every loop
	//Custom rarity string for things like DGM
	$options["customrarity"] = null;
	
	//card search conditions
	$cnd = array();
	//card set keyrune (string)
	$cnd["set"] = $set;
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
	$cnd["sql"] = $debug;
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

	//set max collector's number if we have one to reference (will prevent grabbing promos/masterworks)
	if($basesetsize > 0){
		$cnd["max cn"] = $basesetsize;
	}

	if($cnd["rarity"] == "marketing" or $cnd["rarity"] == "token"){
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
	} elseif($set == "PLC"){
		$cnd["noframeEffect"] = 1;
	}

	//GET THAT PURP
	if($set == "TSP" and $cnd["rarity"] == "purple"){
		$cnd["rarity"] = "rare";
		$cnd["set"] = "TSB";
	}

	//Dragon's maze land override
	if($set == "DGM" and $cnd["rarity"] == "land"){

		$card = dgmland($cnd, $shocks, $gates);

		while(inpack($card, $pack, false) && $nodupe){
			$card = dgmland($cnd, $shocks, $gates);
		}

		$options["customrarity"] = $card["rarity"]." DGM Land";
		printnice($card, $options);

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

		while(inpack($card, $pack, false) && $nodupe){
			$card = getcard($cnd);
		}

		$options["customrarity"] = "Basic Land";
		printnice($card, $options);

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

			while((inpack($card, $pack, false) && $nodupe) or ($card["type"] != "Conspiracy" and !in_array($card["number"], range(53, 65)))){
				$card = getcard($cnd);			
			}
			$options["customrarity"] =  $card["rarity"]." draft-matters";
			printnice($card, $options);
		} else {

			$card = getcard($cnd);
			while((inpack($card, $pack, false) && $nodupe) or ($card["type"] == "Conspiracy" or in_array($card["number"], range(53, 65)))){
				$card = getcard($cnd);
			}
			printnice($card, $options);
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

			while(inpack($card, $pack, false) && $nodupe){
				$card = getcard($cnd);			
			}
			$options["customrarity"] = $card["rarity"]." draft-matters";
			printnice($card, $options);
		} else {
			$cnd["frameEffect"] = null;
			$cnd["noframeEffect"] = 1;
			$card = getcard($cnd);
			while(inpack($card, $pack, false) && $nodupe){
				$card = getcard($cnd);
			}
			printnice($card, $options);
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
			while(inpack($card, $pack, false) && $nodupe){
				$card = getcard($cnd);
			}

			$options["customrarity"] = $card["rarity"]." Contraption";
			printnice($card, $options);

			if(strlen($card["name"])>0){
				$pack[] = $card;
			}
			continue;
		} 


		if($cnd["rarity"] == "common" and $contraptioncount == 1){
			$cnd["type"] = "contraption";
			$contraptioncount = 2;
			$card = getcard($cnd);
			while(inpack($card, $pack, false) && $nodupe){
				$card = getcard($cnd);
			}
			$options["customrarity"] = $card["rarity"]." Contraption";
			printnice($card, $options);

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


		if($lands == "yes" and $cnd["rarity"] == "land"){
			$cnd["basic"] = "yes";
			$cnd["rarity"] = "common";

			$card = getcard($cnd);

			while(inpack($card, $pack, false) && $nodupe){
				$card = getcard($cnd);
			}
			$options["customrarity"] = "Basic Land";
			printnice($card, $options);

			if(strlen($card["name"])>0){
				$pack[] = $card;
			}

			continue;

		}

		$card = getcard($cnd);
		while((inpack($card, $pack, false) && $nodupe ) or $card["type"] == "Artifact — Contraption"){
			$card = getcard($cnd);
		}

		printnice($card, $options);

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

	while(inpack($card, $pack, false) && $nodupe){
		$card = getcard($cnd);
	}

	printnice($card, $options);	

	if(strlen($card["name"])>0){
		$pack[] = $card;
	}
}

//we outie
$conn->close();

//print cards if not using help
if($help != "yes" and $images !="yes"){
	printcards($pack);
}

?>
