<?php
/**
 * Smartmessages SmartmessagesAPI and SmartmessagesAPIException classes
 * PHP Version 5.3
 * @package Smartmessages\Client
 * @author Marcus Bointon <marcus@synchromedia.co.uk>
 * @copyright 2015 Synchromedia Limited
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/Smartmessages/PHPClient
 */

namespace Smartmessages;

/**
 * The Smartmessages API Client class
 * @package Smartmessages\Client
 * @author Marcus Bointon <marcus@synchromedia.co.uk>
 * @copyright 2015 Synchromedia Limited
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://info.smartmessages.net/ Smartmessages mailing lists management
 * @link https://wiki.smartmessages.net/ Smartmessages documentation
 */
class Client
{
    /**
     * The authenticated access key for this session.
     * @type string
     */
    protected $accessKey = '';

    /**
     * The API endpoint to direct requests at, set during login.
     * @type string
     */
    protected $endpoint = '';

    /**
     * Whether we have logged in successfully.
     * @type boolean
     */
    protected $connected = false;

    /**
     * Timestamp of when this session expires.
     * @type integer
     */
    protected $expires = 0;

    /**
     * The user name used to log in to the API, usually an email address.
     * @type string
     */
    protected $accountName = '';

    /**
     * The most recent status value received from the API - true for success, false otherwise.
     * @type boolean
     */
    protected $lastStatus = true;

    /**
     * The most recent error code received. 0 if no error.
     * @type boolean
     */
    protected $errorCode = 0;

    /**
     * The most recent message received in an API response.
     * Does not necessarily indicate an error, may have some other informational content.
     * @type string
     */
    public $message = '';

    /**
     * Whether to run in debug mode.
     * With this enabled, all requests and responses generate descriptive output
     * @type boolean
     */
    public $debug = false;

    /**
     * Constructor, creates a new Smartmessages API instance
     * @param boolean $debug Whether to activate debug mode
     */
    public function __construct($debug = false)
    {
        $this->debug = (boolean)$debug;
    }

    /**
     * Open a session with the Smartmessages API.
     * Throws an exception if login fails
     * @param string $user The user name (usually an email address)
     * @param string $pass
     * @param string $apikey The API key as shown on the settings page of the smartmessages UI
     * @param string $baseurl The initial entry point for the Smartmessage API
     * @return boolean true if login was successful
     * @access public
     */
    public function login($user, $pass, $apikey, $baseurl = 'https://www.smartmessages.net/api/')
    {
        $response = $this->doRequest(
            'login',
            array('username' => $user, 'password' => $pass, 'apikey' => $apikey, 'outputformat' => 'php'),
            $baseurl
        );
        $this->connected = true;
        $this->accessKey = $response['accesskey'];
        $this->endpoint = $response['endpoint'];
        $this->expires = $response['expires'];
        $this->accountName = $response['accountname'];
        return true;
    }

    /**
     * Close a session with the Smartmessages API.
     * @access public
     * @return void
     */
    public function logout()
    {
        $this->doRequest('logout');
        $this->connected = false;
        $this->accessKey = '';
        $this->expires = 0;
    }

    /**
     * Does nothing, but keeps a connection open and extends the session expiry time.
     * @access public
     * @return boolean
     */
    public function ping()
    {
        $res = $this->doRequest('ping');
        return $res['status'];
    }

    /**
     * Subscribe an address to a list.
     * @see getLists()
     * @param string $address The email address
     * @param integer $listid The ID of the list to subscribe the user to
     * @param string $dear A preferred greeting that's not necessarily their actual name,
     *  such as 'Scooter', 'Mrs Smith', 'Mr President'
     * @param string $firstname The subscriber's first name
     * @param string $lastname The subscriber's first name
     * @return boolean
     * @throws ParameterException
     * @access public
     */
    public function subscribe($address, $listid, $dear = '', $firstname = '', $lastname = '')
    {
        if (trim($address) == '' or (integer)$listid <= 0) {
            throw new ParameterException('Invalid subscribe parameters');
        }
        $res = $this->doRequest(
            'subscribe',
            array(
                'address' => trim($address),
                'listid' => (integer)$listid,
                'name' => $dear,
                'firstname' => $firstname,
                'lastname' => $lastname
            )
        );
        return $res['status'];
    }

