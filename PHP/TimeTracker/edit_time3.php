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

// $_SESSION=from_array($_SESSION); // Massage the input with from_array(), again.
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.

$_POST=from_array($_POST); // Massage the input with from_array(), again.
foreach($_POST as $k1=>$v1) $$k1=$v1; // Get all the $_POST key values and assign them associated variable names.

//echo '<br />';
//print_r($_SESSION);
//echo '<br />';
//print_r($_POST);
//echo '<br />';
// exit;
?>

<script type="text/javascript">
function popitup(url) {
		// var curNumActivities = (parseInt(document.getElementById('numToValidate').innerHTML));
		var curNumActivities = '1';
		var newUrl = url + '?curNumActivities=' + curNumActivities;
		newwindow=window.open(newUrl,"","scrollbars=1,location=0,toolbar=0,status=0,menubar=0,height=400,width=650");
		if (window.focus) {newwindow.focus()}
		return false;
}

function validateDeleteTimeNG(formName) {
	var fieldID = 'delete'
	
	if ( document.forms[formName][fieldID].checked ) {
		if ( confirm("Are you sure you want to delete this time entry?") ) {
			document.forms[formName][fieldID].value='true'
			document.forms[formName].submit();
			return true;
		} else { return false; }
	}
}
</script>

<div id="entryTable">
<br />

<?php
	
$startDate = $_SESSION['edit_date'];
$_SESSION['entryDate'] = $startDate; // Establish the current date they're entering time for. Will be used in edit_time4.
$_SESSION['time_id'] = $time_id; // Will be used in edit_time4.php

// Generate $hoursLeft for this date.
$result_tmpHours = mysql_query( "SELECT hours FROM time WHERE users_id = '{$users_id}' AND date = '{$startDate}'" );
if (!$result_tmpHours) {
	$_SESSION['Msg'] = "Could not get hours from time table. Contact the administrator.";
	header("Location: redirect.php?Url=index.php");
	exit;
}
$num_rows = mysql_num_rows($result_tmpHours);
// DEBUG echo 'num_rows = '.$num_rows.'<br />';
$tmpHours = 0.0;
if ( $num_rows > 0 ) {		
	while ( $row_tmpHours = mysql_fetch_array($result_tmpHours, MYSQL_ASSOC) ) {
		// DEBUG echo 'row_tmpHours = '.$row_tmpHours['hours'].'<br />';
		$tmpHours += $row_tmpHours['hours'];
		// DEBUG echo 'tmpHours = '.$tmpHours.'<br />';
	}
	// echo '<input type="hidden" name="'.$tmpDate.'_total" value="'.$tmpHours.'" />';
}
mysql_free_result($result_tmpHours);
$hoursLeft = ($_SESSION['MAX_HOURS_PER_DAY'] - $tmpHours);
$_SESSION['hoursLeft'] = $hoursLeft;

// We receive time_id via POST from edit_time2.php. Use this time ID to populate the table for editing/deleting.
$result_hours = mysql_query( "SELECT * FROM time WHERE time_id = '{$time_id}'" );
if (!$result_hours) {
	$_SESSION['Msg'] = "Could not get hours from time table to edit. Contact the administrator.";
	header("Location: redirect.php?Url=index.php"); // HEADER DOESN'T WORK IN THIS CONTEXT - TODO
	exit;
}
$num_rows = mysql_num_rows($result_hours);
// echo 'num_rows = '.$num_rows.'<br />';  // DEBUG
// exit; // DEBUG
if ( $num_rows <= 0 ) {
	$_SESSION['Msg'] = 'No hours to edit for '.$startDate.'.'; // TODO - MAKE THIS SHOW UP.
} else { // ( $num_rows > 0 )
  $row_hours = mysql_fetch_array($result_hours, MYSQL_ASSOC);
  mysql_free_result($result_hours);
}

?>

<br />

