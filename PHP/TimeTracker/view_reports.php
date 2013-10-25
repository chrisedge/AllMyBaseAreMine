<?php
define('INCLUDE_CHECK', true);
error_reporting (E_ALL ^ E_NOTICE);

require 'connect.php';
require 'functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seLogin');
session_set_cookie_params(2*7*24*60*60);
session_start();

if ( isset($_GET['logoff']) ) {
	$_SESSION = array();
    session_destroy();

    header("Location: index.php");
    exit;
}

if ( !(isset($_SESSION['users_id'])) || !(isset($_GET['id'])) || isset($_SESSION['is_manager']) == '0' ){ 
	// They shouldn't be here.
	$_SESSION['Msg'] = "You can not access this page. Contact the administrator if you believe this is an error.";
  	header("Location: redirect.php?Url=/index.php?logoff");
  	exit;
}

$_SESSION=from_array($_SESSION); // Massage the input with from_array(), again.
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.


$_GET=from_array($_GET);
$id = $_GET['id']; // The requested user's users_id.
// First make sure they've requested a report for a user that falls under them somewhere in the management hierarchy.
$allowed = checkHierarchy($id,$is_manager);

if ( $allowed != '1' ) { // Someone's trying to get a report for a user that does not fall under them somewhere in the chain.
	$_SESSION['Msg'] = "You can not view reports for this user. Contact the administrator if you believe this is an error.";
  	header("Location: redirect.php?Url=/index.php?logoff");
  	exit;
}

// We made it here, so gather the information on the user they want a report on.
$result_user = mysql_query("SELECT userName,firstName,lastName FROM users WHERE users_id = '{$id}'");
if (!$result_user) {
	mysql_free_result($result_user);
	$_SESSION['Msg'] = "Could not get user information from users table for report. Contact the administrator.";
  	header("Location: redirect.php?Url=/index.php?logoff");
  	exit;
}
$row_user = mysql_fetch_array($result_user, MYSQL_ASSOC);
mysql_free_result($result_user);

?>

<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php echo $_SESSION['TITLE']; ?></title>
  <script type="text/javascript" language="JavaScript" src="/js/calendar.js"></script>
  <script type="text/javascript" language="javascript" src="/js/setime.js"></script>
  <link type="text/css" href="/css/calendar.css" rel="stylesheet" />
  <link type="text/css" href="/css/setime.css" rel="stylesheet" />

</head>

<body>
<div style="float:left;font-family:sans-serif;width=25%">
<img src="logo.gif"> <br><br><br>
Login: <?php echo $userName; ?>
<br />
<?php echo $userName; ?> is reporting to:

<?php printMenu(); // functions.php ?>

<br />
<a href="/manage_directsNew.php">Manage Direct Reports</a>

</div> <!-- end left div -->

<div id="main" style="float:right;text-align:center; background-color:white; border-left:solid #808080;width:75%">
<h3><?php echo $_SESSION['TITLE']; ?> Management Reports for: <span class="weak"><?php echo ' '.$firstName.' '.$lastName;?></span></h3>
<h4>Generate a report for user: <strong><?php echo $row_user['userName']; ?></strong></h4>
<form name="reportTime" action="/makeImage.php" method="post" onSubmit="return validateDate('reportTime')" target="_blank">
<?php
	// $weekStart = ( $row_localTZ['weekStart'] == 'Sunday' ? '0' : '1' );
	// Hard code to start on Sunday instead per the request of J. Post
	$weekStart = '0';
	
	// Be default let's set startDate to be 30 days prior to today, and endDate to be today.
	$endDate = date("Y-m-d"); // today
	$endDateNum = strtotime($endDate); // First get $endDate into a UNIX timestamp.
	$lastMonth = date("Y-m-d",(strtotime('-30 days', $endDateNum))); // Then get -30 days, and convert back to Y-m-d.

?>

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
    	<option value="local">Local Codes</option>
    </select>
    <br />
    <label for="chartType"><strong>Select the desired chart type:</strong></label>
    <input type="radio" name="chartType" value="pie" checked="checked" /> <strong>Pie</strong>&nbsp;&nbsp;
    <input type="radio" name="chartType" value="bar" /> <strong>Bar</strong>
    <br />
    <br />
    <input type="hidden" name="id" value="<?php echo $id; ?>"  />
    <input type="submit" name="submit" value="Get Report" /> or 
    <input type="submit" name="submit" value="Download Image" /> or
    <input type="submit" name="submit" value="Download CSV" />
</form>

</div>
</body>
</html>