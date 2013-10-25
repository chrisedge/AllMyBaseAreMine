<?php
define('INCLUDE_CHECK', true);
error_reporting (E_ALL ^ E_NOTICE);

require '../connect.php';
require '../functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seAdmin');
session_set_cookie_params(2*7*24*60*60);
session_start();

if ( isset($_GET['logoff']) || !(isset($_SESSION['Msg'])) || !(isset($_GET['Url'])) ){
    // echo 'SESSION users_id: ' . $_SESSION['users_id']; 
	$_SESSION = array();
    session_destroy();

    echo ('You are not allowed to execute this file directly.');
	header("Location: index.php");
    exit;
}

$Msg = $_SESSION['Msg'];
$Url = $_GET['Url'];

?>

<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php echo $_SESSION['TITLE']; ?></title>
  <script type="text/javascript" language="javascript" src="/js/setime.js"></script>
  <link type="text/css" href="/css/setime.css" rel="stylesheet" />

</head>

<body onLoad="alertAndReturn('<?php echo $Msg; ?>','<?php echo $Url; ?>')">
</body>
</html>


