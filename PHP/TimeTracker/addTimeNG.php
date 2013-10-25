<?php
define('INCLUDE_CHECK', true);
error_reporting (E_ALL ^ E_NOTICE);

require 'connect.php';
require 'functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seLogin');
session_set_cookie_params(2*7*24*60*60);
session_start();

// if ( isset($_GET['logoff']) || !( isset($_GET['dateLabel']) ) || !( isset($_GET['hoursLeft']) ) 
//	|| !( isset($_SESSION['users_id']) ) ){
	
if ( isset($_GET['logoff']) || !( isset($_SESSION['users_id']) ) ){
    // echo 'SESSION users_id: ' . $_SESSION['users_id']; 
	$_SESSION = array();
    session_destroy();

    die('You are not allowed to execute this file directly.');
    exit;
}

// $label = from_array($_GET['dateLabel']); // Massage the input with from_array().
// $hoursLeft = from_array($_GET['hoursLeft']);

// $label = from_array($_POST['dateLabel']); // Massage the input with from_array().
//$hoursLeft = from_array($_POST['hoursLeft']);

$_SESSION['numActivities'] = $_GET['numActivities'];
// $numActivities = $_SESSION['numActivities'];
$users_id = $_SESSION['users_id'];
// $globalCodes = getGlobalCodes($users_id); // Located in functions.php
$managers_id = $_SESSION['managers_id'];
// $localCodes = getLocalCodes($managers_id);

?>

<div id="entryTable<?php echo $_SESSION['numActivities']; ?>">
<div style="visibility:hidden; display: none;">
       <input type="text" name="activityID<?php echo $_SESSION['numActivities']; ?>"
        id="activityID<?php echo $_SESSION['numActivities']; ?>" value="" style="visibility:hidden;" size="0" width="0" height="0" />
       </div>
        <table width="100%" border="0" cellpadding="4" cellspacing="1" bgcolor="#FFFFFF">
            <tr>
            	<td id="entryType<?php echo $_SESSION['numActivities']; ?>" align="center" 
                style="text-align:center; width:10%;" bgcolor="#999999"></td>
                <td id="entryDesc<?php echo $_SESSION['numActivities']; ?>" align="center" 
                style="text-align:center; width:50%; height:auto;" bgcolor="#999999"></td>
                <td id="entryHours<?php echo $_SESSION['numActivities']; ?>" align="center"
                 style="text-align:center; width:20%;" bgcolor="#999999">
                	<select name="activityID<?php echo $_SESSION['numActivities']; ?>hours" 
                    id="activityID<?php echo $_SESSION['numActivities']; ?>hours">
                    <option value=""></option>
        			<?php
					for ( $j = .5; $j <= $_SESSION['MAX_HOURS_PER_DAY']; $j = $j + .5) {
						echo '<option value="'.$j.'">'.$j.'</option>';
					}
					?>
        			</select>
                </td>
                <td align="center" style="text-align:center; width:20%;" bgcolor="#999999">
                <select name="activityID<?php echo $_SESSION['numActivities']; ?>percentage"
                 id="activityID<?php echo $_SESSION['numActivities']; ?>percentage">
                 <option value=""></option>
                 <!--<option value=".8">10%</option> 
                 <option value="1.6">20%</option> 
                 <option value="2.0">25%</option>
                 <option value="2.4">30%</option> 
                 <option value="3.2">40%</option> 
                 <option value="4.0">50%</option>
                 <option value="4.8">60%</option> 
                 <option value="5.6">70%</option> 
                 <option value="6.0">75%</option>
                 <option value="6.4">80%</option> 
                 <option value="7.2">90%</option> 
                 <option value="8.0">100%</option> -->
                 <option value="10">10%</option> 
                 <option value="20">20%</option> 
                 <option value="25">25%</option>
                 <option value="30">30%</option> 
                 <option value="40">40%</option> 
                 <option value="50">50%</option>
                 <option value="60">60%</option> 
                 <option value="70">70%</option> 
                 <option value="75">75%</option>
                 <option value="80">80%</option> 
                 <option value="90">90%</option> 
                 <option value="100">100%</option>
                 </select>
                </td>
            </tr>
        </table>
        </div> <!-- end entryTable<?php echo $_SESSION['numActivities']; ?> -->