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
  header("Location: redirect.php?Url=/index.php");
  exit;
}

if ( isset($_POST['submit']) && $_POST[submit] == 'Review Entries' ) {
	// Here we validate that the time they're trying to submit doesn't go over MAX_HOURS_PER_DAY
	$_POST=from_array($_POST);
	// print_r($_POST);
	$j = 0;
	$finalDates = array( array() ); // Two dimensional array: finalDates[j]['date']['hours']
	$tmpTotal = array(); // An array with key = $date and $value = cumulative hours for that date.
	$i = 1;
	foreach ($_POST as $key => $value) {
		if ( $key == "submit" && $value == "Review Entries" ) { continue; } // Skip the submit key.
		if ( $value == "" ) { continue; } // Skip any empty values.
			
		if ( $i == 1 ) { // First part, activity code
			// Parse the date out and use it to compare to other dates, to see how many we have of each.
			list($date) = explode("_", $key);
			// DEBUG echo 'date after explode = '.$date.'<br />';
			// echo '<br />date: '.$date;
			$i++;
			continue; // Move on to the next part, the activity hours.
		} // $i == 1
		
		if ( $i == 2 ) { // Second part, activity hours
			// echo ' hours: '.$value;
			
			$finalDates[$j]['date'] = $date;
			$finalDates[$j]['hours'] = $value;
			
			if ( $j == 0 ) { // First time through.
				$tmpTotal[$date] = $value;
			} else if ( $finalDates[$j-1]['date'] == $finalDates[$j]['date'] ) { // See if these dates are the same.
				// echo 'true<br />';
				$tmpTotal[$date] += $value;
			} else {
				// echo 'false<br />';
				$tmpTotal[$date] = $value;
			}
			
			$i = 1; // Reset for next iteration.
			$j++; // Increment the $finalDates array.
		} // $i == 2
	} // foreach ($_POST as $key => $value)
	
	// echo '<br />'; print_r($finalDates);
	// $k = count($finalDates);
	// echo 'finalDates count: '.$k.'<br />';
	// echo '<br />'; print_r($tmpTotal);
	
	// $tmpTotal[] now contains all the dates submitted wit their cumulative hours submitted for all of those dates, in
	// the form of $key = YYYY-MM-DD $value = $hours. Now we, loop through and compare to the total to existing dates
	// in the database for this user and make sure that this total number of hours + any existing hours for that date
	// doesn't exceed MAX_HOURS_PER_DAY.
	foreach ( $tmpTotal as $date => $hours ) {
		$total = 0.0;
		$result_hours = mysql_query("SELECT SUM(hours) FROM time WHERE users_id = '{$users_id}' AND date = '{$date}'");
		if (!$result_hours) {
			$_SESSION['Msg'] = "Could not get total hours for date. Contact the administrator.";
			header("Location: redirect.php?Url=/index.php?logoff");
			exit;
		}
		$row_hours = mysql_fetch_row($result_hours);
		if ( $row_hours[0] == '' ) { $row_hours[0] = 0; }
		$total = $row_hours[0] + $hours;
		mysql_free_result($result_hours);
		if ( $total > $_SESSION['MAX_HOURS_PER_DAY'] ) {
			unset($_POST);
			$_SESSION['Msg'] = 
			"Submitted hours (".$hours.") plus existing hours (".$row_hours[0].") for ".$date." exceeds ".$_SESSION['MAX_HOURS_PER_DAY'].". Please try again.";
			header("Location: redirect.php?Url=/time_entry.php");
			exit;
		}
	} // $tmpTotal as $date => $hours
}


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
<font size="-1" face="Arial, Helvetica, sans-serif"><strong>(New entries can only be added 7 days at a time, and the
    date can not be in the future)</strong></font>
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
		// validate and process form
		// first hide any error messages
		
		// var dataString = 'name='+ name + '&email=' + email + '&phone=' + phone;
		//alert (dataString);return false;
		
	var startDate = $("input#startDate").val();
	var dataString = 'startDate=' + startDate;
	
	$.ajax({
      type: "POST",
      url: "time_entry2.php",
      data: dataString,
      success: function(data) {
        $('#calForm').html("<div id='message'></div>");
		$('#message').html(data);
        // $('#message').load("test.php");
		/*
		$('#message').html("<h2>Contact Form Submitted!</h2>")
        .append("<p>We will be in touch soon.</p>")
        .hide()
        .fadeIn(1500, function() {
          $('#message').append("<img id='checkmark' src='images/check.png' />");
        }); */
      }
     });
    return false;
	});
});
</script>



