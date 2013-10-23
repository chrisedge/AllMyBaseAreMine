<?php
/**
 * Template for PDO connection.
 */

if (!defined('INCLUDE_CHECK'))
    die('You are not allowed to execute this file directly');


$db_host		= '127.0.0.1';
$db_port                = '8889';
$db_user		= 'user';
$db_pass		= 'password';
$db_database            = 'db_name';
$dsn = 'mysql:host='.$db_host.';port='.$db_port.';dbname='.$db_database;

try {
    $dbh = new PDO($dsn,$db_user,$db_pass, array(
        PDO::ATTR_PERSISTENT => true    // cache the connection credentials
    ));
    
    $dbh->exec('SET CHARACTER SET utf8');
    
} catch (PDOException $e) {
    if(defined('DEBUG'))
        debug("PDO Error!: " .$e->getMessage());
    die();
}
?>
