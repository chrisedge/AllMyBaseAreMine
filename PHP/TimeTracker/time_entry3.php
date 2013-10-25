<?php
define('INCLUDE_CHECK', true);
error_reporting (E_ALL ^ E_NOTICE);

require 'connect.php';
require 'functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seLogin');
session_set_cookie_params(2*7*24*60*60);
session_start();

if ( isset($_GET['logoff']) || !( isset($_SESSION['users_id']) ) || !( isset($_POST['submit']) )  ) { // Their credentials are not valid, or they logged off. 
	$_SESSION = array();
    session_destroy();
    header("Location: index.php");
    exit;
}

foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.

//print_r($_POST); print_r($_SESSION); //exit;

if ( isset($_POST['submit']) && $_POST[submit] == 'Submit' ) {

	// $_POST=from_array($_POST); // Massage the input with from_array().
	
	$date = $entryDate; // Set above from _SESSION['entryDate'] by time_entry2.php
	//$isHours = 0; // If they're doing hours and not %, this will get set to 1 later on. If we encounter a % while this is set, we bail.
  	
	if ( isset($_POST['isPercentage']) && $_POST['isPercentage'] == '1' ) {
		$isPercent = 1; // Likewise.
		
		$result_tmpHours = mysql_query( "SELECT hours FROM time WHERE users_id = '{$users_id}' AND date = '{$date}'" );
		if (!$result_tmpHours) {
			$_SESSION['Msg'] = "Could not get hours from time table. Contact the administrator.";
			header("Location: redirect.php?Url=index.php");
			exit;
		}
		$num_rows = mysql_num_rows($result_tmpHours);
		$tmpHours = 0.0;
		if ( $num_rows > 0 ) {		
			while ( $row_tmpHours = mysql_fetch_array($result_tmpHours, MYSQL_ASSOC) ) {
				$tmpHours += $row_tmpHours['hours'];
			}
		}
		mysql_free_result($result_tmpHours);
		
		// If tmpHours is already greater than 8, they can't do % based entry.
		if ( $tmpHours >= $_SESSION['MAX_HOURS_PER_DAY'] ) {
			$_SESSION['Msg'] = "You already entered at least 8 hours entered for this date, you can not use percentage based entry.";
			header("Location: redirect.php?Url=landing.php");
			exit;
		}
		
		// Keep track of their hours here, and later on when the new value gets added to them....
		$percentTotal = 0;
		$percentTotal = $percentTotal + $tmpHours;
		
		
	} // isset($_POST['isPercentage']) && $_POST['isPercentage'] == '1'
	
	$sql_string_prefix = "INSERT INTO time (users_id,date,global_id,local_id,hours,managers_id) VALUES ('{$users_id}', ";
  	$i = 1;
	$sqlArray = array();
	
	foreach ($_POST as $key => $value) {
		
		if ( $key == 'submit' && $value == "Submit" ) { continue; } // Skip the submit key.
		if ( $key == 'isPercentage' ) { continue; } // Skip the percentage key.
		
		if ( $i == 1 ) { // First part, activity code
		
			if ( preg_match("/local_/", $value) ) {
				
				$bits = explode("_",$value);
				$value = $bits[1];
				
				$result_globalID = mysql_query("SELECT global_id FROM local_activity WHERE local_id = '{$value}'");
				if (!$result_globalID) {
					$_SESSION['Msg'] = "Could not get global_id from local_activity table. Contact the administrator.";
					header("Location: redirect.php?Url=index.php?logoff");
					exit;
				}
				
				$row_globalID = mysql_fetch_array($result_globalID, MYSQL_ASSOC);
				mysql_free_result($result_globalID);
				$globalID = $row_globalID['global_id'];
				$sql_string_suffix = "'".$date."', '".$globalID."', '".$value."', '";
			
			} else {
				
				$sql_string_suffix = "'".$date."', '".$value."', NULL, '";
			
			} // if ( preg_match("/local_/", $value) )
			
			$i++;
			continue;
		
		} // if ( $i == 1 )
		
		if ( $i == 2 ) {
		
			/*
			if ( $isPercent == 1 && $value != '' ) {
				$_SESSION['Msg'] = "Hours must be entered as a value OR a percentage. Please try again.";
				header("Location: redirect.php?Url=landing.php");
				exit;
			}
			
			if ( $isHours == 1 && $value == '' ) {
				$_SESSION['Msg'] = "Hours must be entered as a value OR a percentage. Please try again.";
				header("Location: redirect.php?Url=landing.php");
				exit;
			}
			*/
			
			if ( $isPercent == '1' || $value == '' ) {
				$i++;
				continue;
			}
			
			// $hoursLeft is established by a _SESSION variable on the previous page. Here we keep track and make sure they're not
			// entering more than MAX_HOURS_PER_DAY.
			$hoursLeft = $hoursLeft - $value;
			if ( $hoursLeft < 0 ) {
				$_SESSION['Msg'] = "You have entered more than ".$_SESSION['MAX_HOURS_PER_DAY']." hours for this date. Please try again.";
				header("Location: redirect.php?Url=landing.php");
				exit;
			}
			
			// $isHours = 1;
			$sql_string_suffix = $sql_string_suffix.$value."', '{$managers_id}')";
			$i++;
			continue;
		
		} // if ( $i == 2 )
		
		if ( $i == 3 ) {
		
			/*
			if ( $value == '' && $isHours == 1 ) {
				echo '<br />$i: '.$i;
				$i = 1;
			  	continue;
			
			}
			*/
			
			/*
			if ( $value == '' && $isHours == 0 ) {
				$_SESSION['Msg'] = "Hours must be entered as a value OR a percentage. Please try again.";
			  	header("Location: redirect.php?Url=landing.php");
			  	exit;
			}
			
			if ( $value != '' && $isHours == 1 ) {
				$_SESSION['Msg'] = "Hours must be entered as a value OR a percentage. Please try again.";
			  	header("Location: redirect.php?Url=landing.php");
				exit;
			}
			*/
			
			if ( $value != '' && $isPercent == '1' ) {
				
				// NG - Broke hours from .5 to 8 down into percentages and used an option list in time_entry2.php instead.
				// Now, the values come to us as hours, and we don't need to process them here.
				// What we check for here is now based on an 8 hour day, instead of whatever MAX_HOURS_PER_DAY is.
				/* 
				 * 1) If time is input as a percentage, that the total hours do not exceed
				 * 8. Any existing hours for that day will have to initially be subtracted
				 * off.
				 *
				 * All based on an 8 hour day:
				 * 8 hours = 100%
				 * 7.5 hours = 93.75%
				 * 7 hours = 87.5%
				 * 6.5 hours = 81.25%
				 * 6 hours = 75%
				 * 5.5 hours = 68.75%
				 * 5 hours = 62.5%
				 * 4.5 hours = 56.25%
				 * 4 hours = 50%
				 * 3.5 hours = 43.75%
				 * 3 hours = 37.5%
				 * 2.5 hours = 31.25%
				 * 2 hours = 25%
				 * 1.5 hours = 18.75%
				 * 1 hour = 12.5%
				 * .5 hour = 6.25%
				 */
				 
				// Step 1 - since the % is going to be based on an 8 hour day, the user could
				// possibly already have more than 8 hours entered for this day (if they've
				// entered hours previously as values). So first we get all their existing hours for this day.
			
				// We also need to keep track of their hours and make sure they don't total more than 8.
				// Values come to us as a percentage of 8. We first convert it to a float. For 20%: 8 * (20 / 100) = 1.6 hours.
				$value = ( 8 * ($value/100) );
				$percentTotal = $percentTotal + $value;
				
				// If we're over 8 hours (100%), bail.
				if ( $percentTotal > $_SESSION['MAX_HOURS_PER_DAY'] ) {
					$_SESSION['Msg'] = "Your percentages evaluate to more than 100% for this day, please try again.";
			  		header("Location: redirect.php?Url=landing.php");
					exit;
				}
				
				// If we get here, they have less than 12 hours entered for this day and are not over 100%.
				// Do some rounding.
				$value = round($value,0,PHP_ROUND_HALF_UP);
				 
				$sql_string_suffix = $sql_string_suffix.$value."', '{$managers_id}')";
				$i = 1;
			}
			
			// else, we reset $i.
			$i = 1;
		
		} // if ( $i == 3 )
		
		$sql_string = $sql_string_prefix.$sql_string_suffix;
		$sqlArray[] = $sql_string; // Shove these in an array to be inserted after all values have been evaluated.
	
	} // foreach ($_POST as $key => $value)
	
	 //print_r($sqlArray); exit;
	

  // INSERT BLOCK
  foreach ( $sqlArray as $sqlString ) {
	if ( !mysql_query($sqlString) ) { 
		
		$_SESSION['Msg'] = "Could not insert values into time table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php");
		exit;
		
	} else {
		
		$insert_success = 1;
		$today = strtotime(date("Y-m-d")); 
		$minusTwoWeeks = strtotime('-14 days', $today);
		$dateNum = strtotime($date); 
		
		if ( $dateNum < $minusTwoWeeks ) { 
			$backDate = '1';
		}
	
	} // if ( !mysql_query($sql_string) )
	
  
	if ( $insert_success == 1 ) {
		
		if ( isset($backDate) == '1' ) {
		
			$managersName = $_SESSION['managersName'];
			$result_managerEmail = mysql_query("SELECT email FROM users WHERE userName = '{$managersName}'");
			
			if (!$result_managerEmail) {
				$_SESSION['Msg'] = "Could not get managers email on time entry, back date. Contact the administrator.";
				header("Location: redirect.php?Url=index.php?logoff");
				exit;
			}
			
			$row_managerEmail = mysql_fetch_array($result_managerEmail, MYSQL_ASSOC);
			mysql_free_result($result_managerEmail);
			
			send_mail('root@localhost',$row_managerEmail['email'],'SE Time Tracker Notification',
		'User '.$userName.' has added a time entry dated more than 14 days in the past. You are receiving this message because you are listed as the manager for '
			.$userName.'. If you believe this is an error, please contact the administrator.');
		
		} // if ( isset($backDate) == '1' )
	
	} // if ( $insert_success == 1 )
	// END INSERT BLOCK
  } // foreach ( $sqlArray as $sqlString )
	

	// Success, so load the page below and show a success message.
