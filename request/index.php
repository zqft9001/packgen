<?php

//add consume to get request info and clean it
include('../consume.php');

//makes the file output as plain text instead of html
header('Content-type: text/plain');

echo "Get/Post Data \n";
if(isset($gclean)){var_dump($gclean);}

echo "\n Put Data \n";
if(isset($pclean)){var_dump($pclean);}


?>
