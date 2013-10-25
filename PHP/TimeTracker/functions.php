<?php

if(!defined('INCLUDE_CHECK')) die('You are not allowed to execute this file directly');

// Set the default timezone.
date_default_timezone_set('America/New_York');

// define ('MAX_HOURS_PER_DAY', 12); <- These have been moved to db.global_vars
// define ('MIN_HOURS_PER_WEEK', 35);

// From http://www.php.net/manual/en/function.mysql-real-escape-string.php
// Use this to escape data prior to inputting into the db, to make sure it's safe.
// call it with $x=from_array($_POST['key_name']);
// or even $_POST=from_array($_POST);
// Added calls to str_replace() to handle double quotes, and the \r\n that mysql_real_escape_string() adds to
// the text provided by tiny_mce.
function from_array($x=null) {
  if(!isset($x)) return null;
  else if(is_string($x)) {
	  $x = str_replace('"', '&quot;', $x);
	  $x = str_replace(array("\r\n", "\n", "\r"), '', $x);
	  return mysql_real_escape_string($x);
  }
  else if(is_array($x)) {
    foreach($x as $k=>$v) {
      $k2=mysql_real_escape_string($k);
      if($k!=$k2) unset($x[$k]);
      $x[$k2]=from_array($v);
    }
    $x = str_replace('"', '&quot;', $x);
	$x = str_replace(array("\r\n", "\n", "\r"), '', $x);
	return $x;
  }
}

// Print out the left side nav menu.
function printMenu() {
	echo '<ul style="list-style-type:square">';
	echo '<li>'.$_SESSION['managersName'].'</li></ul>';
	echo '<a href="landing.php">Home</a><br />';
	echo '<a href="#">Help and FAQ</a>';
	echo '<br /><a href="?logoff">Logout</a><br />';
	if ( isset($_SESSION['is_assumed']) == '1' ) { // This is an assumed role. Provide a constant reminder.
		echo '<br /><span class="err">[ ASSUMED ROLE ]</span><br />';
	}
}

function printMenuLeftNG() {
echo '
<div align="center">
<a href="#" onclick="getPage(\'time_entry.php\');" style="color:#F00000; font-size:16px; width:175px;">Enter Data</a>
<p>&nbsp;</p>
</div>

<div id="codes" class="ui-state-disabled">
<div align="center">
<a href="#" onclick="return popitup(\'jcodes.php\')" style="font-size:16px; width:175px;">Company Codes</a>
</div>

<div align="center">
<a href="#" onclick="return popitup(\'spcodes.php\')" style="font-size:16px; width:175px;">Service Provider Codes</a>
</div>

<div align="center">
<a href="#" onclick="return popitup(\'ecodes.php\')" style="font-size:16px; width:175px;">Enterprise Codes</a>
</div>

<div align="center">
<a href="#" onclick="return popitup(\'ccodes.php\')" style="font-size:16px; width:175px;">Channel Codes</a>
</div>

<div align="center">
<a href="#" onclick="return popitup(\'lcodes.php\')" style="font-size:16px; width:175px;">Local Activity Codes</a>
</div>
</div><!-- #end codes --> 

<div align="center">
<a href="landing.php" style="font-size:16px; width:175px;color:#F00000;">H o m e</a>
</div> ';
}

function printMgmtMenuNG() {
echo '
<div align="center" style="font-size:18px;font-weight:bold;">
<p>&nbsp;</p>
Management Tools
<p>&nbsp;</p>
</div>

<!--<div align="center" class="charts">
<a href="view_directs.php" style="font-size:16px; width:175px;">View Direct Reports</a>
</div> -->

<!--<div align="center" class="charts">
<a href="view_top.php" style="font-size:16px; width:175px;">View Organization Charts</a>
</div> -->

<div align="center">
<a href="view_directs.php" style="font-size:16px; width:175px;">View Reports</a>
</div>

<div align="center" class="charts">
<a href="view_trend.php" style="font-size:16px; width:175px;">View Trend Reports</a>
</div>

<div align="center">
<a href="local_codesNG.php" style="font-size:16px; width:175px;">Edit Local Activity Codes</a>

</div>
<div align="center">
<a href="addDirectNG.php" style="font-size:16px; width:175px;">Manage Direct Reports</a>
</div>';

	if ( isset($_SESSION['positions_id']) && $_SESSION['positions_id'] >= $_SESSION['DELEGATION_THRESHOLD'] ) {
		echo '
		<div align="center">
		<a href="add_delegate.php" target="_blank" style="font-size:16px; width:175px;">Manage Delegates</a>
		</div>
	';
	}

}

function printFooterNG() {
echo '
<div align="center" style="font-size:18px; font-weight:bold; padding-top:2px;">
User Activity
</div>

<div align="center" style="margin:0px 5px 0px 5px;padding-top:5px;background:#69C;" class="ui-widget-header ui-corner-all">
	  
	  <a href="edit_profileNG.php" style="font-size:14px; width:175px; height:35px;">Edit User Profile</a>
	  <a href="#" onclick="getPage(\'edit_time.php\');" style="font-size:14px; width:175px; height:35px;">Edit User Time</a>
	  <a href="user_reports.php" style="font-size:14px; width:175px; height:35px;">View User Reports</a>
	  <a href="passwordNG.php" style="font-size:14px; width:175px; height:35px;">Change Password</a>
	  <a href="/?logoff" style="font-size:14px; width:175px; height:35px; color:#F00000;">LOGOUT</a>
	  
</div> ';
}

