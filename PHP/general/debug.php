<?php

if (!defined('INCLUDE_CHECK'))
    die('You are not allowed to execute this file directly');

/**
 * Establish a global debug flag for debugging. Utilizies
 * file_put_contents to php://stderr which Apache's mod_php
 * uses to write to /var/log/apache2/error.log
 */

/**
 * @param Mixed $message The message to printed into the error log.
 * @return Bool Number of bytes written on success, FALSE otherwise.
 * @author Chris Edge <chris@leftofzero.com>
 * 
 */

define('DEBUG', true);

function debug($message=null){
    $debugFP = 'php://stderr';
    // We want to format the header for our debug message to look like so:
    // [Tue Mar 05 20:46:10 2013] [debug] [filename $filename] Debug: $message
    date_default_timezone_set('UTC');
    $now = date("D M j G:i:s Y");
    $debugMessage = '['.$now.'] [debug] [filename '.$_SERVER['SCRIPT_FILENAME'].'] Debug: '.$message."\n";
    file_put_contents($debugFP, $debugMessage);
    return;
}

?>
