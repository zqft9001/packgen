<?php

function gettokens($cnd){


	if(strlen($cnd["name"]) <= 0){
		return null;
	}

	$pack = null;

	static $trycount = 0;

	$trycount = $trycount + 1;

	if($trycount > 100){
		return;
	}

	//gets a token based on the conditions provided
	//Gives up after 100 tries.

	$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "select * from tokens where tokens.reverserelated like '%".$cnd["name"]."%' or tokens.name like '%".$cnd["name"]."%';";

	if($cnd["sql"]=="yes"){
		echo $sql;
	}

	$result = $conn->query($sql);

	if ($result->num_rows < 1){
		return;
	}

	for($i = 0; $i < $result->num_rows; $i = $i + 1){
		$result->data_seek($i);
		$pack[] = $result->fetch_array();
	}

	$conn->close();

	return $pack;

}

//gets scryfall face for meld cards
function getmeld($otherface){

	$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "select * from cards where cards.uuid like \"".$otherface."\";";

	$result = $conn->query($sql);

	if ($result->num_rows < 1){
		return "Fail to Find";
	}

	$card = $result->fetch_array();

	$conn->close();
	return $card["scryfallId"];

}

function getcard($cnd){

	static $trycount = 0;

	$trycount = $trycount + 1;

	if($trycount > 1000){
		return;
	}

	//gets a card from a set based on the conditions provided
	//Gives up after 1000 tries.

	$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "select * from cards";

	$filterstart = " where ";
	$fbuild = "";
	$filterend = "and (cards.side IS NULL OR cards.side = 'a');";
/*
	if(isset($cnd["max cn"])){
		$fbuild = $fbuild."and cards.number <= ".$cnd["max cn"]." ";
	}
 */
	if(isset($cnd["cn"])){
		if(is_numeric($cnd["cn"])){
			$fbuild = $fbuild."and cards.number = ".$cnd["cn"]." ";
		} else {
			$fbuild = $fbuild."and cards.number like '".$cnd["cn"]."' ";
		}
	}
/*
	if(isset($cnd["colorless"]) and $cnd["colorless"] == 1){
		$fbuild = $fbuild."and cards.colors is null ";
	}

	if(isset($cnd["colorIDless"]) and $cnd["colorIDless"] == 1){
		$fbuild = $fbuild."and cards.coloridentity is null ";
	}

	if(isset($cnd["colors"])){
		if(is_array($cnd["colors"])){
			foreach($cnd["colors"] as $color){
				$fbuild = $fbuild."and cards.colors like '%".$color."%' ";
			} 
		} else {
			$fbuild = $fbuild."and cards.colors like '%".$cnd["colors"]."%' ";
		}
	}

	if(isset($cnd["colorIDs"])){
		if(is_array($cnd["colorIDs"])){
			foreach($cnd["colorIDs"] as $color){
				$fbuild = $fbuild."and cards.coloridentity like '%".$color."%' ";
			} 
		} else {
			$fbuild = $fbuild."and cards.coloridentity like '%".$cnd["colorIDs"]."%' ";
		}
	}
 */
	if(isset($cnd["set"])){
		$fbuild = $fbuild."and cards.setCode = '".$cnd["set"]."' ";
	}
/*
	if(isset($cnd["rarity"])){
		$fbuild = $fbuild."and cards.rarity = '".$cnd["rarity"]."' ";
	}

	if(isset($cnd["timeshifted"])){
		$fbuild = $fbuild."and cards.isTimeshifted = ".$cnd["timeshifted"]." ";
	}

	if(isset($cnd["frameEffect"])){
		$fbuild = $fbuild."and cards.frameEffect like '%".$cnd["frameEffect"]."%' ";
	}

	if(isset($cnd["noframeEffect"]) and $cnd["noframeEffect"] == 1){
		$fbuild = $fbuild."and cards.frameEffect is null ";
	}

	if(isset($cnd["type"])){
		$fbuild = $fbuild."and cards.type like '%".$cnd["type"]."%' ";
	}
 */
	if (count($fbuild)>0){
		$fbuild = substr($fbuild, 4);
		$sql = $sql.$filterstart.$fbuild.$filterend;
	}

	$bannedsets = "('4BB','FBB','PSAL','PHUK','REN','PTC', 'WC00', 'WC01', 'WC02', 'WC03', 'WC04', 'WC97', 'WC98', 'WC99', 'PRM', 'PZ2')";

	if(isset($cnd["name"])){
		if(isset($cnd["fuzzy"])){
			$sql = "select * from cards where cards.name like \"%".$cnd["name"]."%\" and cards.setCode not in ".$bannedsets." and (cards.side IS NULL OR cards.side = 'a');";
		} else {
			$sql = "select * from cards where cards.name = \"".$cnd["name"]."\" and cards.setCode not in ".$bannedsets." and (cards.side IS NULL OR cards.side = 'a');";
		}
	}


	if(isset($cnd["id"])){
		$sql = "select * from cards where cards.uuid = '".$cnd["id"]."';";
	}

	if(isset($cnd["multiverseid"])){
		$sql = "select * from cards where cards.multiverseid = '".$cnd["multiverseid"]."';";
	}

	if(isset($cnd["sql"])){
		echo $sql."\n";
	}

	$result = $conn->query($sql);

	if ($result->num_rows < 1){
		return;
	}

	if(isset($cnd["allprints"])){
		for($i = 0; $i < $result->num_rows; $i = $i + 1){
			$result->data_seek($i);
			$pack[] = $result->fetch_array();
		}

		if($cnd["sql"] != null){
			print_r($pack);
		}
		$conn->close();
		return $pack;
	} else {
		$card = rand(0, $result->num_rows-1);

		$result->data_seek($card);
		$card = $result->fetch_array();

		if($cnd["sql"] != null){
			print_r($card);
		}
		$conn->close();
		return $card;
	}


}

