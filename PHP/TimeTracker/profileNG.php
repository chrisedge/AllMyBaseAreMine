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

// Now check to see which level of this profile interview they're at. If they've submitted a piece of it,
// store the info in the session and take them to the next piece.
if ( isset($_POST['page1']) ) {
	$_POST=from_array($_POST); // Massage the input with from_array().
	foreach($_POST as $k1=>$v1) $$k1=$v1; // Get all the $_POST key values and assign them associated variable names.
	
	$users_id = $_SESSION['users_id'];
	$_SESSION['firstName'] = $firstName; // Set by foreach above.
	$_SESSION['lastName'] = $lastName; // Set by foreach above.
	$_SESSION['email'] = $email; // Set by foreach above.
	$_SESSION['region'] = $region; // Set by foreach above.
	$_SESSION['pageOne'] = 1;
	$_SESSION['managers_id'] = $manager; // Reset this from the initial setting stored by index.php since it maybe changed.
	// Verify the user name they submitted for their manager is valid.
	if ( $manager == "" ) {
		$_SESSION['Msg'] = "A value for manager is required. Try again.";
		unset($_POST);
		unset($_SESSION['pageOne']);
		header("Location: redirect.php?Url=profileNG.php");
		exit;	
	}
	
} // isset($_POST['page1'])

// Page two.
if ( isset($_POST['page2']) ) {
	$_SESSION['tzContinent'] = from_array($_POST['tzContinent']);
	$_SESSION['pageTwo'] = 1;
}

// Page three.
if ( isset($_POST['page3']) ) {
	$_SESSION['localTimezone'] = from_array($_POST['localTimezone']);
	// Update DB.
	$localTimeZone = $_SESSION['localTimezone'];
	$users_id = $_SESSION['users_id'];
	//if (!mysql_query( "INSERT INTO profile ('users_id', 'timeZone') VALUES ('{$users_id}', '{$timeZone}')" )) {
	//	echo "<h4>Could not insert users_id and timeZone into profile table.</h4>";
	//	exit;
	//}
	$_SESSION['pageThree'] = 1;
}

// Page four.
if ( isset($_POST['page4']) ) {
	$_SESSION['weekStart'] = from_array($_POST['weekStart']);
	// Update DB.
	$weekStart = $_SESSION['weekStart'];
	$users_id = $_SESSION['users_id'];
	//if (!mysql_query( "UPDATE profile SET weekStart = '{$weekStart}' WHERE users_id = '{$users_id}'" )) {
	//	echo "<h4>Could not update profile table with weekStart.</h4>";
	//	exit;
	//}
	$_SESSION['pageFour'] = 1;
}

// Page five.
if ( isset($_POST['page5']) ) {
	$_SESSION['position'] = from_array($_POST['position']);
	// Update DB.
	$position = $_SESSION['position'];
	$users_id = $_SESSION['users_id'];
	//if (!mysql_query( "UPDATE profile SET positions_id = '{$position}' WHERE users_id = '{$users_id}'" )) {
	//	echo "<h4>Could not update profile table with position.</h4>";
	//	exit;
	//}
	$_SESSION['pageFive'] = 1;
}

