<?php
define('INCLUDE_CHECK', true);
error_reporting (E_ALL ^ E_NOTICE);

require 'connect.php';
require 'functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seLogin');
session_set_cookie_params(2*7*24*60*60);
session_start();

if ( isset($_GET['logoff']) || !( isset($_SESSION['users_id']) ) ){ // Their credentials are not valid, or they logged off.
	$_SESSION = array();
    session_destroy();

    header("Location: index.php");
    exit;
}

$_SESSION=from_array($_SESSION); // Massage the input with from_array().
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.
// $users_id = $_SESSION['users_id'];

if ( isset($_POST['submit']) && $_POST['submit'] == "Assume Role" ) {
	$_POST=from_array($_POST);
	
	// Change their users_id.
	$_SESSION['users_id'] = $_POST['user'];
	$_SESSION['is_assumed'] = '1';
	// Reestablish their identity.
	$row = mysql_fetch_assoc(mysql_query(
			   "SELECT userName,firstName,lastName,managers_id FROM users WHERE users_id = '{$_POST['user']}'"));
	$_SESSION['userName'] = $row['userName'];
	$_SESSION['firstName'] = $row['firstName'];
	$_SESSION['lastName'] = $row['lastName'];
	$_SESSION['managers_id'] = $row['managers_id'];
	
	// Check to see if the user they've assumed the role of is a manager.
	// Check to see if they're a manager.
	$result_isManager = mysql_query( "SELECT managers_id FROM managers WHERE users_id = '{$_POST['user']}'" );
	if (!$result_isManager) {
		$_SESSION['Msg'] = "Could not connect to database to determine isManager. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	$num_rows = mysql_num_rows($result_isManager);
	
	if ( $num_rows == 0 ) {
		$_SESSION['is_manager'] = '0'; 
	} else {
		// Set their managers_id from the managers table in this variable for use later.
		$row_managersID = mysql_fetch_array($result_isManager, MYSQL_ASSOC);
		$_SESSION['is_manager'] = $row_managersID['managers_id'];
	}
	mysql_free_result($result_isManager);
	
	// Send them to the landing page.
	unset ($_POST);
	$_SESSION['Msg'] = "Role assumed.";
	header("Location: redirect.php?Url=landing.php");
	exit;
}

?>

<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php echo $_SESSION['TITLE']; ?></title>
	<script type="text/javascript" language="javascript" src="js/setime.js"></script>
    <link type="text/css" href="css/setime.css" rel="stylesheet" />
</head>

<body>
<div style="float:left;font-family:sans-serif;width=25%">
<img src="images/logo.gif" alt="" /> <br /><br /><br />
Login: <?php echo $_SESSION['userName']; ?>
<br /><br />
<a href="#">Help and FAQ</a>
<br /><br /><a href='?logoff'>Logout</a>
</div>

<div style="float:right;text-align:center; background-color:white; border-left:solid #808080;width:75%">
<h3><?php echo $_SESSION['TITLE']; ?> Role Selection
<?php if ( $_SESSION['firstName'] != 'NULL' ) { echo " for: " . $_SESSION['firstName'] . " " . $_SESSION['lastName']; } ?>
</h3>



<?php if ( !isset($_POST['submit']) && $_POST['submit'] != 'Assume Role' ) { ?>

You have been delegated permissions to act as another user. Select a user, or continue as yourself.

<hr width="100%" />

<form name="delgate" action="" method="post">
	<label for="user">User Selection:</label>
    <select name="user">
    	<option value="">Select a user...</option>
	<?php
	$result_delegate = mysql_query("SELECT * FROM delegation WHERE users_id = '{$_SESSION['users_id']}'");
	if (!$result_delegate) {
		$_SESSION['Msg'] = "Could not connect to database to determine delegation for user. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	while ( $row_delegate = mysql_fetch_array($result_delegate, MYSQL_ASSOC) ) {
		$euid = $row_delegate['effective_users_id'];
		$result_userName = mysql_query("SELECT userName FROM users WHERE users_id = '{$euid}'");
		if (!$result_delegate) {
			$_SESSION['Msg'] = "Could not connect to database to get user name on delegation. Contact the administrator.";
			header("Location: redirect.php?Url=/index.php?logoff");
			exit;
		}
		$row_userName = mysql_fetch_array($result_userName, MYSQL_ASSOC);
		mysql_free_result($result_userName);
	?>
    	<option value="<?php echo $euid; ?>"><?php echo $row_userName['userName']; ?></option>
    <?php
	}
	mysql_free_result($result_delegate);
	?>
    </select>
    <p align="center"><input type="submit" name="submit" value="Assume Role" /></p>
</form>
<a href="landing.php">Continue as myself</a>

</div>
</body>
</html>
<?php
} //  !isset($_POST['submit']) && $_POST['submit'] != 'Assume Role'

?>
