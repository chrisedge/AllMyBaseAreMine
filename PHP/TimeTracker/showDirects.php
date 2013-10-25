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
    // echo 'SESSION users_id: ' . $_SESSION['users_id']; 
	$_SESSION = array();
    session_destroy();

    header("Location: index.php");
    exit;
}

// If this user is not a manager, but trying to access this page for some reason, redirect them.
if ( isset($_SESSION['is_manager']) == '0' ) {
	$_SESSION['Msg'] = "You can not access this page. Contact the administrator if you believe this is an error.";
  	header("Location: redirect.php?Url=index.php?logoff");
  	exit;
}

// print_r($_SESSION);
// $_SESSION=from_array($_SESSION); // Massage the input with from_array(), again.
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.
// print_r($_SESSION);
	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo $_SESSION['TITLE']; ?></title>
	<meta name="keywords" content="" />
	<meta name="description" content="" />
    <script type="text/javascript" language="javascript" src="js/jquery-1.7.1.min.js"></script>
    <script type="text/javascript" language="javascript" src="js/setime.js"></script>
  	<link type="text/css" href="css/calendar.css" rel="stylesheet" media="screen, projection" />
  	<link type="text/css" href="css/setime.css" rel="stylesheet" media="screen, projection" />
	<link rel="stylesheet" href="css/style.css" type="text/css" media="screen, projection" />
</head>
<body>

<style>
html { overflow: -moz-scrollbars-vertical;} /* For firefox scrollbar issue */
</style>



<script type="text/javascript">
/*
 * Provide a "Select" button for each activity in the pop up window.
 * onClick it should populate the field in the table of window.opener
 * and then close itself.
 */

$(function() {
  
	$(".selectButton").click(function() {
		var value = this.name; // The global_id $value from the first <td>. We set $gid to be this also below so we can use it for the other <td> values.
		// var desc = document.selectCode.descValue.value;
		var descID = "desc" + value;
		var desc = document.getElementById(descID).innerHTML;
		// var desc = document.getElementById(descID).text;
		// var comments = document.selectCode.comments.value;
		var numActivities = document.selectCode.numActivities.value;
		// var label = document.selectCode.label.value;
		var entryType = "td#entryType" + numActivities;
		var entryDesc = "td#entryDesc" + numActivities;
		// $("div#dragTitle3 td:first").text("New title")
		window.opener.$(entryType).text("G"); // G because we know these are Global codes.
		window.opener.$(entryDesc).text(desc);
		var activityID = "activityID" + numActivities;
		window.opener.document.getElementById(activityID).value = this.name;
		// alert("This.name is " + value);
		// alert("numActivities is " + numActivities);
		// alert ("descID is " + descID + " desc is " + desc);
		// window.opener.$("#serverMsg").html
		window.close();
		return false;
	});
});

</script>

<br />


<div id="directReports" style="text-align:left;">
<?php
$directs = getDirectsNew($_SESSION['users_id'],'first'); 
if ( $directs == '0' ) {
?>
	<ul><li>No direct reports</li></ul>
<?php
	exit;
}
?>
</div>


</body>
</html>