unset($_POST);
// Taken from time_entry.php

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

// Convert $lastDate to today if it's "Never", for use in the form.
if ( $lastDate == "Never" ) { $lastDate = date("Y-m-d"); }// today
// Get the next week as well.
$lastDateNum = strtotime($lastDate); // First get $lastDate into a UNIX timestamp.
$nextWeek = date("Y-m-d",(strtotime('+6 days', $lastDateNum))); // Then get next week, and convert back to Y-m-d.
// echo '<!-- '.$nextWeek.' -->';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo $_SESSION['TITLE']; ?></title>
	<meta name="keywords" content="" />
	<meta name="description" content="" />
    <script type="text/javascript" language="javaScript" src="js/calendar.js"></script>
  	<script type="text/javascript" language="javascript" src="js/setime.js"></script>
  	<!--<script type="text/javascript" language="javascript" src="js/jquery-1.6.2.min.js"></script> -->
    <script type="text/javascript" language="javascript" src="js/jquery-1.7.1.min.js"></script>
  	<script type="text/javascript" language="javascript" src="js/jquery-ui-1.8.18.custom.min.js"></script>
    <!--<link type="text/css" href="css/redmond/jquery-ui-1.8.18.custom.css" rel="stylesheet" media="screen, projection" /> -->
    <link type="text/css" href="css/custom-theme/jquery-ui-1.8.18.custom.css" rel="stylesheet" media="screen, projection" />
  	<link type="text/css" href="css/calendar.css" rel="stylesheet" media="screen, projection" />
  	<link type="text/css" href="css/setime.css" rel="stylesheet" media="screen, projection" />
	<link rel="stylesheet" href="css/style.css" type="text/css" media="screen, projection" />

