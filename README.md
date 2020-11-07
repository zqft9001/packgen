# packgen

Generates MTG packs based on a MTGJSON database.

Database can be downloaded here (all printings SQL files):

https://mtgjson.com/downloads/all-files/

You will need to set up a file called db_defs.php in the root directory of the site with the following formatting to use that database:

<?php
define('SERVERNAME', [server]);
define('USERNAME', [username]);
define('PASSWORD', [password]);
define('DBNAME', [database name]);
?>
