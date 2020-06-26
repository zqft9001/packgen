<?php


function getcard($set, $calcrare, $timeshifted, $frameeffect){

	//gets a card from a set based on the rarity provided. Does not pull basics.

	$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "select * from cards where cards.setcode = '".$set."' and cards.rarity = '".$calcrare."' and cards.type not like '%Basic%'";


	//this may get messy in the future. Fixes things like time spiral.
	switch(true){
	case ($frameeffect == "null"):
		$sql = $sql."and cards.isTimeshifted = ".$timeshifted." and cards.frameEffect is null;";
		break;
	case (strlen($frameeffect)>0):
		$sql = $sql." and cards.frameEffect like '%".$frameeffect."%';";
		break;
	default:
		$sql = $sql." and cards.isTimeshifted = ".$timeshifted.";";
		break;
	}

if(false){

	echo $sql."\n";

}
	$result = $conn->query($sql);

	if ($result->num_rows < 1){
		return;
	}

	$card = rand(0, $result->num_rows-1);

	$result->data_seek($card);
	$row = $result->fetch_array();
	$cardname = $row["name"];


	$conn->close();
	return $cardname;
}

function getbytype($set, $calcrare, $type){


	//gets a card by type and rarity from a set

	$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "select * from cards where cards.setcode = '".$set."' and cards.rarity = '".$calcrare."' and cards.type like '%".$type."%';";

	$result = $conn->query($sql);

	//	echo $sql;

	if ($result->num_rows < 1){
		return;
	}

	$card = rand(0, $result->num_rows-1);

	$result->data_seek($card);
	$row = $result->fetch_array();
	$cardname = $row["name"];


	$conn->close();
	return $cardname;

}

function typeonly($type){


	//gets a card by type and rarity from a set

	$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "select * from cards where cards.type like '%".$type."%';";

	$result = $conn->query($sql);

	//	echo $sql;

	if ($result->num_rows < 1){
		return;
	}

	$card = rand(0, $result->num_rows-1);

	$result->data_seek($card);
	$row = $result->fetch_array();
	$cardname = $row["name"];


	$conn->close();
	return $cardname;

}

function rarityonly($calcrare){

	//gets a card by type and rarity from a set

	$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DBNAME);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	$sql = "select * from cards where cards.rarity like '%".$calcrare."%';";

	$result = $conn->query($sql);

	//	echo $sql;

	if ($result->num_rows < 1){
		return;
	}

	$card = rand(0, $result->num_rows-1);

	$result->data_seek($card);
	$row = $result->fetch_array();
	$cardname = $row["name"];


	$conn->close();
	return $cardname;
}

//Generates a rarity based on the length of the string passed.
//curm - common uncommon rare mythic
//cur - common uncommon rare
//cu - common uncommon
//with nothing provided, will just return common.
function raritygenerate($indicator){
	if (rand(1,20) == 1 and strlen($indicator)>2){
		if (rand(1,8) == 1 and strlen($indicator)>3){
			return "mythic";
		} else {
			return "rare"; 
		}
	}
	if (rand(1,5) == 1 and strlen($indicator)>1){
		return "uncommon";
	}
	return "common";
}

function dgmland($shocks, $gates){
	
		if(rand(1,20) == 1){
			if(rand(1,8) == 1){
				return "Maze's End";
			} else {
				return $shocks[rand(0, count($shocks)-1)];
			}
		}
			return $gates[rand(0, count($gates)-1)];

}

function printcards($cardlist){

	//Prints the list of cards in the pack.
	foreach($cardlist as $cardname){
		if (strlen($cardname) > 0){
			echo "1 ".$cardname;
			echo "\n";
		}
	}	

}
