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

// managers_id comes to us in a session variable from the login page.
$row_managersName = mysql_fetch_assoc(mysql_query( "SELECT name FROM managers WHERE managers_id = {$managers_id}" ));
if ($row_managersName['name']) {
  $_SESSION['managersName'] = $row_managersName['name'];
} else {
  $_SESSION['Msg'] = "Could not get name from managers table. Contact the administrator.";
  header("Location: redirect.php?Url=/index.php?logoff");
  exit;
}


?>

<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php echo $_SESSION['TITLE']; ?></title>
  <script type="text/javascript" language="JavaScript" src="js/calendar.js"></script>
  <script type="text/javascript" language="javascript" src="js/setime.js"></script>
  <link type="text/css" href="css/calendar.css" rel="stylesheet" />
  <link type="text/css" href="css/setime.css" rel="stylesheet" />

</head>

<body>
<div style="float:left;font-family:sans-serif;width=25%">
<img src="logo.gif"> <br><br><br>
Login: <?php echo $userName; ?>
<br />
<?php echo $userName; ?> is reporting to:

<?php printMenu(); // functions.php ?>

</div> <!-- end left div -->

<div id="main" style="float:right;text-align:center; background-color:white; border-left:solid #808080;width:75%">
<h3><?php echo $_SESSION['TITLE']; ?> Reports for: <span class="weak"><?php echo ' '.$firstName.' '.$lastName;?></span></h3>

<?php

if ( !isset($_POST['submit']) ) {
	// Get the user's local time zone and set it so all date/time functions in this script will be relative to this.
	// We also get their weekStart to set in the calendar function below.
	$result_localTZ = mysql_query( "SELECT localTimezone,weekStart FROM profile WHERE users_id = '{$users_id}'" );
	if (!$result_localTZ) {
		$_SESSION['Msg'] = "Could not get localTimeZone from profile table. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
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
	
	// Be default let's set startDate to be 30 days prior to today, and endDate to be today.
	$endDate = date("Y-m-d"); // today
	$endDateNum = strtotime($endDate); // First get $endDate into a UNIX timestamp.
	$lastMonth = date("Y-m-d",(strtotime('-30 days', $endDateNum))); // Then get -30 days, and convert back to Y-m-d.

?>

<form name="reportTime" action="/makeImage.php" method="post" onSubmit="return validateDate('reportTime')" target="_blank">
  <label for="startDate"><strong>Select start date for the report time period</strong></label>
  <!-- calendar attaches to existing form element -->
  <input type="text" name="startDate" size="10" maxlength="10" value="<?php echo $lastMonth; ?>" />
      <script language="JavaScript">
        var o_cal = new tcal ({
          'formname': 'reportTime',
          'controlname': 'startDate',
          'selected': '<?php echo $lastMonth; ?>'
        });

        // set first day of the week 
        o_cal.a_tpl.weekstart = <?php echo $weekStart; ?>;
      </script>
	<br />
    <label for="endDate"><strong>Select end date for the report time period</strong></label>
    <input type="text" name="endDate" size="10" maxlength="10" value="<?php echo $endDate; ?>" />
      <script language="JavaScript">
        var p_cal = new tcal ({
          'formname': 'reportTime',
          'controlname': 'endDate',
          'selected': '<?php echo $endDate; ?>'
        });

        // set first day of the week 
        p_cal.a_tpl.weekstart = <?php echo $weekStart; ?>;
      </script>
	<br />
    <label for="category"><strong>Select a category for your report</strong></label>
    <select name="category">
    <option value="all" selected="selected">All Categories</option>
    <?php
	
	$result_categories = mysql_query("SELECT * FROM categories");
	if (!$result_categories) {
		$_SESSION['Msg'] = "Could not get categories from categories table. Contact the administrator.";
  		header("Location: redirect.php?Url=/index.php?logoff");
  		exit;
	}
	while ( $row_categories = mysql_fetch_array($result_categories, MYSQL_ASSOC) ) {
	?>
    	<option value="<?php echo $row_categories['categories_id'];?>"><?php echo $row_categories['category'];?></option>
    <?php
	} // $row_categories = mysql_fetch_array($result_categories, MYSQL_ASSOC)
	mysql_free_result($result_categories);
	
	?>
    	<!--<option value="local">Local Codes</option> -->
    </select>
    <br />
    <label for="chartType"><strong>Select the desired chart type:</strong></label>
    <input type="radio" name="chartType" value="pie" checked="checked" /> <strong>Pie</strong>&nbsp;&nbsp;
    <input type="radio" name="chartType" value="bar" /> <strong>Bar</strong>
    <br />
    <br />
    <input type="submit" name="submit" value="Get Report" /> or <input type="submit" name="submit" value="Download Image" /> or <input type="submit" name="submit" value="Download CSV" />
</form>


<?php
} // !isset($_POST['submit'])
?>


</div>
</body>
</html>