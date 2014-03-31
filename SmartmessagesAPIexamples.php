<?php
/**
 * Smartmessages API example script
 *
 * Example script demonstrating all available Smartmessages API calls using the Smartmessages API wrapper class
 * Note that this script will not work as is - you will need to substitute your own login ID, password, API key and test list IDs in the variables below (or in an override file)
 * @author Marcus Bointon <marcus@synchromedia.co.uk>
 * @copyright 2014 Synchromedia Limited
 * @link https://www.smartmessages.net/ Smartmessages mailing lists management
 * @link http://wiki.smartmessages.net/ Smartmessages user and developer documentation
 * @version $Id: SmartmessagesAPIexamples.phps 2117 2010-08-13 15:32:20Z marcus $
 */

/**
 * Include API class
 */
require 'SmartmessagesAPI.class.php';

/**
 * @var string $baseurl the root location of all API calls
 */
$baseurl = 'https://www.smartmessages.net/api/';
/**
 * @var string $user the user name you use to log into the smartmessages account
 */
$user = '';
/**
 * @var string $pass the password you use to log into the smartmessages account
 */
$pass = '';
/**
 * @var string $apikey The API key that appears on the account page in your smartmessages account
 */
$apikey = '';
/**
 * @var string $endpoint The access location that the login call tells you to use for subsequent requests
 * Will usually be the same as the initial access URL, but may change in future, so please use it
 */
$endpoint = '';
/**
 * @var integer $testlistid A list id to test with as found on the contacts page of your smartmessages account
 * or in response to the getlists API call
 */
$testlistid = 0;
//You can set the above properties in this included file in order not to pollute this example code with real data!
if (file_exists('testsettings.php')) {
    include 'testsettings.php';
}

