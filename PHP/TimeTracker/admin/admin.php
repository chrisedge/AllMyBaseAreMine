<?php
define('INCLUDE_CHECK', true);
error_reporting (E_ALL ^ E_NOTICE);

require '../connect.php';
require '../functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seAdmin');
session_set_cookie_params(2*7*24*60*60);
session_start();

if ( isset($_GET['logoff']) || !( isset($_SESSION['users_id']) ) || !( isset($_SESSION['admin_id']) ) ){
	// Their credentials are not valid, or they logged off.
	$_SESSION = array();
    session_destroy();
    header("Location: index.php?logoff");
    exit;
}

$_SESSION=from_array($_SESSION); // Massage the input with from_array(), again.
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.

?>

<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php echo $_SESSION['TITLE']; ?> Administration</title>
  <script type="text/javascript" language="javaScript" src="../js/calendar.js"></script>
  <script type="text/javascript" language="javascript" src="../js/setime.js"></script>
  <link type="text/css" href="../css/calendar.css" rel="stylesheet" />
  <link type="text/css" href="../css/setime.css" rel="stylesheet" />

</head>

<body>
<div style="float:left;font-family:sans-serif;width=25%">
<img src="/logo.gif"> <br><br><br>
Login: <?php echo $userName; ?>
<br /><br />

<?php printAdminMenu(); // functions.php ?>

</div> <!-- end left div -->

<div id="main" style="float:right;text-align:center; background-color:white; border-left:solid #808080;width:75%">
<h3><?php echo $_SESSION['TITLE']; ?> Administrator</h3>
<font size="-1">Last login from: <?php echo $_SESSION['lastIP'] ?></font></h4>

<h4 align="left">Admin Options:</h4>
<fieldset style="width:50%; border:hidden;">
<legend><strong>User Functions</strong></legend>
<ul style="text-align:left;">
<li><a href="password_reset.php">Reset a user's password</a></li>
<li><a href="profile_edit.php">Modify a user's profile</a></li>
<li><a href="exclude_email.php">Email exclusion list</a></li>
</ul>
</fieldset>

<fieldset style="width:50%; border:hidden;">
<legend><strong>Application Functions</strong></legend>
<ul style="text-align:left;">
<li><a href="vars_edit.php">Modify application variables</a></li>
<li><a href="admin_edit.php">Add/Delete administrator access</a></li>
<li><a href="position_edit.php">Add/Modify positions</a></li>
<li><a href="global_codes.php">Add/Modify global activity codes</a></li>
<li><a href="categories.php">Add/Modify categories</a></li>
</ul>
</fieldset>

<fieldset style="width:50%; border:hidden;">
<legend><strong>Database Functions</strong></legend>
<ul style="text-align:left;">
<li><a href="backup/" target="_new">Backup/Restore database</a></li>
</ul>
</fieldset>


</div>
</body>
</html>