//Generates a rarity based on the length of the string passed.
//curm - common uncommon rare mythic
//cur - common uncommon rare
//cu - common uncommon
//with nothing provided, will just return common.
function raritygenerate($indicator){
	if (rand(1,20) == 1 and substr($indicator, 2 ,1) == "r"){
		if (rand(1,8) == 1 and substr($indicator, 3, 1) == "m"){
			return "mythic";
		} else {
			return "rare"; 
		}
	}
	if (rand(1,5) == 1 and substr($indicator, 1, 1) == "u"){
		return "uncommon";
	}
	return "common";
}


function inpack($card, $pack, $strict = false){
	//Checks if a card is in a pack using the collector's number
	//$strict - if true, uses exact collectors number. False by default, uses only numeric portion of collector's number.

	foreach($pack as $pcard){
		if($strict){
			if($card["number"] == $pcard["number"]){
				return true;
			}
		} else {
			if(preg_replace("/[^0-9]+/", "", $card["number"]) == preg_replace("/[^0-9]+/", "", $pcard["number"])){
				return true;
			}

		}
	}

	return false;
}

//Prints card nicely. used for help option
function printnice($card, $options){

	STATIC $fcount = 0;

	$fcount++;

	if(strlen($card["name"])<1){
		return false;
	}

	if($options["help"] == "yes"){
		echo '<a href="https://scryfall.com/card/'.strtolower($card["setCode"]).'/'.$card["number"].'">';

		if($options["images"] == "yes"){
			echo "<img src=\"https://c1.scryfall.com/file/scryfall-cards/normal/front/".substr($card["scryfallId"],0,1).'/'.substr($card["scryfallId"],1,1).'/'.$card["scryfallId"].'.jpg"  style=\'height:33%;\'>';
			if($fcount % 8 == 0){
				echo "\n";
			}
			return true;
		} else {
			if($options["customrarity"] != null){
				echo $options["customrarity"]." - [".$card["setCode"].":".preg_replace("/[^a-zA-Z0-9]+/", "", $card["number"])."] ".$card["name"];
				echo "</a>\n";
				return true;
			} else {
				echo $card["rarity"]." - [".$card["setCode"].":".preg_replace("/[^a-zA-Z0-9]+/", "", $card["number"])."] ".$card["name"];
				echo "</a>\n";
				return true;
			}
		}
	}


	if($options["images"] == "yes"){
		echo '<a href="https://scryfall.com/card/'.strtolower($card["setCode"]).'/'.$card["number"].'">';
		echo "<img src=\"https://c1.scryfall.com/file/scryfall-cards/normal/front/".substr($card["scryfallId"],0,1).'/'.substr($card["scryfallId"],1,1).'/'.$card["scryfallId"].'.jpg"  style=\'height:33%;\'>';
		echo '</a>';
		if($fcount % 5 == 0){
			echo "<br>";
		}
		return true;
	}
	return false;
}
function printcards($cardlist){
	//Prints the list of cards in the pack.
	foreach($cardlist as $card){
		if($card["type"] == "Phone Card"){
			echo "1 ".$card["image"]." ".$card["name"];
		}else{
			echo "1 [".$card["setCode"].":".preg_replace("/[^a-zA-Z0-9]+/", "", $card["number"])."] ".$card["name"];
		}
		echo "\n";
	}	
}


