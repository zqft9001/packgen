<?php


function getcard($cnd){

	//gets a card from a set based on the conditions provided

	$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "select * from cards";

	$filterstart = " where ";
	$fbuild = "";
	$filterend = ";";

	if($cnd["set"] != null){
		$fbuild = $fbuild."and cards.setCode = '".$cnd["set"]."' ";
	}

	if($cnd["rarity"] != null){
		$fbuild = $fbuild."and cards.rarity = '".$cnd["rarity"]."' ";
	}

	if($cnd["timeshifted"] != null){
		$fbuild = $fbuild."and cards.isTimeshifted = ".$cnd["timeshifted"]." ";
	}

	if($cnd["frameEffect"] != null){
		$fbuild = $fbuild."and cards.frameEffect like '%".$cnd["frameEffect"]."%' ";
	}

	if($cnd["noframeEffect"] == 1){
		$fbuild = $fbuild."and cards.frameEffect is null ";
	}

	if($cnd["type"] != null){
		$fbuild = $fbuild."and cards.type like '%".$cnd["type"]."%' ";
	}

	if($cnd["basic"] == null){
		$fbuild = $fbuild."and cards.type not like '%Basic%' ";
	} else {
		$fbuild = $fbuild."and cards.type like '%Basic%' ";
	}

	if (count($fbuild)>0){
		$fbuild = substr($fbuild, 4);
		$sql = $sql.$filterstart.$fbuild.$filterend;
	}

	if($cnd["name"] != null){
		$sql = "select * from cards where cards.name = '".$cnd["name"]."';";
	}
       

if($cnd["sql"] != null){

	echo $sql."\n";

}

	$result = $conn->query($sql);

	if ($result->num_rows < 1){
		return;
	}

	$card = rand(0, $result->num_rows-1);

	$result->data_seek($card);
	$card = $result->fetch_array();

if($cnd["sql"] != null){
	print_r($card);
}

	$conn->close();
	return $card;
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

function dgmland($cnd, $shocks, $gates){
	
		if(rand(1,20) == 1){
			if(rand(1,8) == 1){
				$cnd["name"]="Maze's End";
				return getcard($cnd);
			} else {
				$cnd["name"] = $shocks[rand(0, count($shocks)-1)];
				return getcard($cnd);
			}
		}
		$cnd["name"] =  $gates[rand(0, count($gates)-1)];
		return getcard($cnd);
}

function printcards($cardlist){

	//Prints the list of cards in the pack.
	foreach($cardlist as $card){
			echo "1 ".$card["name"];
			echo "\n";
	}	

}
