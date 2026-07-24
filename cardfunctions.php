<?php

include('cachedefs.php');

$FailtoFind = [
	"name" => "Fail to Find",
	"type" => "Error Message",
	"convertedManaCost" => 404,
	"manaValue" => 404,
	"text" => "Failed to find\n",
	"image" => "https://i.imgur.com/jOI0aAE.png",
	"setCode" => "errors",
	"number" => 404
];

function gettokens($cnd){

	global $FailtoFind;

	if(strlen($cnd["name"]) <= 0){
		return null;
	}

	$pack = null;

	//gets a token based on the conditions provided

	$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "select * from tokens where (tokens.relatedcards like '%".$cnd["name"]."%' or tokens.name like '%".$cnd["name"]."%') and (tokens.side like 'a' or tokens.side is null)";

	if($cnd["sql"]=="yes"){
		echo $sql;
	}

	$result = $conn->query($sql);

	for($i = 0; $i < $result->num_rows; $i = $i + 1){
		$result->data_seek($i);
		$token = $result->fetch_array();
		$token["isToken"] = "yes";
		$pack[] = $token;
	}

	$conn->close();

	if(count($pack)>0){
		return $pack;
	} else {
		$F2F = $FailtoFind;
		foreach($cnd as $key=>$value){
			$F2F["text"] = $F2F["text"].$key.": ".$value."\n";
		}
		return array($F2F);
	}

}

//gets scryfall face for meld cards
function getother($otherface){

	global $FailtoFind;

	$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "select * from cards where cards.uuid like \"".$otherface."\";";

	$result = $conn->query($sql);

	if (is_object($result)){

		if ($result->num_rows > 0){

			$card = $result->fetch_array();
			$conn->close();
			return $card;
		} else {

			$sql = "select * from tokens where tokens.uuid like \"".$otherface."\";";

			$result = $conn->query($sql);

			if ($result->num_rows > 0){
				$card = $result->fetch_array();
				$conn->close();
				return $card;
			}
		}

	}
	$conn->close();
	return $FailtoFind;

}


//always returns an array with one or more cards in it.

function fuzzyget($variant, $condition = null){

	global $FailtoFind;

	//local variable to provide error data in addition to fallback card.
	$F2F = $FailtoFind;

	//if any conditions are set, use them
	if(isset($condition)){

		//search by given condition
		$cnd = null;
		$cnd[$condition] = $variant;
		$card = getcard($cnd);

		if(count($card) > 0){
			return $card;
		}

		//Search by facename
		$cvar = $variant;
		$cvar["facename"] = "yes";
		$card = getcard($cvar);

		if(count($card) > 0){
			return $card;
		} 

		//Search by partial name
		$cvar = $variant;
		$cvar["fuzzy"] = "yes";
		$card = getcard($cvar);

		if(count($card) > 0){
			return $card;
		}

		//give up
		foreach($variant as $key=>$value){
			$F2F["text"] = $F2F["text"].$key.": ".$value."\n";
		}
		return array($F2F);

	} else {
		//search with no additional conditions
		$card = getcard($variant);

		if(count($card) > 0){
			return $card;
		}

		//Search by facename
		$cvar = $variant;
		$cvar["facename"] = "yes";
		$card = getcard($cvar);

		if(count($card) > 0){
			return $card;
		} 

		//Search by partial name
		$cvar = $variant;
		$cvar["fuzzy"] = "yes";
		$card = getcard($cvar);

		if(count($card) > 0){
			return $card;
		}

		//give up
		foreach($variant as $key=>$value){
			$F2F["text"] = $F2F["text"].$key.": ".$value."\n";
		}
		return array($F2F);

	}

}

