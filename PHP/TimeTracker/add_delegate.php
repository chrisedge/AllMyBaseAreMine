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

if ( isset($_POST['submit']) && $_POST['submit'] == "Add Delegate" ) {
	$_POST=from_array($_POST);
	//print_r($_POST);
	//print_r($_SESSION);
	$user = $_POST['user'];
	
	if ( $user == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No user found to add. Please try again.";
		header("Location: redirect.php?Url=add_delegate.php");
		exit;
	}
	
	
	$result_change = mysql_query("INSERT INTO delegation (users_id,effective_users_id) 
	VALUES ('{$user}', '{$_SESSION['users_id']}')");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not add new delegate. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	} else {
		unset ($_POST);
		$_SESSION['Msg'] = "Delegate added.";
		header("Location: redirect.php?Url=landing.php");
		exit;
	}
	
}

if ( isset($_POST['submit']) && $_POST['submit'] == "Delete Delegate" ) {
	$_POST=from_array($_POST);
	
	$user = $_POST['user'];
	
	if ( $user == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No user found to delete. Please try again.";
		header("Location: redirect.php?Url=add_delegate.php");
		exit;
	}
	
	$result_change = mysql_query("DELETE FROM delegation WHERE delegation_id = '{$user}'");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not delete the delegate you selected. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "Delegate deleted.";
		header("Location: redirect.php?Url=add_delegate.php");
		exit;
	}
}

?>

<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php echo $_SESSION['TITLE']; ?></title>
	<script type="text/javascript" language="javascript" src="/js/setime.js"></script>
    <link type="text/css" href="css/setime.css" rel="stylesheet" />
</head>

<body>
<div style="float:left;font-family:sans-serif;width=25%">
<img src="logo.gif" alt="" /> <br /><br /><br />
Login: <?php echo $_SESSION['userName']; ?>
<br />
<?php echo $userName; ?> is reporting to:

<?php printMenu(); // functions.php ?>

</div> <!-- end left div -->

<div style="float:right;text-align:center; background-color:white; border-left:solid #808080;width:75%">
<h3><?php echo $_SESSION['TITLE']; ?> Add Delegate
<?php if ( $_SESSION['firstName'] != 'NULL' ) { echo " for: " . $_SESSION['firstName'] . " " . $_SESSION['lastName']; } ?>
</h3>



<?php if ( !isset($_POST['submit']) && $_POST['submit'] != 'Add Delegate' ) { ?>

Select a user below that you would like to grant the ability to act on your behalf.<br />
<span class="err">NOTE: This user will have ALL of your privileges.</span>

<hr width="100%" />

<form name="addDelgate" action="" method="post">
	<label for="user"><strong>User Selection:</strong></label>
    <select name="user">
    	<option value="">Select a user...</option>
	<?php
	$result_delegate = mysql_query("SELECT users_id,userName FROM users WHERE users_id != '{$users_id}' 
	AND hasProfile != '0' ORDER BY userName ASC");
	if (!$result_delegate) {
		$_SESSION['Msg'] = "Could not connect to database to get user list for delegation. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	while ( $row_delegate = mysql_fetch_array($result_delegate, MYSQL_ASSOC) ) {
	?>
    	<option value="<?php echo $row_delegate['users_id']; ?>"><?php echo $row_delegate['userName']; ?></option>
    <?php
	}
	mysql_free_result($result_delegate);
	?>
    </select>
    <br />
    <font size="-1">(The user must have an account and profile already established)</font>
    <p align="center"><input type="submit" name="submit" value="Add Delegate" /></p>
</form>
<br  />
<?php
// If they have one or more delegates already established, give them a form to delete them.
$result_delegates = mysql_query("SELECT * FROM delegation WHERE effective_users_id = '{$users_id}'");
if ( !$result_delegates ) {
	$_SESSION['Msg'] = "Could not connect to database to get delegation list. Contact the administrator.";
	header("Location: redirect.php?Url=/index.php?logoff");
	exit;
}
$num_delegates = mysql_num_rows($result_delegates);
if ( $num_delegates > 0 ) {
?>
    <h3>Delete a Delegate</h3>
    <form name="delDelegate" action="" method="post">
        <label for="user"><strong>Existing Delegates:</strong></label>
        <select name="user">
        <option value="">Select a user...</option>
<?php
	while ( $row_delegates = mysql_fetch_array($result_delegates, MYSQL_ASSOC) ) {
		$result_userName = mysql_query("SELECT userName FROM users WHERE users_id = '{$row_delegates['users_id']}'");
		if ( !$result_userName ) {
			$_SESSION['Msg'] = "Could not connect to database to get user name for delegates. Contact the administrator.";
			header("Location: redirect.php?Url=/index.php?logoff");
			exit;
		}
		$row_userName = mysql_fetch_array($result_userName, MYSQL_ASSOC);
		mysql_free_result($result_userName);
	?>
    
    	<option value="<?php echo $row_delegates['delegation_id']; ?>"><?php echo $row_userName['userName']; ?></option>
    
	<?php
	} // $row_delegates = mysql_fetch_array($result_delegates, MYSQL_ASSOC)
	mysql_free_result($result_delegates);
?>
		</select>
    <p align="center"><input type="submit" name="submit" value="Delete Delegate" /></p>
	</form>
<?php
} // $num_deletegates > 0
?>

</div>
</body>
</html>
<?php
} //  !isset($_POST['submit']) && $_POST['submit'] != 'Add Delegate'

?>
