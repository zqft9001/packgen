<?php

//defines database interactions
include('db_defs.php');

//defines pack rarities
include('packgendefs.php');

//defines card functions
include('cardfunctions.php');


//setup connection
$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}


//makes the file output as plain text instead of html
header('Content-type: text/plain');

//escape all variables passed
foreach ($_GET as $key => $value){
	$gclean[$key]=$conn->escape_string($value);
}

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 */

$debug = $gclean["debug"];
//debug help if "yes" (prints SQL, mostly)

$help = $gclean["help"];
//gets pack information if "yes" or "only"

$set = $gclean["set"];
//Keyrune of set
//Grabs a random pack by default

$ptype = $gclean["ptype"];
//type of pack.
// ori - original rarity (default)
// r - oops all rares
// cur/curm - "standard" 15 card pack, curm has mythics
// cu - only commons and uncommons
// c - all commons
// u - all uncommons
// m - all mythics

$lands = $gclean["lands"];
//attempt lands in pack

$dupesflag = $gclean["dupes"];
//sets packs to allow or disallow duplicated. defaults to no duplicate cards.


$marketing = $gclean["marketing"];
//puts in phone cards in the marketing slots.

if($dupesflag == "yes"){
	$nodupe = False;
} else {
	$nodupe = True;
}

$images = $gclean["images"];
//Printnice will have images if "yes".

$JSON = $gclean["JSON"];
//will spit out TTS JSON for the pack instead of normal formatting

$custom = strtolower($gclean["custom"]);
//wacky packs
//kami - all legendary cards, rarity ignored, set ignored, 15 card pack.
//color - grabs only cards of a specific color (from set if specified)

//define array for adding cards to a pack
$pack = array();

//get set info
$sql = "SELECT * FROM sets where sets.keyruneCode like '%".$set."%' and sets.booster is not null order by rand() limit 1;";

$result = $conn->query($sql);

//it's not me, it's the query that is wrong
if ($result->num_rows < 1){
	$rarity = $currarity;
}

//Get booster distribution and keyrunecode
while ($row = $result->fetch_assoc()){

	If($row["booster"] != NULL){
		$cleanedjson = str_replace("True", "1", str_replace("False", "0", (str_replace("'", '"', $row["booster"]))));
		$sinfo = json_decode($cleanedjson, true);
		$set = $row["keyruneCode"];
		$setfriendly = $row["name"];
	}
}

//help+print options. switches to HTML output if enabled.

$options = array();

if($help == "yes" or $help == "only" or $images == "yes"){

	header('Content-type: text/html');

}

if($images == "yes"){
	$options["images"] = $images;
} else {

	$options["images"] = null;
}

$options["customrarity"] = null;


//unclear why "default" is a wrapper for these, remove it
$sinfo = $sinfo["default"];



//what pack layout are we getting?

$bcount = rand(1, $sinfo["boostersTotalWeight"]);
$layout = null;

foreach($sinfo["boosters"] as $b){
	$bcount = $bcount - $b["weight"];
	if($bcount <= 0){
		$layout = $b;
		break;
	}
}

if($help == "yes" or $help == "only"){

	$options["help"] = $help;

	echo "<pre>";
	echo $setfriendly;
	echo "\n";
	echo "keyrune ";
	print_r($set);
	echo "\n";
	echo count($sinfo["boosters"])." possible booster layout(s)";
	echo "\n";
	echo "current layout:";
	echo "\n";
	var_dump($layout);

	if($help == "only"){
		exit;
	}

} else {

	$options["help"] = null;

}
//what's in the pack?

$sheets = $sinfo["sheets"];

$idpack = null;
$pack = null;

foreach($layout["contents"] as $rarity=>$amount){
	$checkwubrg = ['B','G','R','U','W'];
	for($i = 0; $i < $amount; $i = $i + 1){


		redopick:
		$scount = rand(1, $sheets[$rarity]["totalWeight"]);

		foreach($sheets[$rarity]["cards"] as $cardid=>$weight){
			$scount = $scount - $weight;
			if($scount <= 0){
				if(in_array($cardid, $idpack)){
					//do-over on exact matches
					goto redopick;					
				} else {
					$cnd["id"] = $cardid;
					$card = getcard($cnd);
					
					$colors = explode(",", $card["colors"]);

					if(isset($sheets[$rarity]["balanceColors"]) and count($checkwubrg) > 0){
						for( $j = 0; $j < count($checkwubrg); $j = $j + 1){
							if(in_array($checkwubrg[$j], $colors)){
								array_splice($checkwubrg, $j, 1);
								goto cardtopack;
							}
						}
						goto redopick;
					}
					cardtopack:
					$card["text"] = $card["text"]." | ".$rarity;
					$pack[] = $card;
					$idpack[] = $cardid;
					break;
				}
			}

		}

	}	


}

//we outie
$conn->close();

if($JSON == "yes"){
	printJSON($pack, $gclean["back"]);
} else {
	foreach($pack as $card){
		printnice($card, $options);
	}
}

//print cards if not using help and not spittin' JSON
if($help != "yes" and $images !="yes" and $JSON != "yes"){
	printcards($pack);
}

?>
