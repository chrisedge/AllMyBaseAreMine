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

$_SESSION=from_array($_SESSION); // Massage the input with from_array(), again.
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.


if ( isset($_POST['submit']) && $_POST[submit] == 'Submit' ) {

	$_POST=from_array($_POST); // Massage the input with from_array().
	
	// print_r($_POST); echo '<br />'; // DEBUG
	
	$date = $entryDate; // Set above from _SESSION['entryDate'] by time_entry2.php
	$isHours = 0; // If they're doing hours and not %, this will get set to 1 later on. If we encounter a % while this is set, we bail.
  	$isPercent = 0; // Likewise.
	$sql_string_prefix = "INSERT INTO time (users_id,date,global_id,local_id,hours,managers_id) VALUES ('{$users_id}', ";
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
		
			if ( $isPercent == 1 && $value != '' ) {
				$_SESSION['Msg'] = "Hours must be entered as a value OR a percentage. Please try again 1.";
				header("Location: redirect.php?Url=landing.php");
				exit;
			}
			
			if ( $isHours == 1 && $value == '' ) {
				$_SESSION['Msg'] = "Hours must be entered as a value OR a percentage. Please try again 1.5.";
				header("Location: redirect.php?Url=landing.php");
				exit;
			}
			
			if ( $isPercent == 1 || $value == '' ) {
				$i++;
				continue;
			}
			
			$isHours = 1;
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
			
			if ( $value == '' && $isHours == 0 ) {
				$_SESSION['Msg'] = "Hours must be entered as a value OR a percentage. Please try again 2.";
			  	header("Location: redirect.php?Url=landing.php");
			  	exit;
			}
			
			if ( $value != '' && $isHours == 1 ) {
				$_SESSION['Msg'] = "Hours must be entered as a value OR a percentage. Please try again 3.";
			  	header("Location: redirect.php?Url=landing.php");
				exit;
			}
			
			if ( $value != '' && $isHours == 0 ) {
				// NG - INSERT CODE TO BREAK HOURS INTO A NUMBER FROM A %
				/* 
				 * Will need to move the INSERT block outside of the foreach loop.
				 * Will need to store all of the INSERT strings into an array, go through the
				 * array and make sure of the following conditions:
				 * 1) If time is input as a value in hours, that the total for the day
				 * does not exceed any MAX_HOURS_PER_DAY + any existing hours for that day.
				 * 2) If tims is input as a percentage, that the percentage does not exceed
				 * 100%. ANy existing hours for that day will have to initially be subtracted
				 * off the 40 hour base used to calculate the percentages. There will also be
				 * rounding issues.
				 */
				$isPercent = 1;
				$sql_string_suffix = $sql_string_suffix.$value."', '{$managers_id}')";
				$i = 1;
			}
			
			// else, we reset $i.
			$i = 1;
		
		} // if ( $i == 3 )
		
		$sql_string = $sql_string_prefix.$sql_string_suffix;
		
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
		
	} // foreach ($_POST as $key => $value)
	
	// exit; // DEBUG
	

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


	// Success, so load the page below and show a success message.

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
	});
</script>

<script type="text/javascript">
	$(function() {
		$( "#datepicker" ).datepicker();
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


</head>

<body>

<div id="wrapper">

	<div id="middle">

		<div id="container">
			<div id="content" class="ui-widget-content" style="border:none;">
				
              <div style="font-size:24px; font-weight:bold; padding-bottom:5px;">
                <p align="center">&nbsp;<br />
                <?php echo $_SESSION['TITLE']; ?> for <span class="weak"><?php echo ' '.$firstName.' '.$lastName;?></span></p>
              </div>
                
                <div align="center" class="loading"><img src="images/orange_loading.gif" width="62" height="62" alt="Loading...." /></div>
                <div id="updates">
                	After submission and loading.	
                    
                </div>
                
		  </div><!-- #content-->
		</div><!-- #container-->

		<div class="sidebar ui-widget-content" id="sideLeft" style="border:none;">
			<p align="center">Logged in as: <strong><?php echo $userName; ?></strong></p>
            
            <div align="center">
            <a href="#" onclick="getPage('time_entry.php');" style="color:#F00000; font-size:16px; width:175px;">Enter Data</a>
            <p>&nbsp;</p>
            </div>
            
           <div id="codes">
            <div align="center">
            <a href="#" onclick="return popitup('jcodes.php')" style="font-size:16px; width:175px;">Company Codes</a>
            </div>
            
            <div align="center">
            <a href="#" onclick="return popitup('spcodes.php')" style="font-size:16px; width:175px;">Service Provider Codes</a>
            </div>
            
            <div align="center">
            <a href="#" onclick="return popitup('ecodes.php')" style="font-size:16px; width:175px;">Enterprise Codes</a>
            </div>
            
            <div align="center">
            <a href="#" onclick="return popitup('ccodes.php')" style="font-size:16px; width:175px;">Channel Codes</a>
            </div>
            
            <div align="center">
            <a href="#" onclick="return popitup('lcodes.php')" style="font-size:16px; width:175px;">Local Activity Codes</a>
            </div>
           </div><!-- #end codes -->
           
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
			<div align="center" style="font-size:18px; font-weight:bold;">
            <p>&nbsp;</p>
            Management Tools
            <p>&nbsp;</p>
            </div>
            
            <div align="center">
            <a href="#" style="font-size:16px; width:175px;">View Direct Reports</a>
            </div>
            
            <div align="center">
            <a href="#" style="font-size:16px; width:175px;">View Organization Reports</a>
            </div>
            
            <div align="center">
            <a href="#" style="font-size:16px; width:175px;">View Sub-Organization Reports</a>
            </div>
            
            <div align="center">
            <a href="#" style="font-size:16px; width:175px;">View Local Activity Codes Reports</a>
            </div>
            
            <div align="center">
            <a href="#" style="font-size:16px; width:175px;">Edit Local Activity Codes</a>
            </div>
            
		</div><!-- .sidebar#sideRight -->

	</div><!-- #middle-->

</div><!-- #wrapper -->

<div id="footer">
	<div align="center" style="font-size:18px; font-weight:bold; padding-top:2px;">
      User Activity
    </div>
    
    <div align="center" style="padding-top:5px;">
            <a href="#" style="font-size:16px; width:175px; height:35px;">Edit User Profile</a>
            <a href="#" style="font-size:16px; width:175px;">Edit User Time</a>
            <a href="#" style="font-size:14px; width:175px; height:35px;">View User Reports</a>
            <a href="#" style="font-size:14px; width:175px; height:35px;">Change Password</a>
            <a href="/?logoff" style="font-size:16px; width:175px; color:#F00000;">LOGOUT</a>
    </div>
    
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