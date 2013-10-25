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

if ( isset($_POST['submit']) && $_POST['submit'] == 'Change' ) {
	// They're changing a position.
	$_POST=from_array($_POST);
	$newposition = $_POST['newposition'];
	$id = $_POST['id'];
	if ( $newposition == '' || $id == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No new position name found. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/position_edit.php");
		exit;
	}
	
	$result_change = mysql_query("UPDATE positions SET position = '{$newposition}' WHERE positions_id = '{$id}'");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not modify position. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "Position updated.";
		header("Location: redirect_admin.php?Url=/admin/position_edit.php");
		exit;
	}
}

if ( isset($_POST['submit']) && $_POST['submit'] == 'Add' ) {
	// They're adding a position.
	$_POST=from_array($_POST);
	$newposition = $_POST['newposition'];
	if ( $newposition == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No new position name found. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/position_edit.php");
		exit;
	}
	
	$result_change = mysql_query("INSERT INTO positions (position) VALUES ('{$newposition}')");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not insert new position. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "Position added.";
		header("Location: redirect_admin.php?Url=/admin/position_edit.php");
		exit;
	}
}

if ( isset($_POST['submit']) && $_POST['submit'] == 'Delete' ) {
	// They're deleting a position. They've been warned. This will cascade through DB.profile.positions_id and set any
	// that contained this value to NULL.
	$_POST=from_array($_POST);
	$id = $_POST['position'];
	$delete_id = $_POST['delete_id'];
	if ( $id == '' || $delete_id != 'true' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No position name id found. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/position_edit.php");
		exit;
	}
	
	$result_change = mysql_query("DELETE FROM positions WHERE positions_id = '{$id}'");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not delete position. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "Position deleted.";
		header("Location: redirect_admin.php?Url=/admin/position_edit.php");
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



<?php if ( !isset($_POST['submit']) && $_POST['submit'] != 'Modify' && $_POST['submit'] != 'Add' ) { ?>

<div style="text-align:left;"> <!-- fieldset div -->
<fieldset style="width:50%;">
<legend><strong>Modify an existing position</strong></legend>
<strong><span class="err">NOTE: Due to the requirements of the application, you can not delete the lowest level
position. As all positions higher than the lowest level are automatically management level, any new positions you
add will also become management level positions.</span></strong>
<br />
<form name="positionMod" action="" method="post">
    <br />
    <label for="position"><strong>Position:</strong></label>
    <select name="position">
    	<option value="">Select a position...</option>
        <?php
		// Get a list of available positions.
		$result_positions = mysql_query("SELECT * FROM positions");
		if (!$result_positions) {
			$_SESSION['Msg'] = "Could not get list of positions for admin profile change. Contact the developer.";
			header("Location: redirect_admin.php?Url=/admin/admin.php");
			exit;
		}
		while ( $row_positions = mysql_fetch_array($result_positions, MYSQL_ASSOC) ) {
			echo '<option value="'.$row_positions['positions_id'].'">'.$row_positions['position'].'</option>';
		}
		mysql_free_result($result_positions);
		?>
    </select>
  
    <p>
    <input type="submit" name="submit" value="Modify" />
    <button type="submit" name="submit" value="Delete" onclick="return validateDeletePos('positionMod')">Delete</button>
    <input type="hidden" name="delete_id" id="delete_id"  />
    </p>
</form>
</fieldset>
<br />

<fieldset style="width:50%;">
<legend><strong>Add a new position</strong></legend>
<strong><span class="err">REMINDER: Any new positions you add will become management level positions.</span></strong>
<br /><br />
<form name="positionAdd" action="" method="post">
<label for="newposition"><strong>New Position Name:</strong></label>
<input type="text" maxlength="255" name="newposition"  />
<p><input type="submit" name="submit" value="Add" /></p>
</form>
</fieldset>
</div> <!-- end fieldset div -->

</div>
</body>
</html>

<?php
} // !isset($_POST['submit']) && $_POST['submit'] != 'Modiy' && $_POST['submit'] != 'Add'

if ( isset($_POST['submit']) && $_POST['submit'] == 'Modify' ) {
	$_POST=from_array($_POST);
	/*
	print_r($_POST);
	echo '<br />';
	foreach ($_POST as $key => $value) {
		echo 'key: '.$key.' = value: '.$value.'<br />';
	}
	*/
	
	if ( $_POST['position'] == '' ) {
		$_SESSION['Msg'] = "Invalid position selection, or no position selected. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/position_edit.php");
		exit;
	}
	
	$position = $_POST['position'];
?>
<div style="text-align:left;"> <!-- fieldset div -->
<form name="positionMod" action="" method="post">
    <br />
    <table width="60%" border="0" cellpadding="2" cellspacing="5" bgcolor="#999999">
     		<tr>
            <th align="center" bgcolor="#333333"><font color="#CCCCCC">Current Position Name</font></th>
            <th align="center" bgcolor="#333333"><font color="#CCCCCC">New Position Name</font></th>
            </tr>
	<?php
	
	$result_position = mysql_query("SELECT * FROM positions WHERE positions_id = '{$position}'");
	if (!$result_position) {
		$_SESSION['Msg'] = "Could not get position id for modify. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	}
	$row_position = mysql_fetch_array($result_position, MYSQL_ASSOC);
	mysql_free_result($result_position);
	
	?>
            <tr>
            <td align="center" bgcolor="#CCCCCC"><?php echo $row_position['position']; ?></td>
            <td align="center" bgcolor="#CCCCCC">
            <input type="text" maxlength="255" name="newposition"  />
            </td>
            </tr>
   </table>
   <input type="hidden" name="id" value="<?php echo $row_position['positions_id']; ?>"  />
   <br /><input type="submit" name="submit" value="Change" />
</form>
</div> <!-- end fieldset div -->

</div>
</body>
</html>

<?php
} // isset($_POST['submit']) && $_POST['submit'] == 'Modify'
?>