<div id="instructions">
<li><h3 align="left"><strong>1. Select codes from the left to populate entries for <?php echo $startDate; ?></strong>
</h3></li>
<li><h3 align="left"><strong>2. Select a number of hours for the activity.</strong></h3></li>
</div>
<div id="hoursLeft" class="err" align="center">Hours left for <?php echo $startDate.': '.$hoursLeft; ?></div>
<form name="addTime" action="edit_time4.php" method="post" id="addTime" onsubmit="return validateDeleteTimeNG('addTime');"> 
    
    <div id="entryTable<?php echo $startDate; ?>">
        
        <table width="100%" border="0" cellpadding="4" cellspacing="1" bgcolor="#FFFFFF">
        <th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center; width:20%;"></th>
        	<th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center; width:10%;">
            <font color="#FFFFFF" style="font-weight:bold;">G/L</font></th>
            <th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center; width:50%;">
            <font color="#FFFFFF" style="font-weight:bold;">Description</font></th>
            <th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center; width:20%;">
            <font color="#FFFFFF" style="font-weight:bold;">Hours</font></th>
        </table>
        <div id="entryTable1">
        
        <table width="100%" border="0" cellpadding="4" cellspacing="1" bgcolor="#FFFFFF">
            <tr>
            	<td align="center" style="text-align:center; width:20%;" bgcolor="#999999">
                <input type="checkbox" name="delete" id="delete" value="" />&nbsp;Delete&nbsp;</td>
                <?php
                $isLocal = 0;
				if ($row_hours['local_id'] != '') { $isLocal = 1; } // We have a local_id 
				if ( $isLocal == 1 ) {
					$local_id = $row_hours['local_id'];
					$result_local = mysql_query("SELECT description,comments FROM local_activity 
					WHERE local_id = '{$local_id}'");
					if (!$result_local) {
						$_SESSION['Msg'] ="Could not get values from local_activity table on review. Contact the administrator.";
						header("Location: redirect.php?Url=index.php");
						exit;
					}
					$row_local = mysql_fetch_array($result_local, MYSQL_ASSOC);
					mysql_free_result($result_local);
					echo '<input type="hidden" name="activityID1" id="activityID1" value="local_'.$local_id.'" />';
					echo '<td id="entryType1" align="center" style="text-align:center; width:10%;" bgcolor="#999999">L</td>';
					echo '<td id="entryDesc1" align="center" style="text-align:center; width:50%; height:auto;"
					bgcolor="#999999">'.$row_local['description'].'</td>';
				} else { // This is a global code
					$global_id = $row_hours['global_id'];
					$result_global = mysql_query("SELECT description,comments FROM global_activity 
					WHERE global_id = '{$global_id}'");
					if (!$result_global) {
						$_SESSION['Msg']="Could not get values from global_activity table on review. Contact the administrator.";
						header("Location: redirect.php?Url=index.php");
						exit;
					}
					$row_global = mysql_fetch_array($result_global, MYSQL_ASSOC);
					mysql_free_result($result_global);
					echo '<input type="hidden" name="activityID1" id="activityID1" value="'.$global_id.'" />';
					echo '<td id="entryType1" align="center" style="text-align:center; width:10%;" bgcolor="#999999">G</td>';
					echo '<td id="entryDesc1" align="center" style="text-align:center; width:50%; height:auto;"
					bgcolor="#999999">'.$row_global['description'].'</td>';
				} // $isLocal == 1
				?>
           
                <td id="entryHours1" align="center" style="text-align:center; width:20%;" bgcolor="#999999">
                	<select name="activityID1hours" id="activityID1hours">
        			<?php
					$_SESSION['origHours'] = $row_hours['hours']; // To calculate in edit_time4.php
					$selected = ' selected="selected"';
					for ( $j = .5; $j <= $_SESSION['MAX_HOURS_PER_DAY']; $j = $j + .5) {
						if ( $j == $row_hours['hours'] ) {
							echo '<option value="'.$j.'" '.$selected.'>'.$j.'</option>';
						} else {
							echo '<option value="'.$j.'">'.$j.'</option>';
						}
					}
					?>
        			</select>
                </td>
            </tr>
        </table>
        </div> <!-- end entryTable1 -->
        
       </div> <!-- end entryTable<?php echo $startDate; ?> -->
       
    
<br />
<input type="submit" value="Submit" name="submit" />
<input type="reset" name="reset" value="Reset" onclick="alert('Resetting form.'); getPage('edit_time.php');" />
</form>

<script type="text/javascript">

if ( $('#entryTable').length) {
					$('#codes').removeClass('ui-state-disabled', 1000);
				}

</script>

</div> <!-- #end entryTable -->