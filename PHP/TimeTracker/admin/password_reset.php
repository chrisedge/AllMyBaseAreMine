<?php
define('INCLUDE_CHECK', true);
error_reporting (E_ALL ^ E_NOTICE);

require '../connect.php';
require '../functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seAdmin');
session_set_cookie_params(2*7*24*60*60);
session_start();

if ( isset($_GET['logoff']) || !( isset($_SESSION['users_id']) ) || !( isset($_SESSION['admin_id']) ) ){ // Their credentials are not valid, or they logged off.
	$_SESSION = array();
    session_destroy();

    header("Location: index.php");
    exit;
}

$_SESSION=from_array($_SESSION); // Massage the input with from_array().
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.

if ( isset($_POST['submit']) && $_POST['submit'] == "Change" ) {
	// unset($_POST);
	$_POST=from_array($_POST);
	// print_r($_POST);
	
	if ($_POST['newpass1'] == '' || $_POST['newpass2'] == '') {
		unset($_POST);
		$_SESSION['Msg'] = "Neither value can be blank. You must start over.";
		header("Location: redirect_admin.php?Url=admin.php");
		exit;
		// echo 'here1';
	}
	// Verify the submitted passwords match
	if ( (strcmp($_POST['newpass1'],$_POST['newpass2'])) != 0 ) {
		unset($_POST);
		$_SESSION['Msg'] = "Passwords do not match. Please try again.";
		header("Location: redirect_admin.php?Url=admin.php");
		exit;
		// echo 'here2';
	}
	
	$newpass = $_POST['newpass1'];
	$user = $_POST['user'];
	
	$result_update = mysql_query("UPDATE users SET password = MD5( '{$newpass}' ) WHERE users_id = '{$user}'");
	$aff_rows = mysql_affected_rows(); // Call with no argument to use the last connect link identifier.
	
	if ( $aff_rows != 1 ) {
		unset ($_POST);
		$_SESSION['Msg'] = "Could not update new password. New password possibly the same as old password.";
		header("Location: redirect_admin.php?Url=admin.php");
		exit;
		// echo 'here3';
	}
	
	unset ($_POST);
	$_SESSION['Msg'] = "Password updated.";
	header("Location: redirect_admin.php?Url=admin.php");

	exit;

} // isset($_POST['submit']) && $_POST['submit'] == "Change"



?>

<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php echo $_SESSION['TITLE']; ?> Administration</title>
	<script type="text/javascript" language="javascript" src="../js/setime.js"></script>
    <link type="text/css" href="../css/setime.css" rel="stylesheet" />
</head>

<body>
<div style="float:left;font-family:sans-serif;width=25%">
<img src="/logo.gif" alt="" /> <br /><br /><br />
Login: <?php echo $_SESSION['userName']; ?>
<br /><br />
<?php printAdminMenu(); // functions.php ?>

</div> <!-- end left div -->

<div style="float:right;text-align:center; background-color:white; border-left:solid #808080;width:75%">
<h3><?php echo $_SESSION['TITLE']; ?> Administrator</h3>



<?php if ( !isset($_POST['submit']) && $_POST['submit'] != 'Change' ) { ?>

<fieldset style="width:80%;">
<legend><strong>Reset a user's password</strong></legend>
<form name="chpass" action="" method="post">
	<label for="user"><strong>Select a user:</strong></label>
    <select name="user">
    	<option value="">Select....</option>
        <?php
		// Get a list of all the users, sorted alphabetically.
		$result_users = mysql_query("SELECT users_id,userName FROM users 
		WHERE userName != 'admin' AND userName != '{$userName}' ORDER BY userName ASC");
		if (!$result_users) {
			$_SESSION['Msg'] = "Could not get list of users for password reset. Contact the developer.";
			header("Location: redirect_admin.php?Url=admin.php");
			exit;
		}
		while ( $row_users = mysql_fetch_array($result_users, MYSQL_ASSOC) ) {
			echo '<option value="'.$row_users['users_id'].'">'.$row_users['userName'].'</option>';
		}
		mysql_free_result($result_users);
		?>
    </select>
    <br />
	<label for="newpass1"><strong>New Password</strong></label>
    <input type="password" size="20" maxlength="255" name="newpass1" placeholder="********" /><br />
    <label for="newpass2"><strong>Retype Password</strong></label>
    <input type="password" size="20" maxlength="255" name="newpass2" placeholder="********" /><br />
    <p><input type="submit" name="submit" value="Change" /></p>
</form>
</fieldset>


</div>
</body>
</html>
<?php
} //  isset($_SESSION['changed']) !=1

?>
