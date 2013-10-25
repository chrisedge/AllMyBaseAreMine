<?php
define('INCLUDE_CHECK', true);
error_reporting (E_ALL ^ E_NOTICE);

require 'connect.php';
require 'functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seLogin');
session_set_cookie_params(2*7*24*60*60);
session_start();


if ( isset($_GET['logoff']) || !( isset($_GET['current']) ) || ( isset($_SESSION['is_manager']) == '0' ) 
|| !( isset($_SESSION['users_id']) ) ){
	$_SESSION['Msg'] = "You can not access this page. Contact the administrator if you believe this is an error.";
  	header("Location: redirect.php?Url=index.php?logoff");
  	exit;
}


$_GET=from_array($_GET);
// print_r($_GET);
$id = $_GET['current'];

// Update our session variable so we'll know what to put in "Back" on the calling page.
// A combined query getting the users_id of the manager of the user we're getting info for.
$result_previous = mysql_query("SELECT users_id FROM managers WHERE managers_id = 
( SELECT managers_id FROM users WHERE users_id = '{$id}' )");
if (!$result_previous) {
	$_SESSION['Msg'] = "Unable to get previous manager. Contact the administrator.";
  	header("Location: redirect.php?Url=index.php?logoff");
  	exit;
}
$row_previous = mysql_fetch_array($result_previous, MYSQL_ASSOC);
mysql_free_result($result_previous);
$_SESSION['previous'] = $row_previous['users_id'];

$directs = getDirectsNew($id); 
if ( $directs == '0' ) {
?>
	<ul><li>No direct reports</li></ul>
<?php
	exit;
}
?>


