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
     * Timestamp of when this session expires, or 0 if not logged in.
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
     * Whether to run in debug mode.
     * With this enabled, all requests and responses generate descriptive output
     * @type boolean
     */
    public $debug = false;

    /**
     * Constructor, creates a new Smartmessages API instance
     * @param boolean $debug Whether to activate debug output
     */
    public function __construct($debug = false)
    {
        $this->debug = (boolean)$debug;
        ini_set('arg_separator.output', '&');
    }

    /**
     * Destructor - log out explicitly if we've not done so already.
     */
    public function __destruct()
    {
        if ($this->connected) {
            $this->logout();
        }
    }

    /**
     * Open a session with the Smartmessages API.
     * Throws an exception if login fails
     * @param string $user The user name (usually an email address)
     * @param string $pass
     * @param string $apikey The API key as shown on the settings page of the smartmessages UI
     * @param string $baseurl The initial entry point for the Smartmessages API
     * @return bool True if login was successful
     * @throws AuthException
     * @throws Exception
     * @access public
     */
    public function login($user, $pass, $apikey, $baseurl = 'https://www.smartmessages.net/api/')
    {
        $this->endpoint = $baseurl;
        $response = $this->get(
            'login',
            ['username' => $user, 'password' => $pass, 'apikey' => $apikey, 'outputformat' => 'php']
        );
        if (!$response['status']) {
            if ($response['errorcode'] == 1) {
                throw new AuthException($response['msg'], $response['errorcode']);
            } else {
                throw new Exception($response['msg'], $response['errorcode']);
            }
        }
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
        $this->get('logout');
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
        $res = $this->get('ping');
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
        $res = $this->get(
            'subscribe',
            [
                'address' => trim($address),
                'listid' => (integer)$listid,
                'name' => $dear,
                'firstname' => $firstname,
                'lastname' => $lastname
            ]
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
        $res = $this->get('unsubscribe', ['address' => trim($address), 'listid' => (integer)$listid]);
        return $res['status'];
    }

    /**
     * Add an existing subscriber to another list.
     * Similar to subscribe, but without the associated semantics,
     * simply adds them to a list without notifications or verification.
     * @see getLists()
     * @param string $address The email address
     * @param integer $listid The ID of the list to add the user to
     * @param string $note A description of why this change was made, e.g. 'submitted competition form'
     * @return boolean
     * @throws ParameterException
     * @access public
     */
    public function addSubscription($address, $listid, $note = '')
    {
        if (trim($address) == '' or (integer)$listid <= 0) {
            throw new ParameterException('Invalid add subscription parameters');
        }
        $res = $this->get(
            'addsubscription',
            [
                'address' => trim($address),
                'listid' => (integer)$listid,
                'note' => $note
            ]
        );
        return $res['status'];
    }

    /**
     * Delete an address from a list.
     * Does the same as unsubscribe, but without the associated semantics,
     * simply deletes them from a list without notifications, creating suppressions etc
     * @see getLists()
     * @param string $address The email address
     * @param integer $listid The ID of the list to delete the user from
     * @return boolean
     * @throws ParameterException
     * @access public
     */
    public function deleteSubscription($address, $listid)
    {
        if (trim($address) == '' or (integer)$listid <= 0) {
            throw new ParameterException('Invalid delete subscription parameters');
        }
        $res = $this->get(
            'deletesubscription',
            [
                'address' => trim($address),
                'listid' => (integer)$listid
            ]
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
        $res = $this->get('getlists', ['showall' => (boolean)$showall]);
        return $res['mailinglists'];
    }

    /**
     * Get the details of your designated test list.
     * @return array
     * @access public
     */
    public function getTestList()
    {
        $res = $this->get('gettestlist');
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
        $res = $this->get(
            'getlist',
            ['listid' => (integer)$listid, 'ascsv' => (boolean)$ascsv],
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
        $res = $this->get(
            'addlist',
            ['name' => trim($name), 'description' => trim($description), 'visible' => (boolean)$visible]
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
        $res = $this->get(
            'updatelist',
            [
                'listid' => (integer)$listid,
                'name' => trim($name),
                'description' => trim($description),
                'visible' => (boolean)$visible
            ]
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
        $res = $this->get('deletelist', ['listid' => (integer)$listid]);
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
        $res = $this->get('getuserinfo', ['address' => $address]);
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
        $res = $this->get('setuserinfo', ['address' => $address, 'userinfo' => $userinfo]);
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
        $res = $this->get('getspamreporters');
        return $res['spamreporters'];
    }

    /**
     * Get your current default import field order list.
     * @return array
     * @access public
     */
    public function getFieldOrder()
    {
        $res = $this->get('getfieldorder');
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
        $res = $this->get('setfieldorder', ['fields' => $fieldstring]);
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
        $res = $this->get('getlistunsubs', ['listid' => (integer)$listid]);
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
        $res = $this->post(
            'uploadlist',
            [
                'method' => 'uploadlist',
                'listid' => (integer)$listid,
                'source' => $source,
                'definitive' => (boolean)$definitive,
                'replace' => (boolean)$replace,
                'fieldorderfirstline' => (boolean)$fieldorderfirstline
            ],
            [$listfilename]
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
        $res = $this->get(
            'getuploadinfo',
            [
                'listid' => (integer)$listid,
                'uploadid' => (integer)$uploadid
            ]
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
        $res = $this->get('getuploads', ['listid' => (integer)$listid]);
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
     * @return boolean
     * @throws ParameterException
     * @access public
     */
    public function cancelUpload($listid, $uploadid)
    {
        if ((integer)$listid <= 0 or (integer)$uploadid <= 0) {
            throw new ParameterException('Invalid getuploadinfo parameters');
        }
        $res = $this->get(
            'cancelupload',
            [
                'listid' => (integer)$listid,
                'uploadid' => (integer)$uploadid
            ]
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
        $res = $this->get('getcallbackurl');
        return $res['url'];
    }

    /**
     * Set the callback URL for your account.
     * Read our support wiki for more details on this
     * @param string $url The URL of your callback script (this will be on YOUR web server, not ours)
     * @return boolean
     * @throws ParameterException
     * @access public
     */
    public function setCallbackURL($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_SCHEME_REQUIRED)) {
            throw new ParameterException('Invalid callback URL');
        }
        $res = $this->get('setcallbackurl', ['url' => $url]);
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
        $res = $this->get('validateaddress', ['address' => $address]);
        return (boolean)$res['valid'];
    }

    /**
     * Get a list of campaign folder names and IDs.
     * @return array
     * @access public
     */
    public function getCampaigns()
    {
        $res = $this->get('getcampaigns');
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
        $res = $this->get('addcampaign', ['name' => $name]);
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
        $res = $this->get(
            'updatecampaign',
            [
                'campaignid' => (integer)$campaignid,
                'name' => trim($name)
            ]
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
        $res = $this->get('deletecampaign', ['campaignid' => (integer)$campaignid]);
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
        $res = $this->get('getcampaignmailshots', ['campaignid' => $campaignid]);
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
        $res = $this->get('getmailshot', ['mailshotid' => $mailshotid]);
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
        $res = $this->get(
            'getmailshotclicks',
            [
                'mailshotid' => $mailshotid,
                'ascsv' => (boolean)$ascsv
            ],
            (boolean)$ascsv
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
        $res = $this->get(
            'getmailshotopens',
            [
                'mailshotid' => $mailshotid,
                'ascsv' => (boolean)$ascsv
            ],
            (boolean)$ascsv
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
        $res = $this->get(
            'getmailshotunsubs',
            [
                'mailshotid' => $mailshotid,
                'ascsv' => (boolean)$ascsv
            ],
            (boolean)$ascsv
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
        $res = $this->get(
            'getmailshotbounces',
            [
                'mailshotid' => $mailshotid,
                'ascsv' => (boolean)$ascsv
            ],
            (boolean)$ascsv
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
        $res = $this->get(
            'gettemplates',
            ['includeglobal' => $includeglobal, 'includeinherited' => $includeinherited]
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
        $res = $this->get('gettemplate', ['templateid' => $templateid]);
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
     * @param boolean $importimages Whether to do a one-off import and URL conversion
     *      of images referenced in the template
     * @param boolean $convertformat Set to true to automatically identify and convert from other template formats
     * @return integer|boolean Returns the ID of the new template or false on failure
     * @access public
     */
    public function addTemplate(
        $name,
        $html,
        $plain,
        $subject,
        $description = '',
        $generateplain = false,
        $importimages = false,
        $convertformat = false
    ) {
        $res = $this->post(
            'addtemplate',
            [
                'name' => $name,
                'plain' => $plain,
                'html' => $html,
                'subject' => $subject,
                'description' => $description,
                'generateplain' => (boolean)$generateplain,
                'importimages' => (boolean)$importimages,
                'convertformat' => (boolean)$convertformat
            ]
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
        $importimages = false,
        $convertformat = false
    ) {
        //Use a post request to cope with large content
        $res = $this->post(
            'updatetemplate',
            [
                'templateid' => (integer)$templateid,
                'name' => $name,
                'plain' => $plain,
                'html' => $html,
                'subject' => $subject,
                'description' => $description,
                'generateplain' => $generateplain,
                'importimages' => (boolean)$importimages,
                'convertformat' => (boolean)$convertformat
            ]
        );
        //Return true on success, or false if it failed
        return $res['status'];
    }

    /**
     * Add a new template from a URL.
     * All string params should use UTF-8 character set
     * Templates imported this way will automatically have a plain text version generated
     * @param string $name The name of the new template
     * @param string $url The location of the template web page
     * @param string $subject The default subject template
     * @param string $description A plain-text description of the template (in UTF-8 charset)
     * @param boolean $importimages Whether to do a one-off import and URL conversion
     *      of images referenced in the template
     * @param boolean $convertformat Set to true to automatically identify and convert from other template formats
     * @return integer|boolean Returns the ID of the new template or false on failure
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
        $res = $this->get(
            'addtemplatefromurl',
            [
                'name' => $name,
                'url' => $url,
                'subject' => $subject,
                'description' => $description,
                'importimages' => (boolean)$importimages,
                'convertformat' => (boolean)$convertformat
            ]
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
        $res = $this->get('deletetemplate', ['templateid' => (integer)$templateid]);
        return $res['status']; //Return true on success, or false if it failed
    }

    /**
     * Create and optionally send a new mailshot.
     * All string params should use UTF-8 character set
     * @param integer $templateid The id of the template to send
     * @param integer $listid The id of the mailing list to send to
     * @param string $title The name of the new mailshot (if left blank, one will created automatically)
     * @param integer $campaignid The id of the campaign folder to store the mailshot in (test campaign by default)
     * @param string $subject The subject template (if left blank, template subject will be used)
     * @param string $fromaddr The address the mailshot will be sent from (account default or your login address)
     * @param string $fromname The name the mailshot will be sent from
     * @param string $replyto If you want replies to go somewhere other than the from address, supply one here
     * @param string $when When to send the mailshot, the string 'now' (or empty) for immediate send,
     *      or an ISO-format UTC date ('yyyy-mm-dd hh:mm:ss')
     * @param boolean $continuous Is this a continuous mailshot? (never completes, existing subs are ignored,
     *      new subscriptions are sent a message immediately, ideal for 'welcome' messages)
     * @return integer|boolean ID of the new mailshot id, or false on failure
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
        $res = $this->get(
            'sendmailshot',
            [
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
            ]
        );
        //Return the new mailshot ID on success, or false if it failed
        return ($res['status'] ? $res['mailshotid'] : false);
    }

    /**
     * Generic wrapper for issuing API requests.
     * @param string $verb HTTP verb to use, in lower case (e.g. get, post)
     * @param string $command The name of the API function to call
     * @param array $params An associative array of function parameters to pass
     * @param array $files An array of local filenames to attach to a POST request
     * @param boolean $returnraw Whether to decode the response (default) or return it as-is
     * @return mixed
     * @throws AuthException
     * @throws ConnectionException
     * @throws DataException
     */
    protected function request(
        $verb,
        $command,
        $params = [],
        $files = [],
        $returnraw = false
    ) {
        //All commands except login need an accessKey
        if (!empty($this->accessKey)) {
            $params['accesskey'] = $this->accessKey;
        }
        //Check whether the session has expired before making the request
        //unless we're logging in
        if ($command != 'login' and $this->expires <= time()) {
            throw new AuthException('Session has expired; Please log in again.');
        }
        if (empty($this->endpoint)) {
            //We can't connect
            throw new ConnectionException('Missing Smartmessages API URL');
        }
        $client = new \GuzzleHttp\Client(['base_uri' => $this->endpoint]);
        if ($this->debug) {
            echo "#$verb Request, command = ", $command, ":\n", $this->endpoint, "\n";
            echo "\n##Params: ", var_export($params, true), "\n";
            if (!empty($files)) {
                echo "\nFiles: ", var_export($files, true), "\n";
            }
            echo "\n";
        }
        //Make the request
        try {
            if ($verb == 'get') {
                $response = $client->get(
                    $command,
                    [
                        'query' => $params
                    ]
                );
            } else {
                //Assume it's a POST
                $form_files = [];
                foreach ($files as $file) {
                    $form_files[] = ['name' => basename($file), 'contents' => fopen($file, 'r')];
                }
                $response = $client->post(
                    $command,
                    [
                        'form_params' => $params,
                        'form_files' => $form_files
                    ]
                );
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $r = $e->getResponse();
            throw new ConnectionException($r->getReasonPhrase(), $r->getStatusCode());
        }
        //Get the whole contents of the response stream
        $body = $response->getBody()->getContents();
        if ($returnraw) {
            //Return complete body if that was asked for
            return $body;
        }
        //If you want to use a response format other than serialised PHP, you'll need to write your own,
        //though serialised PHP is obviously the best fit.
        //Response should be serialized PHP, so try to decode it
        $decodedResponse = @unserialize($body);
        if ($decodedResponse === false) {
            throw new DataException('Failed to unserialize PHP data', 0);
        }
        if (array_key_exists('status', $decodedResponse)) {
            $this->lastStatus = (boolean)$decodedResponse['status'];
        }
        if (array_key_exists('errorcode', $decodedResponse)) {
            $this->errorCode = $decodedResponse['errorcode'];
        } else {
            $this->errorCode = '';
        }
        if (array_key_exists('expires', $decodedResponse)) {
            $this->expires = (integer)$decodedResponse['expires'];
        }
        if ($this->debug) {
            echo "#Response:\n", var_export($decodedResponse, true), "\n";
        }
        return $decodedResponse;
    }

    /**
     * Send an HTTP GET request to the API
     * @param string $command
     * @param array $params
     * @param boolean $returnraw
     * @return mixed
     * @throws AuthException
     * @throws ConnectionException
     * @throws DataException
     */
    protected function get(
        $command,
        $params = [],
        $returnraw = false
    )
    {
        return $this->request('get', $command, $params, null, $returnraw);
    }

    /**
     * Send an HTTP POST request to the API.
     * @param string $command
     * @param array $params
     * @param array $files Files to upload
     * @param boolean $returnraw
     * @return mixed
     * @throws AuthException
     * @throws ConnectionException
     * @throws DataException
     */
    protected function post(
        $command,
        $params = [],
        $files = [],
        $returnraw = false
    ) {
        return $this->request('post', $command, $params, $files, $returnraw);
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
