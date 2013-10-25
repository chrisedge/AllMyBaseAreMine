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
	
	if ($_POST['user'] == '' || $_POST['position'] == '') {
		unset($_POST);
		$_SESSION['Msg'] = "Neither value can be blank. You must start over.";
		header("Location: redirect_admin.php?Url=admin.php");
		exit;
		// echo 'here1';
	}
	
	
	$newPosition = $_POST['position'];
	$user = $_POST['user'];
	$result_name = mysql_query("SELECT userName FROM users WHERE users_id = '{$user}'");
	if (!$result_name) {
		unset ($_POST);
		$_SESSION['Msg'] = "Could not get user's userName. Contact the developer.";
		header("Location: redirect_admin.php?Url=admin.php");
		exit;
	}
	$row_name = mysql_fetch_array($result_name, MYSQL_ASSOC);
	mysql_free_result($result_name);
	$name = $row_name['userName'];
	// See comment on line 117 of profile.php regarding managers and user's set as SEs.
	// If this user was anything other than an SE, check to see if they're listed as a manager (which they should be).
	// If they are listed as a manager and $newPosition is now '1' (SE) set isActive in the manager's table to 0, and
	// then find their direct reports and set their managers_id to the admin.
	$result_oldPosition = mysql_query("SELECT positions_id FROM profile WHERE users_id = '{$user}'");
	if (!$result_oldPosition) {
		unset ($_POST);
		$_SESSION['Msg'] = "Could not get user's previous position. Contact the developer.";
		header("Location: redirect_admin.php?Url=admin.php");
		exit;
	}
	$num_rows = mysql_num_rows($result_oldPosition);
	if ( $num_rows != '1' ) { // This user might not have established a profile yet.
		unset ($_POST);
		$_SESSION['Msg'] = "No previous position found. Perhaps this user hasn't established a profile yet?";
		header("Location: redirect_admin.php?Url=admin.php");
		exit;
	}
	$row_oldPosition = mysql_fetch_array($result_oldPosition, MYSQL_ASSOC);
	$oldPosition = $row_oldPosition['positions_id'];
	mysql_free_result($result_oldPosition);
	
	if ( $oldPosition != '1' && ($newPosition == '1' || $newPosition == 'inactive') ) {
		// They were something other than an SE, and they're being demoted to an SE. Check to see if they're listed as a 
		// manager.
		$result_isManager = mysql_query("SELECT managers_id FROM managers WHERE users_id = '{$user}'");
		if (!$result_isManager) {
			unset ($_POST);
			$_SESSION['Msg'] = "Could not determine manager status for user. Contact the developer.";
			header("Location: redirect_admin.php?Url=admin.php");
			exit;
		}
		$num_rows_manager = mysql_num_rows($result_isManager);
		$row_isManager = mysql_fetch_array($result_isManager, MYSQL_ASSOC);
		mysql_free_result($result_isManager);
		if ( $num_rows_manager == '1' ) {
			// They're listed as a manager, we need to get their direct reports and assign them the managers_id of the
			// admin.
			$managers_id = $row_isManager['managers_id'];
			$result_directs = mysql_query("SELECT users_id FROM users WHERE managers_id = '{$managers_id}'");
			if (!$result_directs) {
				unset ($_POST);
				$_SESSION['Msg'] = "Could not get direct reports for user. Contact the developer.";
				header("Location: redirect_admin.php?Url=admin.php");
				exit;
			}
			$num_directs = mysql_num_rows($result_directs);
			if ( $num_directs > '0' ) { // They have at least one direct report. Set their managers_id to that of the admin.
				$result_adminID = mysql_query("SELECT managers_id FROM managers WHERE name = 'admin'");
				if (!$result_adminID) {
					unset ($_POST);
					$_SESSION['Msg'] = "Could not get managers_id for admin. Contact the developer.";
					header("Location: redirect_admin.php?Url=admin.php");
					exit;
				}
				$row_adminID = mysql_fetch_array($result_adminID, MYSQL_ASSOC);
				mysql_free_result($result_adminID);
				$adminID = $row_adminID['managers_id'];
				
				while ( $row_directs = mysql_fetch_array($result_directs, MYSQL_ASSOC) ) {
					$tmp_users_id = $row_directs['users_id'];
					$result_update = mysql_query("UPDATE users SET managers_id = '{$adminID}' 
					WHERE users_id = '{$tmp_users_id}'");
					if (!$result_update) {
						unset ($_POST);
						$_SESSION['Msg'] = "Could not update managers_id for direct report. Contact the developer.";
						header("Location: redirect_admin.php?Url=admin.php");
						exit;
					}
				} // $row_directs = mysql_fetch_array($result_directs, MYSQL_ASSOC)
				mysql_free_result($result_directs);
				
			} // $num_directs > '0'
			
			// Since they're listed as a manager, and being demoted to SE, we remove the isActive flag in the manager's 	
			// table.
			$result_updateActive = mysql_query("UPDATE managers SET isActive = '0' WHERE managers_id = '{$managers_id}'");
			if (!$result_updateActive) {
				unset ($_POST);
				$_SESSION['Msg'] = "Could not update isActive in managers table to 0. Contact the developer.";
				header("Location: redirect_admin.php?Url=admin.php");
				exit;
			}
			
		} // $num_rows_manager == '1'
		
		
		// If they're being made inactive, toggle the hasProfile bit so nag emails will stop.
		if ( $newPosition == 'inactive' ) {
			$result_profileBit = mysql_query("UPDATE users SET hasProfile = '0' WHERE users_id = '{$user}'");
			if (!$result_profileBit) {
				unset ($_POST);
				$_SESSION['Msg'] = "Could not update users table for hasProfile bit. Contact the developer.";
				header("Location: redirect_admin.php?Url=admin.php");
				exit;
			}
			if ( !(mysql_affected_rows()) ) {
				unset ($_POST);
				$_SESSION['Msg'] = "No user to update? Perhaps this user has not yet established a profile?.";
				header("Location: redirect_admin.php?Url=admin.php");
				exit;
			}
			
			// Since they're being made inactive, reset newPosition to 1 (SE) for the last update below.
			$newPosition = '1';
			
		} // $newPosition == 'inactive'
		
		// Lastly, set their new position in their profile.
		$result_profile = mysql_query("UPDATE profile SET positions_id = '{$newPosition}' WHERE users_id = '{$user}'");
		if (!$result_profile) {
			unset ($_POST);
			$_SESSION['Msg'] = "Could not update profile with new position for user. Contact the developer.";
			header("Location: redirect_admin.php?Url=admin.php");
			exit;
		}
		if ( !(mysql_affected_rows()) ) {
			unset ($_POST);
			$_SESSION['Msg'] = "No profile to update? Perhaps this user has not yet established a profile?.";
			header("Location: redirect_admin.php?Url=admin.php");
			exit;
		}
		
	}  // $oldPosition != '1' && ($newPosition == '1' || $newPosition == 'inactive')
	
	if ( $oldPosition == '1' && $newPosition != '1' ) { // They've been promoted, so establish them in the manager's table.
		
		// Check to see if they're in there first.
		$result_isManager = mysql_query("SELECT managers_id FROM managers WHERE users_id = '{$user}'");
		if (!$result_isManager) {
			unset ($_POST);
			$_SESSION['Msg'] = "Could not determine manager status for user. Contact the developer.";
			header("Location: redirect_admin.php?Url=admin.php");
			exit;
		} // !$result_isManager
		$num_rows_manager = mysql_num_rows($result_isManager);
		$row_isManager = mysql_fetch_array($result_isManager, MYSQL_ASSOC);
		mysql_free_result($result_isManager);
		if ( $num_rows_manager == '1' ) { // They're already in there, as an SE? We'll just update isActive in case.
			$managers_id = $row_isManager['managers_id'];
			$result_updateActive = mysql_query("UPDATE managers SET isActive = '1' WHERE managers_id = '{$managers_id}'");
			if (!$result_updateActive) {
				unset ($_POST);
				$_SESSION['Msg'] = "Could not update isActive in managers table to 1. Contact the developer.";
				header("Location: redirect_admin.php?Url=admin.php");
				exit;
			}
		} else { // They're not listed as a manager, what we expected. So establish them as one.
			if ( !mysql_query("INSERT INTO managers (users_id,name,isActive) 
			VALUES ( '{$user}', '{$name}', '1' )") ) {
				unset ($_POST);
				$_SESSION['Msg'] = "Could not insert user into managers table as a new manager. Contact the developer.";
				header("Location: redirect_admin.php?Url=admin.php");
				exit;
			}
		} // $num_rows_manager == '1'
		
		// Lastly, set their new position in their profile.
		$result_profile = mysql_query("UPDATE profile SET positions_id = '{$newPosition}' WHERE users_id = '{$user}'");
		if (!$result_profile) {
			unset ($_POST);
			$_SESSION['Msg'] = "Could not update profile with new position for user. Contact the developer.";
			header("Location: redirect_admin.php?Url=admin.php");
			exit;
		}
		if ( !(mysql_affected_rows()) ) { // call with no arguments to use the last open link.
			unset ($_POST);
			$_SESSION['Msg'] = "No profile to update? Perhaps this user has not yet established a profile?";
			header("Location: redirect_admin.php?Url=admin.php");
			exit;
		}
		
	} // $oldPosition == '1' && $newPosition != '1'
	
	// Any other change is just a promotion or demotion from one level of manager to another.
	if ( $oldPosition != '1' && $newPosition != '1' ) {
		// Lastly, set their new position in their profile.
		$result_profile = mysql_query("UPDATE profile SET positions_id = '{$newPosition}' WHERE users_id = '{$user}'");
		if (!$result_profile) {
			unset ($_POST);
			$_SESSION['Msg'] = "Could not update profile with new position for user. Contact the developer.";
			header("Location: redirect_admin.php?Url=admin.php");
			exit;
		}
		if ( !(mysql_affected_rows()) ) { // call with no arguments to use the last open link.
			unset ($_POST);
			$_SESSION['Msg'] = "No profile to update? Perhaps this user has not yet established a profile?";
			header("Location: redirect_admin.php?Url=admin.php");
			exit;
		}
	} // $oldPosition != '1' && $newPosition != '1'
	
	unset ($_POST);
	$_SESSION['Msg'] = "Position updated.";
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

<div style="text-align:left;">
<fieldset style="width:50%;">
<legend><strong>Modify a user's profile</strong></legend>
<strong><span class="err">NOTE: If you set a user's position to be "SE", or make a user "inactive", and that user is currently listed as a manager, all of the users that report up to that user will be set to report to "admin".</span></strong>
<form name="profileMod" action="" method="post">
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
    <label for="position"><strong>User's Position:</strong></label>
    <select name="position">
    	<option value="">Select a position...</option>
        <?php
		// Get a list of available positions.
		$result_positions = mysql_query("SELECT * FROM positions");
		if (!$result_positions) {
			$_SESSION['Msg'] = "Could not get list of positions for admin profile change. Contact the developer.";
			header("Location: redirect_admin.php?Url=admin.php");
			exit;
		}
		while ( $row_positions = mysql_fetch_array($result_positions, MYSQL_ASSOC) ) {
			echo '<option value="'.$row_positions['positions_id'].'">'.$row_positions['position'].'</option>';
		}
		mysql_free_result($result_positions);
		?>
        <option value="inactive">Inactive</option>
    </select>
  
    <p><input type="submit" name="submit" value="Change" /></p>
</form>
</fieldset>
</div>

</div>
</body>
</html>
<?php
} //  !isset($_POST['submit']) && $_POST['submit'] != 'Change'

?>
