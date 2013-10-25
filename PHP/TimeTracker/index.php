<?php
define('INCLUDE_CHECK', true);

require 'connect.php';
require 'functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seLogin');
// Starting the session

session_set_cookie_params(2 * 7 * 24 * 60 * 60);
// Making the cookie live for 2 weeks

session_start();

if (isset($_GET['logoff'])) {
    $_SESSION = array();
    session_destroy();

    header("Location: index.php");
    exit;
}

/**
 * SSO:
 * if ( isset($_SERVER['SSO_USER_ID']) != '') {
 * ... and then later on....
 * $row = mysql_fetch_assoc(mysql_query(
			   "SELECT users_id,userName,firstName,lastName,managers_id,hasProfile FROM users WHERE userName='{$_SERVER['SSO_USER_ID']}'"));
 */

if ( isset($_POST['submit']) == 'Login') {
    // Checking whether the Login form has been submitted
	// $_POST=from_array($_POST); // Massage the input with from_array().
    $err = array();
    // Will hold our errors

    if (!$_POST['userName'] || !$_POST['password'])
        $err[] = 'All the fields must be filled in!';

    if (!count($err)) {
        $_POST['userName'] = mysql_real_escape_string($_POST['userName']);
        $_POST['password'] = mysql_real_escape_string($_POST['password']);

        // Escaping all input data

        $row = mysql_fetch_assoc(mysql_query(
			   "SELECT users_id,userName,firstName,lastName,managers_id,hasProfile FROM users WHERE userName='{$_POST['userName']}' AND password='" . md5($_POST['password']) . "'"));

        if ($row['userName']) {
			// If everything is OK login
			// Store some data in the session
            $_SESSION['userName'] = $row['userName'];
            $_SESSION['users_id'] = $row['users_id'];
			$_SESSION['firstName'] = $row['firstName'];
			$_SESSION['lastName'] = $row['lastName'];
			$_SESSION['managers_id'] = $row['managers_id'];
			$_SESSION=from_array($_SESSION); // Massage the input with from_array().
			$users_id = $_SESSION['users_id'];
			
			// Get some of their profile information.
			$result_profile = mysql_query("SELECT positions_id FROM profile WHERE users_id = '{$users_id}'");
			if ( !$result_profile ) {
				$_SESSION['Msg'] = "Could not connect to database to determine position. Contact the administrator.";
				header("Location: redirect.php?Url=index.php?logoff");
				exit;
			}
			$num_rows_profile = mysql_num_rows($result_profile);
			if ( $num_rows_profile == 1 ) {
				$row_profile = mysql_fetch_array($result_profile, MYSQL_ASSOC);
				mysql_free_result($result_profile);
				$_SESSION['positions_id'] = $row_profile['positions_id'];
			}

			// Get database globals and store them as session variables. These can be modified by the admin.
			$result_globals = mysql_query("SELECT name,value FROM global_vars");
			if (!$result_globals) {
				$_SESSION['Msg'] = "Unable to obtain global variables. Contact the administrator.";
				header("Location: redirect.php?Url=index.php?logoff");
				exit;
			}
			while ( $row_globals = mysql_fetch_array($result_globals, MYSQL_ASSOC) ) {
				$_SESSION[$row_globals['name']] = $row_globals['value'];
			}
			mysql_free_result($result_globals);
			
			// Check to see if they've changed their password.
			$result_password = mysql_query("SELECT passwordChange FROM users where users_id = '{$users_id}'");
			if (!$result_password) {
				$_SESSION['Msg'] = "Could not connect to database to determine passwordChange. Contact the administrator.";
				header("Location: redirect.php?Url=index.php?logoff");
				exit;
			}
			$row_password = mysql_fetch_assoc($result_password);
			if ( $row_password['passwordChange'] == '' ) {
				header("Location: passwordNG.php");
				exit;
			}
			
			// Check to see if they're a manager.
			$result_isManager = mysql_query( "SELECT managers_id FROM managers WHERE users_id = '{$users_id}'" );
			if (!$result_isManager) {
				$_SESSION['Msg'] = "Could not connect to database to determine isManager. Contact the administrator.";
				header("Location: redirect.php?Url=index.php?logoff");
				exit;
			}
			$num_rows = mysql_num_rows($result_isManager);
			
			if ( $num_rows == 0 ) {
				$_SESSION['is_manager'] = '0'; 
			} else {
				// Set their managers_id from the managers table in this variable for use later.
				$row_managersID = mysql_fetch_array($result_isManager, MYSQL_ASSOC);
				$_SESSION['is_manager'] = $row_managersID['managers_id'];
			}
			mysql_free_result($result_isManager);
			// $_SESSION['is_manager'] = '0'; // DEBUG
			
			// Check to see if they have delegated permissions.
			$result_delegate = mysql_query("SELECT * FROM delegation WHERE users_id = '{$_SESSION['users_id']}'");
			if (!$result_delegate) {
				$_SESSION['Msg'] = "Could not connect to database to determine delegation. Contact the administrator.";
				header("Location: redirect.php?Url=index.php?logoff");
				exit;
			}
			$num_rows_delegate = mysql_num_rows($result_delegate);
			mysql_free_result($result_delegate);
			
			if ( !$row['hasProfile'] ) { // They haven't completed their user profile interview.
				header("Location: profileNG.php");
			} else if ( $num_rows_delegate != 0 ) { // Send them to a page where they can decide who they want to be.
				header("Location: delegate.php");
			} else {
				header("Location: landing.php");
			}
        }
        else
            $err[] = 'Wrong username and/or password!';
    }

    if ($err) {
        $_SESSION['msg']['login-err'] = implode('<br />', $err);
    	// Save the error messages in the session
    	header("Location: index.php");
    	exit;
	}
}

?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
     
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title>Login</title>
  <link type="text/css" href="css/setime.css" rel="stylesheet" />
</head>
<body>
<div style="float:left;font-family:sans-serif;font-size:smaller">
  
  <img style="float:left;" src="images/logo.gif">
  
  <br><br><br><br><br>
  <a href="#">Help</a></div>
  <div style="float:right;text-align:center; background-color:white; border-left:solid #808080;width:85%">
  <br>
  <h1>Login</h1>
  <?php
  if ( !isset($_SESSION['users_id']) ) { // They're not logged in.
		  if ( isset($_SESSION['msg']['login-err']) ) {
			  echo '<div class="err">' . $_SESSION['msg']['login-err'] . '</div>';
			  unset($_SESSION['msg']['login-err']);
		  }
  }
  ?>
  <form action="index.php" method="post">
    <table align="center" border="1" cellspacing="0" cellpadding="3">
      <tr>
        <td>Username:</td>
        <td><input type="text" name="userName" maxlength="16" placeholder="myusername"></td>
      </tr>
      <tr>
        <td>Password:</td>
        <td><input type="password" name="password" maxlength="16" placeholder="******"></td>
      </tr>
      <tr>
        <td colspan="2" align="center"><input type="submit" name="submit" value="Login"></td>
      </tr>
    </table>
  </form>
  
  <br />
  </div>
  <br />&nbsp
  </div>
</body>
</html>

<?php
// }
?> 