    /**
     * Unsubscribe an address from a list.
     * @see getLists()
     * @param string $address The email address
     * @param integer $listid The ID of the list to unsubscribe the user from
     * @return boolean
     * @throws ParameterException
     * @access public
     */
    public function unsubscribe($address, $listid)
    {
        if (trim($address) == '' or (integer)$listid <= 0) {
            throw new ParameterException('Invalid unsubscribe parameters');
        }
        $res = $this->doRequest('unsubscribe', array('address' => trim($address), 'listid' => (integer)$listid));
        return $res['status'];
    }

    /**
     * Delete an address from a list.
     * Does the same as unsubscribe, but without the associated semantics,
     * simply deletes them from a list without notifications, creating suppressions etc
     * @see getLists()
     * @param string $address The email address
     * @param integer $listid The ID of the list to delete the user from
     * @return bool
     * @throws ParameterException
     * @access public
     */
    public function deleteSubscription($address, $listid)
    {
        if (trim($address) == '' or (integer)$listid <= 0) {
            throw new ParameterException('Invalid delete subscription parameters');
        }
        $res = $this->doRequest(
            'deleteSubscription',
            array(
                'address' => trim($address),
                'listid' => (integer)$listid
            )
        );
        return $res['status'];
    }

    /**
     * Get the details of all the mailing lists in your account.
     * @param boolean $showall Whether to get all lists or just those set to visible
     * @return array
     * @access public
     */
    public function getLists($showall = false)
    {
        $res = $this->doRequest('getlists', array('showall' => (boolean)$showall));
        return $res['mailinglists'];
    }

    /**
     * Get the details of your designated test list.
     * @return array
     * @access public
     */
    public function getTestList()
    {
        $res = $this->doRequest('gettestlist');
        return $res;
    }

    /**
     * Download a complete mailing list.
     * Gets a complete list of recipients on a mailing list.
     * If the ascsv parameter is supplied and true, results will be provided in CSV format,
     * which is smaller, faster and easier to handle (just save it directly to a file) than other formats.
     * We strongly recommend that you use the ascsv option as the response can be extremely large
     * in PHP, JSON or XML formats, extending to hundreds of megabytes for large lists,
     * taking a correspondingly long time to download, and possibly causing memory problems
     * in client code. For this reason, this function defaults to CSV format.
     * @param integer $listid The ID of the list to fetch
     * @param boolean $ascsv Whether to get the list as CSV,
     *      as opposed to the currently selected format (e.g. JSON or XML)
     * @return string|array
     * @access public
     */
    public function getList($listid, $ascsv = true)
    {
        $res = $this->doRequest(
            'getlist',
            array('listid' => (integer)$listid, 'ascsv' => (boolean)$ascsv),
            '',
            false,
            array(),
            $ascsv
        );
        if ($ascsv) {
            return $res;
        } else {
            return $res['list'];
        }
    }

    /**
     * Add a mailing list.
     * @param string $name The name of the new list (max 100 chars)
     * @param string $description The description of the new list (max 255 chars)
     * @param boolean $visible Whether this list is publicly visible or not
     * @return integer The ID of the newly created list
     * @access public
     */
    public function addList($name, $description = '', $visible = true)
    {
        $res = $this->doRequest(
            'addlist',
            array('name' => trim($name), 'description' => trim($description), 'visible' => ($visible == true))
        );
        return $res['listid'];
    }

    /**
     * Update all the properties of a mailing list.
     * Note that all params are required, you can't just set one
     * @param integer $listid The ID of the list to update
     * @param string $name The new name of the list (max 100 chars)
     * @param string $description The new description of the list (max 255 chars)
     * @param boolean $visible Whether this list is publicly visible or not
     * @return boolean True on success
     * @access public
     */
    public function updateList($listid, $name, $description, $visible)
    {
        $res = $this->doRequest(
            'updatelist',
            array(
                'listid' => (integer)$listid,
                'name' => trim($name),
                'description' => trim($description),
                'visible' => ($visible == true)
            )
        );
        return $res['status'];
    }

    /**
     * Delete a mailing list.
     * Note that deleting a mailing list will also delete all mailshots that have used it
     * @param integer $listid The ID of the list to delete
     * @return boolean True on success
     * @access public
     */
    public function deleteList($listid)
    {
        $res = $this->doRequest('deletelist', array('listid' => (integer)$listid));
        return $res['status'];
    }

