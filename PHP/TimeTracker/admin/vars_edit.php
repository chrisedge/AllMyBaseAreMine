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
	// They're changing a variable value.
	$_POST=from_array($_POST);
	$newvalue = $_POST['newvalue'];
	$id = $_POST['id'];
	if ( $newvalue == '' || $id == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No new value found. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/vars_edit.php");
		exit;
	}
	
	$result_change = mysql_query("UPDATE global_vars SET value = '{$newvalue}', 
	updatedBy = '{$admin_id}' WHERE global_vars_id = '{$id}'");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not modify variable value. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "Value updated.";
		header("Location: redirect_admin.php?Url=/admin/vars_edit.php");
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



<?php if ( !isset($_POST['submit']) && $_POST['submit'] != 'Modify' ) { ?>

<div style="text-align:left;"> <!-- fieldset div -->
<fieldset style="width:50%;">
<legend><strong>Modify application variables</strong></legend>
<form name="varMod" action="" method="post">
    <br />
    <label for="var"><strong>Variable:</strong></label>
    <select name="var">
    	<option value="">Select a variable...</option>
        <?php
		// Get a list of available variables.
		$result_var = mysql_query("SELECT global_vars_id,name,value FROM global_vars");
		if (!$result_var) {
			$_SESSION['Msg'] = "Could not get list of global variables for admin change. Contact the developer.";
			header("Location: redirect_admin.php?Url=/admin/admin.php");
			exit;
		}
		while ( $row_var = mysql_fetch_array($result_var, MYSQL_ASSOC) ) {
			echo '<option value="'.$row_var['global_vars_id'].'">'.$row_var['name'].' ('.$row_var['value'].')</option>';
		}
		mysql_free_result($result_var);
		?>
    </select>
  
    <p><input type="submit" name="submit" value="Modify" /></p>
</form>
</fieldset>
</div> <!-- end fieldset div -->

</div>
</body>
</html>

<?php
} // !isset($_POST['submit']) && $_POST['submit'] != 'Modify'

if ( isset($_POST['submit']) && $_POST['submit'] == 'Modify' ) {
	$_POST=from_array($_POST);
	
	if ( $_POST['var'] == '' ) {
		$_SESSION['Msg'] = "Invalid variable selection, or no variable selected. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/vars_edit.php");
		exit;
	}
	
	$var = $_POST['var'];
?>
<div style="text-align:left;"> <!-- fieldset div -->
<form name="varMod" action="" method="post">
    <br />
    <table width="60%" border="0" cellpadding="2" cellspacing="5" bgcolor="#999999">
     		<tr>
            <th align="center" bgcolor="#333333"><font color="#CCCCCC">Current Variable Name</font></th>
            <th align="center" bgcolor="#333333"><font color="#CCCCCC">Comments</font></th>
            <th align="center" bgcolor="#333333"><font color="#CCCCCC">Current Value</font></th>
            <th align="center" bgcolor="#333333"><font color="#CCCCCC">New Value</font></th>
            </tr>
	<?php
	
	$result_var = mysql_query("SELECT * FROM global_vars WHERE global_vars_id = '{$var}'");
	if (!$result_var) {
		$_SESSION['Msg'] = "Could not get variable information for modify. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	}
	$row_var = mysql_fetch_array($result_var, MYSQL_ASSOC);
	mysql_free_result($result_var);
	
	?>
            <tr>
            <td align="center" bgcolor="#CCCCCC"><?php echo $row_var['name']; ?></td>
            <td align="center" bgcolor="#CCCCCC"><?php echo $row_var['comment']; ?></td>
            <td align="center" bgcolor="#CCCCCC"><?php echo $row_var['value']; ?></td>
            <td align="center" bgcolor="#CCCCCC">
            <input type="text" maxlength="255" name="newvalue"  />
            </td>
            </tr>
   </table>
   <input type="hidden" name="id" value="<?php echo $row_var['global_vars_id']; ?>"  />
   <br /><input type="submit" name="submit" value="Change" />
</form>
</div> <!-- end fieldset div -->

</div>
</body>
</html>

<?php
} // isset($_POST['submit']) && $_POST['submit'] == 'Modify'
?>
