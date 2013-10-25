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

    header("Location: /admin/index.php");
    exit;
}

$_SESSION=from_array($_SESSION); // Massage the input with from_array().
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.

if ( isset($_POST['submit']) && $_POST['submit'] == 'Add' ) {
	// They're adding an admin.
	$_POST=from_array($_POST);
	$id = $_POST['user']; // The users.users_id of the person want to add.
	if ( $id == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No new users ID found. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/admin_edit.php");
		exit;
	}
	
	$result_change = mysql_query("INSERT INTO admin (users_id) VALUES ('{$id}')");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not insert new admin. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "New administrator added.";
		header("Location: redirect_admin.php?Url=/admin/admin_edit.php");
		exit;
	}
}

if ( isset($_POST['submit']) && $_POST['submit'] == 'Delete' ) {
	// They're deleting an admin.
	$_POST=from_array($_POST);
	$id = $_POST['user'];
	if ( $id == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No users ID found to delete. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/admin_edit.php");
		exit;
	}
	
	$result_change = mysql_query("DELETE FROM admin WHERE users_id = '{$id}'");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not delete the administrator you selected. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "Administrator deleted.";
		header("Location: redirect_admin.php?Url=/admin/admin_edit.php");
		exit;
	}
}

?>

<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php echo $_SESSION['TITLE']; ?> Administration</title>
	<script type="text/javascript" language="javascript" src="/js/setime.js"></script>
    <link type="text/css" href="/css/setime.css" rel="stylesheet" />
</head>

<body>
<div style="float:left;font-family:sans-serif;width=25%">
<img src="/logo.gif" alt="" /> <br /><br /><br />
Login: <?php echo $_SESSION['userName']; ?>
<br /><br />
<?php printAdminMenu(); // functions.php ?>

</div> <!-- end left div -->

<div id="main" style="float:right;text-align:center; background-color:white; border-left:solid #808080;width:75%">
<h3><?php echo $_SESSION['TITLE']; ?> Administrator</h3>



<?php if ( !isset($_POST['submit']) && $_POST['submit'] != 'Add' && $_POST['submit'] != 'Delete' ) { ?>

<div style="text-align:left;"> <!-- fieldset div -->
<fieldset style="width:50%;">
<legend><strong>Add a new administrator</strong></legend>
<br />
<form name="newAdmin" action="" method="post">
    <label for="user"><strong>User:</strong></label>
    <select name="user">
    	<option value="">Select a user...</option>
        <?php
		// Get a list of users that currently are not administrators.
		$result_users = mysql_query("SELECT users_id,userName FROM users WHERE
		users_id NOT IN ( SELECT users_id FROM admin ) ORDER BY userName ASC");
		if (!$result_users) {
			$_SESSION['Msg'] = "Could not get list of users for admin addition. Contact the developer.";
			header("Location: redirect_admin.php?Url=/admin/admin.php");
			exit;
		}
		while ( $row_users = mysql_fetch_array($result_users, MYSQL_ASSOC) ) {
			echo '<option value="'.$row_users['users_id'].'">'.$row_users['userName'].'</option>';
		}
		mysql_free_result($result_users);
		?>
    </select>
  
    <p>
    <input type="submit" name="submit" value="Add" />
    </p>
</form>
</fieldset>
<br />

<fieldset style="width:50%;">
<legend><strong>Delete an administrator</strong></legend>
<br />
<form name="deleteAdmin" action="" method="post">
<label for="user"><strong>User to delete:</strong></label>
<select name="user">
	<option value="">Select a user...</option>
        <?php
		// Get a list of users that currently are administrators, excluding our current user of course and the 'admin'.
		$result_admins = mysql_query("SELECT users.users_id,users.userName FROM users 
		LEFT JOIN admin ON users.users_id = admin.users_id WHERE admin.users_id IS NOT NULL
		AND users.userName != 'admin' AND admin.admin_id != '{$admin_id}' ORDER BY users.userName");
		if (!$result_admins) {
			$_SESSION['Msg'] = "Could not get list of users for admin deletion. Contact the developer.";
			header("Location: redirect_admin.php?Url=/admin/admin.php");
			exit;
		}
		while ( $row_admins = mysql_fetch_array($result_admins, MYSQL_ASSOC) ) {
			echo '<option value="'.$row_admins['users_id'].'">'.$row_admins['userName'].'</option>';
		}
		mysql_free_result($result_admins);
		?>
</select>
<p><input type="submit" name="submit" value="Delete" /></p>
</form>
</fieldset>
</div> <!-- end fieldset div -->

</div>
</body>
</html>

<?php
} // !isset($_POST['submit']) && $_POST['submit'] != 'Add' && $_POST['submit'] != 'Delete'