if ( isset($_POST['confirm']) ) {
	$_POST=from_array($_POST); // Massage the input with from_array().
	$users_id = $_SESSION['users_id'];
	$userName = $_SESSION['userName'];
	// print_r($_POST);
	foreach($_POST as $k2=>$v2) {
		$$k2=$v2; // Get all the $_POST key values and assign them associated variable names.
		if ( $v2 == "" ) { // Lazy validation here at the end. If anything is NULL, start them over.
			$_SESSION['Msg'] = "Empty value detected. All fields must contain a value.";
			header("Location: redirect.php?Url=index.php?logoff");
			exit;
		}
	}
	// Here we go, shovel it all into the DB.
	if (!mysql_query( "INSERT INTO profile (users_id,localTimezone,weekStart,positions_id,regions_id) 
	VALUES ('{$users_id}','{$localTimezone}','{$weekStart}','{$position}','{$region}')" )) {
		$_SESSION['Msg'] = "Could not insert values into profile table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	if (!mysql_query( "UPDATE users 
	SET email = '{$email}', firstName = '{$firstName}', lastName = '{$lastName}', managers_id = '{$manager}',
	 hasProfile = '1' WHERE users_id = '{$users_id}'")) {
		$_SESSION['Msg'] = "Could not update users table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	
	// DEBUG print_r($_SESSION); echo '<br />'; print_r($_POST); exit;
	
	// Per jpost - based on a user's self-defined position, establish them as a manager in the managers table
	// as long as their position is not an SE. See comments in the positions table - those values can never change.
	if ( $position != '1' ) {
		// First we have to check to see if they're already listed as a manager.
		$result_isManager = mysql_query("SELECT managers_id FROM managers WHERE name = '{$userName}'");
		if (!$result_isManager) {
			$_SESSION['Msg'] = "Could not determine if user is a manager for profile. Contact the administrator.";
			header("Location: redirect.php?Url=index.php?logoff");
			exit;
		}
		$num_rows = mysql_num_rows($result_isManager);
		mysql_free_result($result_isManager);
		if ( $num_rows == 0 ) { // They aren't listed as a manager already.
			if (!mysql_query( "INSERT INTO managers (users_id,name) VALUES ('{$users_id}','{$userName}')" )) {
				$_SESSION['Msg'] = "Could not insert values into managers table for profile. Contact the administrator.";
				header("Location: redirect.php?Url=index.php?logoff");
				exit;
			}
		}
	}
	
	$_SESSION['success'] = 1;
	$_SESSION['confirm'] = 1; // So the confirm portion below doesn't load again.

}
?>

<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php echo $_SESSION['TITLE']; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
<div style="float:left;font-family:sans-serif;width=25%">
<img src="images/logo.gif" alt="" /> <br /><br /><br />
Login: <?php echo $_SESSION['userName']; ?>
<br /><br />
<a href="#">Help and FAQ</a>
<br /><br /><a href='?logoff'>Logout</a>
</div>

<div style="float:right;text-align:center; background-color:white; border-left:solid #808080;width:75%">
<h3><?php echo $_SESSION['TITLE']; ?> Profile 
<?php if ( $_SESSION['firstName'] != 'NULL' ) { echo " for: " . $_SESSION['firstName'] . " " . $_SESSION['lastName']; } ?>
</h3>

<?php if ( isset($_SESSION['success']) !=1 ) { ?>

Please complete the following form to establish your profile.<br />
You will be given a chance to review the information prior to saving it.<br />
<strong>Once saved, any profile information modified in the future will be subject<br />
to your manager's approval.</strong>

<?php } else { ?>

Thank you. Your profile information has now been saved.<br />
You must <a href="index.php?logoff">login again</a> to continue.

<?php
	$_SESSION = array();
	session_destroy();
?>
</div>
</body>
</html>

<?php
	exit;
} //isset($_SESSION['success']) !=1 
?>

<hr width="100%" />

<?php
// First, get firstName lastName. @TODO: Add JS validation.

if ( isset($_SESSION['pageOne']) != 1 ) {
?>

<form action="" method="post" enctype="multipart/form-data" name="profileName">
	<label for="firstName"><strong>First Name</strong></label>
    <input type="text" size="20" maxlength="255" name="firstName" placeholder="FirstName" /><br />
    <label for="lastName"><strong>Last Name</strong></label>
    <input type="text" size="20" maxlength="255" name="lastName" placeholder="LastName" /><br />
    <label for="email"><strong>Company Email Address</strong></label>
    <input type="text" size="20" maxlength="255" name="email" placeholder="example@domain.com"  /><br />
    <label for="manager"><strong>Your manager's user name</strong></label>
    <select name="manager">
    <option value="" selected="selected">Select your manager...</option>
    <?php
	// Make sure they can't select themselves as their own manager.
	$result_managers = mysql_query("SELECT managers_id,name FROM managers WHERE isActive = '1' 
	AND name != '{$_SESSION['userName']}' ORDER BY name ASC");
	if (!$result_managers) {
			$_SESSION['Msg'] = "Could not get list of managers from managers table. Contact the administrator.";
			header("Location: redirect.php?Url=index.php?logoff");
			exit;
	}
	while ( $row_managers = mysql_fetch_array($result_managers, MYSQL_ASSOC) ) {
			echo '<option value="'.$row_managers['managers_id'].'">'.$row_managers['name'].'</option>';	
	}
	mysql_free_result($result_managers);
	?>
    </select>
    <br />
    <label for="region"><strong>Select your region</strong></label>
    <select name="region">
    
	<?php
	// Provide a list of regions.
	$result_regions = mysql_query("SELECT * FROM regions");
	if (!$result_regions) {
		$_SESSION['Msg'] = "Could not get regions. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	while ( $row_regions = mysql_fetch_array($result_regions, MYSQL_ASSOC) ) {
		echo '<option value="'.$row_regions['regions_id'].'">'.$row_regions['region'].'</option>';	
	}
	mysql_free_result($result_regions);
	?>
    </select>
    <input type="hidden" name="page1" value="page1" />
    <p align="center"><input type="submit" value="Next"  /></p>
</form>

</div>
</body>
</html>
<?php
	exit;
} //$_SESSION['pageOne'] != 1


if ( isset($_SESSION['pageTwo']) != 1 ) {
	
?>

<form action="" method="post" enctype="multipart/form-data" name="timezoneContinent">
	<label for="tzContinent"><strong>Select your continent (used to determine time zone)</strong></label>
    <select name="tzContinent">
    	<!-- These values can be found here: http://www.php.net/manual/en/class.datetimezone.php -->
        <option value="Africa" selected="selected">Africa</option>
        <option value="America">America</option>
        <option value="Antarctica">Antarctica</option>
        <option value="Arctic">Arctic</option>
        <option value="Asia">Asia</option>
        <option value="Atlantic">Atlantic</option>
        <option value="Australia">Australia</option>
        <option value="Europe">Europe</option>
        <option value="Indian">Indian</option>
        <option value="Pacific">Pacific</option>
    </select>
    <input type="hidden" name="page2" value="page2" />
    <p align="center"><input type="submit" value="Next"  /></p>
</form>

</div>
</body>
</html>
<?php
	exit;
} //$_SESSION['pageTwo'] != 1

if ( isset($_SESSION['pageThree']) != 1 ) {
	$tzContinent = $_SESSION['tzContinent'];
?>

<form action="" method="post" enctype="multipart/form-data" name="timezoneByContient">
<label for="localTimezone"><strong>Select your local timezone for <?php echo $tzContinent; ?></strong></label>
<select name="localTimezone">
    <?php
    $timezone_identifiers = DateTimeZone::listIdentifiers();
    $pregPattern = '/^' . $tzContinent . '\//';
	foreach( $timezone_identifiers as $value ){
        // if ( preg_match( '/^(America|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific)\//', $value ) ){
		if ( preg_match( $pregPattern, $value ) ){
            $ex=explode("/",$value);//obtain continent,city   
            if ($continent!=$ex[0]){
                if ($continent!="") echo '</optgroup>';
                echo '<optgroup label="'.$ex[0].'">';
            }
            $city=$ex[1];
            $continent=$ex[0];
            $myDateTime = new DateTime('', new DateTimeZone($value));
			$myDateTime->setTimezone(new DateTimeZone($value));
			echo '<option value="'.$value.'">'.$city. ': GMT ' .$myDateTime->format('P').'</option>';               
        }
    }
    
	?>
        </optgroup>
    </select>
    <input type="hidden" name="page3" value="page3" />
    <p align="center"><input type="submit" value="Next"  /></p>
</form>

</div>
</body>
</html>
<?php
	exit;
} //$_SESSION['pageThree'] != 1

if ( isset($_SESSION['pageFour']) != 1 ) {
?>

<form action="" method="post" enctype="multipart/form-data" name="weekDayStart">
	<label for="weekStart"><strong>Select the day of the week that your work week starts on</strong></label>
    <select name="weekStart">
    	<option value="Sunday" selected="selected">Sunday</option>
        <option value="Monday">Monday</option>
    </select>
    <input type="hidden" name="page4" value="page4" />
    <p align="center"><input type="submit" value="Next"  /></p>
</form>

</div>
</body>
</html>
<?php
	exit;
} //$_SESSION['pageFour'] != 1

if ( isset($_SESSION['pageFive']) != 1 ) {
?>

<form action="" method="post" enctype="multipart/form-data" name="positionSelect">
	<label for="position"><strong>Select your current position</strong></label>
    <select name="position">
    	
        <?php
		
		$result_positions = mysql_query( "SELECT * FROM positions ORDER BY positions_id ASC" );
						if (!$result_positions) {
							$_SESSION['Msg'] = "Could not get positions. Contact the administrator.";
							header("Location: redirect.php?Url=index.php");
							exit;
						}
						while ($row_positions = mysql_fetch_array($result_positions, MYSQL_ASSOC)) {
							echo '<option value="' . $row_positions['positions_id'] . '">' . $row_positions['position'] . '</option>';
						}
						mysql_free_result($result_positions);
		
		?>
        
    </select>
    <input type="hidden" name="page5" value="page5" />
     <p align="center"><input type="submit" value="Next"  /></p>
</form>

</div>
</body>
</html>
<?php
	exit;
} //$_SESSION['pageFive'] != 1


if ( isset($_SESSION['confirm']) != 1 ) { // Display all the info, and give them a chance to edit it before we commit it.
								   // Subsequent changes to this info will require manager's approval.
	foreach($_SESSION as $k2=>$v2) $$k2=$v2; // Get all the $_SESSION key values and assign them associated variable names.
?>

<form action="" method="post" enctype="multipart/form-data" name="roleSelect">

<?php
echo    '<label for="firstName"><strong>Your First Name</strong></label>
		<input type="text" size="20" maxlength="255" name="firstName" value="'.$firstName.'" /><br />';
echo    '<label for="lastName"><strong>Your Last Name</strong></label>
		<input type="text" size="20" maxlength="255" name="lastName" value="'.$lastName.'" /><br />';
echo    '<label for="email"><strong>Your Email Address</strong></label>
		<input type="text" size="20" maxlength="255" name="email" value="'.$email.'" /><br />';
$selected = ' selected="selected"'; // Define this to be used in the select(s) below.
?>
<label for="manager"><strong>Your manager</strong></label>
<select name="manager">
<?php
$result_managers = mysql_query("SELECT managers_id,name FROM managers ORDER BY name ASC");
if (!$result_managers) {
		$_SESSION['Msg'] = "Could not get list of managers from managers table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php");
		exit;
}
while ( $row_managers = mysql_fetch_array($result_managers, MYSQL_ASSOC) ) {
	if( $managers_id == $row_managers['managers_id'] ) {
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
	header("Location: redirect.php?Url=index.php");
	exit;
}
while ($row_regions = mysql_fetch_array($result_regions, MYSQL_ASSOC)) {
	if ($region==$row_regions['regions_id']) {
	  echo '<option value="'.$row_regions['regions_id'].'"'.$selected.'>'.$row_regions['region'].'</option>';
	} else {
	  echo'<option value="'.$row_regions['regions_id'].'">'.$row_regions['region'].'</option>';
	}
}
mysql_free_result($result_regions);

?>
</select>
<br />
	
	<label for="tzContinent"><strong>Your continent</strong></label>
    <select name="tzContinent">
    	<!-- These values can be found here: http://www.php.net/manual/en/class.datetimezone.php -->
        <option value="Africa"<?php if ($tzContinent=="Africa"){echo $selected;} ?>>Africa</option>
        <option value="America"<?php if ($tzContinent=="America"){echo $selected;} ?>>America</option>
        <option value="Antarctica"<?php if ($tzContinent=="Antarctica"){echo $selected;} ?>>Antarctica</option>
        <option value="Arctic"<?php if ($tzContinent=="Arctic"){echo $selected;} ?>>Arctic</option>
        <option value="Asia"<?php if ($tzContinent=="Asia"){echo $selected;} ?>>Asia</option>
        <option value="Atlantic"<?php if ($tzContinent=="Atlantic"){echo $selected;} ?>>Atlantic</option>
        <option value="Australia"<?php if ($tzContinent=="Australia"){echo $selected;} ?>>Australia</option>
        <option value="Europe"<?php if ($tzContinent=="Europe"){echo $selected;} ?>>Europe</option>
        <option value="Indian"<?php if ($tzContinent=="Indian"){echo $selected;} ?>>Indian</option>
        <option value="Pacific"<?php if ($tzContinent=="Pacific"){echo $selected;} ?>>Pacific</option>
    </select>
    
    <br />

	<label for="localTimezone"><strong>Your local timezone for <?php echo $tzContinent; ?></strong></label>
	<select name="localTimezone">
    <?php
    $timezone_identifiers = DateTimeZone::listIdentifiers();
    // $pregPattern = '/^' . $tzContinent . '\//';
	foreach( $timezone_identifiers as $value ){
        if ( preg_match( '/^(Africa|America|Antarctica|Arctic|Asia|Atlantic|Australia|Europe|Indian|Pacific)\//', $value ) ){
		// if ( preg_match( $pregPattern, $value ) ){
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
			if ($localTimezone==$value) {
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
    	<option value="Sunday"<?php if ($weekStart=="Sunday"){echo $selected;} ?>>Sunday</option>
        <option value="Monday"<?php if ($weekStart=="Monday"){echo $selected;} ?>>Monday</option>
    </select>
    
    <br />
    
    <label for="position"><strong>Your current position</strong></label>
    <select name="position">
    	
        <?php
		
		$result_positions = mysql_query( "SELECT * FROM positions ORDER BY positions_id ASC" );
		if (!$result_positions) {
			$_SESSION['Msg'] = "Could not get positions on review. Contact the administrator.";
			header("Location: redirect.php?Url=index.php");
			exit;
		}
		while ($row_positions = mysql_fetch_array($result_positions, MYSQL_ASSOC)) {
			if ($position==$row_positions['positions_id']) {
			  echo '<option value="'.$row_positions['positions_id'].'"'.$selected.'>'.$row_positions['position'].'</option>';
			} else {
			  echo'<option value="'.$row_positions['positions_id'].'">'.$row_positions['position'].'</option>';
			}
		}
		mysql_free_result($result_positions);
		
		?>
        
    </select>

    <input type="hidden" name="users_id" value="<?php echo $users_id; ?>" />
    <input type="hidden" name="confirm" value="confirm" />
    <p align="center"><input type="submit" value="Save" /></p>

</form>
</div>
</body>
</html>
<?php
	exit;
} //$_SESSION['confirm'] != 1

?>
</div>
</body>
</html>