    /**
     * Get info about a recipient.
     * @param string $address The email address
     * @return array
     * @throws ParameterException
     * @access public
     */
    public function getUserInfo($address)
    {
        if (trim($address) == '') {
            throw new ParameterException('Invalid email address');
        }
        $res = $this->doRequest('getuserinfo', array('address' => $address));
        return $res['userinfo'];
    }

    /**
     * Set info about a recipient.
     * @see getUserInfo()
     * @param string $address The email address
     * @param array $userinfo Array of user properties in the same format as returned by getuserinfo()
     * @return boolean
     * @throws ParameterException
     * @access public
     */
    public function setUserInfo($address, $userinfo)
    {
        if (trim($address) == '') {
            throw new ParameterException('Invalid email address');
        }
        $res = $this->doRequest('setuserinfo', array('address' => $address, 'userinfo' => $userinfo));
        return $res['status'];
    }

    /**
     * Get a list of everyone that has reported messages from you as spam.
     * Only available from some ISPs, notably hotmail and AOL
     * @return array
     * @access public
     */
    public function getSpamReporters()
    {
        $res = $this->doRequest('getspamreporters');
        return $res['spamreporters'];
    }

    /**
     * Get your current default import field order list.
     * @return array
     * @access public
     */
    public function getFieldOrder()
    {
        $res = $this->doRequest('getfieldorder');
        return $res['fields'];
    }

    /**
     * Set your default import field order list.
     * The field list MUST include emailaddress
     * Any invalid or unknown names will be ignored
     * @see getFieldOrder()
     * @param array $fields Simple array of field names
     * @return array
     * @throws ParameterException
     * @access public
     */
    public function setFieldOrder($fields)
    {
        if (empty($fields) or !in_array('emailaddress', $fields)) {
            throw new ParameterException('Invalid field order');
        }
        $fieldstring = implode(',', $fields);
        $res = $this->doRequest('setfieldorder', array('fields' => $fieldstring));
        return $res['fields'];
    }

    /**
     * Get a list of everyone that has unsubscribed from the specified mailing list.
     * @param integer $listid The list ID
     * @return array
     * @throws ParameterException
     * @access public
     */
    public function getListUnsubs($listid)
    {
        if ((integer)$listid <= 0) {
            throw new ParameterException('Invalid list id');
        }
        $res = $this->doRequest('getlistunsubs', array('listid' => (integer)$listid));
        return $res['unsubscribes'];
    }

    /**
     * Upload a mailing list.
     * @see getLists()
     * @see getFieldOrder()
     * @see getUploadInfo()
     * @param integer $listid The ID of the list to upload into
     * @param string $listfilename A path to a local file containing your mailing list in CSV format
     *      (may also be zipped)
     * @param string $source For audit trail purposes, you must populate this with a description
     *      of where this list came from
     * @param boolean $definitive If set to true, overwrite any existing data in the fields included
     *      in the file, otherwise existing data will not be touched, but recipients will still be added to the list
     * @param boolean $replace Whether to empty the list before uploading this list
     *      (actually deletes anyone not in this upload so history is maintained)
     * @param boolean $fieldorderfirstline Set to true if the first line of the file contains field names
     * @return boolean|integer
     * @throws ParameterException
     * @access public
     */
    public function uploadList(
        $listid,
        $listfilename,
        $source,
        $definitive = false,
        $replace = false,
        $fieldorderfirstline = false
    ) {
        if ((integer)$listid <= 0) {
            throw new ParameterException('Invalid list id');
        }
        if (!file_exists($listfilename)) {
            throw new ParameterException('File does not exist!');
        }
        if (filesize($listfilename) < 6) { //This is the smallest a single external email address could possibly be
            throw new ParameterException('File does not contain any data!');
        }
        $res = $this->doRequest(
            'uploadlist',
            array(
                'method' => 'uploadlist',
                'listid' => (integer)$listid,
                'source' => $source,
                'definitive' => (boolean)$definitive,
                'replace' => (boolean)$replace,
                'fieldorderfirstline' => (boolean)$fieldorderfirstline
            ),
            null,
            true,
            array($listfilename)
        ); //This one requires a POST request for the list file attachment
        return ($res['status'] ? $res['uploadid'] : false); //Return the upload ID on success, or false if it failed
    }

