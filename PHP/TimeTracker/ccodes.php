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

if ( !( isset($_GET['curNumActivities']) ) ) {
	$_SESSION = array();
    session_destroy();

    echo 'Error. Please close this window and contact the administrator';
    exit;
}

// print_r($_SESSION);
// $_SESSION=from_array($_SESSION); // Massage the input with from_array(), again.
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.
// print_r($_SESSION);

$numActivities = $_GET['curNumActivities'];
	
// This is getting Channel codes. That '4' below is hard coded based on the Channel categories_id being '4' in the
// database.
$globalCodes = getGlobalCodesByCategory('4'); // Located in functions.php
// Get their local codes as well.
$managers_id = $_SESSION['managers_id'];
// UNCOMMENT FOR LOCAL CODES LATER.
// $localCodes = getLocalCodes($managers_id);
	
// DEBUG 
//print_r($globalCodes);
/*
echo '<br />';
echo "size of array = ".sizeof($globalCodes)."<br>";
echo "<ol>";
for ($row = 0; $row <= (sizeof($globalCodes)-1); $row++)
{
	echo "<li><b>The row number $row</b>";
	echo "<ul>";

	foreach($globalCodes[$row] as $key => $value)
	{
		echo "<li>".$key." ".$value."</li>";
	}

	echo "</ul>";
	echo "</li>";
}
echo "</ol>";
$j = .5;
echo '$j: '.$j;
$j = $j + .5;
echo '<br />$j now: '.$j;
*/

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo $_SESSION['TITLE']; ?></title>
	<meta name="keywords" content="" />
	<meta name="description" content="" />
  	<!--<script type="text/javascript" language="javascript" src="js/jquery-1.6.2.min.js"></script> -->
    <script type="text/javascript" language="javascript" src="js/jquery-1.7.1.min.js"></script>
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

<table width="100%" border="0" cellpadding="4" cellspacing="1" bgcolor="#FFFFFF">
	<th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center;"></th>
    <th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center;">
    <font color="#FFFFFF" style="font-weight:bold;">Description</font></th>
    <th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center;">
    <font color="#FFFFFF" style="font-weight:bold;">Comments</font></th>
	<form name="selectCode" id="selectCode" action="">
<?php
// $numActivities is stored as a session variable. We use it to keep track of where in the parent the values selected will go.
// $numActivities = $_SESSION['numActivities'];

for ($row = 0; $row <= (sizeof($globalCodes)-1); $row++) {
	if ( isEven($row) ) {
		$bgcolor= "#CCFFFF";
	} else {
		$bgcolor = "#CCCCFF";
	}
	
	echo '<tr bgcolor='.$bgcolor.'>';
	foreach($globalCodes[$row] as $key => $value) {
		if ( $key == 'global_id' ) {
			echo '<td><input type="button" name="'.$value.'" value="Select" class="selectButton" /></td>';
			$gid = $value;
		}
		if ( $key == 'description' ) {
			echo '<td id="desc'.$gid.'">'.$value.'</td>';
			// echo '<input type="hidden" name="desc'.$gid.'" id="desc'.$gid.'" value="'.$value.'" />';
		}
		if ( $key == 'comments' ) {
			echo '<td>'.$value.'</td>';
		}
	}
	echo '</tr>';
}
?>
		<input type="hidden" name="numActivities" id="numActivities" value="<?php echo $numActivities; ?>" /> 
    </form>
</table>
</body>
</html>