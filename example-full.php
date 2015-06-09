<?php
/**
 * Smartmessages API example script.
 *
 * Example script demonstrating all available Smartmessages API calls using the Smartmessages API wrapper class.
 * Note that this script will not work as is - you will need to substitute your own login ID, password, API key
 * and test list IDs in the variables below (or in an override file)
 * @author Marcus Bointon <marcus@synchromedia.co.uk>
 * @copyright 2015 Synchromedia Limited
 * @link https://info.smartmessages.net/ Smartmessages mailing lists management
 * @link https://wiki.smartmessages.net/ Smartmessages user and developer documentation
 */

/**
 * Include API client class if you're not using composer's autoloader
 */
require 'src/Smartmessages/Client.php';

/**
 * @var string $baseurl The initial root URL of API calls
 */
$baseurl = 'https://www.smartmessages.net/api/';
/**
 * @var string $user The user name (email address) you use to log into your Smartmessages account
 */
$user = '';
/**
 * @var string $pass The password you use to log into your Smartmessages account
 */
$pass = '';
/**
 * @var string $apikey The API key that appears on the account page in your Smartmessages account
 */
$apikey = '';
/**
 * @var string $endpoint The access location that the login call tells you to use for subsequent requests
 * Will usually be the same as the initial access URL, but may change in future, so please use it
 */
$endpoint = '';
/**
 * @var integer $testlistid A list id to test with, as found on the contacts page of your Smartmessages account
 * or in response to the getLists API call
 */
$testlistid = 0;

//You can set the above properties in this included file in order not to pollute this example code with real data!
if (file_exists('testsettings.php')) {
    include 'testsettings.php';
}