    /**
     * Get info on a previous list upload.
     * @see getLists()
     * @see getFieldOrder()
     * @see uploadList()
     * @param integer $listid The ID of the list the upload belongs to
     * @param integer $uploadid The ID of the upload (as returned from uploadlist())
     * @return array
     * @throws ParameterException
     * @access public
     */
    public function getUploadInfo($listid, $uploadid)
    {
        if ((integer)$listid <= 0 or (integer)$uploadid <= 0) {
            throw new ParameterException('Invalid getuploadinfo parameters');
        }
        $res = $this->doRequest(
            'getuploadinfo',
            array(
                'listid' => (integer)$listid,
                'uploadid' => (integer)$uploadid
            )
        );
        return $res['upload'];
    }

    /**
     * Get info on all previous list uploads.
     * Only gives basic info on each upload, more detail can be obtained using getuploadinfo()
     * @see getLists()
     * @see uploadList()
     * @see getUploadInfo()
     * @param integer $listid The ID of the list the upload belongs to
     * @return array
     * @throws ParameterException
     * @access public
     */
    public function getUploads($listid)
    {
        if ((integer)$listid <= 0) {
            throw new ParameterException('Invalid getuploads parameters');
        }
        $res = $this->doRequest('getuploads', array('listid' => (integer)$listid));
        return $res['uploads'];
    }

    /**
     * Cancel a pending or in-progress upload.
     * Cancelled uploads are deleted, so won't appear in getuploads()
     * Deletions are asynchronous, so won't happen immediately
     * @see getLists()
     * @see uploadList()
     * @param integer $listid The ID of the list the upload belongs to
     * @param integer $uploadid The ID of the upload (as returned from uploadlist())
     * @return bool
     * @throws ParameterException
     * @access public
     */
    public function cancelUpload($listid, $uploadid)
    {
        if ((integer)$listid <= 0 or (integer)$uploadid <= 0) {
            throw new ParameterException('Invalid getuploadinfo parameters');
        }
        $res = $this->doRequest(
            'cancelupload',
            array(
                'listid' => (integer)$listid,
                'uploadid' => (integer)$uploadid
            )
        );
        return $res['status'];
    }

    /**
     * Get the callback URL for your account.
     * Read our support wiki for more details on this
     * @return array
     * @access public
     */
    public function getCallbackURL()
    {
        $res = $this->doRequest('getcallbackurl');
        return $res['url'];
    }

