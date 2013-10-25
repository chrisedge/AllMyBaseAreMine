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

$_SESSION=from_array($_SESSION); // Massage the input with from_array(), again.
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.


if ( !isset($_POST['submit']) ) {

/* First we check to see when they last put in their time. This information is provided for informational
 * purposes only; the user isn't required to start entering time after this date. For whatever week (or day for
 * that matter) that they specify they want to enter time, we will check to see if any time has been added for
 * that date, display it (as non-editable), and even allow them to add more time for that date.
 * For time they want to modify however, we will provide text that explains 'modification of existing data
 * requires manager approval' within the form, as well as a link to the modify_time.php page.
 */
 
 // Get date last added.
$result_lastDate = mysql_query( "SELECT date FROM time WHERE users_id = '{$users_id}' ORDER BY date DESC LIMIT 0,1" );
if (!$result_lastDate) {
	$_SESSION['Msg'] = "Could not get last_date from time table. Contact the administrator.";
	header("Location: redirect.php?Url=index.php");
	exit;
}
$num_rows = mysql_num_rows($result_lastDate);
// If this user has not entered any time at all yet, $num_rows could be 0. If that's the case, $lastDate = 'Never'.
if ( $num_rows == 0 ) {
	$lastDate = "Never"; 
} else {
	$row_lastDate = mysql_fetch_array($result_lastDate, MYSQL_ASSOC);
	$lastDate = $row_lastDate['date'];
} // $num_rows == 0
mysql_free_result($result_lastDate);

// Get the user's local time zone and set it so all date/time functions in this script will be relative to this.
// We also get their weekStart to set in the calendar function below.
$result_localTZ = mysql_query( "SELECT localTimezone,weekStart FROM profile WHERE users_id = '{$users_id}'" );
if (!$result_localTZ) {
	$_SESSION['Msg'] = "Could not get localTimeZone from profile table. Contact the administrator.";
	header("Location: redirect.php?Url=index.php");
	exit;
}
$row_localTZ = mysql_fetch_array($result_localTZ, MYSQL_ASSOC);
$localTimezone = $row_localTZ['localTimezone'];
// The calendar function allows the setting of the first day of the week to be Sunday (0) or Monday (1).
// $weekStart = ( $row_localTZ['weekStart'] == 'Sunday' ? '0' : '1' );
// Hard code to start on Sunday instead per the request of J. Post
$weekStart = '0';
mysql_free_result($result_localTZ);
date_default_timezone_set($localTimezone);
// echo '<!-- '.date('Y-m-d H:i:sP') . " -->";

?>

<h4 align="center">Your local time:<span class="weak"><?php echo ' '.date('Y-m-d H:i (\G\M\TP)'); ?></span></h4>
<p>&nbsp;</p>
<h4>The last date you entered time was:<span class="weak"><?php echo ' '.$lastDate; ?></span></h4>

<?php

// Convert $lastDate to today if it's "Never", for use in the form.
if ( $lastDate == "Never" ) { $lastDate = date("Y-m-d"); }// today
// Get the next week as well.
$lastDateNum = strtotime($lastDate); // First get $lastDate into a UNIX timestamp.
$nextWeek = date("Y-m-d",(strtotime('+6 days', $lastDateNum))); // Then get next week, and convert back to Y-m-d.
// echo '<!-- '.$nextWeek.' -->';
?>

<script type="text/javascript">
$(function() {
  

  $(".button").click(function() {
	var startDate = $("input#startDate").val();
	var dataString = 'startDate=' + startDate;
	
	$.ajax({
      type: "POST",
      url: "edit_time2.php",
      data: dataString,
      success: function(data) { // data is what gets returned from url, and then passed below.
        $('#calForm').html("<div id='message'></div>");
		$('#message').html(data).hide().fadeIn(1500); // data is what comes back from url.
      }
     });
    return false;
	});
});
</script>

<script type="text/javascript">
	$(function() {
		$( "#startDate" ).datepicker({
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			maxDate: "+0D"
		});
	});
</script>

<div align="center" class="loading"><img src="images/orange_loading.gif" width="62" height="62" alt="Loading...." /></div>

<div id="calForm">

<p>&nbsp;</p>
<!--<form name="addTime" action="" method="post" onSubmit="return validateDate('addTime')"> -->
<form name="editTime" action="">
  <label for="startDate">Select a date to edit time for:</label>
  <!-- calendar attaches to existing form element -->
  <input type="text" name="startDate" id="startDate" size="10" maxlength="10" /><br>
  <font size="-2" face="Arial, Helvetica, sans-serif"><strong>(The date can not be in the future)</strong></font>
	<br /><br>
    <input type="submit" name="submit" class="button" value="Edit Time" />
</form>

</div>

<?php
} // !isset($_POST['submit']) == "Enter Time"
?>