<script type="text/javascript">
	$(function() {
		$( "button, a", "#sideLeft" ).button();
		$( "button, a", "#sideRight" ).button();
		$( "button, a", "#footer" ).button();
		
		$('.disabled').button('disable'); // Initially disabled buttons until functionality is added.
	});
</script>

<script type="text/javascript">
	$(function() {
		$( "#datepicker" ).datepicker();
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

<script type="text/javascript">

	$(function() {
		
		$( "#entrySuccess" ).dialog({
			// autoOpen: false,
			height: 50,
			modal: true
		});
		
		// $("#entrySuccess").dialog.fadeOut('slow');
		setTimeout( function() { $("#entrySuccess").dialog("close") }, 1500 );
		
		
		//$("#entrySuccess").fadeOut('slow', function() { $("#entrySuccess").dialog("close")) };
		
		
		// callback function to bring a hidden box back
		/*
		function callback() {
			setTimeout(function() {
				$( "#effect" ).removeAttr( "style" ).hide().fadeIn();
			}, 1000 );
		};
		*/

	});

</script>


<script type="text/javascript">
	
	/* $(document).ready(function () {

	    $.history.init(pageload);	
	    
		$('a[href=' + window.location.hash + ']').addClass('selected');
		
		$('a[rel=ajax]').click(function () {
		
			var hash = this.href;
			hash = hash.replace(/^.*#/, '');
	 		$.history.load(hash);	
	 		
	 		$('a[rel=ajax]').removeClass('selected');
	 		$(this).addClass('selected');
	 		$('#body').hide();
	 		$('.loading').show();
	 		
			// getPage();
	
			return false;
		});
	});
	
	function pageload(hash) {
		if (hash) getPage();    
	} */
	
function getPage(url) {
	/* var data = 'page=' + encodeURIComponent(document.location.hash); */
	/* var url = document.location.hash; */
	// $('#body').hide();
	$('#updates').hide();
	$('.loading').show();
	$.ajax({
		/* url: "loader.php",	
		type: "GET",	
		data: data, */
		url: url,
		type: "POST",
		cache: false,
		success: function (html) {	
			$('.loading').hide();				
			$('#updates').html(html);
			$('#updates').fadeIn('slow');
			// $('#body').fadeIn('slow');		
	
		}		
	});
}

</script>

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
      success: function(data) { // data is what gets returned from url, and then passed below.
        $('#calForm').html("<div id='message'></div>");
		$('#message').html(data).hide().fadeIn(1500); // data is what comes back from url.
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


</head>

<body>

<div id="wrapper">

	<div id="middle">

		<div id="container">
			<div id="content" class="ui-widget-content" style="border:none;">
				
              <div style="font-size:24px; font-weight:bold; padding-bottom:5px;">
                <p align="center">&nbsp;<br />
                <?php echo $_SESSION['TITLE']; ?> for <span class="weak"><?php echo ' '.$_SESSION['firstName'].' '.$_SESSION['lastName'];?></span></p>
              </div>
                
                <div align="center" class="loading"><img src="images/orange_loading.gif" width="62" height="62" alt="Loading...." /></div>
                <div id="updates">
                
                  	<div id="entrySuccess" class="ui-widget-overlay ui-widget-shadow" style="color:#000000; font-weight:bold; font:bolder;" title="Success">
					</div>
                    
                    <h4 align="center">Your local time:<span class="weak"><?php echo ' '.date('Y-m-d H:i (\G\M\TP)'); ?></span></h4>
					<p>&nbsp;</p>
					<h4>The last date you entered time was:<span class="weak"><?php echo ' '.$lastDate; ?></span></h4>
                    
                    <div id="calForm">

                    <p>&nbsp;</p>
                    <!--<form name="addTime" action="" method="post" onSubmit="return validateDate('addTime')"> -->
                    <form name="addTime" action="">
                      <label for="startDate">Select a date to begin:</label>
                      <!-- calendar attaches to existing form element -->
                      <input type="text" name="startDate" id="startDate" size="10" maxlength="10" /><br>
                      <font size="-2" face="Arial, Helvetica, sans-serif"><strong>(The date can not be in the future)</strong></font>
                        <br /><br>
                        <input type="submit" name="submit" class="button" value="Enter Time" />
                    </form>
                    
                    </div>
                    

                    
                </div><!-- end #updates -->
                
		  </div><!-- #content-->
		</div><!-- #container-->

		<div class="sidebar ui-widget-content" id="sideLeft" style="border:none;">
			<p align="center">Logged in as: <strong><?php echo $_SESSION['userName']; ?></strong></p>
            
      		<?php printMenuLeftNG(); ?>
           
           <script type="text/javascript">
		   // Stop the #codes links from firing until time_entry2.php has loaded the #entryTable div.
				$("#codes").click(function(e) {
    				    if( !$('#entryTable').length) { // There is no 'exists' check so we look for length.
					  		e.preventDefault();
				  		} 
				});
			</script>
            
		</div><!-- .sidebar#sideLeft -->

		<div class="sidebar ui-widget-content" id="sideRight" style="border:none;">
        
        <?php
			
		if ( $_SESSION['is_manager'] != '0' ) {
			printMgmtMenuNG();
		}
		
		?>
            
		</div><!-- .sidebar#sideRight -->

	</div><!-- #middle-->

</div><!-- #wrapper -->

<div id="footer">

	<?php printFooterNG(); ?>
    
</div><!-- #footer -->

</body>
</html>
<?php

} // isset($_POST['submit']) && $_POST[submit] == 'Submit'

/* FG
	// We need 4 pieces of information to do the insertion into the database:
	// 1) The users_id, which is already stored in a SESSION variable
	// 2) The date for each entry, also stored in a SESSION variable - NG
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

FG */
// } // isset($_POST['submit']) && $_POST[submit] == 'Submit'
?>