<?php
/**
 * Copy this file and rename it to 'testsettings.php', change the values to match your Smartmessages account,
 * and it will be picked up by the example script.
 */

/**
 * The initial root URL for API calls
 */
$baseurl = 'https://www.smartmessages.net/api/';

/**
 * The username you use to log into your Smartmessages account
 */
$user = 'user@example.com';

/**
 * The password you use to log into your Smartmessages account
 */
$pass = 'password';

/**
 * The API key that appears on the account page in your Smartmessages account
 */
$apikey = '0123456789abcdefghijkl';

/**
 * The access location that the login call tells you to use for subsequent requests
 * Will usually be the same as the initial access URL, but may change in future, so please use it
 */
$endpoint = '';

/**
 * A list id to send mailshots to, as found on the contacts page
 * of your Smartmessages account, or in response to the gettestlist API call
 */
$testlistid = 0;
