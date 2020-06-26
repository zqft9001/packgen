# packgen

Generates MTG packs based on a MTGJSON database.

Database can be downloaded here (all printings SQL files):

https://mtgjson.com/downloads/all-files/

You will need to set up a file called db_defs.php with the following formatting to use that database:


define('SERVERNAME', [server]);
define('USERNAME', [username]);
define('PASSWORD', [password]);
define('DBNAME', [database name]);

Additionally, you will need to define the below saved procedure in the database:

CREATE DEFINER=`[username]`@`[server]` FUNCTION `randrune`()
RETURNS varchar(255) CHARSET utf8mb4
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
DECLARE randrune VARCHAR(255);
SELECT keyruneCode INTO randrune
FROM MTG.sets AS r1
JOIN
 (
SELECT CEIL(RAND() *
 (
SELECT MAX(id)
FROM MTG.sets
WHERE MTG.sets.boosterV3 IS NOT NULL)) AS id) AS r2
WHERE r1.id >= r2.id AND r1.boosterV3 IS NOT NULL
ORDER BY r1.id ASC
LIMIT 1;
RETURN randrune;
END