<script type="text/javascript">
	$(function() {
		$( "#startDate" ).datepicker({
			changeMonth: true,
			changeYear: true
		});
	});
</script>
<div align="center" class="loading"><img src="images/orange_loading.gif" width="62" height="62" alt="Loading...." /></div>

<div id="calForm">

<p>&nbsp;</p>
<!--<form name="addTime" action="" method="post" onSubmit="return validateDate('addTime')"> -->
<form name="addTime" action="">
  <label for="startDate">Select a date to begin:</label>
  <!-- calendar attaches to existing form element -->
  <input type="text" name="startDate" id="startDate" size="10" maxlength="10" />
	<br /><br>
    <input type="submit" name="submit" class="button" value="Enter Time" />
</form>

</div>

<?php
} // !isset($_POST['submit']) == "Enter Time"

if ( isset($_POST['submit']) && $_POST['submit'] == "Enter Time" ) {
	$_POST=from_array($_POST); // Massage the input with from_array(), again.
	foreach($_POST as $k1=>$v1) $$k1=$v1; // Get all the $_POST key values and assign them associated variable names.
	
	// We have a startDate and an endDate for which the user wants to enter time. By default this is either today (the
	// user hasn't enetered any time before), or the last date they entered time. This default is a 7 day span (startDate
	// + 6 days).
	// The user could have also specified other dates, including dates greater than a 7 day span. We'll handle that first.
	// First problem: make sure startDate and endDate are no greater than 7 days apart. If they are, truncate endDate
	// to 6 days after startDate.
	$startDateNum = strtotime($startDate); // First get $startDate into a UNIX timestamp.....
	
	// NG UPDATE: $endDateNum = strtotime($endDate); // ...and $endDate as well.
	
	// DEBUG echo 'startDateNum: '.$startDateNum.'<br />';
	// DEBUG echo 'endDateNum: '.$endDateNum.'<br />';
	// If the difference between these two is greater than 518,400 (the number of seconds in 6 days), we know
	// that endDate is more than 7 days from startDate. We also check for selection of an endDate that is before
	// startDate, this will make diff negative.
	
	// NG UPDATE: $diff = $endDateNum - $startDateNum;
	
	// DEBUG echo 'diff: '.$diff.'<br />';
	
	/* NG UPDATE
	if ( $diff > 518400 || $diff < 0 ) { // More than a week, so set endDate to be startDate + 6 days.
		$endDate = date("Y-m-d",(strtotime('+6 days', $startDateNum))); // Set to next week, and convert back to Y-m-d.
		// DEBUG echo 'endDate now: '.$endDate.'<br />';
	}
	*/
	
	// Second problem: The user can not enter time for the future. We check for startDate or endDate to be greater than
	// today. First we need to determine the local time at midnight, which will be the following day.
	$localMidnight = date("Y-m-d",(strtotime('tomorrow 00:00')));
	// DEBUG echo 'localMidnight: '.$localMidnight.'<br />';
	$localMidnightNum = strtotime($localMidnight);
	// DEBUG echo 'localMidnightNum: '.$localMidnightNum.'<br />';
	$startDateNum = strtotime($startDate); // Do this again in case the value changed as a result of the greater than...
	
	// NG UPDATE: $endDateNum = strtotime($endDate);     // ...7 days check earlier.
	
	/* NG UPDATE
	if ( $startDateNum >= $localMidnightNum || $endDateNum >= $localMidnightNum ) {
	*/

	if ( $startDateNum >= $localMidnightNum ) {
		// One of the dates is in the future, doesn't matter which. So we default to providing only today. Maybe add
		// in an error message so they'll know why?
		$startDate = date("Y-m-d");
		// NG UPDATE: $endDate = date("Y-m-d");
		// DEBUG echo 'startDate now: '.$startDate.'<br />';
		// DEBUG echo 'endDate now: '.$endDate.'<br />';	
	}
	
	// We should now have valid startDate and endDate values with which we can build the input form. However, we will have
	// new problems to handle:
	// 1) The user doesn't remember what time has been entered already for the given week. We should
	// provide a pop up window showing a summary of the week which can be used as reference.
	// 2) We need to enforce a maximum of 40 hours per week. Understand though that the startDate to endDate span we
	// currently have to work with might cross two different work weeks, so calculations will need to be made accordingly.
	
	// NG UPDATE: New design only specifies for one day at a time for entering data.
	
	// First though, create the basic form. Start with global codes.
	// The user will be allowed to select from a drop down list of global codes, and select a time value .5 hour increments
	// up to a max of 12 hours for each entry (.5 - 12).
	// Based on startDate and endDate, we need to generate a dynamic list of entries.
	
	// NG UPDATE: $weekSpanSeconds = ( strtotime($endDate) - strtotime($startDate) ); // Number of seconds between the two dates.
	
	// DEBUG echo 'weekSpanSeconds: '.$weekSpanSeconds.'<br />';
	// DEBUG echo 'startDate now: '.$startDate.'<br />';
	// DEBUG echo 'endDate now: '.$endDate.'<br />';
	
	// NG UPDATE: $startDateNum = strtotime($startDate); // First get $startDate into a UNIX timestamp.....
	// NG UPDATE: $endDateNum = strtotime($endDate); // ...and $endDate as well.
	
	// DEBUG echo 'startDateNum now: '.$startDateNum.'<br />';
	// DEBUG echo 'endDateNum now: '.$endDateNum.'<br />';
	// Special case of 0, where startDate and endDate are the same, so only one entry.
	
	/* NG UPDATE
	if ( $weekSpanSeconds == 0 ) {
		$numEntries = 1;
		// DEBUG echo 'numEntries: '.$numEntries.'<br />';
	} else {
		// We use ceil() below to account for the oddity of negative diff's from above causing weekSpanSeconds to become a
		// value such as 514800 (not 518400 mind you), and leaving us with numEntries of 6.9533333333. For example, a 
		// startDate of 2011-03-09 and an endDate of 2011-03-02 (7 days days BEFORE) yields a diff of -604800, and something
		// gets lost in a conversion above.
		$numEntries = ceil(($weekSpanSeconds / 86400) + 1); // The +1 is to include the startDate as well.
		// DEBUG echo 'numEntries: '.$numEntries.'<br />';
	}
	*/
	
	$numEntries = 1; // NG UPDATE
	
	// Generate the date labels for the number of entries.
	
	$dateLabel = array();
	$dateEntries = $numEntries;
	$i = 0;

	while ( $dateEntries > 0 ) {
		$dateLabel[$i] = $startDate;
		// DEBUG echo 'dateLabel['.$i.']: '.$dateLabel[$i].'<br />';
		$dateEntries--;
		$i++;
		if ( $dateEntries != 0 ) {
			$startDate = date("Y-m-d",(strtotime('+1 day', $startDateNum))); // Increment startDate.....
			$startDateNum = strtotime($startDate); // ...and increment startDateNum for the next iteration.
		}
	}
	
	$globalCodes = getGlobalCodes($users_id); // Located in functions.php
	// Get their local codes as well.
	$managers_id = $_SESSION['managers_id'];
	$localCodes = getLocalCodes($managers_id);
		
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
<br />
<form name="addTime" action="" method="post">

<?php
	$numActivities = 1;
	
	$numDates = 1;
	foreach ($dateLabel as $label) { // NG UPDATE: this is just one iteration now, but we'll leave the loop for now.
		// Get any hours that have already been input for this date, display it in hoursLeft, and use it for the
		// validation rule of no more than 12 hours per day.
		$result_tmpHours = mysql_query( "SELECT hours FROM time WHERE users_id = '{$users_id}' AND date = '{$label}'" );
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
    <div id="timeContainer" style=" background-color:#999;"> <!-- outtermost container -->
    <p>&nbsp;</p>
    <p align="left"><button type="button" 
    onclick="loadXMLDoc(<?php echo "'".$label."','".$numActivities."','".$hoursLeft."'"; ?>)">
    	Insert another entry for <?php echo $label; ?></button></p>

     <div id="container<?php echo $label; ?>"> <!-- additionals will be appended to this one  -->
<?php
	// Load three entries for each date they've selected.
	$n = 1;
	while ( $n <= 3 ) {
?>
        <div id="container<?php echo $label; ?>_activity<?php echo $numActivities; ?>">    
        <table width="100%" border="0" cellpadding="2" cellspacing="5" bgcolor="#666666">
        <th align="left" bgcolor="#CCCCCC" bordercolor="#000" style="text-align:center;">
        <strong>Time Entry for <?php echo $label; ?></strong></th>
        <th align="right" bgcolor="#CCCCCC" bordercolor="#000" style="text-align:center;">
        <strong>Hours left for <?php echo $label.': '; ?></strong>
        <span class="err"><?php echo $hoursLeft; ?></span>
        </th>
        <tr>
		<td align="right" width="20%" bgcolor="#CCCCCC">
        <label for="<?php echo $label; ?>_activity<?php echo $numActivities; ?>">Activity Code</label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
		<select name="<?php echo $label; ?>_activity<?php echo $numActivities; ?>">
        	<option value="" selected="selected">Select an activity....</option>
            <optgroup label="Global Codes">
        	<?php
			for ($row = 0; $row <= (sizeof($globalCodes)-1); $row++)
			{
				foreach($globalCodes[$row] as $key => $value)
				{
					if ( $key == 'global_id' ) {
						echo '<option value="'.$value.'">';
					}
					if ( $key == 'description' ) {
						echo $value.': ';
					}
					if ( $key == 'comments' ) {
						echo $value.'</option>';
					}
				}
			}
			?>
            </optgroup>
            <optgroup label="Local Codes">
			<?php
			
			for ($row = 0; $row <= (sizeof($localCodes)-1); $row++)
			{
				foreach($localCodes[$row] as $key => $value)
				{
					if ( $key == 'local_id' ) {
						// Here's the problem: when this is submitted, we need a way in the next step to know which
						// values that come over are local values so we can display the appropriate information (this
						// is only a problem because the local_id's also map to global_id's). When this value is selected
						// it's ID of local_$row will be used to generate a new $value of local_$value. In the next
						// step we parse the local_ off (allowing us to know it's a local value) and then just use the
						// value bit to get the appropriate information.
						echo '<option id="local_'.$row.'_'.$label.'_'.$numActivities.'" value="'.$value.'" 
						onclick="setLocal(this.id,'.$value.')">'; 
					}
					if ( $key == 'local_description' ) {
						echo $value.': ';
					}
					if ( $key == 'local_comments' ) {
						echo $value.'</option>';
					}
				}
			}
			
			?>
            </optgroup>	
        </select>
        </td>
        </tr>
        <tr>
        <td align="right" width="20%" bgcolor="#CCCCCC">
        <label for="<?php echo $label; ?>_activity<?php echo $numActivities; ?>_hours">Hours</label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
        <select name="<?php echo $label; ?>_activity<?php echo $numActivities; ?>_hours">
        	<option value="" selected="selected">Select number of hours....</option>
        	<?php
			
			for ( $j = .5; $j <= $_SESSION['MAX_HOURS_PER_DAY']; $j = $j + .5)
			{
				echo '<option value="'.$j.'">'.$j.'</option>';
			}
			
			?>
        </select>
        </td>
        </tr>
        </table>
        </div> <!-- end container$label_activity$numActivities -->
<?php
		$n++;
		$numActivities++;
	} // while ( $n <= 3 )
	$_SESSION['numActivities'] = $numActivities; // will be incremented by called script if additionals are added.
?>
      </div> <!-- end container$label -->
	</div> <!-- end timeContainer -->
	
<?php	
		$numDates++;
	} // foreach ($dateLabel as $label)

?>
<br />
<input type="submit" name="submit" value="Review Entries" /><input type="reset" name="reset" value="Reset" />
</form>


<?php

} // isset($_POST['submit']) == "Enter Time"


