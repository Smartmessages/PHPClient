<?php
/**
 * A simple example of connecting to the Smartmessages API and calling a function.
 */
//Load the class, or use composer's autoloader
require 'src/Smartmessages/Client.php';

//See testsettings-dist.php
if (file_exists('testsettings.php')) {
    include 'testsettings.php';
}

try {
    $sm = new Smartmessages\Client(true);
    //Login
    $sm->login($user, $pass, $apikey, $baseurl);
    $tl = $sm->getTestList();
    //Log out
    $sm->logout();
} catch (Smartmessages\Exception $e) {
    echo "<h1>Exception caught</h1>\n<p>An error (" . $e->getCode() . ') occurred: ' . $e->getMessage() . "</p>\n";
}
