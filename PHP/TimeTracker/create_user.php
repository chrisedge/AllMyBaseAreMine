<?php
include("class.Htpasswd.php");

if (isset($_POST['submit'])) { // if form has been submitted
  /* check they filled in what they were supposed to and authenticate */
  if(!$_POST['uname'] | !$_POST['passwd']) {
     die('You did not fill in a required field.');
  }

$UserID = $_POST['uname'];
$Pass = $_POST['passwd'];

// authenticate.
$Htpasswd = new Htpasswd(".htpasswd");

if(!($Htpasswd->EXISTS)) {
  echo "Authentication Error <BR>\n";
  exit;
}

if ($Htpasswd->addUser($UserID,$Pass)) {
    echo "User Created <BR>\n";
    echo "<a href='index.php'>Return to login</a>";
    exit;
}
else {
  echo "Unable to create user: $Htpasswd->ERROR <BR> \n";
  exit;
}
?>

<html>
<head>
<title>Create Login Account</title>
</head>
<body>

<?php

}

else {    // if form hasn't been submitted

?>
<div style="text-align: center;">
<h1>Create Login Account</h1>
<form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">
<table align="center" border="1" cellspacing="0" cellpadding="3">
<tr><td>Username:</td><td>
<input type="text" name="uname" maxlength="40">
</td></tr>
<tr><td>Password:</td><td>
<input type="password" name="passwd" maxlength="50">
</td></tr>
<tr><td colspan="2" align="right">
<input type="submit" name="submit" value="Add User">
</td></tr>
</table>
</form>
</div>
<?php
}
?>
</body>
</html>

