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

// echo 'here';
?>

<!-- <script type="text/javascript" src="js/jquery-1.6.2.min.js"></script> -->

<script type="text/javascript">
// This handles the form submission. -- NO Longer. Left in because it's useful code.
$(function() {
  
  $(".button").click(function() {
		// validate and process form
		// first hide any error messages
		
		// var dataString = 'name='+ name + '&email=' + email + '&phone=' + phone;
		//alert (dataString);return false;
		
	// var startDate = $("input#startDate").val();
	// var dataString = 'startDate=' + startDate;
	// var dataString = 'name=foo';
	// var dataString = $("#addTime").serialize();
	
	$.ajax({
      type: "POST",
      url: "time_entry3.php",
      // data: dataString,
	  data: $("#addTime").serialize(),
      /* success: function(html) { // html is what gets returned from url, and then passed below.
        $('#entryTable').html("<div id='timeSubmitted'></div>");
		$('#timeSubmitted').html(html).hide().fadeIn(1000); // html is what comes back from url.
     
      } */
     });
    return false;
	});
});
</script>

<script type="text/javascript">
function popitup(url) {
		var curNumActivities = (parseInt(document.getElementById('numToValidate').innerHTML));
		var newUrl = url + '?curNumActivities=' + curNumActivities;
		newwindow=window.open(newUrl,"","scrollbars=1,location=0,toolbar=0,status=0,menubar=0,height=400,width=650");
		if (window.focus) {newwindow.focus()}
		return false;
}

function deleteLast() {
	var curNumActivities = (parseInt(document.getElementById('numToValidate').innerHTML));
	if ( curNumActivities == 1 ) {
		alert("You can not delete the first entry.");
		return false;
	}
	// var numActivities = (parseInt(document.getElementById('numToValidate').innerHTML) - 1);
	var where = '#entryTable' + (parseInt(curNumActivities));
	// $('div').remove('.hello');
	$('div').remove(where);
	document.getElementById('numToValidate').innerHTML = curNumActivities - 1;
	return true;
}


function loadTime(dateLabel) {
		var numActivities = (parseInt(document.getElementById('numToValidate').innerHTML) + 1);
		var where = '#entryTable' + dateLabel;
		// var data = 'dateLabel=' + dateLabel + '&hoursLeft=' + hoursLeft;
		// This hidden div below gets updated so our form validation script will know how many elements to process.
		document.getElementById('numToValidate').innerHTML = numActivities;
		var data = 'numActivities=' + numActivities;
		/* var data = 'page=' + encodeURIComponent(document.location.hash); */
		/* var url = document.location.hash; */
		// $('#body').hide();
		// $('#updates').hide();
	 	// $('.loading').show();
		$.ajax({
		    url: "addTimeNG.php",	
			type: "GET",	
			data: data,
			// url: url,
			//type: "POST",
			cache: false,
			success: function (html) { // html is the var containing the data that is returned by the ajax call from 
									   // addTimeNG.php
				$(where).append(html);
				// $('.loading').hide();				
				// $('#updates').html(html);
				// $('#updates').fadeIn('slow');
				// $('#body').fadeIn('slow');			
			}		
		});
}	
</script>

<div id="entryTable">
<br />

<?php
	$numActivities = 1;
	$_SESSION['numActivities'] = $numActivities;
	
	$numDates = 1;
	foreach ($dateLabel as $label) { // NG UPDATE: this is just one iteration now, but we'll leave the loop for now.
		// Get any hours that have already been input for this date, display it in hoursLeft, and use it for the
		// validation rule of no more than 12 hours per day.
		$_SESSION['label'] = $label; // Used in jcodes.php to keep track of current $label being operated on.
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
	}
	$_SESSION['entryDate'] = $label; // Establish the current date they're entering time for. Will be used in time_entry3.
	$_SESSION['hoursLeft'] = $hoursLeft; // Also used in time_entry3
    // print_r($_SESSION);  // DEBUG
?>



<br />

