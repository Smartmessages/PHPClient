<?php
/**
 * A simple example of connecting to the Smartmessages API and calling a function.
 */
//Load composer's autoloader
require 'vendor/autoload.php';

//See testsettings-dist.php
if (file_exists('testsettings.php')) {
    include 'testsettings.php';
}

try {
    $sm = new Smartmessages\Client(true);
    //Login
    $sm->login($user, $pass, $apikey, $baseurl);
    //$tl = $sm->getTestList();
    $p = $sm->ping();
    //Log out happens automatically
} catch (Smartmessages\Exception $e) {
    echo "#Exception caught:\nAn error (", $e->getCode(), ') occurred: ', $e->getMessage(), "\n";
}
