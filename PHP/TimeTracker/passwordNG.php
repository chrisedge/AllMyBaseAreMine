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
	$_SESSION = array();
    session_destroy();
    header("Location: index.php");
    exit;
}

unset($_SESSION['Msg']);
//$_SESSION=from_array($_SESSION); // Massage the input with from_array().
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.

$users_id = $_SESSION['users_id'];

if ( isset($_POST['submit']) && $_POST['submit'] == "Change" ) {
	// unset($_POST);
	//$_POST=from_array($_POST);
	// print_r($_POST);
	
	if ($_POST['newpass1'] == '' || $_POST['newpass2'] == '') {
		unset($_POST);
		$_SESSION['Msg'] = "Neither value can be blank. You must start over.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
		// echo 'here1';
	}
	// Verify the submitted passwords match
	if ( (strcmp($_POST['newpass1'],$_POST['newpass2'])) != 0 ) {
		unset($_POST);
		$_SESSION['Msg'] = "Passwords do not match. Please try again.";
		header("Location: redirect.php?Url=passwordNG.php");
		exit;
		// echo 'here2';
	}
	
	$newpass = $_POST['newpass1'];
	
	$result_update = mysql_query("UPDATE users SET password = MD5( '{$newpass}' ) WHERE users_id = '{$users_id}'");
	$aff_rows = mysql_affected_rows(); // Call with no argument to use the last connect link identifier.
	
	if ( $aff_rows != 1 ) {
		unset ($_POST);
		$_SESSION['Msg'] = "Could not update new password. New password possibly the same as old password.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
		// echo 'here3';
	}
	
	// Update passwordChange field with today's date.
	$date = date("Y-m-d"); // today
	$result_date = mysql_query("UPDATE users SET passwordChange = '{$date}' WHERE users_id = '{$users_id}'");
	$aff_rows = mysql_affected_rows(); // Call with no argument to use the last connect link identifier.
	if ( $aff_rows == -1 ) { // -1 means failure. A value of 1 means 1 row updated. If the values were the same however,
							 // the return will be 0 (which is still success).
		unset ($_POST);
		$_SESSION['Msg'] = "Could not update profile with change date. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	
	unset ($_POST);
	$_SESSION['Msg'] = "Password updated. Please login again.";
	header("Location: redirect.php?Url=index.php?logoff");

	exit;

}

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
    <script type="text/javascript" language="javascript" src="js/jquery-1.7.1.min.js"></script>
  	<script type="text/javascript" language="javascript" src="js/jquery-ui-1.8.18.custom.min.js"></script>
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
		$( "#endDate" ).datepicker({
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			maxDate: "+0D"
		});
	});
</script>


</head>

<!--<body onload="popupDirects('showDirects.php');"> -->
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
					
                    <div align="right" style="padding-right:5px;">
                    <span class="err"><?php echo $_SESSION['Msg']; ?></span>
                    </div>
                    
                    <div class="ui-widget-header" style="margin:0px 5px 0px 5px; padding:5px;">Change Your Password</div>
					
                    <div class="ui-widget-content" style="margin:0px 5px 0px 5px; padding:5px;" id="passwordEditMain">

<?php if ( !isset($_POST['submit']) && $_POST['submit'] != 'Change' ) { ?>
	
                    
<form name="chpass" action="" method="post">
	<label for="newpass1"><strong>New Password</strong></label>
    <input type="password" size="20" maxlength="255" name="newpass1" placeholder="********" /><br />
    <label for="newpass2"><strong>Retype Password</strong></label>
    <input type="password" size="20" maxlength="255" name="newpass2" placeholder="********" /><br />
    <br />
    <p align="center"><input type="submit" name="submit" value="Change" /></p>
</form>

</div><!-- end passwordEditMain -->
                               

<?php
} // !isset($_POST['submit']) && $_POST['submit'] != 'Change'
?>
                    

           </div><!-- end updates -->
                
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