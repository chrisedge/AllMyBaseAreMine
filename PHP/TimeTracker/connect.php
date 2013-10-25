<?php

if(!defined('INCLUDE_CHECK')) die('You are not allowed to execute this file directly');

define('SERVER_ROOT',   '/var/www/html/'); // NOTE THE TRAILING SLASH!

/* Database config */
$db_host		= 'localhost';
$db_user		= 'time-user';
$db_pass		= 'qwerty';
$db_database    = 'time-db';
/* End config */

$link = mysql_connect($db_host,$db_user,$db_pass) or die('Unable to establish a DB connection');

mysql_select_db($db_database,$link);
mysql_query("SET names UTF8");

// Forcing SSL during UAT.
/*if ($_SERVER['SERVER_PORT']!=444) {
	$url = "https://". $_SERVER['SERVER_NAME'] . ":444".$_SERVER['REQUEST_URI'];
	header("Location: $url");
}*/

// Handle session cache so stuff shouldn't be reloadable (i.e., time_entry3.php)
session_cache_limiter('nocache');

// NOTE: IE (shocker) will not load Flash (used by Fusion Charts) files over an HTTPS connection with nocache
// set. So, in the charting pages, this needs to be reset to 'public' prior to session_start.

?>