if ( isset($_POST['submit']) && $_POST[submit] == 'Review Entries' ) {
	$_POST=from_array($_POST); // Massage the input with from_array().
	// DEBUG
	/*
	echo "<ol>";
	echo "<ul>";
	foreach($_POST as $key => $value)
	{
		if ( $value != "" ) 
			echo "<li>".$key." ".$value."</li>";
	}
	echo "</ul>";
	echo "</ol>";
	*/
	
	// We now have N number of activities to be input that are currently in the following format:
	// For each date we will have two data points: an activity code, and a number of hours.
	// $key == YYYY-MM-DD_activityN, $value (activity code) == N
	// $key == YYYY-MM-DD_activityN_hours, $value (the number of hours) == N
	// We first present it back to the user for verification. Then we parse the date, hours,
	// and activity code for validation that no more than 12 hours per date, per activity can
	// be submitted.
	
	// Get their list of global codes. Then check to see which ones they've selected already, and
	// make sure they're selected="selected".
	//$users_id = $_SESSION['users_id'];
	//$globalCodes = getGlobalCodes($users_id); // Located in functions.php
	//$managers_id = $_SESSION['managers_id'];
	//$localCodes = getLocalCodes($managers_id);
	
?>

	<br />
	<form name="addTime" action="" method="post">
    
<?php
	// Loop through the values, and generate the form, but skip the submit value and any NULL values.
	// We need to determine how many activities per date have been submitted to us for review.
	// We know that each activity will have 2 key/value pairs.
	$numDates = 0;
	$numActivities = 0;
	$dates = array (); // TODO - Not sure if this is ever used.
	$i = 1;
	$isLocal = 0;
	// print_r($_POST);
	foreach ($_POST as $key => $value) {
		if ( $key == "submit" && $value == "Review Entries" ) { continue; } // Skip the submit key.
		if ( $value == "" ) { continue; } // Skip any empty values.
			
		if ( $i == 1 ) { // First part, activity code
			// Parse the date out and use it to compare to other dates, to see how many we have of each.
			list($date) = explode("_", $key);
			// DEBUG echo 'date after explode = '.$date.'<br />';
			$dates[$numDates] = $date; // TODO - Not sure if this is ever used.
			$numDates++;
			$numActivities++;
			// Check $value to see if it contains 'local_'. If it does, set isLocal for this iteration. Reset when done.
			if (preg_match("/local_/", $value)) {
    			$isLocal = 1;
				$bits = explode("_",$value);
				$value = $bits[1]; // Reset value from local_n to just n.
			}
?>
	<div id="container<?php echo $date; ?>_activity<?php echo $numActivities; ?>">    
        <table width="100%" border="0" cellpadding="2" cellspacing="5" bgcolor="#999999">
        <th align="left" bgcolor="#CCCCCC" bordercolor="#000000" style="text-align:center;">
        <strong>Time Entry for <?php echo $date; ?></strong></th>
        <tr>
		<td align="right" width="20%" bgcolor="#CCCCCC">
        <label for="<?php echo $date; ?>_activity<?php echo $numActivities; ?>">Activity Code</label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
		<select name="<?php echo $date; ?>_activity<?php echo $numActivities; ?>">
        	<?php
			// $value is either global_id or local_id. Get the associated information.
			// We already determined if this was a global or local code. What a pain in the ass that was.
			if ( $isLocal == 1 ) {
				$result_local = mysql_query("SELECT description,comments FROM local_activity WHERE local_id = '{$value}'");
				if (!$result_local) {
					$_SESSION['Msg'] ="Could not get values from local_activity table on review. Contact the administrator.";
					header("Location: redirect.php?Url=/index.php");
					exit;
				}
				$row_local = mysql_fetch_array($result_local, MYSQL_ASSOC);
				mysql_free_result($result_local);
				// Reset this back to local_$value so once this is submitted we can check for local_ again and insert
				// into the DB appropriately.
				echo '<option value="local_'.$value.'" selected="selected">'.$row_local['description'].
				': '.$row_local['comments'].'</option>';
			} else {
				$result_global =mysql_query("SELECT description,comments FROM global_activity WHERE global_id = '{$value}'");
				if (!$result_global) {
					$_SESSION['Msg']="Could not get values from global_activity table on review. Contact the administrator.";
					header("Location: redirect.php?Url=/index.php");
					exit;
				}
				$row_global = mysql_fetch_array($result_global, MYSQL_ASSOC);
				mysql_free_result($result_global);
				echo '<option value="'.$value.'" selected="selected">'.$row_global['description'].
				': '.$row_global['comments'].'</option>';
			}
			?>
        </select>
        </td>
        </tr>
        
<?php
		$i++;
		$isLocal = 0; // Reset for next iteration.
		continue; // Move on to the next part, the activity hours.
		} // $i == 1
		
		if ( $i == 2 ) { // Second part, activity hours
		
		
?>
	
        <tr>
        <td align="right" width="20%" bgcolor="#CCCCCC">
        <label for="<?php echo $date; ?>_activity<?php echo $numActivities; ?>_hours">Hours</label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
        <input name="<?php echo $date; ?>_activity<?php echo $numActivities; ?>_hours" readonly="readonly"
        value="<?php echo $value; ?>" size="4" maxlength="4">
        </td>
        </tr>
        </table>
	</div> <!-- end container$label_activity$numActivities -->

<?php
		$i = 1; // Reset for the next iteration.
		} // $i == 2
	} // foreach ($_POST as $key => $value)
?>
       
<input type="submit" name="submit" value="Submit" />
<br />
<?php

// Loop through our $dates array we created, and look in the database for any hours that have already
// been posted for those dates. These values will be passed on to our javascript function for comparison
// during the onsubmit() call to make sure no more than 12 hours per day are added.
// NOTE: Decided to do this another way. But this is good code, so I left it in just in case.

	// DEBUG echo 'numDates = '.$numDates.'<br />';
	// DEBUG print_r($dates);
/*
	$k = 0;
	while ( $k <= ($numDates - 1) ) {
		// If there are multiple entries for the same day, we only need the total once. So compare and skip.
		if ( $k > 0 ) {
			// DEBUG echo 'dates[k] = '.$dates[$k].'<br />'; echo 'dates[k-1] = '.$dates[$k-1].'<br />';
			while ( $dates[$k] == $dates[$k-1] ) {
				$k++;
			}
		}
		$tmpDate = $dates[$k];
		// DEBUG echo 'tmpDate = '.$tmpDate.'<br />';
		$result_tmpHours = mysql_query( "SELECT hours FROM time WHERE users_id = '{$users_id}' AND date = '{$tmpDate}'" );
		if (!$result_tmpHours) {
			echo 'Could not connect to database to determine tmpHours.';
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
			echo '<input type="hidden" name="'.$tmpDate.'_total" value="'.$tmpHours.'" />';
		}
		mysql_free_result($result_tmpHours);
		$k++;
	} // while ( $k <= $numDates )
*/
?>
</form>
<button name="cancel" onclick="alertAndReturn('Entry Cancelled','/time_entry.php')">Cancel</button>
<?php	
	
} // isset($_POST['submit']) == "Review Entries"

