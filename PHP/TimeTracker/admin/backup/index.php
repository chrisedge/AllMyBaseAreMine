<?php
error_reporting (E_ALL ^ E_NOTICE);

session_name('seAdmin');
session_set_cookie_params(2*7*24*60*60);
session_start();

if ( !( isset($_SESSION['admin_id']) ) || !( isset($_SESSION['users_id']) ) ){
	// Their credentials are not valid, or they logged off.
	$_SESSION = array();
    session_destroy();
    header("Location: /admin/index.php?logoff");
    exit;
}

if (!@ob_start("ob_gzhandler")) @ob_start();
include ('inc/functions.php');
$page=(isset($_GET['page'])) ? $_GET['page'] : 'main.php';
if (!file_exists("./work/config/mysqldumper.php"))
{
	header("location: install.php");
	ob_end_flush();
	die();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
        "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="Author" content="Daniel Schlichtholz">
<title>MySQLDumper</title>
</head>

<frameset border=0 cols="190,*">
	<frame name="MySQL_Dumper_menu" src="menu.php" scrolling="no" noresize
		frameborder="0" marginwidth="0" marginheight="0">
	<frame name="MySQL_Dumper_content" src="<?php
	echo $page;
	?>"
		scrolling="auto" frameborder="0" marginwidth="0" marginheight="0">
</frameset><noframes></noframes>
</html>
<?php
ob_end_flush();