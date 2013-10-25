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

if ( isset($_POST['submit']) && $_POST['submit'] == 'Add' ) {
	// They're adding a user to be excluded.
	$_POST=from_array($_POST);
	$id = $_POST['user']; // The users.users_id of the person they want to add.
	if ( $id == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No new users ID found. Please try again.";
		header("Location: redirect_admin.php?Url=exclude_email.php");
		exit;
	}
	
	$result_change = mysql_query("INSERT INTO email_exclusion (users_id) VALUES ('{$id}')");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not insert new user for exclusion. Contact the developer.";
		header("Location: redirect_admin.php?Url=admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "New user excluded from emails.";
		header("Location: redirect_admin.php?Url=exclude_email.php");
		exit;
	}
}

if ( isset($_POST['submit']) && $_POST['submit'] == 'Delete' ) {
	// They're deleting a user from the exclusion list.
	$_POST=from_array($_POST);
	$id = $_POST['user'];
	if ( $id == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No users ID found to delete. Please try again.";
		header("Location: redirect_admin.php?Url=exclude_email.php");
		exit;
	}
	
	$result_change = mysql_query("DELETE FROM email_exclusion WHERE users_id = '{$id}'");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not delete the user you selected. Contact the developer.";
		header("Location: redirect_admin.php?Url=admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "User removed from email exclusion list.";
		header("Location: redirect_admin.php?Url=exclude_email.php");
		exit;
	}
}

if ( isset($_POST['submit']) && $_POST['submit'] == 'Update' ) {
	// They're deleting a user from the exclusion list.
	$_POST=from_array($_POST);
	// print_r($_POST);

	$id = $_POST['id'];
	if ( $id == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No users ID found to modify. Please try again.";
		header("Location: redirect_admin.php?Url=exclude_email.php");
		exit;
	}
	
	if ( isset($_POST['reminder']) ) {	// If this is set, it needs to be 1 in the DB. If not, it needs to be 0.
		$result_update = mysql_query("UPDATE email_exclusion SET reminder = '1' WHERE users_id = '{$id}'");
	} else {
		$result_update = mysql_query("UPDATE email_exclusion SET reminder = '0' WHERE users_id = '{$id}'");
	} // isset($_POST['reminder'])
	
	if ( isset($_POST['report']) ) {
		$result_update = mysql_query("UPDATE email_exclusion SET report = '1' WHERE users_id = '{$id}'");
	} else {
		$result_update = mysql_query("UPDATE email_exclusion SET report = '0' WHERE users_id = '{$id}'");
	} // isset($_POST['report'])
	
	unset($_POST);
	$_SESSION['Msg'] = "User\'s exclude options modified.";
	header("Location: redirect_admin.php?Url=exclude_email.php");
	exit;
	
} // isset($_POST['submit']) && $_POST['submit'] == 'Update'

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

<div id="main" style="float:right;text-align:center; background-color:white; border-left:solid #808080;width:75%">
<h3><?php echo $_SESSION['TITLE']; ?> Administrator</h3>



