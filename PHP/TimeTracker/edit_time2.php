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

// echo '_SESSION: '; print_r($_SESSION); // DEBUG
// echo '<br />_POST: '; print_r($_POST); // DEBUG
// exit;  // DEBUG

$_SESSION['edit_date'] = $startDate; // Used in edit_time3.php
$_SESSION['numActivities'] = '1'; // Set this to one since we're only allowing one edit at a time.

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

?>

<!-- <script type="text/javascript" src="js/jquery-1.6.2.min.js"></script> -->
<script type="text/javascript">
function editTime(formID) {
	var formID = parseInt(formID);
	var form = "#editTime" + formID;
	
	$.ajax({
		   type: "POST",
		   url: "edit_time3.php",
		   data: $(form).serialize(),
		   success: function(data) { // data is what gets returned from url, and then passed below.
		   	$('#editTable').html("<div id='new_time'></div>");
			$('#new_time').html(data).hide().fadeIn(1500); // data is what comes back from url.
		   }
	});
	return false;
}
</script>



<div id="editTable">

<br />


<div id="hoursLeft" class="err" align="center">Hours left for <?php echo $startDate.': '.$hoursLeft; ?></div>
    
    <div id="time">
        
        <table width="100%" border="0" cellpadding="4" cellspacing="1" bgcolor="#FFFFFF">
        <th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center; width:10%;"></th>
        	<th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center; width:10%;">
            <font color="#FFFFFF" style="font-weight:bold;">G/L</font></th>
            <th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center; width:60%;">
            <font color="#FFFFFF" style="font-weight:bold;">Description</font></th>
            <th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center; width:20%;">
            <font color="#FFFFFF" style="font-weight:bold;">Hours</font></th>
        </table>
        
        <?php
	
		// We receive from edit_time.php $_POST['startDate']. Use this date to get all of this user's time for this date.
		// We will then present it in a table for them to edit.
		
		$result_hours = mysql_query( "SELECT * FROM time WHERE users_id = '{$users_id}' AND date = '{$startDate}'" );
		if (!$result_hours) {
			$_SESSION['Msg'] = "Could not get hours from time table. Contact the administrator.";
			header("Location: redirect.php?Url=index.php"); // HEADER DOESN'T WORK IN THIS CONTEXT - TODO
			exit;
		}
		$num_rows = mysql_num_rows($result_hours);
		// echo 'num_rows = '.$num_rows.'<br />';  // DEBUG
		// exit; // DEBUG
		if ( $num_rows <= 0 ) {
			$_SESSION['Msg'] = 'No hours to edit for '.$startDate.'.'; // TODO - MAKE THIS SHOW UP.
		} else { // ( $num_rows > 0 )
			$i = 0;
			while ( $row_hours = mysql_fetch_array($result_hours, MYSQL_ASSOC) ) {
		?>
        	<form name="editTime<?php echo $i; ?>" id="editTime<?php echo $i; ?>" action="">
            <div id="editTable<?php echo $i; ?>">
            <input type="hidden" name="time_id" id="time_id" value="<?php echo $row_hours['time_id']; ?>" />
            <table width="100%" border="0" cellpadding="4" cellspacing="1" bgcolor="#FFFFFF">
            <tr>
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
					// Prepend local_ so we can check later if this is a local code or not.
					// echo '<input type="hidden" name="local_id'.$i.'" id="local_id'.$i.'" value="'.$local_id.'" />';
					echo '<td align="center" style="text-align:center; width:10%;" bgcolor="#999999">';
					echo '<button type="button" onclick="editTime(\''.$i.'\');">Edit</button></td>';
					// echo '<input type="submit" value="Edit" name="submit" onclick="getPage(edit_time3.php);" /></td>';
					echo '<td align="center" style="text-align:center; width:10%;" bgcolor="#999999">L</td>';
					echo '<td align="center" style="text-align:center; width:60%; height:auto;" bgcolor="#999999">
					'.$row_local['description'].'</td>';
				} else { // This is a global_code.
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
					// echo '<input type="hidden" name="global_id'.$i.'" id="global_id'.$i.'" value="'.$global_id.'" />';
					echo '<td align="center" style="text-align:center; width:10%;" bgcolor="#999999">';
					echo '<button type="button" onclick="editTime(\''.$i.'\');">Edit</button></td>';
					// echo '<input type="submit" value="Edit" name="submit" onclick="getPage(edit_time3.php);" /></td>';
					echo '<td align="center" style="text-align:center; width:10%;" bgcolor="#999999">G</td>';
					echo '<td align="center" style="text-align:center; width:60%; height:auto;" bgcolor="#999999">
					'.$row_global['description'].'</td>';
				} // $isLocal == 1
				
				?>
                
                <td align="center" style="text-align:center; width:20%;" bgcolor="#999999">
				<?php echo $row_hours['hours']; ?></td>
            </tr>
        </table>
        </div> <!-- end editTable<?php echo $i; ?> -->
        </form>
        
		<?php
				$i++;
			} // while ( $row_hours = mysql_fetch_array($result_hours, MYSQL_ASSOC) )
		} // if ( $num_rows <= 0 )
		mysql_free_result($result_hours);
		
		?>
        
       </div> <!-- end #time -->
       
       <script type="text/javascript">

		if ( $('#entryTable').length) {
							$('#codes').removeClass('ui-state-disabled', 1000);
						}
		
		</script>
       
<!--<input type="reset" name="reset" value="Reset" onclick="alert('Resetting form.'); getPage('time_entry.php');" /> -->

</div> <!-- #end editTable -->