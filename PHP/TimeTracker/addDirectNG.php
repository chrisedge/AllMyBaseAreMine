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

if ( $_SESSION['is_manager'] == '0' ) {
	$_SESSION['Msg'] = "Only managers may view this page. If you believe this is an error, contact the administrator.";
	header("Location: redirect.php?Url=index.php");
	exit;
}

// $_SESSION=from_array($_SESSION); // Massage the input with from_array(), again.
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.

if ( isset($_POST['confirm']) ) {
	$_POST=from_array($_POST); // Massage the input with from_array().
	$users_id = $_SESSION['users_id'];
	// print_r($_POST); print_r($_SESSION); exit;
	foreach($_POST as $k2=>$v2) {
		$$k2=$v2; // Get all the $_POST key values and assign them associated variable names.
		if ( $v2 == "" ) { // Lazy validation here at the end. If anything is NULL, start them over.
			unset($_POST);
			$_SESSION['Msg'] = "Empty value detected. All fields must contain a value.";
			header("Location: redirect.php?Url=addDirectNG.php");
			exit;
		}
	}
	
	$result_users = mysql_query("SELECT userName FROM users WHERE userName = '{$newUserName}'");
	if (!$result_users) {
		$_SESSION['Msg'] = "Could not get user names from users table. Contact the administrator.";
		unset($_POST);
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	
	$num_rows = mysql_num_rows($result_users);
	mysql_free_result($result_users);
	
	if ( $num_rows > 0 ) {
		$_SESSION['Msg'] = "The user name you have selected is already in use. Please try again.";
		unset($_POST);
		header("Location: redirect.php?Url=addDirectNG.php");
		exit;
	}
	
	// Here we go, update the DB.
	if (!mysql_query( "INSERT INTO users (userName,managers_id) VALUES ('{$newUserName}','{$is_manager}')" )) {
		$_SESSION['Msg'] = "Could not insert new user into users table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	
	$_SESSION['Msg'] = "New user added. The new user can now login and establish their profile.";
	unset($_POST);
	header("Location: redirect.php?Url=addDirectNG.php");
	exit;
} // isset($_POST['confirm']


// unset($_SESSION['Msg']);


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo $_SESSION['TITLE']; ?></title>
	<meta name="keywords" content="" />
	<meta name="description" content="" />
    <script type="text/javascript" language="javascript" src="js/calendar.js"></script>
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
                <?php echo $_SESSION['TITLE']; ?> for <span class="weak"><?php echo ' '.$_SESSION['firstName'].' '.$_SESSION['lastName'];?></span></p>
              </div>
                
                <div align="center" class="loading" id="dialog-modal"><img src="images/orange_loading.gif" width="62" height="62" alt="Loading...." /></div>
                
                <div id="updates">
                
                	<div align="right" style="padding-right:5px;">
                    <span class="err"><?php echo $_SESSION['Msg']; ?></span>
                    </div>
                    
                    <div class="ui-widget-header" style="margin:0px 5px 0px 5px; padding:5px;">Add a new direct report</div>
                    <div class="ui-widget-content" style="margin:0px 5px 0px 5px; padding:5px;" id="AddUserMain">
                    Please complete the following form to establish a new user that reports directly to you.<br /><br />
<?php
if ( !isset($_POST['confirm']) ) {
?>
					<form action="" method="post" enctype="multipart/form-data" name="profileEdit">
                    <label for="newUserName"><strong>User Name</strong></label>
                    <input type="text" size="20" maxlength="255" name="newUserName" /><br />
                    <font size="-1">(The username <span class="err">must</span> match the user's Windows login)</font><br /><br />
                    <label for="manager"><strong>Manager</strong></label>
                    <input type="text" size="20" maxlength="255" name="manager" value="<?php echo $_SESSION['userName']; ?>" disabled="disabled" />
                    <input type="hidden" name="confirm" value="confirm" />
    				<p align="center"><input type="submit" value="Add User" /></p>
					</form>
					<button name="cancel" onclick="alertAndReturn('Update Cancelled','landing.php')">Cancel</button>
                    
                    </div><!-- end AddUserMain -->
<?php
} // !isset($_POST['confirm'])
?>          		     
               </div>
                
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

<?php unset($_SESSION['Msg']); ?>