<?php
/**
 * Smartmessages API example script
 *
 * Example script demonstrating all available Smartmessages API calls using the Smartmessages API wrapper class
 * Note that this script will not work as is - you will need to substitute your own login ID, password, API key and test list IDs in the variables below (or in an override file)
 * @author Marcus Bointon <marcus@synchromedia.co.uk>
 * @copyright 2010 Synchromedia Limited
 * @link https://www.smartmessages.net/ Smartmessages mailing lists management
 * @link http://wiki.smartmessages.net/ Smartmessages user and developer documentation
 * @version $Id: SmartmessagesAPIexamples.phps 2117 2010-08-13 15:32:20Z marcus $
 */

/**
 * Include API class
 */
require 'SmartmessagesAPI.class.phps';

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
if (file_exists('testsettings.php')) include 'testsettings.php';

try {
	$sm = new SmartmessagesAPI;
	//Login
	$sm->login($user, $pass, $apikey, $baseurl);

	//Create a campaign folder
	$cid = $sm->addcampaign('api test campaign');

	//$sm = new SmartmessagesAPI(false); //Create an API instance
	$sm = new SmartmessagesAPI(true); //Create an API instance in debug mode (will produce debug output)
	$sm = new SmartmessagesAPI;
	//Login
	$sm->login($user, $pass, $apikey, $baseurl);

	//Manage campaign folders
	$cid = $sm->addcampaign('api test campaign');
	if ($cid > 0) {
		$sm->updatecampaign($cid, 'updated api campaign');
		$sm->deletecampaign($cid);
	}
	$campaigns = $sm->getcampaigns();
	$mailshots = $sm->getcampaignmailshots(key($campaigns));

	//Manage mailing lists
	$mlid = $sm->addlist('new test', 'description', true);
	$lists = $sm->getlists(true); //Get all lists, not just visible ones
	if ($mlid > 0) {
		$r = $sm->updatelist($mlid, 'updated new test', 'updated description', false);
		$sm->deletelist($mlid);
	}
	echo "<p>Mailing lists:</p>\n<ul>\n";
	foreach($lists as $list) {
		echo "<li>List ID {$list['id']}, {$list['name']}: {$list['description']}</li>\n";
	}
	echo "</ul>\n";

	//Manage templates
	$templates = $sm->gettemplates(true);
	$sm->gettemplate(key($templates));
	$t1 = $sm->addtemplate('apitest', '<html><head></head><body><h1>HTML</h1></body></html>', 'plain', 'subject', 'test template', false);
	$t2 = $sm->addtemplatefromurl('apitest url', 'http://www.google.com/', 'mysubject', 'Grabbed from google');
	$sm->gettemplate($t1);
	$sm->gettemplate($t2);
	$sm->deletetemplate($t1);
	$sm->deletetemplate($t1);

	//Get / Set Callback URL
	$url = $sm->getcallbackurl();
	echo "<p>Callback URL is $url</p>\n";
	try {
		$sm->setcallbackurl('http://www.example.com/callback.php'); //Valid URL test
		$sm->setcallbackurl('blah'); //Invalid URL test
	} catch (Exception $e) {
		echo "</p>Callback URL set failed (as expected)</p>";
	}
	$url = $sm->getcallbackurl();
	echo "<p>Callback URL is now $url</p>\n";

	//Email address validation
	$address = 'rubbish';
	if ($sm->validateaddress($address)) {
		echo "'$address' is valid<br />";
	} else {
		echo "'$address' is not valid<br />";
	}
	$address = 'valid@example.com';
	if ($sm->validateaddress($address)) {
		echo "'$address' is valid<br />";
	} else {
		echo "'$address' is not valid<br />";
	}

	//Subscribe
	try {
		$sm->subscribe($user, $testlistid, 'The Dude');
		echo "<p>Subscribed OK</p>";
		$sm->subscribe($user, $testlistid, 'The Dude'); //This will fail as it's already subscribed
		echo "<p>Subscribed OK</p>";
	} catch (Exception $e) {
		echo "</p>Subscribe failed (as expected)</p>";
	}

	//Unsubscribe
	try {
		$sm->unsubscribe($user, $testlistid);
		echo "<p>Unsubscribed OK</p>";
		$sm->unsubscribe($user, $testlistid); //This will fail as it's already unsubscribed
		echo "<p>Unsubscribed OK</p>";
	} catch (Exception $e) {
		echo "</p>Unsubscribe failed (as expected)</p>";
	}

	//Get unsubscribes
	$unsubs = $sm->getlistunsubs($testlistid);
	echo "<p>Unsubscribes:</p>\n<ul>\n";
	foreach($unsubs as $unsub) {
		echo "<li>$unsub</li>\n";
	}
	echo "</ul>\n";

	//Get spam reporters
	$spamreporters = $sm->getspamreporters();
	echo "<p>Spam reporters:</p>\n<ul>\n";
	foreach($spamreporters as $spamreporter) {
		echo "<li>$spamreporter</li>\n";
	}
	echo "</ul>\n";
	//Get/Set user info
	$sm->getuserinfo($user);
	$sm->setuserinfo($user, array('firstname' => 'Joe', 'lastname' => 'User', 'country' => 'FR', 'jobtitle' => 'Chief Techie Lurker', 'custom10' => date('Y-m-d H:i:s'), 'custom3' => '1 2 3'));
	$sm->getuserinfo($user);

	//Get/Set import order
	$fo = $sm->getfieldorder();
	$sm->setfieldorder(array('emailaddress', 'firstname', 'lastname', 'custom1'));
	$nfo = $sm->getfieldorder();
	echo "<p>Field order:</p>\n<ul>\n";
	foreach($nfo as $f) {
		echo "<li>$f</li>\n";
	}
	echo "</ul>\n";
	$sm->setfieldorder($fo); //Reset to previous order

	//Get info about pending list uploads
	$uploads = $sm->getuploads($testlistid);
	echo "<p>Uploads:</p>\n<ul>\n";
	foreach($uploads as $upload) {
		echo "<li>".var_dump($upload)."</li>\n";
	}
	echo "</ul>\n";

	//Upload a mailing list
	if ($uploadid = $sm->uploadlist($testlistid, 'testlist.csv', 'API upload', true, true, true)) {
		echo "Uploaded list OK<br />";
	} else {
		echo "List upload failed<br />";
	}

	//Get upload info
	if ($uploadid) {
		echo "<p>Upload info:</p>\n";
		$uploadinfo = $sm->getuploadinfo($testlistid, $uploadid);
		var_dump($uploadinfo);
	}

	//Get the complete contents of a mailing list as a CSV
	$list = $sm->getlist($testlistid, true);
	var_dump($list);

	//Remove all subscribers from a list
	//Disabled in this example to avoid accidental data loss
	//$c = $sm->emptylist($testlistid);
	//echo "Emptied list, deleting $c subscriptions<br />";
	
	//Send a mailshot
	$mailshotid = 0;
	//Note that you explicit permission to use this function; it's disabled by default.
	//This line is commented out in this example code as it's dangerous to call it without being sure of your parameters
	//- a single line of code could cause hundreds of thousands of messages to be sent!
	//$mailshotid = $sm->sendmailshot($templates[0], $testlistid, 'API Send', $campaigns[0], 'API test subject', 'user@example.com', 'API Sender', 'subscriber@example.com', 'now', array());

	//Get data relating to a mailshot as CSVs
	if ($mailshotid > 0) {
		$bounces = $sm->getmailshotbounces($mailshotid, true);
		var_dump($bounces);
		$opens = $sm->getmailshotopens($mailshotid, true);
		var_dump($opens);
		$clicks = $sm->getmailshotclicks($mailshotid, true);
		var_dump($clicks);
		$unsubs = $sm->getmailshotunsubs($mailshotid, true);
		var_dump($unsubs);
	}

	//Log out
	$sm->logout();
} catch (SmartmessagesAPIException $e) {
	echo '<h1>Exception caught</h1><p>An error ('.$e->getCode().') occurred: '.$e->getMessage()."</p>\n";
}
?>