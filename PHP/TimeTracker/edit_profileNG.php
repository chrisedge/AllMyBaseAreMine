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

if ( isset($_POST['confirm']) ) {
	$_POST=from_array($_POST); // Massage the input with from_array().
	$users_id = $_SESSION['users_id'];
	// print_r($_POST);
	foreach($_POST as $k2=>$v2) {
		$$k2=$v2; // Get all the $_POST key values and assign them associated variable names.
		if ( $v2 == "" ) { // Lazy validation here at the end. If anything is NULL, start them over.
			unset($_POST);
			$_SESSION['Msg'] = "Empty value detected. All fields must contain a value.";
			header("Location: redirect.php?Url=edit_profileNG.php");
			exit;
		}
	}
	
	// Here we go, update the DB.
	if ( !mysql_query( "UPDATE profile
	SET localTimezone ='{$localTimezone}',weekStart='{$weekStart}',positions_id='{$position}',regions_id='{$region}' 
	WHERE profile_id = '{$profile_id}'" )) {
		$_SESSION['Msg'] = "Could not update profile table on edit. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	
	if (!mysql_query( "UPDATE users 
	SET email = '{$email}', firstName = '{$firstName}', lastName = '{$lastName}', managers_id = '{$manager}' 
	WHERE users_id = '{$users_id}'")) {
		$_SESSION['Msg'] = "Could not update users table on edit. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	
	$_SESSION['Msg'] = "Values updated successfully.";
	unset($_POST);
	header("Location: redirect.php?Url=landing.php");
	exit;
} // isset($_POST['confirm']

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
                    
                    <div class="ui-widget-header" style="margin:0px 5px 0px 5px; padding:5px;">Edit Your Profile</div>
					
                    <div class="ui-widget-content" style="margin:0px 5px 0px 5px; padding:5px;" id="profileEditMain">
<?php
if ( !isset($_POST['confirm']) ) {
	// Get all of this user's profile information. First from the users table.
	$result_users = mysql_query("SELECT email,firstName,lastName,managers_id FROM users WHERE users_id = '{$users_id}'");
	if (!$result_users) {
		$_SESSION['Msg'] = "Could not get user information from users table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	$row_users = mysql_fetch_array($result_users, MYSQL_ASSOC);
	mysql_free_result($result_users);
	
	$result_profile = mysql_query("SELECT profile_id,localTimezone,weekStart,positions_id,regions_id 
	FROM profile WHERE users_id = '{$users_id}'");
	if (!$result_profile) {
		$_SESSION['Msg'] = "Could not get user information from profile table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	$row_profile = mysql_fetch_array($result_profile, MYSQL_ASSOC);
	mysql_free_result($result_profile);
	
?>
                    
<form action="" method="post" enctype="multipart/form-data" name="profileEdit">
<label for="firstName"><strong>Your First Name</strong></label>
<input type="text" size="20" maxlength="255" name="firstName" value="<?php echo $row_users['firstName']; ?>" /><br />
<label for="lastName"><strong>Your Last Name</strong></label>
<input type="text" size="20" maxlength="255" name="lastName" value="<?php echo $row_users['lastName']; ?>" /><br />
<label for="email"><strong>Your Email Address</strong></label>
<input type="text" size="20" maxlength="255" name="email" value="<?php echo $row_users['email']; ?>" /><br />

<label for="manager"><strong>Your manager</strong></label>
<select name="manager">
<?php
$selected = ' selected="selected"'; // Define this to be used in the select(s) below.
$result_managers = mysql_query("SELECT managers_id,name FROM managers WHERE isActive = '1' 
AND name != '{$_SESSION['userName']}' ORDER BY name ASC");
if (!$result_managers) {
		$_SESSION['Msg'] = "Could not get list of managers from managers table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
}
while ( $row_managers = mysql_fetch_array($result_managers, MYSQL_ASSOC) ) {
	if( $row_users['managers_id'] == $row_managers['managers_id'] ) {
		echo '<option value="'.$row_managers['managers_id'].'"'.$selected.'>'.$row_managers['name'].'</option>';
	} else {
		echo '<option value="'.$row_managers['managers_id'].'">'.$row_managers['name'].'</option>';
	}	
}
mysql_free_result($result_managers);
?>
</select>
<br />

<label for="region"><strong>Your region</strong></label>
<select name="region">
<?php		
$result_regions = mysql_query( "SELECT * FROM regions" );
if (!$result_regions) {
	$_SESSION['Msg'] = "Could not get regions on review. Contact the administrator.";
	header("Location: redirect.php?Url=index.php?logoff");
	exit;
}
while ( $row_regions = mysql_fetch_array($result_regions, MYSQL_ASSOC) ) {
	if ( $row_profile['regions_id'] == $row_regions['regions_id'] ) {
	  echo '<option value="'.$row_regions['regions_id'].'"'.$selected.'>'.$row_regions['region'].'</option>';
	} else {
	  echo'<option value="'.$row_regions['regions_id'].'">'.$row_regions['region'].'</option>';
	}
}
mysql_free_result($result_regions);

?>
</select>
<br />
	
	<label for="localTimezone"><strong>Your local timezone</strong></label>
	<select name="localTimezone">
    <?php
    $timezone_identifiers = DateTimeZone::listIdentifiers();
	foreach( $timezone_identifiers as $value ){
        if ( preg_match( '/^(Africa|America|Antarctica|Arctic|Asia|Atlantic|Australia|Europe|Indian|Pacific)\//', $value ) ){
            $ex=explode("/",$value);//obtain continent,city   
            if ($continent!=$ex[0]){
                if ($continent!="") echo '</optgroup>';
                echo '<optgroup label="'.$ex[0].'">';
            }
            $city=$ex[1];
            $continent=$ex[0];
			// Running this below could take a while for all of the zones?
            $myDateTime = new DateTime('', new DateTimeZone($value));
			$myDateTime->setTimezone(new DateTimeZone($value));
			if ( $row_profile['localTimezone'] == $value ) {
				echo '<option value="'.$value.'"'.$selected.'>'.$city.': GMT '.$myDateTime->format('P').'</option>';
			} else {
				echo '<option value="'.$value.'">'.$city.': GMT '.$myDateTime->format('P').'</option>';
			}
			              
        }
    }
    
	?>
        </optgroup>
    </select>
	
    <br />
    
    <label for="weekStart"><strong>Your work week starts on</strong></label>
    <select name="weekStart">
    	<option value="Sunday"<?php if ($row_profile['weekStart']=="Sunday"){echo $selected;} ?>>Sunday</option>
        <option value="Monday"<?php if ($row_profile['weekStart']=="Monday"){echo $selected;} ?>>Monday</option>
    </select>
    
    <br />
    
    <label for="position"><strong>Your current position</strong></label>
    <select name="position">
    	
        <?php
		
		$result_positions = mysql_query( "SELECT * FROM positions ORDER BY positions_id ASC" );
		if (!$result_positions) {
			$_SESSION['Msg'] = "Could not get positions on profile update. Contact the administrator.";
			header("Location: redirect.php?Url=index.php?logoff");
			exit;
		}
		while ($row_positions = mysql_fetch_array($result_positions, MYSQL_ASSOC)) {
			if ( $row_profile['positions_id'] == $row_positions['positions_id'] ) {
			  echo '<option value="'.$row_positions['positions_id'].'"'.$selected.'>'.$row_positions['position'].'</option>';
			} else {
			  echo'<option value="'.$row_positions['positions_id'].'">'.$row_positions['position'].'</option>';
			}
		}
		mysql_free_result($result_positions);
		
		?>
        
    </select>

    <input type="hidden" name="users_id" value="<?php echo $users_id; ?>" />
    <input type="hidden" name="profile_id" value="<?php echo $row_profile['profile_id']; ?>" />
    <input type="hidden" name="confirm" value="confirm" />
    <p align="center"><input type="submit" value="Update" /></p>

</form>
<button name="cancel" onclick="alertAndReturn('Update Cancelled','landing.php')">Cancel</button>

</div><!-- end profileEditMain -->
                               

<?php
} // !isset($_POST['confirm'])
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