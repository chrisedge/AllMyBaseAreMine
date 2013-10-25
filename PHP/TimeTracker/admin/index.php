<?php
define('INCLUDE_CHECK', true);

require '../connect.php';
require '../functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seAdmin');
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

if ( isset($_POST['submit']) == 'Login') {
    // Checking whether the Login form has been submitted
	// $_POST=from_array($_POST); // Massage the input with from_array().
    $err = array();
    // Will hold our errors

    if (!$_POST['userName'] || !$_POST['password'])
        $err[] = 'All the fields must be filled in!';

    if ( isset($err) && count($err) == 0 ) {
        $_POST['userName'] = mysql_real_escape_string($_POST['userName']);
        $_POST['password'] = mysql_real_escape_string($_POST['password']);
		// $_POST['lastIP'] = mysql_real_escape_string($_POST['lastIP']);
        // Escaping all input data

		$row = mysql_fetch_assoc(mysql_query(
			   "SELECT users_id,userName,firstName,lastName,managers_id,hasProfile FROM users WHERE userName='{$_POST['userName']}' AND password='" . md5($_POST['password']) . "'"));

        if ($row['userName']) { // Valid userName and password.
            $users_id = $row['users_id'];
			// Check the admin table.
			$row_admin = mysql_fetch_assoc(mysql_query("SELECT admin_id,lastIP FROM admin WHERE users_id = '{$users_id}'"));
			if ( $row_admin['admin_id'] ) { // Valid admin.
				$_SESSION['admin_id'] = $row_admin['admin_id'];
				$_SESSION['userName'] = $row['userName'];
            	$_SESSION['users_id'] = $row['users_id'];
				$_SESSION['firstName'] = $row['firstName'];
				$_SESSION['lastName'] = $row['lastName'];
				$_SESSION['lastIP'] = $row_admin['lastIP'];
				
				// Get database globals and store them as session variables. These can be modified by the admin.
				$result_globals = mysql_query("SELECT name,value FROM global_vars");
				if (!$result_globals) {
					$_SESSION['Msg'] = "Unable to obtain global variables. Contact the administrator.";
					//echo 'here'; exit;
					header("Location: redirect.php?Url=index.php?logoff");
					exit;
				}
				while ( $row_globals = mysql_fetch_array($result_globals, MYSQL_ASSOC) ) {
					$_SESSION[$row_globals['name']] = $row_globals['value'];
				}
				mysql_free_result($result_globals);
				
				// Now, update lastIP with the currentIP submitted during login.
				if (!mysql_query( "UPDATE admin SET lastIP='{$_POST['currentIP']}' 
				WHERE admin_id='{$_SESSION['admin_id']}'" ))
					$err[] = 'Unable to update current IP address.';
				
				//echo 'here 1'; exit;
				header("Location: admin.php");
				exit;
			} else {
				$err[] = 'You are not listed as an administrator.';
			}
		} // $row['userName']
	} else {
		$err[] = 'Wrong username and/or password!';
	} // !count($err)
	
    if ($err) {
        $_SESSION['msg']['login-err'] = implode('<br />', $err);
    	// Save the error messages in the session
    	//echo 'here 2'; exit;
		header("Location: index.php");
    	exit;
	}

} // isset($_POST['submit']) == 'Login'

?>

<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
     
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title>Login</title>
  <link type="text/css" href="../css/setime.css" rel="stylesheet" />
</head>
<body>
<div style="float:left;font-family:sans-serif;font-size:smaller">
  <?php
  /*
  <img style="float:left;" src="logo.gif">
  */
  ?>
  <br><br><br><br><br>
  <a href="#">Help</a></div>
  <div style="float:right;text-align:center; background-color:white; border-left:solid #808080;width:85%">
  <br>
  <h1>Admin Login</h1>
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
    
    <input type="hidden" value="<?php
					  if ( isset($_SERVER["REMOTE_ADDR"]) ) {
						  echo $_SERVER["REMOTE_ADDR"];
					  } else if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ) {
						  echo $_SERVER["HTTP_X_FORWARDED_FOR"];
					  } else if ( isset($_SERVER["HTTP_CLIENT_IP"]) ) {
						  echo $_SERVER["HTTP_CLIENT_IP"];
					  }
					  ?>" name="currentIP"  />
                      
  </form>
  <br />
  </div>
  <br />&nbsp
  </div>
</body>
</html>