    /**
     * Set the callback URL for your account.
     * Read our support wiki for more details on this
     * @param string $url The URL of your callback script (this will be on YOUR web server, not ours)
     * @return bool
     * @throws ParameterException
     * @access public
     */
    public function setCallbackURL($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_SCHEME_REQUIRED)) {
            throw new ParameterException('Invalid callback URL');
        }
        $res = $this->doRequest('setcallbackurl', array('url' => $url));
        return $res['status'];
    }

    /**
     * Simple address validator.
     * It's more efficient to use a function on your own site to do this, but using this will ensure that
     * any address you add to a list will also be accepted by us
     * If you encounter an address that we reject that you think we shouldn't, please tell us!
     * Read our support wiki for more details on this
     * @param string $address The address to validate
     * @param boolean $remote Whether to do the validation locally (saving a round trip) or remotely
     * @return boolean
     * @access public
     */
    public function validateAddress($address, $remote = false)
    {
        if (!$remote) {
            return (boolean)filter_var($address, FILTER_VALIDATE_EMAIL);
        }
        $res = $this->doRequest('validateaddress', array('address' => $address));
        return (boolean)$res['valid'];
    }

    /**
     * Get a list of campaign folder names and IDs.
     * @return array
     * @access public
     */
    public function getCampaigns()
    {
        $res = $this->doRequest('getcampaigns');
        return $res['campaigns'];
    }

    /**
     * Create a campaign folder.
     * Note that folder names do NOT need to be unique, but we suggest you make them so
     * @param string $name The name for the new campaign folder - up to 100 characters long
     * @return integer The ID of the new campaign
     * @access public
     */
    public function addCampaign($name)
    {
        $res = $this->doRequest('addcampaign', array('name' => $name));
        return $res['campaignid'];
    }

    /**
     * Update the name of a campaign folder.
     * @param integer $campaignid The ID of the campaign folder to update
     * @param string $name The new name of the campaign folder (max 100 chars)
     * @return boolean True on success
     * @access public
     */
    public function updateCampaign($campaignid, $name)
    {
        $res = $this->doRequest(
            'updatecampaign',
            array(
                'campaignid' => (integer)$campaignid,
                'name' => trim($name)
            )
        );
        return $res['status'];
    }

    /**
     * Delete a campaign folder.
     * Note that deleting a campaign will also delete all mailshots that it contains
     * @param integer $campaignid The ID of the campaign folder to delete
     * @return boolean True on success
     * @access public
     */
    public function deleteCampaign($campaignid)
    {
        $res = $this->doRequest('deletecampaign', array('campaignid' => (integer)$campaignid));
        return $res['status'];
    }


    /**
     * Get a list of mailshots within a campaign folder.
     * Contains sufficient info to populate list displays, so you don't need to call getmailshot() on each one
     * Note that message_count will only be populated for sending or completed mailshots
     * @param integer $campaignid The ID of the campaign you want to get mailshots from
     * @return array
     * @access public
     */
    public function getCampaignMailshots($campaignid)
    {
        $res = $this->doRequest('getcampaignmailshots', array('campaignid' => $campaignid));
        return $res['mailshots'];
    }

    /**
     * Get detailed info about a single mailshot.
     * @param integer $mailshotid The ID of the mailshot you want to get info on
     * @return array
     * @access public
     */
    public function getMailshot($mailshotid)
    {
        $res = $this->doRequest('getmailshot', array('mailshotid' => $mailshotid));
        return $res['mailshot'];
    }

    /**
     * Get clicks generated by a single mailshot.
     * @param integer $mailshotid The ID of the mailshot you want to get clicks for
     * @param boolean $ascsv Set to true if you'd like the response in CSV instead of the currently selected format
     * @return array|string
     * @access public
     */
    public function getMailshotClicks($mailshotid, $ascsv = false)
    {
        $res = $this->doRequest(
            'getmailshotclicks',
            array(
                'mailshotid' => $mailshotid,
                'ascsv' => ($ascsv == true)
            )
        );
        if ($ascsv) {
            return $res;
        } else {
            return $res['clicks'];
        }
    }

    /**
     * Get opens relating to a single mailshot.
     * @param integer $mailshotid The ID of the mailshot you want to get opens for
     * @param boolean $ascsv Set to true if you'd like the response in CSV instead of the currently selected format
     * @return array|string
     * @access public
     */
    public function getMailshotOpens($mailshotid, $ascsv = false)
    {
        $res = $this->doRequest(
            'getmailshotopens',
            array(
                'mailshotid' => $mailshotid,
                'ascsv' => ($ascsv == true)
            )
        );
        if ($ascsv) {
            return $res;
        } else {
            return $res['opens'];
        }
    }

    /**
     * Get unsubs relating to a single mailshot.
     * @param integer $mailshotid The ID of the mailshot you want to get unsubs for
     * @param boolean $ascsv Set to true if you'd like the response in CSV instead of the currently selected format
     * @return array|string
     * @access public
     */
    public function getMailshotUnsubs($mailshotid, $ascsv = false)
    {
        $res = $this->doRequest(
            'getmailshotunsubs',
            array(
                'mailshotid' => $mailshotid,
                'ascsv' => ($ascsv == true)
            )
        );
        if ($ascsv) {
            return $res;
        } else {
            return $res['unsubs'];
        }
    }

    /**
     * Get bounces relating to a single mailshot.
     * @param integer $mailshotid The ID of the mailshot you want to get bounces for
     * @param boolean $ascsv Set to true if you'd like the response in CSV instead of the currently selected format
     * @return array|string
     * @access public
     */
    public function getMailshotBounces($mailshotid, $ascsv = false)
    {
        $res = $this->doRequest(
            'getmailshotbounces',
            array(
                'mailshotid' => $mailshotid,
                'ascsv' => ($ascsv == true)
            )
        );
        if ($ascsv) {
            return $res;
        } else {
            return $res['bounces'];
        }
    }

    /**
     * Get a list of all available templates.
     * @param boolean $includeglobal Whether to include standard smartmessages templates
     * @param boolean $includeinherited Whether to include inherited templates
     * @return array
     * @access public
     */
    public function getTemplates($includeglobal = false, $includeinherited = true)
    {
        $res = $this->doRequest(
            'gettemplates',
            array('includeglobal' => $includeglobal, 'includeinherited' => $includeinherited)
        );
        return $res['templates'];
    }

    /**
     * Get detailed info about a single template.
     * @param integer $templateid The ID of the template you want to get
     * @return array
     * @access public
     */
    public function getTemplate($templateid)
    {
        $res = $this->doRequest('gettemplate', array('templateid' => $templateid));
        return $res['template'];
    }

    /**
     * Add a new template.
     * All string params should use UTF-8 character set
     * @param string $name The name of the new template
     * @param string $html The HTML version of the template
     * @param string $plain The plain text version of the template
     * @param string $subject The default subject template
     * @param string $description A plain-text description of the template
     * @param boolean $generateplain Whether to generate a plain text version from the HTML version
     *      (if set, will ignore the value of $plain)
     * @param string $language What language this template is in (ISO 639-1 2-char code),
     *      mainly for internal tracking purposes, but you may find it useful if you use several languages
     * @param boolean $importimages Whether to do a one-off import and URL conversion
     *      of images referenced in the template
     * @param boolean $convertformat Set to true to automatically identify and convert from other template formats
     * @return int|boolean Returns the ID of the new template or false on failure
     * @access public
     */
    public function addTemplate(
        $name,
        $html,
        $plain,
        $subject,
        $description = '',
        $generateplain = false,
        $language = 'en',
        $importimages = false,
        $convertformat = false
    ) {
        //Use a post request to cope with large content
        $res = $this->doRequest(
            'addtemplate',
            array(
                'name' => $name,
                'plain' => $plain,
                'html' => $html,
                'subject' => $subject,
                'description' => $description,
                'generateplain' => (boolean)$generateplain,
                'language' => $language,
                'importimages' => (boolean)$importimages,
                'convertformat' => (boolean)$convertformat
            ),
            null,
            true
        );
        //Return the new template ID on success, or false if it failed
        return ($res['status'] ? $res['templateid'] : false);
    }

    /**
     * Update an existing template.
     * All string params should use UTF-8 character set
     * @param integer $templateid
     * @param string $name The name of the template
     * @param string $html The HTML version of the template
     * @param string $plain The plain text version of the template
     * @param string $subject The default subject template
     * @param string $description A plain-text description of the template
     * @param boolean $generateplain Whether to generate a plain text version from the HTML version
     *      (if set, will ignore the value of $plain), defaults to false
     * @param string $language What language this template is in (ISO 639-1 2-char code),
     *      mainly for internal tracking purposes, but you may find it useful if you use several languages
     * @param boolean $importimages Whether to do a one-off import and URL conversion
     *      of images referenced in the template
     * @param boolean $convertformat Set to true to automatically identify and convert from other template formats
     * @return boolean
     * @access public
     */
    public function updateTemplate(
        $templateid,
        $name,
        $html,
        $plain,
        $subject,
        $description = '',
        $generateplain = false,
        $language = 'en',
        $importimages = false,
        $convertformat = false
    ) {
        //Use a post request to cope with large content
        $res = $this->doRequest(
            'updatetemplate',
            array(
                'templateid' => (integer)$templateid,
                'name' => $name,
                'plain' => $plain,
                'html' => $html,
                'subject' => $subject,
                'description' => $description,
                'generateplain' => $generateplain,
                'language' => $language,
                'importimages' => ($importimages == true),
                'convertformat' => ($convertformat == true)
            ),
            null,
            true
        );
        return $res['status']; //Return true on success, or false if it failed
    }

    /**
     * Add a new template from a URL.
     * All string params should use ISO 8859-1 character set
     * Templates imported this way will automatically have a plain text version generated
     * @param string $name The name of the new template
     * @param string $url The location of the template web page
     * @param string $subject The default subject template
     * @param string $description A plain-text description of the template (in ISO 8859-1 charset)
     * @param boolean $importimages Whether to do a one-off import and URL conversion
     *      of images referenced in the template
     * @param boolean $convertformat Set to true to automatically identify and convert from other template formats
     * @return bool|int Returns the ID of the new template or false on failure
     * @throws ParameterException
     * @access public
     */
    public function addTemplateFromURL(
        $name,
        $url,
        $subject,
        $description = '',
        $importimages = false,
        $convertformat = false
    ) {
        if (!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_SCHEME_REQUIRED)) {
            throw new ParameterException('Invalid template URL');
        }
        $res = $this->doRequest(
            'addtemplatefromurl',
            array(
                'name' => $name,
                'url' => $url,
                'subject' => $subject,
                'description' => $description,
                'importimages' => ($importimages == true),
                'convertformat' => ($convertformat == true)
            )
        );
        //Return the new template ID on success, or false if it failed
        return ($res['status'] ? (integer)$res['templateid'] : false);
    }

    /**
     * Delete a template.
     * Note that deleting a template will also delete any mailshots that used it,
     * and all records and reports relating to it
     * To delete inherited templates you need to connect using the account they are inherited from
     * @param integer $templateid The template id to delete
     * @return boolean
     * @access public
     */
    public function deleteTemplate($templateid)
    {
        $res = $this->doRequest('deletetemplate', array('templateid' => (integer)$templateid));
        return $res['status']; //Return true on success, or false if it failed
    }

    /**
     * Create and optionally send a new mailshot.
     * All string params should use ISO 8859-1 character set
     * @param integer $templateid The id of the template to send
     * @param integer $listid The id of the mailing list to send to
     * @param string $title The name of the new mailshot (if left blank, one will created automatically)
     * @param integer $campaignid The id of the campaign folder to store the mailshot in (test campaign by default)
     * @param string $subject The subject template (if left blank, template default subject will be used)
     * @param string $fromaddr The address the mailshot will be sent from (account default or your login address by default)
     * @param string $fromname The name the mailshot will be sent from
     * @param string $replyto If you want replies to go somewhere other than the from address, supply one here
     * @param string $when When to send the mailshot, the string 'now' (or empty) for immediate send,
     *      or an ISO-format UTC date ('yyyy-mm-dd hh:mm:ss')
     * @param boolean $continuous Is this a continuous mailshot? (never completes, existing subs are ignored,
     *      new subscriptions are sent a message immediately, ideal for 'welcome' messages)
     * @return integer|bool ID of the new mailshot id, or false on failure
     * @access public
     */
    public function sendMailshot(
        $templateid,
        $listid,
        $title = '',
        $campaignid = 0,
        $subject = '',
        $fromaddr = '',
        $fromname = '',
        $replyto = '',
        $when = 'now',
        $continuous = false
    ) {
        $res = $this->doRequest(
            'sendmailshot',
            array(
                'templateid' => (integer)$templateid,
                'listid' => (integer)$listid,
                'title' => $title,
                'campaignid' => (integer)$campaignid,
                'subject' => $subject,
                'fromaddr' => $fromaddr,
                'fromname' => $fromname,
                'replyto' => $replyto,
                'when' => $when,
                'continuous' => (boolean)$continuous
            )
        );
        //Return the new mailshot ID on success, or false if it failed
        return ($res['status'] ? $res['mailshotid'] : false);
    }

    /**
     * Generic wrapper for issuing API requests.
     * @param string $command The name of the API function to call
     * @param array $params An associative array of function parameters to pass
     * @param string $urloverride A URL to override the default location (typically used by login)
     * @param boolean $post whether to do a POST instead of a GET
     * @param array $files An array of local filenames to attach to a POST request
     * @param bool $returnraw
     * @return mixed
     * @throws ConnectionException
     * @throws DataException
     * @throws Exception
     */
    protected function doRequest(
        $command,
        $params = array(),
        $urloverride = '',
        $post = false,
        $files = array(),
        $returnraw = false
    ) {
        ini_set('arg_separator.output', '&');
        //All commands except login need an accessKey
        if (!empty($this->accessKey)) {
            $params['accesskey'] = $this->accessKey;
        }
        if (empty($urloverride)) {
            if (empty($this->endpoint)) {
                //We can't connect
                throw new ConnectionException('Missing Smartmessages API URL');
            } else {
                $url = $this->endpoint;
            }
        } else {
            $url = $urloverride;
        }
        $url .= $command;
        $verb = ($post?'POST':'GET');
        //Make the request (must have fopen wrappers enabled)
        if ($this->debug) {
            echo "<h1>$verb Request (" . htmlspecialchars($command) . "):</h1>\n<p>" . htmlspecialchars(
                $url
            ) . "</p>\n";
            echo "<div>\n". htmlspecialchars(var_export($params, true))."\n</div>\n";
        }
        if ($post) {
            $response = $this->doPostRequest($url, $params, $files);
        } else {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            $params = array();
            //Enforce valid SSL certificates
            if (substr($url, 0, 6) == 'https:') {
                $params['ssl'] = array(
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false
                );
            }
            $ctx = stream_context_create($params);
            $response = file_get_contents($url, null, $ctx);
        }
        //If you want to support response types other than serialised PHP, you'll need to write your own,
        //though php is obviously the best fit since we are in it already!
        if ($returnraw) { //Return undecoded response if that was asked for
            return $response;
        }
        $response = @unserialize($response);
        if ($response === false) {
            $this->message = 'Failed to unserialize PHP data';
            throw new DataException($this->message, 0);
        }
        if (array_key_exists('status', $response)) {
            $this->lastStatus = ($response['status'] == true);
        }
        if (array_key_exists('msg', $response)) {
            $this->message = $response['msg'];
        } else {
            $this->message = '';
        }
        if (array_key_exists('errorcode', $response)) {
            $this->errorCode = $response['errorcode'];
        } else {
            $this->errorCode = '';
        }
        if ($this->debug) {
            echo "<h1>Response:</h1>\n<div><pre>";
            echo htmlspecialchars(var_export($response, true));
            echo "</pre></div>\n";
        }
        if (!$this->lastStatus) {
            throw new DataException($this->message, $this->errorCode);
        }
        return $response;
    }

    /**
     * Submit a multipart POST request - like a form submission with FILE attachments.
     * Adapted from do_post_request written by dresel at gmx dot at and Wez Furlong
     * @link http://uk2.php.net/manual/en/function.stream-context-create.php#90411
     * @link http://netevil.org/blog/2006/nov/http-post-from-php-without-curl
     * @param string $url
     * @param array $postdata
     * @param array $files
     * @throws Exception
     * @return string
     */
    protected function doPostRequest($url, $postdata, $files = array())
    {
        ini_set('arg_separator.output', '&');
        $data = '';
        $boundary = "---------------------" . substr(md5(rand(0, 32000)), 0, 10);

        //Collect Postdata
        foreach ($postdata as $key => $val) {
            $data .= "--$boundary\n";
            $data .= "Content-Disposition: form-data; name=\"" . $key . "\"\n\n" . $val . "\n";
        }

        $data .= "--$boundary\n";

        //Collect Filedata
        foreach ($files as $file) {
            $filename = basename($file);
            $data .= "Content-Disposition: form-data; name=\"$filename\"; filename=\"$filename\"\n";
            $data .= "Content-Type: application/octet-stream\n"; //Could be anything, so just upload as raw binary
            $data .= "Content-Transfer-Encoding: binary\n\n";
            $data .= file_get_contents($file) . "\n";
            $data .= "--$boundary--\n";
        }

        $params = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: multipart/form-data; boundary=' . $boundary,
                'content' => $data
            )
        );
        //Enforce valid SSL certificates
        if (substr($url, 0, 6) == 'https:') {
            $params['ssl'] = array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            );
        }

        if ($this->debug) {
            echo "<h2>POST body:</h2><pre>\n";
            echo htmlentities(substr($data, 0, 8192), ENT_QUOTES, 'UTF-8'); //Limit size of debug output
            echo "</pre>\n";
        }

        $ctx = stream_context_create($params);
        $fileh = fopen($url, 'rb', false, $ctx);

        if (!$fileh) {
            throw new ConnectionException("Problem with $url, $php_errormsg");
        }

        $response = @stream_get_contents($fileh);
        if ($response === false) {
            throw new ConnectionException("Problem reading data from $url, $php_errormsg");
        }
        return $response;
    }
}

/**
 * Exception base class.
 */
class Exception extends \Exception
{

}

/**
 * Thrown when curl connections fail: DNS failure, HTTP timeout etc.
 */
class ConnectionException extends Exception
{

}

/**
 * Thrown when invalid data is encountered,
 * such as when responses are not valid JSON or serialized PHP.
 */
class DataException extends Exception
{

}

/**
 * Thrown when invalid method parameters are encountered,
 */
class ParameterException extends Exception
{

}

/**
 * Thrown when login fails or session has expired.
 */
class AuthException extends Exception
{

}
