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

// $_SESSION=from_array($_SESSION); // Massage the input with from_array(), again.
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.

//print_r($_SESSION); exit;

if ( isset($_POST['delete']) && $_POST['delete'] == 'true' ) {
	if ( $time_id == "" ) { // Set by _SESSION
		$_SESSION['Msg'] = "No values to delete from time table. Starting over.";
		header("Location: redirect.php?Url=landing.php");
		exit;
	}
	
	$result_delete = mysql_query("DELETE FROM time where time_id = '{$time_id}' AND users_id = '{$users_id}'");
	if (!$result_delete) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not delete entry from time table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php");
		exit;
	}

	$aff_rows = mysql_affected_rows(); // Call with no argument to use the last connect link identifier.
	// echo '<br />aff_rows: '.$aff_rows;
	
	if ( $aff_rows != 1 ) {
		unset($_POST);
		$_SESSION['Msg'] = "Entry not deleted from time table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php");
		exit;
	} else {
		// It was a good delete.
		$insert_success = 1;
		unset($_SESSION['time_id']); // Unset this now, don't want it to be set if we get sessions issues here later on.
		$today = strtotime(date("Y-m-d")); 
		$minusTwoWeeks = strtotime('-14 days', $today);
		$dateNum = strtotime($date); 
		
		if ( $dateNum < $minusTwoWeeks ) { 
			$backDate = '1';
		}
		
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
			
			send_mail('root@localhost',$row_managerEmail,'SE Time Tracker Notification',
		'User '.$userName.' has modified a time entry dated '.$_SESSION['entryDate'].'. You are receiving this message because you are listed as the manager for '
			.$userName.'. If you believe this is an error, please contact the administrator.');
		
		} // if ( isset($backDate) == '1' )
		
	} // if ( $aff_rows != 1 ) {
	
} else // They're not deleting, just updating.

if ( isset($_POST['submit']) && $_POST[submit] == 'Submit' && !( isset($_POST['delete']))  ) {

	$_POST=from_array($_POST); // Massage the input with from_array().
	
	// echo '<br />'; // DEBUG
	// print_r($_POST); echo '<br />'; // DEBUG
	// print_r($_SESSION); // DEBUG
	// exit; // DEBUG
	
	$date = $entryDate; // Set above from _SESSION['entryDate'] by time_entry2.php
	$sql_string_prefix = "UPDATE time SET users_id = '{$users_id}', date = '{$date}', managers_id = '{$managers_id}', ";
  	$i = 1;
	
	foreach ($_POST as $key => $value) {
		
		if ( $key == 'submit' && $value == "Submit" ) { continue; } // Skip the submit key.
		
		if ( $i == 1 ) { // First part, activity code
		
			if ( preg_match("/local_/", $value) ) {
				
				$bits = explode("_",$value);
				$value = $bits[1];
				
				$result_globalID = mysql_query("SELECT global_id FROM local_activity WHERE local_id = '{$value}'");
				if (!$result_globalID) {
					$_SESSION['Msg'] = "Could not get global_id from local_activity table. Contact the administrator.";
					header("Location: redirect.php?Url=index.php?logoff");
					// echo 'here 1';
					exit;
				}
				
				$row_globalID = mysql_fetch_array($result_globalID, MYSQL_ASSOC);
				mysql_free_result($result_globalID);
				$globalID = $row_globalID['global_id'];
				// $sql_string_suffix = "'".$date."', '".$globalID."', '".$value."', '";
				$sql_string_suffix = "local_id = '".$value."', global_id = '".$globalID."', ";
			
			} else {
				
				// $sql_string_suffix = "'".$date."', '".$value."', NULL, '";
				$sql_string_suffix = "local_id = NULL, global_id = '".$value."', ";
			
			} // if ( preg_match("/local_/", $value) )
			
			$i++;
			continue;
		
		} // if ( $i == 1 )
		
		if ( $i == 2 ) {
		
			if ( $value == '' ) {
				$_SESSION['Msg'] = "Hours can not be blank. Please try again.";
				header("Location: redirect.php?Url=landing.php");
				// echo 'here 2';
				exit;
			}
			
			// $hoursLeft is established by a _SESSION variable on the previous page. Here we keep track and make sure they're not
			// entering more than MAX_HOURS_PER_DAY. $origHours is established in edit_time3. Add them back on before the calculation.
			$hoursLeft = $hoursLeft + $origHours;
			$hoursLeft = $hoursLeft - $value; 
			if ( $hoursLeft < 0 ) {
				$_SESSION['Msg'] = "You have entered more than ".$_SESSION['MAX_HOURS_PER_DAY']." hours for this date. Please try again.";
				header("Location: redirect.php?Url=landing.php");
				// echo 'here 3';
				exit;
			}
			
			// $sql_string_suffix = $sql_string_suffix.$value."', '{$managers_id}')";
			$sql_string_suffix = $sql_string_suffix."hours = '".$value."' WHERE time_id = '{$time_id}'";
			
			// $i++;
			// continue;
		
		} // if ( $i == 2 )
		
		$sql_string = $sql_string_prefix.$sql_string_suffix;
		
		// echo '<br />sql_string: '.$sql_string; // DEBUG
		// exit; // DEBUG
		 
		// INSERT BLOCK
		if ( !mysql_query($sql_string) ) { 
			
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
				
				send_mail('root@localhost',$row_managerEmail,'SE Time Tracker Notification',
			'User '.$userName.' has modified a time entry dated '.$_SESSION['entryDate'].'. You are receiving this message because you are listed as the manager for '
				.$userName.'. If you believe this is an error, please contact the administrator.');
			
			} // if ( isset($backDate) == '1' )
		
		} // if ( $insert_success == 1 )
		// END INSERT BLOCK
	
	
	} // foreach ($_POST as $key => $value)
	
	// echo '<br />sql_string: '.$sql_string; // DEBUG
	// exit; // DEBUG
	
} // isset($_POST['submit']) && $_POST[submit] == 'Submit'


if ( $insert_success == 1 ) {

	// Success, so load the page below and show a success message.

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
			height: 50,
			modal: true
		});
		
		setTimeout( function() { $("#entrySuccess").dialog("close") }, 1500 );
	});

</script>


<script type="text/javascript">
	
	
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

} // if ( $insert_success == 1 )

?>