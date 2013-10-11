<?php
if (!defined('INCLUDE_CHECK'))
    die('You are not allowed to execute this file directly');

/**
 * 
 * @function is_success()
 * 
 * Generates a random number between 0 and 100,000.
 * The probability of something happening is then
 * multiplied by 100 (since it's a percentage).
 * If the random number is less than or equal to
 * the probability of the event happening, then this
 * event was a success, and the function returns true.
 * 
 * I culled this from somewhere, and then bastardized it.
 */

/**
 * 
 * @param Int $probability The chance that something will occur as a percentage.
 * @return Bool
 * @author Someone Else If it's you, thanks. I can't remember where I came across this.
 * 
 */
function is_success($probability = null) {
    // Generate a random number between 0 and 100,000
    $random_number = rand(0, 100000);
    // Multiply the chance of something happening
    // by 100. This allows for probabilities that
    // aren't rounded. Example 10.256% or 35.259%
    $is_probable = $probability * 100;
    // If the random number is less than or equal
    // to the probability of the event happening
    // we know that this instance was a success.
    if ($random_number <= $is_probable) {
        return true;
    } else {
        return false;
    }
}

?>