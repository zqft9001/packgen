<?php

include('../consume.php');

print_r(dirname(__DIR__));

echo "<BR>";

//Displays all images in the directory. Make it nicer later.

if(isset($gclean['clearcache'])){
	echo "<a href=\".\">Refresh Page</a>";
} else {
	echo "<a href=\"./?clearcache=yes\">Clear Cache</a>";
}

$directory = scandir('./');

echo count($directory);

foreach ($directory as $file){
	if(str_contains($file, ".jpg")){
		if(isset($gclean['clearcache'])){
			$realpath = realpath($file);
			if(file_exists($realpath)){
				unlink($realpath);
			} else {
				echo "file doesn't exist";
			}

		}else{
			echo "<BR>";
			echo $file;
		}
	}
}

?>