if ( isset($_POST['submit']) && $_POST[submit] == 'Submit' ) {
	$_POST=from_array($_POST); // Massage the input with from_array().
	// print_r($_POST);
	// We need 4 pieces of information to do the insertion into the database:
	// 1) The users_id, which is already stored in a SESSION variable
	// 2) The date for each entry
	// 3) The global_id (activity code) for each entry
	// 4) The number of hours for each entry
	
	$i = 1;
	$sql_string_prefix = "INSERT INTO time (users_id,date,global_id,local_id,hours,managers_id)
					VALUES ('{$users_id}', ";  
	foreach ($_POST as $key => $value) {
		if ( $key == 'submit' && $value == "Submit" ) { continue; } // Skip the submit key.
		
		if ( $i == 1 ) { // First part, activity code
			// Parse the date.
			list($date) = explode("_", $key);
			// Check $value to see if it contains 'local_'. If it does, reset the value and add for insertion.
			if (preg_match("/local_/", $value)) {
    			// $isLocal = 1;
				// echo $value;
				$bits = explode("_",$value);
				$value = $bits[1]; // Reset value from local_n to just n. Here $value is the local_id, we need
				// to get the associated global_id as well for insertion.
				$result_globalID = mysql_query("SELECT global_id FROM local_activity WHERE local_id = '{$value}'");
				if (!$result_globalID) {
					$_SESSION['Msg'] = "Could not get global_id from local_activity table. Contact the administrator.";
					header("Location: redirect.php?Url=/index.php");
					exit;
				}
				$row_globalID = mysql_fetch_array($result_globalID, MYSQL_ASSOC);
				mysql_free_result($result_globalID);
				$globalID = $row_globalID['global_id'];
				$sql_string_suffix = "'".$date."', '".$globalID."', '".$value."', '";
				// echo $sql_string_suffix;
				// exit;
			} else { // Here, $value is global_id, so we need a NULL value for local_id.
				// echo $value."<br />";
				// exit;
				$sql_string_suffix = "'".$date."', '".$value."', NULL, '";
			} // preg_match("/local_/", $value)
			$i++;
			continue; // Move on to the next part, the activity hours.
		} // $i == 1
		
		if ( $i == 2 ) { // Second part, activity hours. $value is now hours.
			$sql_string_suffix = $sql_string_suffix.$value."', '{$managers_id}')";
			$i = 1; // Reset for the next iteration.
		} // $i == 2
		
		$sql_string = $sql_string_prefix.$sql_string_suffix;
		// echo $sql_string;
		// exit;
		
		// ** TODO: Add in validation  prior to insertion for not more than 12 hours. **
		
		// Here we go, shovel it all into the DB.
		if ( !mysql_query($sql_string) ) {
			$_SESSION['Msg'] = "Could not insert values into time table. Contact the administrator.";
			header("Location: redirect.php?Url=/index.php");
			exit;
		} else {
			$insert_success = 1;
			// Here we need to check each $date and see if it's more than 14 days in the past. If any one of the dates
			// are, we generate an alert.
			$today = strtotime(date("Y-m-d")); // Today in seconds
			$minusTwoWeeks = strtotime('-14 days', $today);
			$dateNum = strtotime($date); // The date this time was for, in seconds.
			if ( $dateNum < $minusTwoWeeks ) { // The date they've submitted is more than two weeks ago.
				$backDate = '1';
			}
		}
		// echo $sql_string.'<br />';
	} // foreach ($_POST as $key => $value)

	if ( $insert_success == 1 ) {
		
		if ( isset($backDate) == '1' ) { // We have at least one entry that is for more than 14 days ago.
			// Get their manager's email address.
			$managersName = $_SESSION['managersName'];
			$result_managerEmail = mysql_query("SELECT email FROM users WHERE userName = '{$managersName}'");
			if (!$result_managerEmail) {
				$_SESSION['Msg'] = "Could not get managers email on time entry, back date. Contact the administrator.";
				header("Location: redirect.php?Url=/index.php");
				exit;
			}
			$row_managerEmail = mysql_fetch_array($result_managerEmail, MYSQL_ASSOC);
			mysql_free_result($result_managerEmail);
			send_mail('root@localhost',$row_managerEmail['email'],'SE Time Tracker Notification',
			'User '.$userName.' has added a time entry dated more than 14 days in the past. You are receiving this message because you are listed as the manager for '.$userName.'. If you believe this is an error, please contact the administrator.');
		} // isset($backDate) == '1'
		
		echo '</div></body></html>';
		$_SESSION['Msg'] = "Values successfully inserted.";
		header("Location: redirect.php?Url=/landing.php");
		exit;
	} // $insert_success == 1
	
	// We can't have any empty values. If we get here, some of them are, or there were none. Abort.
	$_SESSION['Msg'] = "No values to insert into time table. Starting over.";
	header("Location: redirect.php?Url=/time_entry.php");
	exit;
	
?>



<?php

} // isset($_POST['submit']) && $_POST[submit] == 'Submit'

?>
</div> <!-- end "main" -->
</body>
</html>