try {
    //Create an API instance in debug mode (will produce debug output)
    $smartmessages = new Smartmessages\Client(true);

    //Login
    $smartmessages->login($user, $pass, $apikey, $baseurl);

    //Manage campaign folders
    $cid = $smartmessages->addCampaign('api test campaign');
    if ($cid > 0) {
        $smartmessages->updateCampaign($cid, 'updated api campaign');
        $smartmessages->deleteCampaign($cid);
    }
    $campaigns = $smartmessages->getCampaigns();
    $mailshots = $smartmessages->getCampaignMailshots(key($campaigns));

    //Manage mailing lists
    $tl = $smartmessages->getTestList();
    echo "<p>Test list: id: {$tl['id']}, name: {$tl['name']}: description: {$tl['description']}</p>";
    $mlid = $smartmessages->addList('new test', 'description', true);
    $lists = $smartmessages->getLists(true); //Get all lists, not just visible ones
    if ($mlid > 0) {
        $r = $smartmessages->updateList($mlid, 'updated new test', 'updated description', false);
        $smartmessages->deleteList($mlid);
    }
    echo "<p>Mailing lists:</p>\n<ul>\n";
    foreach ($lists as $list) {
        echo "<li>List ID {$list['id']}, {$list['name']}: {$list['description']}</li>\n";
    }
    echo "</ul>\n";

    //Manage templates
    $templates = $smartmessages->getTemplates(true);
    $smartmessages->getTemplate(key($templates));
    //Create a new template
    $template1_id = $smartmessages->addTemplate(
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
    $template2_id = $smartmessages->addTemplateFromURL(
        'apitest url',
        'http://www.google.com/',
        'mysubject',
        'Grabbed from google'
    );
    //Update the template we created earlier by adding an image and asking it to import the image and rewrite its URL
    $template1_id = $smartmessages->updateTemplate(
        $template1_id,
        'apitest',
        '<html><head></head><body>'.
        '<h1>HTML <img alt="Butterfly" src="http://www.smartmessages.net/images/butterfly.png">'.
        '</h1></body></html>',
        'plain',
        'subject',
        'test template',
        false,
        'en',
        true
    );
    $smartmessages->getTemplate($template1_id);
    $smartmessages->getTemplate($template2_id);
    //Clean up
    $smartmessages->deleteTemplate($template2_id);
    $smartmessages->deleteTemplate($template1_id);

    //Get / Set Callback URL
    $url = $smartmessages->getCallbackURL();
    echo "<p>Callback URL is $url</p>\n";
    try {
        $smartmessages->setCallbackURL('http://www.example.com/callback.php'); //Valid URL test
        $smartmessages->setCallbackURL('blah'); //Invalid URL test
    } catch (Smartmessages\ParameterException $e) {
        echo "</p>Callback URL set failed (as expected)</p>";
    }
    $url = $smartmessages->getCallbackURL();
    echo "<p>Callback URL is now $url</p>\n";

    //Email address validation
    $address = 'rubbish';
    if ($smartmessages->validateAddress($address)) {
        echo "'$address' is valid<br />";
    } else {
        echo "'$address' is not valid<br />";
    }
    $address = 'valid@example.com';
    if ($smartmessages->validateAddress($address)) {
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
    $unsubs = $smartmessages->getListUnsubs($testlistid);
    echo "<p>Unsubscribes:</p>\n<ul>\n";
    foreach ($unsubs as $unsub) {
        echo "<li>{$unsub['address']}</li>\n";
    }
    echo "</ul>\n";

    //Get spam reporters
    $spamreporters = $smartmessages->getSpamReporters();
    echo "<p>Spam reporters:</p>\n<ul>\n";
    foreach ($spamreporters as $spamreporter) {
        echo "<li>$spamreporter</li>\n";
    }
    echo "</ul>\n";
    //Get/Set user info
    $smartmessages->getUserInfo($user);
    $smartmessages->setUserInfo(
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
    $smartmessages->getUserInfo($user);

    //Get/Set import order
    $fo = $smartmessages->getFieldOrder();
    $smartmessages->setFieldOrder(array('emailaddress', 'firstname', 'lastname', 'custom1'));
    $nfo = $smartmessages->getFieldOrder();
    echo "<p>Field order:</p>\n<ul>\n";
    foreach ($nfo as $f) {
        echo "<li>$f</li>\n";
    }
    echo "</ul>\n";
    $smartmessages->setFieldOrder($fo); //Reset to previous order

    //Get info about pending list uploads for this list
    $uploads = $smartmessages->getUploads($testlistid);
    echo "<p>Uploads:</p>\n<ul>\n";
    foreach ($uploads as $upload) {
        echo "<li>" . var_dump($upload) . "</li>\n";
    }
    echo "</ul>\n";

    //Upload a mailing list
    $uploadid = $smartmessages->uploadList($testlistid, 'testlist.csv', 'API upload', true, true, true);
    if ((integer)$uploadid > 0) {
        echo "Uploaded list OK<br />";
        //Get upload info
        echo "<p>Upload info:</p>\n";
        $uploadinfo = $smartmessages->getUploadInfo($testlistid, (integer)$uploadid);
        var_dump($uploadinfo);
    } else {
        echo "List upload failed<br />";
    }

    //Get the complete contents of a mailing list as a CSV
    $list = $smartmessages->getList($testlistid, true);
    var_dump($list);

    //Get unsubscribes
    $unsubs = $smartmessages->getListUnsubs($testlistid);
    echo "<p>Unsubscribes:</p>\n<ul>\n";
    foreach ($unsubs as $unsub) {
        echo "<li>{$unsub['address']}</li>\n";
    }
    echo "</ul>\n";

    //Remove all subscribers from a list
    //Disabled in this example to avoid accidental data loss
    //$c = $sm->emptyList($testlistid);
    //echo "Emptied list, deleted $c subscriptions<br />";

    //Send a mailshot
    $mailshotid = 0;
    //This is commented out in this example code as it's dangerous to call it without being sure of your parameters
    //- this single call could cause hundreds of thousands of messages to be sent!
    /*
    $mailshotid = $sm->sendMailshot(
        $templates[0],
        $testlistid,
        'API Send',
        $campaigns[0],
        'API test subject',
        'user@example.com',
        'API Sender',
        'subscriber@example.com',
        'now',
        false,
        array()
    );
    */

    //Get data relating to a mailshot as CSVs
    if ($mailshotid > 0) {
        $bounces = $smartmessages->getMailshotBounces($mailshotid, true);
        var_dump($bounces);
        $opens = $smartmessages->getMailshotOpens($mailshotid, true);
        var_dump($opens);
        $clicks = $smartmessages->getMailshotClicks($mailshotid, true);
        var_dump($clicks);
        $unsubs = $smartmessages->getMailshotUnsubs($mailshotid, true);
        var_dump($unsubs);
    }

    //Log out
    $smartmessages->logout();
} catch (Smartmessages\Exception $e) {
    echo '<h1>Exception caught</h1><p>An error (' . $e->getCode() . ') occurred: ' . $e->getMessage() . "</p>\n";
}