<div id="instructions">
<li><h3 align="left"><strong>1. Select codes from the left to populate entries for <?php echo $label; ?></strong></h3></li>
<li><h3 align="left"><strong>2. Select a number of hours for the activity <span class="err">OR</span> a percentage.</strong></h3></li>
</div>
<br />
<div id="hoursLeft" align="center" style="font-size:14px;">Hours left for 
<?php echo $label.': <span class="err" style="font-size:24px;">'.$hoursLeft.'</span>'; ?></div>
<br />
<form name="addTime" action="time_entry3.php" method="post" id="addTime" onsubmit="return validateTimeNG();"> 
    <button type="button" id="loadMore" 
    onclick="loadTime(<?php echo "'".$label."'"; ?>)">
    Insert another entry for <?php echo $label; ?></button>
    <button type="button" id="delete" 
    onclick="return deleteLast()">
    Delete last entry for <?php echo $label; ?></button>
    
    <div id="entryTable<?php echo $label; ?>"> <!-- additionals will be appended to this one -->
        
        <table width="100%" border="0" cellpadding="4" cellspacing="1" bgcolor="#FFFFFF">
        	<th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center; width:10%;">
            <font color="#FFFFFF" style="font-weight:bold;">G/L</font></th>
            <th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center; width:50%;">
            <font color="#FFFFFF" style="font-weight:bold;">Description</font></th>
            <th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center; width:20%;">
            <font color="#FFFFFF" style="font-weight:bold;">Hours</font></th>
            <th align="center" bgcolor="#000000" bordercolor="#000000" style="text-align:center; width:20%;">
            <font color="#FFFFFF" style="font-weight:bold;">%</font></th>
        </table>
        
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
        
         
    </div> <!-- end entryTable<?php echo $label; ?> -->
    
    <div style="visibility:hidden; display: none;">
        <input type="text" name="isPercentage" id="isPercentage" value="0" style="visibility:hidden;" size="0" width="0" height="0"  />
    </div>
     
<br />
<input type="submit" value="Submit" name="submit" />
<input type="reset" name="reset" value="Reset" onclick="alert('Resetting form.'); getPage('time_entry.php');" />
</form>

<script type="text/javascript">
function validateTimeNG() {
	var isHours = 0;
	var isPercent = 0;
	var tmpTotal = 0.0;
	for (var i=1, j=document.getElementById('numToValidate').innerHTML; i<=j; i++) {
		var tmp = parseInt(i);
		var fieldID = 'activityID' + tmp;
		if ( document.getElementById(fieldID).value==null || document.getElementById(fieldID).value=="" ) {
			alert("An entry from the left must be made for each time entry.");
			return false;
		}
		
		var hoursID = 'activityID' + tmp + 'hours';
		var percentID = 'activityID' + tmp + 'percentage';
		if ( (document.getElementById(hoursID).value==null || document.getElementById(hoursID).value=="") &&
			 (document.getElementById(percentID).value==null || document.getElementById(percentID).value=="") ) {
			alert("A time value must be entered for each time entry.");
			return false;
		}
		
		// This only compares matching hours/percentage elements. If even one of them is either/or, we need to fail.
		if ( document.getElementById(hoursID).value!="" ) {
			if ( document.getElementById(percentID).value!="" ) {
				alert("You can not use both hours as a value and percentage.");
				return false;
			}
		}
		
		if ( document.getElementById(hoursID).value!="" ) {
			isHours = 1;
			tmpTotal = tmpTotal + (parseFloat(document.getElementById(hoursID).value));
		}
		
		if ( document.getElementById(percentID).value!="" ) {
			isPercent = 1;
			tmpTotal = tmpTotal + (parseFloat(document.getElementById(percentID).value));
			//alert("Percentage: " + document.getElementById(percentID).innerHTML);
		}
		
		if ( isHours == 1 && tmpTotal > 12 ) {
			alert("Your hours total more than 12. Please try again.");
			return false;
		}
		
		if ( isPercent == 1 && tmpTotal > 100 ) {
			alert("Your hours total more than 100%. Please try again.");
			return false;
		}
		
		
	} // end for loop
	
	// This should make sure there is no mixture of hours/percent.
	if ( isHours == 1 && isPercent == 1 ) {
		alert("You can not use both hours as a value and percentage. Please pick only one.");
		return false;
	}
	
	if ( isHours == 0 && isPercent == 1 ) {
		document.getElementById('isPercentage').value = '1';
	}
}
</script>

<script type="text/javascript">

if ( $('#entryTable').length) {
					$('#codes').removeClass('ui-state-disabled', 1000);
				}

</script>

<div id="numToValidate" style="visibility:hidden; display:none;"><?php echo $_SESSION['numActivities']; ?></div>

</div> <!-- #end entryTable -->