// Print out the admin left side nav menu.
function printAdminMenu() {
	echo '<a href="/admin/admin.php">Home</a><br />';
	echo '<a href="#">Help and FAQ</a>';
	echo '<br /><a href="?logoff">Logout</a><br />';
}

// Generate an array of global codes to be used later.
// Receives: nothing.
// Returns: a two dimensional array of global codes.
function getGlobalCodes() {
    $z = array( array() ); // Two dimensional array.
	// Get all the global activities.
	$result_categoryAll = mysql_query(
	"SELECT global_id,description,comments FROM global_activity WHERE isActive = 1 ORDER BY categories_id ASC");
	if (!$result_categoryAll) {
		$_SESSION['Msg'] = "Could not get global activities from global_activity table. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php");
		exit;
	}
	$i = 0;
	while ( $row_categoryAll = mysql_fetch_array($result_categoryAll, MYSQL_ASSOC) ) {
		$z[$i]['global_id'] = $row_categoryAll['global_id'];
		$z[$i]['description'] = $row_categoryAll['description'];
		$z[$i]['comments'] = $row_categoryAll['comments'];
		$i++;
	}
	mysql_free_result($result_categoryAll);
	return $z;
}

// Generate an array of global codes by category.
// Receives: nothing.
// Returns: a two dimensional array of global codes.
function getGlobalCodesByCategory($y=null) {
    $z = array( array() ); // Two dimensional array.
	// Get all the global activities.
	$result_categoryAll = mysql_query(
	"SELECT global_id,description,comments FROM global_activity 
	WHERE isActive = 1 AND categories_id = '${y}' ORDER BY description ASC");
	if (!$result_categoryAll) {
		$_SESSION['Msg'] = "Could not get global activities by category from global_activity table. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php");
		exit;
	}
	$i = 0;
	while ( $row_categoryAll = mysql_fetch_array($result_categoryAll, MYSQL_ASSOC) ) {
		$z[$i]['global_id'] = $row_categoryAll['global_id'];
		$z[$i]['description'] = $row_categoryAll['description'];
		$z[$i]['comments'] = $row_categoryAll['comments'];
		$i++;
	}
	mysql_free_result($result_categoryAll);
	return $z;
}

// Generate an array of global codes to be used later. We only allow the user to select global codes from the
// internal global category (All), and the category that corresponds to their defined role. Users defined as "both"
// will see codes from Enterprise and Service Provider categories.
// Receives: $users_id from the caller, which is set from a stored $_SESSION variable.
// Returns: a two dimensional array of global codes.
function getGlobalCodesByRole($y=null) { // NOTE: This was deprecated, but might be used later.
	if(!isset($y)) return null;
	// First, determine this user's role.
	$result_role = mysql_query( "SELECT roles_id FROM profile WHERE users_id = '{$y}'" );
	if (!$result_role) {
		echo "Could not connect to database to determine user's role.";
		exit;
	}
	$row_role = mysql_fetch_array($result_role, MYSQL_ASSOC);
	$roles_id = $row_role['roles_id'];
	mysql_free_result($result_role);
	// Roles as currently defined (see warning below):
	// 1 = Enterprise, 2 = Service Provider, 3 = Channel, 4 = Both (Channel and Service Provider). 
	// Categories as currently defined (see warning below):
	// 1 = All (Used for Internal COMPANY activities)
	// 2 = Enterprise
	// 3 = Service Provider
	// 4 = Channel
	/* ************************************ WARNING ******************************************
	 * FUNCTIONALITY MAY BE ADDED TO THE ADMIN INTERFACE THAT WILL ALLOW FOR ADDITIONAL ROLES
	 * AND/OR CATEGORIES TO BE DEFINED. THIS MAY BREAK THE FOLLOWING FUNCTIONALITY IF NOT
	 * IMPLEMENTED PROPERLY.
	 * **************************************************************************************/
	 // Determine what specific category to get now, based on their role, and save it as a WHERE clause.
	if ($roles_id == '1') { $specific_id = 'categories_id = 2'; }
	if ($roles_id == '2') { $specific_id = 'categories_id = 3'; }
	if ($roles_id == '3') { $specific_id = 'categories_id = 4'; }
	if ($roles_id == '4') { $specific_id = 'categories_id = 3 OR categories_id = 4'; }
    $z = array( array() ); // Two dimensional array.
	// Get all the global activities from category 1 (All), and their specific categories as well.
	$result_categoryAll = mysql_query(
	"SELECT global_id,description,comments FROM global_activity WHERE (categories_id = 1 OR $specific_id) AND isActive = 1");
	if (!$result_categoryAll) {
		echo "Could not connect to database to select global categories.";
		exit;
	}
	$i = 0;
	while ( $row_categoryAll = mysql_fetch_array($result_categoryAll, MYSQL_ASSOC) ) {
		$z[$i]['global_id'] = $row_categoryAll['global_id'];
		$z[$i]['description'] = $row_categoryAll['description'];
		$z[$i]['comments'] = $row_categoryAll['comments'];
		$i++;
	}
	mysql_free_result($result_categoryAll);
	return $z;
}