function printJSON($cardlist, $aback = null, $aface = null, $apos = null, $arot = null, $ascl = null, $anote = null){

	include('JSONdefs.php');

	if($aback == null){
		$back = $CARDBACK; 
	} else {
		$back = $aback;
	}

	if($apos == null){
		$pos = $_POS;
	} else {
		$pos = $apos;
	}

	if($arot == null){
		$rot = $_ROT;
	} else {
		$rot = $arot;
	}

	if($ascl == null){
		$scl = $_SCL;
	} else {
		$scl = $ascl;
	}

	$note = $anote;

	foreach($cardlist as $card){

		if(isset($card["note"])){
			//new notes change position of pile
			if($note != $card["note"]." ".$anote){
				$pos["x"] = $pos["x"] + 3;
			}
			$note = $card["note"]." ".$anote;
		}

		$nickname = addslashes($card["name"]).' | '.$card["type"].' | CMC'.$card["convertedManaCost"].' | '.$note;

		$description = addslashes($card["text"]).' | '.$card["setCode"].':'.$card["number"];
		if(isset($aface) and $aface != ""){
			$face = $aface;
		}elseif(isset($card["image"])){
			$face = $card["image"];
		} else {
			$face = 'https://c1.scryfall.com/file/scryfall-cards/normal/front/'.substr($card["scryfallId"],0,1).'/'.substr($card["scryfallId"],1,1).'/'.$card["scryfallId"].'.jpg';
		}
		if($card["otherFaceIds"] != null){
			if($card["layout"] == "meld"){
				$meldface = getmeld($card["otherFaceIds"]);
				$dfcback = 'https://c1.scryfall.com/file/scryfall-cards/normal/front/'.substr($meldface,0,1).'/'.substr($meldface,1,1).'/'.$meldface.'.jpg';
			} else {
				$dfcback = 'https://c1.scryfall.com/file/scryfall-cards/normal/back/'.substr($card["scryfallId"],0,1).'/'.substr($card["scryfallId"],1,1).'/'.$card["scryfallId"].'.jpg';
			}
			echo
				'{
				"Name": "Card",
					"Transform": {
					"posX": '.$pos["x"].',
						"posY":	'.$pos["y"].',
						"posZ": '.$pos["z"].',
						"rotX": '.$rot["x"].',
						"rotY": '.$rot["z"].',
						"rotZ": '.$rot["z"].',
						"scaleX": '.$scl["x"].',
						"scaleY": '.$scl["y"].',
						"scaleZ": '.$scl["z"].'
		},
			"Nickname": "'.$nickname.'",
			"Description": "'.$description.'",
			"GMNotes": "",
			"ColorDiffuse": {
			"r": 0.713235259,
				"g": 0.713235259,
				"b": 0.713235259
		},
			"Locked": false,
			"Grid": true,
			"Snap": true,
			"IgnoreFoW": false,
			"MeasureMovement": false,
			"DragSelectable": true,
			"Autoraise": true,
			"Sticky": true,
			"Tooltip": true,
			"GridProjection": false,
			"HideWhenFaceDown": true,
			"Hands": true,
			"CardID": 100,
			"SidewaysCard": false,
			"CustomDeck": {
			"1": {
			"FaceURL": "'.$face.'",
				"BackURL": "'.$back.'",
				"NumWidth": 1,
				"NumHeight": 1,
				"BackIsHidden": true,
				"UniqueBack": false,
				"Type": 0
		}
		},
			"LuaScript": "",
			"LuaScriptState": "",
			"XmlUI": "",
			"GUID": "748460",
			"States": {
			"2": {
			"Name": "Card",
				"Transform": {
				"posX": '.$pos["x"].',
					"posY":	'.$pos["y"].',
					"posZ": '.$pos["z"].',
					"rotX": '.$rot["x"].',
					"rotY": '.$rot["z"].',
					"rotZ": '.$rot["z"].',
					"scaleX": '.$scl["x"].',
					"scaleY": '.$scl["y"].',
					"scaleZ": '.$scl["z"].'
		},
			"Nickname": "'.$nickname.'",
			"Description": "'.$description.'",
			"GMNotes": "",
			"ColorDiffuse": {
			"r": 0.713235259,
				"g": 0.713235259,
				"b": 0.713235259
		},
			"Locked": false,
			"Grid": true,
			"Snap": true,
			"IgnoreFoW": false,
			"MeasureMovement": false,
			"DragSelectable": true,
			"Autoraise": true,
			"Sticky": true,
			"Tooltip": true,
			"GridProjection": false,
			"HideWhenFaceDown": false,
			"Hands": true,
			"CardID": 100,
			"SidewaysCard": false,
			"CustomDeck": {
			"1": {
			"FaceURL": "'.$face.'",
				"BackURL": "'.$dfcback.'",
				"NumWidth": 1,
				"NumHeight": 1,
				"BackIsHidden": true,
				"UniqueBack": false,
				"Type": 0
		}
		},
			"LuaScript": "",
			"LuaScriptState": "",
			"XmlUI": "",
			"GUID": "947dc9"
		}
		}
		}';
echo "@";

		} else {
			if($aback == null){
				$back = $CARDBACK; 
			} else {
				$back = $aback;
			}
			$deckid = $deckid + 1;
			echo
				'{
				"Name": "Card",
					"Transform": {
					"posX": '.$pos["x"].',
						"posY":	'.$pos["y"].',
						"posZ": '.$pos["z"].',
						"rotX": '.$rot["x"].',
						"rotY": '.$rot["z"].',
						"rotZ": '.$rot["z"].',
						"scaleX": '.$scl["x"].',
						"scaleY": '.$scl["y"].',
						"scaleZ": '.$scl["z"].'
		},
			"Nickname": "'.$nickname.'",
			"Description": "'.$description.'",
			"GMNotes": "",
			"ColorDiffuse": {
			"r": 0.713235259,
				"g": 0.713235259,
				"b": 0.713235259
		},
			"Locked": false,
			"Grid": true,
			"Snap": true,
			"IgnoreFoW": false,
			"MeasureMovement": false,
			"DragSelectable": true,
			"Autoraise": true,
			"Sticky": true,
			"Tooltip": true,
			"GridProjection": false,
			"HideWhenFaceDown": true,
			"Hands": true,
			"CardID": 100,
			"SidewaysCard": false,
			"CustomDeck": {
			"1": {
			"FaceURL": "'.$face.'",
				"BackURL": "'.$back.'",
				"NumWidth": 1,
				"NumHeight": 1,
				"BackIsHidden": true,
				"UniqueBack": false,
				"Type": 0
		}
		},
			"LuaScript": "",
			"LuaScriptState": "",
			"XmlUI": "",
			"GUID": "947dc9"
		}'
	;
		echo "@";
		}
	}
}