function getcard($cnd){

	//gets an array of cards based on the conditions provided

	$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "select * from cards";

	$filterstart = " where ";
	$fbuild = "";
	$filterend = "and (cards.side IS NULL OR cards.side = 'a');";

	$cn = "";

	if(isset($cnd["cn"])){
		if(is_numeric($cnd["cn"])){
			$cn = "and cards.number = ".$cnd["cn"]." ";
		} else {
			$cn = "and cards.number like '".$cnd["cn"]."' ";
		}
		$fbuild = $fbuild.$cn;
	}

	$set = "";

	if(isset($cnd["set"])){
		$set = "and cards.setCode = '".$cnd["set"]."' ";
		$fbuild = $fbuild.$set;
	}

	if (strlen($fbuild)>0){
		$fbuild = substr($fbuild, 4);
		$sql = $sql.$filterstart.$fbuild.$filterend;
	}

	//List of sets to never return results from for regular deck spawning.
	$bannedsets = "and isOnlineOnly is null and borderColor <> 'gold' and cards.setCode not in ('4BB', 'FBB', 'PHJ', 'PJJT', 'PMPS', 'PSAL', 'PMPS06', 'PMPS07', 'PMPS08', 'PMPS09', 'PMPS10', 'PMPS11', 'PRED', 'PS11', 'REN', 'RIN') ";

	//Name Searches
	if(isset($cnd["name"])){

		if(isset($cnd["facename"])){
			$sql = "select * from cards where cards.facename like \"".$cnd["name"]."\" ".$bannedsets.$set.$cn.$filterend;
		}elseif(isset($cnd["fuzzy"])){
			$sql = "select * from cards where cards.name like \"%".$cnd["name"]."%\" ".$bannedsets.$set.$cn.$filterend;
		} else {
			$sql = "select * from cards where cards.name = \"".$cnd["name"]."\" ".$bannedsets.$set.$cn.$filterend;
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
		$conn->close();
		return array();
	}

	if(isset($cnd["allprints"])){
		for($i = 0; $i < $result->num_rows; $i = $i + 1){
			$result->data_seek($i);
			$pack[] = $result->fetch_array();
		}

		if(isset($cnd["sql"])){
			print_r($pack);
		}
		$conn->close();
		return $pack;
	} else {
		$card = rand(0, $result->num_rows-1);

		$result->data_seek($card);
		$card = $result->fetch_array();

		if(isset($cnd["sql"])){
			print_r(array($card));
		}
		$conn->close();
		return array($card);
	}


}

function getimagebyuuid($uuid, $special = array()){

	$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	if (isset($special["token"])){

		$sql = "select * from tokenIdentifiers where tokenIdentifiers.uuid like \"".$uuid."\";";

	}else{

		$sql = "select * from cardIdentifiers where cardIdentifiers.uuid like \"".$uuid."\";";
	}

	$result = $conn->query($sql);

	global $FailtoFind;

	if ($result->num_rows < 1){
		$conn->close();
		return $FailtoFind['image'];
	}

	$card = $result->fetch_array();


	$conn->close();

	global $scryfallcache;

	if (isset($special["back"])){
		return $scryfallcache.'/normal/back/'.substr($card["scryfallId"],0,1).'/'.substr($card["scryfallId"],1,1).'/'.$card["scryfallId"].'.jpg';
	}
	return $scryfallcache.'/normal/front/'.substr($card["scryfallId"],0,1).'/'.substr($card["scryfallId"],1,1).'/'.$card["scryfallId"].'.jpg';
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
			echo "<img src=\"https://cards.scryfall.io/normal/front/".substr($card["scryfallId"],0,1).'/'.substr($card["scryfallId"],1,1).'/'.$card["scryfallId"].'.jpg"  style=\'height:33%;\'>';
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
		echo "<img src=\"https://cards.scryfall.io/normal/front/".substr($card["scryfallId"],0,1).'/'.substr($card["scryfallId"],1,1).'/'.$card["scryfallId"].'.jpg"  style=\'height:33%;\'>';
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
			echo "1 ",$card["image"]," ",$card["name"];
		}else{
			echo "1 [",$card["setCode"],":",preg_replace("/[^a-zA-Z0-9]+/", "", $card["number"])."] ",$card["name"];
		}
		echo "\n";
	}	
}