// Generate an array of local codes to be used later.
// Receives: $managers_id from the caller, which is set from a stored $_SESSION variable.
// Returns: a two dimensional array of local codes.
function getLocalCodes($y=null) {
	if(!isset($y)) return null;
	// $y is the user's $managers_id. We need to get their manager's users_id from the managers table.
	$result_managersId = mysql_query( "SELECT users_id FROM managers WHERE managers_id = '{$y}'" );
	if (!$result_managersId) {
		$_SESSION['Msg'] = "Could not get managers_id from managers table. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php");
		exit;
	}
	$row_manager = mysql_fetch_array($result_managersId, MYSQL_ASSOC);
	mysql_free_result($result_managersId);
	$manager = $row_manager['users_id'];
	
    $z = array( array() ); // Two dimensional array.
	// Get any local codes defined by this users manager.
	$result_locals = mysql_query(
	"SELECT local_id,description,comments FROM local_activity WHERE users_id = '{$manager}' AND isActive = 1");
	if (!$result_locals) {
		$_SESSION['Msg'] = "Could not get values from local_activity table. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$i = 0;
	while ( $row_locals = mysql_fetch_array($result_locals, MYSQL_ASSOC) ) {
		$z[$i]['local_id'] = $row_locals['local_id'];
		$z[$i]['local_description'] = $row_locals['description'];
		$z[$i]['local_comments'] = $row_locals['comments'];
		$i++;
	}
	mysql_free_result($result_locals);
	return $z;
}

