<?php

if(!defined('INCLUDE_CHECK')) die('You are not allowed to execute this file directly');

/**
 * Pretty sure I pulled this off of php.net somewhere.
 * It's super deluxe and useful.
 * 
 * @function get_realip()
 * @return String A user's alleged IP address
 * 
 */


function get_realip()
{
    // No IP found (will be overwritten by for
    // if any IP is found behind a firewall)
    $ip = FALSE;
   
    // If HTTP_CLIENT_IP is set, then give it priority
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }
   
    // User is behind a proxy and check that we discard RFC1918 IP addresses
    // if they are behind a proxy then only figure out which IP belongs to the
    // user.
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

        // Put the IP's into an array which we shall work with shortly.
        $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }

        for ($i = 0; $i < count($ips); $i++) {
            // Skip RFC 1918 IP's 10.0.0.0/8, 172.16.0.0/12 and
            // 192.168.0.0/16
            if (!preg_match('/^(?:10|172\.(?:1[6-9]|2\d|3[01])|192\.168)\./', $ips[$i])) {
                if (version_compare(phpversion(), "5.0.0", ">=")) {
                    if (ip2long($ips[$i]) != false) {
                        $ip = $ips[$i];
                        break;
                    }
                } else {
                    if (ip2long($ips[$i]) != -1) {
                        $ip = $ips[$i];
                        break;
                    }
                }
            }
        }
    }

    // Return with the found IP or the remote address
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}
?>