<?php if ( !isset($_POST['submit']) && $_POST['submit'] != 'Add' && $_POST['submit'] != 'Delete' && $_POST['submit'] != 'Modify' && $_POST['submit'] != 'Update' ) { ?>

<div style="text-align:left;"> <!-- fieldset div -->
<fieldset style="width:50%;">
<legend><strong>Exclude a new user from receiving emails</strong></legend>
<br />
<form name="newExclude" action="" method="post">
    <label for="user"><strong>User to add:</strong></label>
    <select name="user">
    	<option value="">Select a user...</option>
        <?php
		// Get a list of users that currently are not excluded.
		$result_users = mysql_query("SELECT users_id,userName FROM users WHERE
		users_id NOT IN ( SELECT users_id FROM email_exclusion ) ORDER BY userName ASC");
		if (!$result_users) {
			$_SESSION['Msg'] = "Could not get list of users for email exclusion. Contact the developer.";
			header("Location: redirect_admin.php?Url=admin.php");
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
<legend><strong>Modify a user's exclude options</strong></legend>
<br />
<form name="modExclude" action="" method="post">
<label for="user"><strong>User to modify:</strong></label>
<select name="user">
	<option value="">Select a user...</option>
        <?php
		// Get a list of users that currently are excluded, excluding the 'admin'.
		$result_excludes = mysql_query("SELECT users.users_id,users.userName FROM users 
		LEFT JOIN email_exclusion ON users.users_id = email_exclusion.users_id WHERE email_exclusion.users_id IS NOT NULL
		AND users.userName != 'admin' ORDER BY users.userName");
		if (!$result_excludes) {
			$_SESSION['Msg'] = "Could not get list of users for exclude deletion. Contact the developer.";
			header("Location: redirect_admin.php?Url=admin.php");
			exit;
		}
		while ( $row_excludes = mysql_fetch_array($result_excludes, MYSQL_ASSOC) ) {
			echo '<option value="'.$row_excludes['users_id'].'">'.$row_excludes['userName'].'</option>';
		}
		mysql_free_result($result_excludes);
		?>
</select>
<p><input type="submit" name="submit" value="Modify" /></p>
</form>
</fieldset>

<fieldset style="width:50%;">
<legend><strong>Remove a user from the exclude list</strong></legend>
<br />
<form name="deleteExclude" action="" method="post">
<label for="user"><strong>User to remove:</strong></label>
<select name="user">
	<option value="">Select a user...</option>
        <?php
		// Get a list of users that currently are excluded, excluding the 'admin'.
		$result_excludes = mysql_query("SELECT users.users_id,users.userName FROM users 
		LEFT JOIN email_exclusion ON users.users_id = email_exclusion.users_id WHERE email_exclusion.users_id IS NOT NULL
		AND users.userName != 'admin' ORDER BY users.userName");
		if (!$result_excludes) {
			$_SESSION['Msg'] = "Could not get list of users for exclude deletion. Contact the developer.";
			header("Location: redirect_admin.php?Url=admin.php");
			exit;
		}
		while ( $row_excludes = mysql_fetch_array($result_excludes, MYSQL_ASSOC) ) {
			echo '<option value="'.$row_excludes['users_id'].'">'.$row_excludes['userName'].'</option>';
		}
		mysql_free_result($result_excludes);
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
if ( isset($_POST['submit']) && $_POST['submit'] == "Modify" ) {
	$_POST=from_array($_POST);
	$id = $_POST['user']; // The users.users_id of the person they want to modify.
	if ( $id == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No new users ID found. Please try again.";
		header("Location: redirect_admin.php?Url=exclude_email.php");
		exit;
	}
	// Get this user's current exclude options.
	$result_exclude = mysql_query("SELECT * FROM email_exclusion WHERE users_id = '{$id}'");
	if (!$result_exclude) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not get user information for selected user to modify. Contact the developer.";
		header("Location: redirect_admin.php?Url=admin.php");
		exit;
	}
	$row_exclude = mysql_fetch_array($result_exclude, MYSQL_ASSOC);
	mysql_free_result($result_exclude);
	// Get their name.
	$result_name = mysql_query("SELECT userName FROM users WHERE users_id = '{$id}'");
	if (!$result_name) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not get user name for selected user to modify. Contact the developer.";
		header("Location: redirect_admin.php?Url=admin.php");
		exit;
	}
	$row_name = mysql_fetch_array($result_name, MYSQL_ASSOC);
	mysql_free_result($result_name);
	$name = $row_name['userName'];

?>

<div style="text-align:left;"> <!-- fieldset div -->
<fieldset style="width:50%;">
<legend><strong>Modify a user's exclude options</strong></legend>
<br />
<form name="modExclude" action="" method="post">
    <strong>Current exclude options for <?php echo $name; ?>:</strong><br />
    (Note: checked means the user will NOT receive the specified type of email)<br /><br />
	<label for="reminder"><strong>Reminder</strong></label>
    <input type="checkbox" name="reminder"
    <?php
	if ($row_exclude['reminder'] == 1) {
		echo ' checked="checked" ';
	}
	?> />
    
    <label for="report"><strong>Report</strong></label>
    <input type="checkbox" name="report"
    <?php
	if ($row_exclude['report'] == 1) {
		echo ' checked="checked" ';
	}
	?> />
    
    <input type="hidden" name="id" value="<?php echo $id; ?>"  />
    
    <p>
    <input type="submit" name="submit" value="Update" />
    </p>
</form>
</fieldset>
</div> <!-- end fieldset div -->

</div>
</body>
</html>

<?php
} // isset($_POST['submit']) && $_POST['submit'] == "Modify"
?>