// Receives a users_id(x), start date(sd), and end date(ed). Returns total hours for the user during the time frame.
function getHoursByUser($x=null,$sd=null,$ed=null) {
	$total = 0.0;
	$sql_string = "SELECT SUM(hours) FROM time WHERE users_id = '{$x}' AND ( date BETWEEN '{$sd}' AND '{$ed}' )";
	$result_j = mysql_query($sql_string);
	if (!$result_j) {
		$_SESSION['Msg'] = "Could not get hours from time table for report. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	$row_j = mysql_fetch_row($result_j);
	mysql_free_result($result_j);
	$total = $row_j[0];
	return $total;
}


// Receives a users_id(x), categories_id(y), start date(sd), and end date(ed). Returns total hours for the category.
function getHoursByCategory($x=null,$y=null,$sd=null,$ed=null) {
	// echo 'passed vars: '.$x.' '.$y.' '.$sd.' '.$ed.'<br />';
	$result_i = mysql_query("SELECT global_id FROM global_activity WHERE categories_id = '{$y}' AND isActive = 1");
	if (!$result_i) {
		$_SESSION['Msg'] = "Could not get values from global_activity table for report. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$total = 0.0;
	while ( $row_i = mysql_fetch_array($result_i, MYSQL_ASSOC) ) {
		$i = $row_i['global_id'];
		// echo '$i: '.$i.'<br />';
		$sql_string = "SELECT SUM(hours) FROM time WHERE users_id = '{$x}' 
		AND ( date BETWEEN '{$sd}' AND '{$ed}' ) AND global_id = '{$i}'";
		// echo 'sql_string: '.$sql_string.'<br />';
		$result_j = mysql_query($sql_string);
		if (!$result_j) {
			$_SESSION['Msg'] = "Could not get hours from time table for report. Contact the administrator.";
			header("Location: redirect.php?Url=/index.php?logoff");
			exit;
		}
		$row_j = mysql_fetch_row($result_j);
		// echo 'row_j[0]: '.$row_j[0].'<br />';
		$total += $row_j[0];
		mysql_free_result($result_j);
	}
	mysql_free_result($result_i);
	return $total;
}

// Receives a users_id(x), global_id(y), start date(sd), and end date(ed). Returns total hours for the global_id.
function getHoursByGlobal($x=null,$y=null,$sd=null,$ed=null) {
	// echo 'passed vars: '.$x.' '.$y.' '.$sd.' '.$ed.'<br />';
	$total = 0.0;
	$sql_string = "SELECT SUM(hours) FROM time WHERE users_id = '{$x}' 
	AND ( date BETWEEN '{$sd}' AND '{$ed}' ) AND global_id = '{$y}'";
	// echo 'sql_string: '.$sql_string.'<br />';
	$result_j = mysql_query($sql_string);
	if (!$result_j) {
		$_SESSION['Msg'] = "Could not get hours from time table for report (global). Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$row_j = mysql_fetch_row($result_j);
	// echo 'row_j[0]: '.$row_j[0].'<br />';
	$total = $row_j[0];
	mysql_free_result($result_j);
	return $total;
}


// Receives a users_id(x), local_id(y), start date(sd), and end date(ed). Returns total hours for the local_id.
function getHoursByLocal($x=null,$y=null,$sd=null,$ed=null) {
	// echo 'passed vars: '.$x.' '.$y.' '.$sd.' '.$ed.'<br />';
	$total = 0.0;
	$sql_string = "SELECT SUM(hours) FROM time WHERE users_id = '{$x}' 
	AND ( date BETWEEN '{$sd}' AND '{$ed}' ) AND local_id = '{$y}'";
	// echo 'sql_string: '.$sql_string.'<br />';
	$result_j = mysql_query($sql_string);
	if (!$result_j) {
		unset($_SESSION['Msg']);
		$_SESSION['Msg'] = "Could not get hours from time table for report (local). Contact the administrator (0x111).";
		header("Location: view_directs.php");
		exit;
	}
	$row_j = mysql_fetch_row($result_j);
	// echo 'row_j[0]: '.$row_j[0].'<br />';
	$total = $row_j[0];
	mysql_free_result($result_j);
	return $total;
}


// Get a list of a user's direct reports, if they're a manager.
// Recieves a users_id(x) and an optional parent_id(y) to identify a user as a subordinate of another manager.
// Returns a two dimensional array of [users_id] and [userName] on success and 0 if they're not a manager or
// don't have any direct reports.
function getDirects($x=null,$y=null) {
	$result_isManager = mysql_query("SELECT managers_id FROM managers where users_id = '{$x}'");
	if (!$result_isManager) {
		$_SESSION['Msg'] = "Could not get determine if user is a manager. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$numRows = mysql_num_rows($result_isManager);
	if ( $numRows == '0' ) { // This user isn't listed as a manager.
		mysql_free_result($result_isManager);
		return '0';
	}
	$row_manager = mysql_fetch_array($result_isManager, MYSQL_ASSOC);
	mysql_free_result($result_isManager);
	$managers_id = $row_manager['managers_id'];
	// This user is a manager, so let's get all the users that report to them.
	$result_directs = mysql_query("SELECT users_id,userName FROM users 
	WHERE managers_id = '{$managers_id}' ORDER BY userName ASC");
	if (!$result_directs) {
		$_SESSION['Msg'] = "Could not get determine a users direct reports. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$numDirects = mysql_num_rows($result_directs);
	if ( $numDirects == '0' ) { // This user has no direct reports.
		mysql_free_result($result_directs);
		return '0';
	}
	// Ok, they have some direct reports.
	// $z = array( array() );
	// $j = 0;
	while ( $row_directs = mysql_fetch_array($result_directs, MYSQL_ASSOC) ) {
		if ( $y != NULL ) {
			echo '<ul id="child_'.$y.'">';
		} else {
			echo '<ul>';
		}
		printDirects($row_directs['users_id'],$row_directs['userName']);
		// $z[$j]['users_id'] = $row_directs['users_id'];
		// $z[$j]['userName'] = $row_directs['userName'];
		// $j++;
		echo '</ul>';
	}
	mysql_free_result($result_directs);
	// return $z;
}

// Recieves a users_id and assocaited userName. Prints a link for the associted user, and
// makes a call back to getDirects() with the current users_id to see if they have any direct reports.
function printDirects($x=null,$y=null) {
	$b = checkDirects($x); // If this user has at least one direct report, we need different formatting.
	if ( $b == '1' ) {
		$parentID = $x;
		echo '<li id="parent_'.$parentID.'">';
		echo '<a href="/view_reports.php?id='.$x.'">'.$y.'</a>';
		echo '<a href="#"><span class="ui-icon ui-icon-plus"></span></a>';
		$a = getDirects($x,$parentID); // Check this user for directs. Let the potentially infinite recursion begin!
		if ( $a == '0' ) {
			echo '</li>';	
		}
	} else {
		echo '<li>';
		echo '<a href="/view_reports.php?id='.$x.'">'.$y.'</a>';
		$a = getDirects($x); // Check this user for directs. Let the potentially infinite recursion begin!
		if ( $a == '0' ) {
			echo '</li>';
		}
	}	
}

// Get a list of a user's direct reports, if they're a manager.
// Recieves a users_id(x).
// Returns a two dimensional array of [users_id] and [userName] on success and 0 if they're not a manager or
// don't have any direct reports. Formatted for a <select> list.
function getDirectsSelect($x=null) {
	$result_isManager = mysql_query("SELECT managers_id FROM managers where users_id = '{$x}'");
	if (!$result_isManager) {
		$_SESSION['Msg'] = "Could not get determine if user is a manager. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$numRows = mysql_num_rows($result_isManager);
	if ( $numRows == '0' ) { // This user isn't listed as a manager.
		mysql_free_result($result_isManager);
		return '0';
	}
	$row_manager = mysql_fetch_array($result_isManager, MYSQL_ASSOC);
	mysql_free_result($result_isManager);
	$managers_id = $row_manager['managers_id'];
	// This user is a manager, so let's get all the users that report to them.
	$result_directs = mysql_query("SELECT users_id,userName FROM users 
	WHERE managers_id = '{$managers_id}' ORDER BY userName ASC");
	if (!$result_directs) {
		$_SESSION['Msg'] = "Could not get determine a users direct reports. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$numDirects = mysql_num_rows($result_directs);
	if ( $numDirects == '0' ) { // This user has no direct reports.
		mysql_free_result($result_directs);
		return '0';
	}
	// Ok, they have some direct reports.
	while ( $row_directs = mysql_fetch_array($result_directs, MYSQL_ASSOC) ) {
		printDirectsSelect($row_directs['users_id'],$row_directs['userName']);
	}
	mysql_free_result($result_directs);
	// return $z;
}

// Recieves a users_id and assocaited userName. Prints a link for the associted user, and
// makes a call back to getDirects() with the current users_id to see if they have any direct reports.
// Formatted for a <select> list.
function printDirectsSelect($x=null,$y=null) {
	echo '<option value="'.$x.'">'.$y.'</option>';
	$a = getDirectsSelect($x); // Check this user for directs. Let the potentially infinite recursion begin!	
}

// Used to simply check if a user has any direct reports. If they do, we'll know to properly format
// the <ul> and associated <li>'s we'll be generating in the caller, printDirects().
// Recieves: a users_id(x).
// Returns: 1 if the user has any direct reports, and 0 if they're not a manager, or they have no direct reports.
function checkDirects($x=null) {
	$result_isManager = mysql_query("SELECT managers_id FROM managers where users_id = '{$x}'");
	if (!$result_isManager) {
		$_SESSION['Msg'] = "Could not get determine if user is a manager. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$x=null;
	$numRows = mysql_num_rows($result_isManager);
	if ( $numRows == '0' ) { // This user isn't listed as a manager.
		mysql_free_result($result_isManager);
		$numRows=null;
		$x=null;
		return '0';
	}
	$numRows=null;
	$row_manager = mysql_fetch_array($result_isManager, MYSQL_ASSOC);
	mysql_free_result($result_isManager);
	$managers_id = $row_manager['managers_id'];
	$row_manager=null;
	// This user is a manager, but all we care about is if they have at least 1 direct report.
	$result_directs = mysql_query("SELECT users_id FROM users WHERE managers_id = '{$managers_id}' LIMIT 0,1");
	if (!$result_directs) {
		$_SESSION['Msg'] = "Could not get determine a users direct reports. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$managers_id=null;
	$numDirects = mysql_num_rows($result_directs);
	if ( $numDirects == '0' ) { // This user has no direct reports.
		mysql_free_result($result_directs);
		$numDirects=null;
		
		return '0';
	}
	// We made it here, so they have at least 1 direct report.
	$numDirects=null;
	return '1';
}


// This function will take a users_id, and a managers_id. We will then query the returned managers_id for the user
// all the way up the chain until we get a match for the managers_id that is passed from the caller. If we get a
// match, the requesting managers_id is above the requested users_id (and their associated managers) and can
// therefore view a report for this user. If we don't get a match, someone has tried to run a report for a
// user that does not report either 1) directly to them, or 2) to another manager below them in the chain (i.e.,
// John and Mary are managers of the same level and John has tried to view a report on one of Mary's employees).
function checkHierarchy($x=null,$y=null) {
	$result_admin = mysql_query("SELECT managers_id FROM managers WHERE name = 'admin'");
	if (!$result_admin) {
		mysql_free_result($result_admin);
		$_SESSION['Msg'] = "Could not connect to determine hierarchy. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$numAdmins = mysql_num_rows($result_admin);
	if ( $numAdmins != 1 ) { // This should never happen. There can be only one. Did I just type that?
		mysql_free_result($result_admin);
		$_SESSION['Msg'] = "Found more than 1, or 0 top level managers. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$row_admin = mysql_fetch_array($result_admin, MYSQL_ASSOC);
	mysql_free_result($result_admin);
	$adminID = $row_admin['managers_id'];
	
	$match = '0'; 
	// If we find a match before $row_thisManager['managers_id'] == $adminID, then all is good and we can return '1'.
	// If we can't find a match as we go up the chain though, we return '0' and abruptly log the user off.
	while ( $match == '0' ) {
		// Find out who this user's manager is, and check to see if it matches the value we've been given (y).
		// By design every user will have a managers_id (except admin). So, there should be no 0 row returns here.
		$result_thisManager = mysql_query("SELECT managers_id FROM users WHERE users_id = '{$x}'");
		if (!$result_thisManager) {
			mysql_free_result($result_thisManager);
			$_SESSION['Msg'] = "Could not connect to database to check hierarchy. Contact the administrator.";
			header("Location: redirect.php?Url=/index.php?logoff");
			exit;
		}
		$numRows = mysql_num_rows($result_thisManager);
		if ( $numRows != 1 ) { // This should never happen. Everyone (except the admin user) MUST have a managers_id.
			mysql_free_result($result_thisManager);
			$_SESSION['Msg'] = "Could not locate a manager for the user. Contact the administrator.";
			header("Location: redirect.php?Url=/index.php?logoff");
			exit;
		}
		
		$row_thisManager = mysql_fetch_array($result_thisManager, MYSQL_ASSOC);
		mysql_free_result($result_thisManager);
		$thisManager = $row_thisManager['managers_id'];
		// Handle the extreme case first. No match, and we reached the top of the hierarchy.
		if ( $thisManager == $adminID ) {
			return '0';
		}
		// Handle the best case next, the user reports directly to them
		if ( $thisManager == $y ) {
			return '1';
		}
		// We get here, so we haven't made it all the way up the chain yet. So we now have the
		// managers_id of who this user DOES report to. We need to find out who that is.
		$result_thisUser = mysql_query("SELECT users_id FROM managers WHERE managers_id = '{$thisManager}'");
		if (!$result_thisUser) {
			mysql_free_result($result_thisUser);
			$_SESSION['Msg'] = "Could not determine user<->manager relationship. Contact the administrator.";
			header("Location: redirect.php?Url=/index.php?logoff");
			exit;
		}
		$numRows = mysql_num_rows($result_thisUser);
		if ( $numRows != 1 ) { // This should never happen. We just determined this person was a manager.
			mysql_free_result($result_thisUser);
			$_SESSION['Msg'] = "Manager not listed at next layer up. Contact the administrator.";
			header("Location: redirect.php?Url=/index.php?logoff");
			exit;
		}
		$row_thisUser = mysql_fetch_array($result_thisUser, MYSQL_ASSOC);
		mysql_free_result($result_thisUser);
		// We set x to be our new users_id and let the loop continue to search up the chain for a match.
		$x = $row_thisUser['users_id'];
	} // $match == '0'
}

// ************************************ TODO *********************************************
// Move this over to the left <div> and modify the listing as so:
// IF the user has no directs, just have a link on their name.
// IF the user has additional directs, make the + icon a link, and
// if clicked, reload the list on the left with just that manager's directs.
// Provide a back button at the top to go back a level.
// ***************************************************************************************
// Get a list of a user's direct reports, if they're a manager.
// Recieves a users_id(x) and an optional parent_id(y) to format the back button to go back up a level.
// Returns a two dimensional array of [users_id] and [userName] on success and 0 if they're not a manager or
// don't have any direct reports.
function getDirectsNew($x=null,$y=null) {
	$result_isManager = mysql_query("SELECT managers_id FROM managers where users_id = '{$x}'");
	if (!$result_isManager) {
		$_SESSION['Msg'] = "Could not get determine if user is a manager. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$numRows = mysql_num_rows($result_isManager);
	if ( $numRows == '0' ) { // This user isn't listed as a manager.
		mysql_free_result($result_isManager);
		return '0';
	}
	$row_manager = mysql_fetch_array($result_isManager, MYSQL_ASSOC);
	mysql_free_result($result_isManager);
	$managers_id = $row_manager['managers_id'];
	// This user is a manager, so let's get all the users that report to them.
	$result_directs = mysql_query("SELECT users_id,userName FROM users 
	WHERE managers_id = '{$managers_id}' ORDER BY userName ASC");
	if (!$result_directs) {
		$_SESSION['Msg'] = "Could not get determine a users direct reports. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	$numDirects = mysql_num_rows($result_directs);
	if ( $numDirects == '0' ) { // This user has no direct reports.
		mysql_free_result($result_directs);
		return '0';
	}
	
	// Ok, they have some direct reports.
	$topLevel = $_SESSION['users_id']; // This is our top level. If we get back here, we don't need a back button.
	// echo '<br />'.$topLevel.'<br />';
	
	if ( $y != 'first' && $x != $topLevel ) {
		echo '<ul><li>';
		echo '<a href="#" title="'.$_SESSION['previous'].'" onclick="loadDirects('.$_SESSION['previous'].')">
		&lt;-&nbsp;Back</a><br />';
		echo '</li></ul>';
	} 
		
	echo '<ul>';
	while ( $row_directs = mysql_fetch_array($result_directs, MYSQL_ASSOC) ) {
		echo '<li>';
		$b = checkDirects($row_directs['users_id']);
		if ( $b == '1' ) { // If this user has at least one direct report, we need different formatting.
			echo '<a href="#" title="'.$row_directs['users_id'].'" 
			onclick="loadDirects('.$row_directs['users_id'].')">
			<img src="images/icons/arrow_down_blue.png" style="margin-top:5px;" border="0" /></a>&nbsp;&nbsp;';
			echo '<a href="user_reports.php?id='.$row_directs['users_id'].'">'.$row_directs['userName'].'</a>';
			echo '&nbsp;&nbsp;
			<a href="#" onclick="populateSubOrg(\''.$row_directs['userName'].'\');">
			<img src="images/icons/arrow_right.png" style="margin-top:5px;" border="0" /></a>';
			// This line below works, but was replaced in NG by the above function.
			// <a href="#" onclick="document.getElementById(\'suborgname\').value=\''.$row_directs['userName'].'\'">
		} else {
			echo '<a href="user_reports.php?id='.$row_directs['users_id'].'">'.$row_directs['userName'].'</a>';
		}
		echo '</li>';
		$_SESSION['previous'] = $x;
	}
	mysql_free_result($result_directs);
	echo '</ul>';
} // getDirectsNew


// Get a list of a user's direct reports, if they're a manager.
// Receives a users_id(x) that is the manager we're getting direct reports for.
// This function will be used to generate organizational reports for all users under a manager.
// We will return an array of users_id's that will be passed to one of the getHours functions.
// Returns an array of [users_id] on success and 0 if they're not a manager or don't have any direct reports.
function getDirectsOrg($x=null) {
	$result_isManager = mysql_query("SELECT managers_id FROM managers where users_id = '{$x}'");
	if (!$result_isManager) {
		$_SESSION['Msg'] = "Could not determine if user is a manager. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php?logoff");
		exit;
	}
	$numRows = mysql_num_rows($result_isManager);
	if ( $numRows == '0' ) { // This user isn't listed as a manager.
		mysql_free_result($result_isManager);
		return '0';
	}
	$row_manager = mysql_fetch_array($result_isManager, MYSQL_ASSOC);
	mysql_free_result($result_isManager);
	$managers_id = $row_manager['managers_id'];
	// This user is a manager, so let's get all the users that report to them.
	$result_directs = mysql_query("SELECT users_id FROM users WHERE managers_id = '{$managers_id}' ORDER BY userName ASC");
	if (!$result_directs) {
		$_SESSION['Msg'] = "Could not get determine a users direct reports. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	$numDirects = mysql_num_rows($result_directs);
	if ( $numDirects == '0' ) { // This user has no direct reports.
		mysql_free_result($result_directs);
		return '0';
	}
	
	while ( $row_directs = mysql_fetch_array($result_directs, MYSQL_ASSOC) ) {
		// Add this user to the array.
		$directsOrg[] = $row_directs['users_id'];
		$b = checkDirects($row_directs['users_id']);
		if ( $b == '1' ) { // If this user has at least one direct report, we need them as well.
			$directsOrg[] = getDirectsOrg($row_directs['users_id']);
			$directsOrg = array_flatten($directsOrg); // Flatten the array as it comes back.
		}
	}
	mysql_free_result($result_directs);
	return $directsOrg;
} // getDirectsOrg


/**
 * Flattens an array, or returns FALSE on fail.
 */
function array_flatten($array) {
  if (!is_array($array)) {
    return FALSE;
  }
  $result = array();
  foreach ($array as $key => $value) {
    if (is_array($value)) {
      $result = array_merge($result, array_flatten($value));
    }
    else {
      $result[$key] = $value;
    }
  }
  return $result;
}

// Send an email. Call like so:
// send_mail($from,$to,$subject,$body)
// NOTE: system must be configured with an outnbound MTA.
function send_mail($from,$to,$subject,$body) {
	ini_set('SMTP','localhost');
	$headers = '';
	$headers .= "From: $from\n";
	$headers .= "Reply-to: $from\n";
	$headers .= "Return-Path: $from\n";
	$headers .= "Message-ID: <" . md5(uniqid(time())) . "@" . $_SERVER['SERVER_NAME'] . ">\n";
	$headers .= "MIME-Version: 1.0\n";
	$headers .= "Date: " . date('r', time()) . "\n";
	// Include a standard email footer.
	$notice="\r\n\r\nDo not reply to this message. This is an automated email from an unattended email account. Thank you.";
	$body = $body.$notice;
	// Wrap the body at 70 characters.
	$body = wordwrap($body, 70);
	mail($to,$subject,$body,$headers);
}

/* backup the db OR just a table */
function backup_tables($tables = '*') {
  
  //get all of the tables
  if($tables == '*')
  {
    $tables = array();
    $result = mysql_query('SHOW TABLES');
    while($row = mysql_fetch_row($result))
    {
      $tables[] = $row[0];
    }
  }
  else
  {
    $tables = is_array($tables) ? $tables : explode(',',$tables);
  }
  
  //cycle through
  foreach($tables as $table)
  {
    $result = mysql_query('SELECT * FROM '.$table);
    $num_fields = mysql_num_fields($result);
    
    $return.= 'DROP TABLE IF EXISTS '.$table.';';
    $row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
    $return.= "\n\n".$row2[1].";\n\n";
    
    for ($i = 0; $i < $num_fields; $i++) 
    {
      while($row = mysql_fetch_row($result))
      {
        $return.= 'INSERT INTO '.$table.' VALUES(';
        for($j=0; $j<$num_fields; $j++) 
        {
          $row[$j] = addslashes($row[$j]);
          $row[$j] = ereg_replace("\n","\\n",$row[$j]);
          if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
          if ($j<($num_fields-1)) { $return.= ','; }
        }
        $return.= ");\n";
      }
    }
    $return.="\n\n\n";
  }
  
  //save file locally if desired.
  // NOTE: Windows rwx file permisssions must be granted for the user ISUR to the directory below.
  if ( !$handle = fopen(SERVER_ROOT.'admin/backups/db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql','w+') ) {
  	unset($_POST);
	$_SESSION['Msg'] = "Can not open file for writing. Contact the developer.";
	header("Location: redirect_admin.php?Url=/admin/admin.php");
	exit;
  }
  if ( fwrite($handle,$return) === FALSE ) {
  	unset($_POST);
	$_SESSION['Msg'] = "Can not write backup to file handle. Contact the developer.";
	header("Location: redirect_admin.php?Url=/admin/admin.php");
	exit;
  }
  
  fclose($handle);
  unset($_POST);
  $_SESSION['Msg'] = "Backup successful.";
  header("Location: redirect_admin.php?Url=/admin/backup_db.php");
  exit;
  
  // Or we can require the admin to download the file.
  /*
  $filename = 'db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql';
  header('Content-type: application/octet-stream');
  header("Content-Disposition: attachment; filename=".$filename."");
  print $return;
  exit;
  */
}

// Receives a users_id(x) and minHours value (y).
// Returns total number of hours for the user if they're delinquent, or -1 if they're OK.
function checkTwoWeek($x=null,$y=null){
	$total = 0.0;
	$minHours = $y*2; // This is a two week check, so we double $minHours.
	// Get this users weekStart.
	$result_weekStart = mysql_query("SELECT weekStart FROM profile WHERE users_id = '{$x}'");
	if (!$result_weekStart) {
		error_log("Could not get weekStart for user ".$x.". Continuing.\n", 3, "error.log");
		continue;
	}
	$row_weekStart = mysql_fetch_array($result_weekStart, MYSQL_ASSOC);
	mysql_free_result($result_weekStart);
	if ( $row_weekStart['weekStart'] == 'Monday' ) {
		$startDate = date("Y-m-d",(strtotime('-15 days')));
		$endDate = date("Y-m-d",(strtotime('-2 days')));
	} else {
		$startDate = date("Y-m-d",(strtotime('-16 days')));
		$endDate = date("Y-m-d",(strtotime('-3 days')));
	}
	$result_hours = mysql_query("SELECT SUM(hours) FROM time WHERE users_id = '{$x}' 
	AND ( date BETWEEN '{$startDate}' AND '{$endDate}' )");
	if (!$result_hours) {
		error_log("Could not get total hours for user ".$x.". Continuing.\n", 3, "error.log");
		continue;
	}
	$row_hours = mysql_fetch_row($result_hours);
	mysql_free_result($result_hours);
	$total += $row_hours[0];
	if ($total < $minHours ) { // Return their total hours for inclusion in the report.
		$row_hours=null;
		return $total;
	} else {
		$row_hours=null;
		$total=null;
		return '-1';
	}
}

// Get a list of a user's direct reports, if they're a manager.
// Recieves a users_id(x), and minHours (y).
// Returns a summary of delinquent users below the manager
// in the form of output showing the number of user below that
// manager that are delinquent on success and 0 if they're not a 
// manager or have no direct reports.

function getDirectsTwoWeek($x=null,$y=null) {
	$result_isManager = mysql_query("SELECT managers_id FROM managers where users_id = '{$x}'");
	if (!$result_isManager) {
		error_log("Could not connect to database to determine direct reports.\n", 3, "error.log");
		exit;
	}
	$x=null;
	$numRows = mysql_num_rows($result_isManager);
	if ( $numRows == '0' ) { // This user isn't listed as a manager.
		mysql_free_result($result_isManager);
		$numRows=null;
		return '0';
	}
	$numRows=null;
	$row_manager = mysql_fetch_array($result_isManager, MYSQL_ASSOC);
	mysql_free_result($result_isManager);
	$managers_id = $row_manager['managers_id'];
	$row_manager=null;
	// This user is a manager, so let's get all the users that report to them.
	$result_directsTmp = mysql_query("SELECT users_id,userName FROM users 
	WHERE managers_id = '{$managers_id}' ORDER BY userName ASC");
	if (!$result_directsTmp) {
		error_log("Could not connect to database to determine direct reports.\n", 3, "error.log");
		exit;
	}
	$managers_id=null;
	$numDirects = mysql_num_rows($result_directsTmp);
	if ( $numDirects == '0' ) { // This user has no direct reports.
		mysql_free_result($result_directsTmp);
		$numDirects=null;
		return '0';
	}
	$numDirects=null;
	$num_users = 0;
	$tmpTotal=null; 
	while ( $row_directsTmp = mysql_fetch_array($result_directsTmp, MYSQL_ASSOC) ) {
		$b = checkDirects($row_directsTmp['users_id']);
		if ( $b == '1' ) { // If this user has at least one direct report, call ourself.
			$b=null;
			$num_users += getDirectsTwoWeek($row_directsTmp['users_id'],$y);
			// $num_users += $num_usersTmp;
			// $num_usersTmp=null;
		} else {
			$tmpTotal = checkTwoWeek($row_directsTmp['users_id'],$y);
			$b=null;
			if ($tmpTotal != '-1') {
				$num_users++;
			}
			$tmpTotal=null;;
		}
	}
	mysql_free_result($result_directsTmp);
	$row_directsTmp=null;
	$num_usersTmp=null;
	$y=null;
	return $num_users;
} // getDirectsTwoWeek

// Receives a managers_id (x), their users_id, (y), and minHours (z).
// Produces a two week report for that manager including user-level and summary reports.
function genTwoWeek($x=null,$y=null,$z=null) {
	$minHours = $z;
	$result_managerInfo = mysql_query("SELECT userName,email FROM users WHERE users_id = '{$y}' AND userName != 'admin'");
	if (!$result_managerInfo) {
		error_log("Could not connect to database to determine manager info.\n", 3, "error.log");
		exit;
	}
	$row_managerInfo = mysql_fetch_array($result_managerInfo, MYSQL_ASSOC);
	mysql_free_result($result_managerInfo);
	$email = $row_managerInfo['email'];
	
	$result_directs = mysql_query("SELECT users_id,userName FROM users 
	WHERE managers_id = '{$x}' ORDER BY userName ASC");
	if (!$result_directs) {
		error_log("Could not connect to database to determine direct reports.\n", 3, "error.log");
		exit;
	}
	$numDirects = mysql_num_rows($result_directs);
	if ( $numDirects == '0' ) { // This user has no direct reports.
		mysql_free_result($result_directs);
		$numDirects=null;
		// return '0';
		exit;
	}
	$report = "\nUser level report for manager: ".$row_managerInfo['userName']."\n";
	$row_managerInfo=null;
	$tmpTotal = 0.0;	
	while ( $row_directs = mysql_fetch_array($result_directs, MYSQL_ASSOC) ) {
		// We want a user-level report for all of these users.
		$tmpTotal = checkTwoWeek($row_directs['users_id'],$minHours);
		if ($tmpTotal != '-1') {
			$report .= "User ".$row_directs['userName']." is behind in reporting their time.\n";
		}
		$tmpTotal = 0.0;
	} // $row_directs = mysql_fetch_array($result_directs, MYSQL_ASSOC)
	mysql_free_result($result_directs);
	$tmpTotal=null;
	$row_directs=null;
	
	
	// Now we go through $row_directs again and generate a summary report for all the managers below this one.
	$num_users = '0';
	$result_directs = mysql_query("SELECT users_id,userName FROM users 
	WHERE managers_id = '{$x}' ORDER BY userName ASC");
	if (!$result_directs) {
		error_log("Could not connect to database to determine direct reports.\n", 3, "error.log");
		exit;
	}
	$numDirects = mysql_num_rows($result_directs);
	if ( $numDirects == '0' ) { // This user has no direct reports.
		mysql_free_result($result_directs);
		$numDirects=null;
		// return '0';
		exit;
	}
	$report .= "\n";
	while ( $row_directs = mysql_fetch_array($result_directs, MYSQL_ASSOC) ) {
		$num_users = getDirectsTwoWeek($row_directs['users_id'],$minHours);
		if ($num_users != '0') {
			$report .= "Summary report for manager ".$row_directs['userName'].": ".$num_users; $report .= ( ($num_users > 1) ?  " are " : " is "); $report .= "behind on their time.\n";
		}
		$num_users = 0;
	}
	mysql_free_result($result_directs);
	$row_directs=null;
	$num_users=null;
	// print $report;
	if ($email != '') {
		send_mail('root@localhost',$email.': SE Time Tracker Two Week Report',$report);
	}
	$email=null;
}

function isEven($i) {
	return ($i % 2) == 0;
}
?>
