<?php



// database
echo "Setting up database...";

$db = new mysqli('localhost:8889', 'root', 'root');

$q = "CREATE DATABASE IF NOT EXISTS PHTwitter DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$db->query($q);

$q = "USE PHTwitter";
$db->query($q);

$q = "CREATE TABLE IF NOT EXISTS tweet (
    timestamp bigint(20) unsigned NOT NULL, 
    id varchar(15) NOT NULL, 
    username char(60) NOT NULL, 
    tweetID varchar(60) NOT NULL, 
    tweet char(160) NOT NULL, 
    deleted tinyint(1) NOT NULL DEFAULT 0, 
    PRIMARY KEY (id)
)";
$db->query($q);

$db->close();

echo "\nDatabase setup done.";

?>