try {
    $smartmessages = new SmartmessagesAPI;
    //Login
    $smartmessages->login($user, $pass, $apikey, $baseurl);

    //Create a campaign folder
    $cid = $smartmessages->addcampaign('api test campaign');

    //$sm = new SmartmessagesAPI(false); //Create an API instance
    $smartmessages = new SmartmessagesAPI(true); //Create an API instance in debug mode (will produce debug output)
    //$smartmessages = new SmartmessagesAPI;
    //Log in
    $smartmessages->login($user, $pass, $apikey, $baseurl);

    //Manage campaign folders
    $cid = $smartmessages->addcampaign('api test campaign');
    if ($cid > 0) {
        $smartmessages->updatecampaign($cid, 'updated api campaign');
        $smartmessages->deletecampaign($cid);
    }
    $campaigns = $smartmessages->getcampaigns();
    $mailshots = $smartmessages->getcampaignmailshots(key($campaigns));

    //Manage mailing lists
    $mlid = $smartmessages->addlist('new test', 'description', true);
    $lists = $smartmessages->getlists(true); //Get all lists, not just visible ones
    if ($mlid > 0) {
        $r = $smartmessages->updatelist($mlid, 'updated new test', 'updated description', false);
        $smartmessages->deletelist($mlid);
    }
    echo "<p>Mailing lists:</p>\n<ul>\n";
    foreach ($lists as $list) {
        echo "<li>List ID {$list['id']}, {$list['name']}: {$list['description']}</li>\n";
    }
    echo "</ul>\n";

    //Manage templates
    $templates = $smartmessages->gettemplates(true);
    $smartmessages->gettemplate(key($templates));
    //Create a new template
    $template1_id = $smartmessages->addtemplate(
        'apitest',
        '<html><head></head><body><h1>HTML</h1></body></html>',
        'plain',
        'subject',
        'test template',
        false,
        'en',
        false
    );
    //Create a new template by loading it from a URL
    $template2_id = $smartmessages->addtemplatefromurl('apitest url', 'http://www.google.com/', 'mysubject', 'Grabbed from google');
    //Update the template we created earlier by adding an image and asking it to import the image and rewrite its URL
    $template1_id = $smartmessages->updatetemplate(
        $template1_id,
        'apitest',
        '<html><head></head><body><h1>HTML <img alt="Butterfly" src="http://www.smartmessages.net/images/butterfly.png"></h1></body></html>',
        'plain',
        'subject',
        'test template',
        false,
        'en',
        true
    );
    $smartmessages->gettemplate($template1_id);
    $smartmessages->gettemplate($template2_id);
    //Clean up
    $smartmessages->deletetemplate($template2_id);
    $smartmessages->deletetemplate($template1_id);

    //Get / Set Callback URL
    $url = $smartmessages->getcallbackurl();
    echo "<p>Callback URL is $url</p>\n";
    try {
        $smartmessages->setcallbackurl('http://www.example.com/callback.php'); //Valid URL test
        $smartmessages->setcallbackurl('blah'); //Invalid URL test
    } catch (Exception $e) {
        echo "</p>Callback URL set failed (as expected)</p>";
    }
    $url = $smartmessages->getcallbackurl();
    echo "<p>Callback URL is now $url</p>\n";

    //Email address validation
    $address = 'rubbish';
    if ($smartmessages->validateaddress($address)) {
        echo "'$address' is valid<br />";
    } else {
        echo "'$address' is not valid<br />";
    }
    $address = 'valid@example.com';
    if ($smartmessages->validateaddress($address)) {
        echo "'$address' is valid<br />";
    } else {
        echo "'$address' is not valid<br />";
    }

    //Subscribe
    try {
        $smartmessages->subscribe($user, $testlistid, 'The Dude');
        echo "<p>Subscribed OK</p>";
        $smartmessages->subscribe($user, $testlistid, 'The Dude'); //This will fail as it's already subscribed
        echo "<p>Subscribed OK</p>";
    } catch (Exception $e) {
        echo "</p>Subscribe failed (as expected)</p>";
    }

    //Unsubscribe
    try {
        $smartmessages->unsubscribe($user, $testlistid);
        echo "<p>Unsubscribed OK</p>";
        $smartmessages->unsubscribe($user, $testlistid); //This will fail as it's already unsubscribed
        echo "<p>Unsubscribed OK</p>";
    } catch (Exception $e) {
        echo "</p>Unsubscribe failed (as expected)</p>";
    }

    //Get unsubscribes
    $unsubs = $smartmessages->getlistunsubs($testlistid);
    echo "<p>Unsubscribes:</p>\n<ul>\n";
    foreach ($unsubs as $unsub) {
        echo "<li>{$unsub['address']}</li>\n";
    }
    echo "</ul>\n";

    //Get spam reporters
    $spamreporters = $smartmessages->getspamreporters();
    echo "<p>Spam reporters:</p>\n<ul>\n";
    foreach ($spamreporters as $spamreporter) {
        echo "<li>$spamreporter</li>\n";
    }
    echo "</ul>\n";
    //Get/Set user info
    $smartmessages->getuserinfo($user);
    $smartmessages->setuserinfo(
        $user,
        array(
            'firstname' => 'Joe',
            'lastname' => 'User',
            'country' => 'FR',
            'jobtitle' => 'Chief Techie Lurker',
            'custom10' => date('Y-m-d H:i:s'),
            'custom3' => '1 2 3'
        )
    );
    $smartmessages->getuserinfo($user);

    //Get/Set import order
    $fo = $smartmessages->getfieldorder();
    $smartmessages->setfieldorder(array('emailaddress', 'firstname', 'lastname', 'custom1'));
    $nfo = $smartmessages->getfieldorder();
    echo "<p>Field order:</p>\n<ul>\n";
    foreach ($nfo as $f) {
        echo "<li>$f</li>\n";
    }
    echo "</ul>\n";
    $smartmessages->setfieldorder($fo); //Reset to previous order

    //Get info about pending list uploads
    $uploads = $smartmessages->getuploads($testlistid);
    echo "<p>Uploads:</p>\n<ul>\n";
    foreach ($uploads as $upload) {
        echo "<li>" . var_dump($upload) . "</li>\n";
    }
    echo "</ul>\n";

    //Upload a mailing list
    $uploadid = $smartmessages->uploadlist($testlistid, 'testlist.csv', 'API upload', true, true, true);
    if ((integer)$uploadid > 0) {
        echo "Uploaded list OK<br />";
        //Get upload info
        echo "<p>Upload info:</p>\n";
        $uploadinfo = $smartmessages->getuploadinfo($testlistid, (integer)$uploadid);
        var_dump($uploadinfo);
    } else {
        echo "List upload failed<br />";
    }

    //Get the complete contents of a mailing list as a CSV
    $list = $smartmessages->getlist($testlistid, true);
    var_dump($list);

    //Get unsubscribes
    $unsubs = $smartmessages->getlistunsubs($testlistid);
    echo "<p>Unsubscribes:</p>\n<ul>\n";
    foreach ($unsubs as $unsub) {
        echo "<li>{$unsub['address']}</li>\n";
    }
    echo "</ul>\n";

    //Remove all subscribers from a list
    //Disabled in this example to avoid accidental data loss
    //$c = $sm->emptylist($testlistid);
    //echo "Emptied list, deleting $c subscriptions<br />";

    //Send a mailshot
    $mailshotid = 0;
    //Note that you explicit permission to use this function; it's disabled by default.
    //This line is commented out in this example code as it's dangerous to call it without being sure of your parameters
    //- this single line of code could cause hundreds of thousands of messages to be sent!
    //$mailshotid = $sm->sendmailshot($templates[0], $testlistid, 'API Send', $campaigns[0], 'API test subject', 'user@example.com', 'API Sender', 'subscriber@example.com', 'now', false, array());

    //Get data relating to a mailshot as CSVs
    if ($mailshotid > 0) {
        $bounces = $smartmessages->getmailshotbounces($mailshotid, true);
        var_dump($bounces);
        $opens = $smartmessages->getmailshotopens($mailshotid, true);
        var_dump($opens);
        $clicks = $smartmessages->getmailshotclicks($mailshotid, true);
        var_dump($clicks);
        $unsubs = $smartmessages->getmailshotunsubs($mailshotid, true);
        var_dump($unsubs);
    }

    //Log out
    $smartmessages->logout();
} catch (SmartmessagesAPIException $e) {
    echo '<h1>Exception caught</h1><p>An error (' . $e->getCode() . ') occurred: ' . $e->getMessage() . "</p>\n";
}