function printJSON($cardlist, $aback = null, $aface = null, $apos = null, $arot = null, $ascl = null, $anote = null, $GUID = null){

	include('JSONdefs.php');

	//var_dump($arot);

	$JSON = null;

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

	//var_dump($rot);

	$note = $anote;

	foreach($cardlist as $card){

		$description = null;

		$dfctext = null;

		$nickname = null;

		$gm = null;

		$uuid = $card["uuid"];

		$script = null;

		if(!array_key_exists("manaValue", $card)){
			$card["manaValue"] = "0";
		}

		if($card["manaValue"] == "0"){
			$card["manaCost"] = null;
		}

		if(isset($card["note"])){
			//new notes change position of pile
			if($note != $card["note"]." ".$anote){
				$pos["x"] = $pos["x"] + 3;
			}
			$note = $card["note"]." ".$anote;
		}

		$nickname = addslashes($card["name"]).' | '.$card["type"].' | MV '.$card["manaValue"].' | '.$note;

		if(isset($card["manaCost"])){
			$description = $description.$card["manaCost"]."\n\n";
		}

		if(isset($card["text"])){
			$description = $description.$card["text"]."\n";
		}

		if(isset($card["power"])){
			$description = $description."\n".$card["power"]."/".$card["toughness"]."\n";	
		}

		if(isset($card["loyalty"])){
			$description = $description."\n".$card["loyalty"]." Loyalty\n";
		}

		if(isset($card["otherFaceIds"])){
			if($card["otherFaceIds"] != ""){
				foreach(explode(", ",$card["otherFaceIds"]) as $otherface){
					$othercard = getother($otherface);

					$description = $description."\n// ".$othercard["type"]."\n";

					if(isset($othercard["manaCost"])){
						if($othercard["manaCost"] <> ""){
							$description = $description.$othercard["manaCost"]."\n";
						}
					}

					$description = $description."\n";

					if(isset($othercard["text"])){
						$description = $description.$othercard["text"]."\n";
					}

					if(isset($othercard["power"])){
						$description = $description."\n".$othercard["power"]."/".$othercard["toughness"]."\n";	
					}

					if(isset($othercard["loyalty"])){
						$description = $description."\n".$othercard["loyalty"]." Loyalty\n";
					}
				}
			}
		}

		if(isset($card["relatedCards"]) and !isset($card["isToken"])){
			$facefortokens = "";
			if(isset($card["faceName"])){
				$facefortokens = $card["faceName"];
				} else {
				$facefortokens = $card["name"];
			}
			$script = $script."\n
				function onLoad()
					self.addContextMenuItem('Get Token(s)', porter)
					end

					function porter(player_color)
						importer = getObjectFromGUID('".$GUID."') 
						importer.call('selftoken', {name=\\\"".addslashes($facefortokens)."\\\", ref=self, owner=player_color})
						end";
		}

		$description =  $description."\n".$card["setCode"].':'.$card["number"];

		if(isset($card["cutsheet"])){
			$description = $description."\n".$card["cutsheet"];
		}

		$description = addslashes($description);

		if(isset($aface) and $aface != ""){
			$face = $aface;
		}elseif(isset($card["image"])){
			$face = $card["image"];
		} else {
			if(isset($card["isToken"])){
				$face = getimagebyuuid($card["uuid"], array("token"  =>  "yes"));
			}else{
				$face = getimagebyuuid($card["uuid"]);
			}
		}

		$gm = $gm.$card["name"].';'.$uuid.';'.$face;

		$gm = addslashes($gm);

		if($card["otherFaceIds"] != null and ($card["layout"] == "transform" or $card["layout"] == "modal_dfc" or $card["layout"] == "reversible_card" or $card["layout"] == "meld" or $card["layout"] == "double_faced_token" or $card["layout"] == "art_series")){
			
			$othercard = getother($card["otherFaceIds"]);
				
			if($card["layout"] == "meld"){
				$meldface = $othercard["uuid"];
				$dfcback = getimagebyuuid($meldface);
			} else {
				$tokenback = array();
				if(isset($card["isToken"])){
					$tokenback["token"] = "yes";
				}
				$tokenback["back"] = "yes";
				$dfcback = getimagebyuuid($card["uuid"], $tokenback);
			}

			$backscript = "";
		
			if(isset($othercard["relatedCards"]) and !isset($othercard["isToken"])){
				$backscript = $backscript."\n
					function onLoad()
						self.addContextMenuItem('Get Token(s)', porter)
						end

						function porter(player_color)
							importer = getObjectFromGUID('".$GUID."') 
							importer.call('selftoken', {name=\\\"".addslashes($othercard["faceName"])."\\\", ref=self, owner=player_color})
							end";
			}


			echo '{
			"Name": "Card",
				"Transform": {
				"posX": ',$pos["x"],',
					"posY":	',$pos["y"],',
					"posZ": ',$pos["z"],',
					"rotX": ',$rot["x"],',
					"rotY": ',$rot["y"],',
					"rotZ": ',$rot["z"],',
					"scaleX": ',$scl["x"],',
					"scaleY": ',$scl["y"],',
					"scaleZ": ',$scl["z"],'
		},
			"Nickname": "',$nickname,'",
			"Description": "',$description,'",
			"GMNotes": "',$gm,'",
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
			"FaceURL": "',$face,'",
				"BackURL": "',$back,'",
				"NumWidth": 1,
				"NumHeight": 1,
				"BackIsHidden": true,
				"UniqueBack": false,
				"Type": 0
		}
		},
			"LuaScript": "',$script,'",
			"LuaScriptState": "",
			"XmlUI": "",
			"GUID": "748460",
			"States": {
			"2": {
			"Name": "Card",
				"Transform": {
				"posX": ',$pos["x"],',
					"posY":	',$pos["y"],',
					"posZ": ',$pos["z"],',
					"rotX": ',$rot["x"],',
					"rotY": ',$rot["y"],',
					"rotZ": ',$rot["z"],',
					"scaleX": ',$scl["x"],',
					"scaleY": ',$scl["y"],',
					"scaleZ": ',$scl["z"],'
		},
			"Nickname": "',$nickname,'",
			"Description": "',$description,'",
			"GMNotes": "',$gm,'",
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
			"FaceURL": "',$face,'",
				"BackURL": "',$dfcback,'",
				"NumWidth": 1,
				"NumHeight": 1,
				"BackIsHidden": true,
				"UniqueBack": false,
				"Type": 0
		}
		},
			"LuaScript": "',$backscript."\nfunction onObjectEnterZone(zone, object) if object == self then object.setState(1) end end",'",
			"LuaScriptState": "",
			"XmlUI": "",
			"GUID": "947dc9"
		}
		}
		}@';

		} else {
			if($aback == null){
				$back = $CARDBACK; 
			} else {
				$back = $aback;
			}
			echo '{
			"Name": "Card",
				"Transform": {
				"posX": ',$pos["x"],',
					"posY":	',$pos["y"],',
					"posZ": ',$pos["z"],',
					"rotX": ',$rot["x"],',
					"rotY": ',$rot["y"],',
					"rotZ": ',$rot["z"],',
					"scaleX": ',$scl["x"],',
					"scaleY": ',$scl["y"],',
					"scaleZ": ',$scl["z"],'
		},
			"Nickname": "',$nickname,'",
			"Description": "',$description,'",
			"GMNotes": "',$gm,'",
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
			"FaceURL": "',$face,'",
				"BackURL": "',$back,'",
				"NumWidth": 1,
				"NumHeight": 1,
				"BackIsHidden": true,
				"UniqueBack": false,
				"Type": 0
		}
		},
			"LuaScript": "',$script,'",
			"LuaScriptState": "",
			"XmlUI": "",
			"GUID": "947dc9"
		}@';
		}
	}
}
