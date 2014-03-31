<?php
/*
 * Smartmessages SmartmessagesAPI and SmartmessagesAPIException classes
 */

/**
 * The Smartmessages API class
 *
 * @author Marcus Bointon <marcus@synchromedia.co.uk>
 * @copyright 2014 Synchromedia Limited
 * @link https://www.smartmessages.net/ Smartmessages mailing lists management
 * @link http://wiki.smartmessages.net/ Smartmessages user and developer documentation
 * @throws Exception|SmartmessagesAPIException
 */
class SmartmessagesAPI
{
    /**
     * The authenticated access key for this session.
     * @type string
     */
    protected $accesskey = '';
    /**
     * The API endpoint to direct requests at, set during login.
     * @type string
     */
    protected $endpoint = '';
    /**
     * Whether we have logged in successfully.
     * @type boolean
     */
    public $connected = false;
    /**
     * Timestamp of when this session expires.
     * @type integer
     */
    public $expires = 0;
    /**
     * The user name used to log in to the API, usually an email address.
     * @type string
     */
    public $accountname = '';
    /**
     * The most recent status value received from the API - true for success, false otherwise.
     * @type boolean
     */
    public $laststatus = true;
    /**
     * The most recent error code received. 0 if no error.
     * @type boolean
     */
    public $errorcode = 0;
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
        $this->debug = $debug;
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
        $response = $this->dorequest(
            'login',
            array('username' => $user, 'password' => $pass, 'apikey' => $apikey, 'outputformat' => 'php'),
            $baseurl
        );
        $this->connected = true;
        $this->accesskey = $response['accesskey'];
        $this->endpoint = $response['endpoint'];
        $this->expires = $response['expires'];
        $this->accountname = $response['accountname'];
        return true;
    }

    /**
     * Close a session with the Smartmessages API.
     * @access public
     */
    public function logout()
    {
        $this->dorequest('logout');
        $this->connected = false;
        $this->accesskey = '';
        $this->expires = 0;
    }

    /**
     * Does nothing, but keeps a connection open and extends the session expiry time.
     * @access public
     */
    public function ping()
    {
        $res = $this->dorequest('ping');
        return $res['status'];
    }

    /**
     * Subscribe an address to a list.
     * @see getlists()
     * @param string $address The email address
     * @param integer $listid The ID of the list to subscribe the user to
     * @param string $dear A preferred greeting that's not necessarily their actual name,
     *  such as 'Scooter', 'Mrs Smith', 'Mr President'
     * @param string $firstname
     * @param string $lastname
     * @throws SmartmessagesAPIException
     * @return boolean true if subscribe was successful
     * @access public
     */
    public function subscribe($address, $listid, $dear = '', $firstname = '', $lastname = '')
    {
        if (trim($address) == '' or (integer)$listid <= 0) {
            throw new SmartmessagesAPIException('Invalid subscribe parameters');
        }
        $res = $this->dorequest(
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
     * @see getlists()
     * @param string $address The email address
     * @param integer $listid The ID of the list to unsubscribe the user from
     * @throws SmartmessagesAPIException
     * @return boolean true if unsubscribe was successful
     * @access public
     */
    public function unsubscribe($address, $listid)
    {
        if (trim($address) == '' or (integer)$listid <= 0) {
            throw new SmartmessagesAPIException('Invalid unsubscribe parameters');
        }
        $res = $this->dorequest('unsubscribe', array('address' => trim($address), 'listid' => (integer)$listid));
        return $res['status'];
    }

    /**
     * Delete an address from a list.
     * Does the same as unsubscribe, but without the associated semantics,
     * simply deletes them from a list without notifications, creating suppressions etc
     * @see getlists()
     * @param string $address The email address
     * @param integer $listid The ID of the list to delete the user from
     * @throws SmartmessagesAPIException
     * @return boolean true if deletion was successful
     * @access public
     */
    public function deletesubscription($address, $listid)
    {
        if (trim($address) == '' or (integer)$listid <= 0) {
            throw new SmartmessagesAPIException('Invalid delete subscription parameters');
        }
        $res = $this->dorequest(
            'deletesubscription',
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
    public function getlists($showall = false)
    {
        $res = $this->dorequest('getlists', array('showall' => (boolean)$showall));
        return $res['mailinglists'];
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
    public function getlist($listid, $ascsv = true)
    {
        $res = $this->dorequest(
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
    public function addlist($name, $description = '', $visible = true)
    {
        $res = $this->dorequest(
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
    public function updatelist($listid, $name, $description, $visible)
    {
        $res = $this->dorequest(
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
    public function deletelist($listid)
    {
        $res = $this->dorequest('deletelist', array('listid' => (integer)$listid));
        return $res['status'];
    }

    /**
     * Get info about a recipient.
     * @param string $address The email address
     * @throws SmartmessagesAPIException
     * @return array Info about the user
     * @access public
     */
    public function getuserinfo($address)
    {
        if (trim($address) == '') {
            throw new SmartmessagesAPIException('Invalid email address');
        }
        $res = $this->dorequest('getuserinfo', array('address' => $address));
        return $res['userinfo'];
    }

    /**
     * Set info about a recipient.
     * @see getuserinfo()
     * @param string $address The email address
     * @param array $userinfo Array of user properties in the same format as returned by getuserinfo()
     * @throws SmartmessagesAPIException
     * @return boolean true on success
     * @access public
     */
    public function setuserinfo($address, $userinfo)
    {
        if (trim($address) == '') {
            throw new SmartmessagesAPIException('Invalid email address');
        }
        $res = $this->dorequest('setuserinfo', array('address' => $address, 'userinfo' => $userinfo));
        return $res['status'];
    }

    /**
     * Get a list of everyone that has reported messages from you as spam.
     * Only available from some ISPs, notably hotmail and AOL
     * @return array
     * @access public
     */
    public function getspamreporters()
    {
        $res = $this->dorequest('getspamreporters');
        return $res['spamreporters'];
    }

    /**
     * Get your current default import field order list.
     * @return array
     * @access public
     */
    public function getfieldorder()
    {
        $res = $this->dorequest('getfieldorder');
        return $res['fields'];
    }

    /**
     * Set your default import field order list.
     * The field list MUST include emailaddress
     * Any invalid or unknown names will be ignored
     * @see getfieldorder()
     * @param array $fields Simple array of field names
     * @throws SmartmessagesAPIException
     * @return array The array of field names that was set, after filtering
     * @access public
     */
    public function setfieldorder($fields)
    {
        if (empty($fields) or !in_array('emailaddress', $fields)) {
            throw new SmartmessagesAPIException('Invalid field order');
        }
        $fieldstring = implode(',', $fields);
        $res = $this->dorequest('setfieldorder', array('fields' => $fieldstring));
        return $res['fields'];
    }

    /**
     * Get a list of everyone that has unsubscribed from the specified mailing list.
     * @param integer $listid
     * @throws SmartmessagesAPIException
     * @return array
     * @access public
     */
    public function getlistunsubs($listid)
    {
        if ((integer)$listid <= 0) {
            throw new SmartmessagesAPIException('Invalid list id');
        }
        $res = $this->dorequest('getlistunsubs', array('listid' => (integer)$listid));
        return $res['unsubscribes'];
    }

    /**
     * Upload a mailing list.
     * @see getlists()
     * @see getfieldorder()
     * @see getuploadinfo()
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
     * @throws SmartmessagesAPIException
     * @return integer|boolean On success, the upload ID for passing to getuploadinfo(), otherwise boolean false
     * @access public
     */
    public function uploadlist(
        $listid,
        $listfilename,
        $source,
        $definitive = false,
        $replace = false,
        $fieldorderfirstline = false
    ) {
        if ((integer)$listid <= 0) {
            throw new SmartmessagesAPIException('Invalid list id');
        }
        if (!file_exists($listfilename)) {
            throw new SmartmessagesAPIException('File does not exist!');
        }
        if (filesize($listfilename) < 6) { //This is the smallest a single external email address could possibly be
            throw new SmartmessagesAPIException('File does not contain any data!');
        }
        $res = $this->dorequest(
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
     * @see getlists()
     * @see getfieldorder()
     * @see uploadlist()
     * @param integer $listid The ID of the list the upload belongs to
     * @param integer $uploadid The ID of the upload (as returned from uploadlist())
     * @throws SmartmessagesAPIException
     * @return array A list of upload properties. Includes lists of bad or invalid addresses, source tracking field
     * @access public
     */
    public function getuploadinfo($listid, $uploadid)
    {
        if ((integer)$listid <= 0 or (integer)$uploadid <= 0) {
            throw new SmartmessagesAPIException('Invalid getuploadinfo parameters');
        }
        $res = $this->dorequest(
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
     * @see getlists()
     * @see uploadlist()
     * @see getuploadinfo()
     * @param integer $listid The ID of the list the upload belongs to
     * @throws SmartmessagesAPIException
     * @return array An array of uploads with properties for each.
     * @access public
     */
    public function getuploads($listid)
    {
        if ((integer)$listid <= 0) {
            throw new SmartmessagesAPIException('Invalid getuploads parameters');
        }
        $res = $this->dorequest('getuploads', array('listid' => (integer)$listid));
        return $res['uploads'];
    }

    /**
     * Cancel a pending or in-progress upload.
     * Cancelled uploads are deleted, so won't appear in getuploads()
     * Deletions are asynchronous, so won't happen immediately
     * @see getlists()
     * @see uploadlist()
     * @param integer $listid The ID of the list the upload belongs to
     * @param integer $uploadid The ID of the upload (as returned from uploadlist())
     * @throws SmartmessagesAPIException
     * @return boolean true on success
     * @access public
     */
    public function cancelupload($listid, $uploadid)
    {
        if ((integer)$listid <= 0 or (integer)$uploadid <= 0) {
            throw new SmartmessagesAPIException('Invalid getuploadinfo parameters');
        }
        $res = $this->dorequest(
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
    public function getcallbackurl()
    {
        $res = $this->dorequest('getcallbackurl');
        return $res['url'];
    }

    /**
     * Set the callback URL for your account.
     * Read our support wiki for more details on this
     * @param string $url The URL of your callback script (this will be on YOUR web server, not ours)
     * @throws SmartmessagesAPIException
     * @return boolean true on success
     * @access public
     */
    public function setcallbackurl($url)
    {
        if (trim($url) == '') {
            throw new SmartmessagesAPIException('Invalid setcallbackurl url');
        }
        $res = $this->dorequest('setcallbackurl', array('url' => $url));
        return $res['status'];
    }

    /**
     * Simple address validator.
     * It's more efficient to use a function on your own site to do this, but using this will ensure that
     * any address you add to a list will also be accepted by us
     * If you encounter an address that we reject that you think we shouldn't, please tell us!
     * Read our support wiki for more details on this
     * @param string $address
     * @return array
     * @access public
     */
    public function validateaddress($address)
    {
        $res = $this->dorequest('validateaddress', array('address' => $address));
        return $res['valid'];
    }

    /**
     * Get a list of campaign folder names and IDs.
     * @return array
     * @access public
     */
    public function getcampaigns()
    {
        $res = $this->dorequest('getcampaigns');
        return $res['campaigns'];
    }

    /**
     * Create a campaign folder.
     * Note that folder names do NOT need to be unique, but we suggest you make them so
     * @param string $name The name for the new campaign folder - up to 100 characters long
     * @return integer The ID of the new campaign
     * @access public
     */
    public function addcampaign($name)
    {
        $res = $this->dorequest('addcampaign', array('name' => $name));
        return $res['campaignid'];
    }

    /**
     * Update the name of a campaign folder.
     * @param integer $campaignid The ID of the campaign folder to update
     * @param string $name The new name of the campaign folder (max 100 chars)
     * @return boolean True on success
     * @access public
     */
    public function updatecampaign($campaignid, $name)
    {
        $res = $this->dorequest(
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
    public function deletecampaign($campaignid)
    {
        $res = $this->dorequest('deletecampaign', array('campaignid' => (integer)$campaignid));
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
    public function getcampaignmailshots($campaignid)
    {
        $res = $this->dorequest('getcampaignmailshots', array('campaignid' => $campaignid));
        return $res['mailshots'];
    }

    /**
     * Get detailed info about a single mailshot.
     * @param integer $mailshotid The ID of the mailshot you want to get info on
     * @return array
     * @access public
     */
    public function getmailshot($mailshotid)
    {
        $res = $this->dorequest('getmailshot', array('mailshotid' => $mailshotid));
        return $res['mailshot'];
    }

    /**
     * Get clicks generated by a single mailshot.
     * @param integer $mailshotid The ID of the mailshot you want to get clicks for
     * @param boolean $ascsv Set to true if you'd like the response in CSV instead of the currently selected format
     * @return array|string
     * @access public
     */
    public function getmailshotclicks($mailshotid, $ascsv = false)
    {
        $res = $this->dorequest(
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
    public function getmailshotopens($mailshotid, $ascsv = false)
    {
        $res = $this->dorequest(
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
    public function getmailshotunsubs($mailshotid, $ascsv = false)
    {
        $res = $this->dorequest(
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
    public function getmailshotbounces($mailshotid, $ascsv = false)
    {
        $res = $this->dorequest(
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
    public function gettemplates($includeglobal = false, $includeinherited = true)
    {
        $res = $this->dorequest(
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
    public function gettemplate($templateid)
    {
        $res = $this->dorequest('gettemplate', array('templateid' => $templateid));
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
     * @return integer, or false on failure
     * @access public
     */
    public function addtemplate(
        $name,
        $html,
        $plain,
        $subject,
        $description = '',
        $generateplain = false,
        $language = 'en',
        $importimages = false
    ) {
        //Use a post request to cope with large content
        $res = $this->dorequest(
            'addtemplate',
            array(
                'name' => $name,
                'plain' => $plain,
                'html' => $html,
                'subject' => $subject,
                'description' => $description,
                'generateplain' => $generateplain,
                'language' => $language,
                'importimages' => ($importimages == true)
            ),
            null,
            true
        );
        return ($res['status'] ? $res['templateid'] : false); //Return the new template ID on success, or false if it failed
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
     * @return integer, or false on failure
     * @access public
     */
    public function updatetemplate(
        $templateid,
        $name,
        $html,
        $plain,
        $subject,
        $description = '',
        $generateplain = false,
        $language = 'en',
        $importimages = false
    ) {
        //Use a post request to cope with large content
        $res = $this->dorequest(
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
                'importimages' => ($importimages == true)
            ),
            null,
            true
        );
        return $res['status']; //Return the new template ID on success, or false if it failed
    }

    /**
     * Add a new template from a URL.
     * All string params should use ISO 8859-1 character set
     * Templates imported this way will automatically have a plain text version generated
     * @param string $name The name of the new template
     * @param string $url The location of the template web page
     * @param string $subject The default subject template
     * @param string $description A plain-text description of the template (in ISO 8859-1 charset)
     * @param bool $importimages
     * @return integer, or false on failure
     * @access public
     */
    public function addtemplatefromurl($name, $url, $subject, $description = '', $importimages = false)
    {
        $res = $this->dorequest(
            'addtemplatefromurl',
            array(
                'name' => $name,
                'url' => $url,
                'subject' => $subject,
                'description' => $description,
                'importimages' => ($importimages == true)
            )
        );
        //Return the new template ID on success, or false if it failed
        return ($res['status'] ? $res['templateid'] : false);
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
    public function deletetemplate($templateid)
    {
        $res = $this->dorequest('deletetemplate', array('templateid' => (integer)$templateid));
        return $res['status']; //Return the new template ID on success, or false if it failed
    }

    /**
     * Create and optionally send a new mailshot.
     * All string params should use ISO 8859-1 character set
     * @param integer $templateid The id of the template to send
     * @param integer $listid The id of the mailing list to send to
     * @param string $title The name of the new mailshot (if left blank, one will created automatically)
     * @param integer $campaignid The id of the campaign folder to store the mailshot in
     * @param string $subject The subject template (if left blank, template default subject will be used)
     * @param string $fromaddr The email address the mailshot will be sent from
     * @param string $fromname The name the mailshot will be sent from
     * @param string $replyto If you want replies to go somewhere other than the from address, supply one here
     * @param string $when When to send the mailshot, the string 'now' (or empty) for immediate send,
     *      or an ISO-format UTC date ('yyyy-mm-dd hh:mm:ss')
     * @param boolean $continuous Is this a continuous mailshot? (never completes, existing subs are ignored,
     *      new subscriptions are sent a message immediately, ideal for 'welcome' messages)
     * @param array $elements For future expansion; ignore for now
     * @return integer of the new mailshot id, or false on failure
     * @access public
     */
    public function sendmailshot(
        $templateid,
        $listid,
        $title = '',
        $campaignid,
        $subject = '',
        $fromaddr,
        $fromname = '',
        $replyto = '',
        $when = 'now',
        $continuous = false,
        $elements = array()
    ) {
        $res = $this->dorequest(
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
                'continuous' => (boolean)$continuous,
                'elements' => $elements
            )
        );
        return ($res['status'] ? $res['mailshotid'] : false); //Return the new template ID on success, or false if it failed
    }

    /**
     * Generic wrapper for issuing API requests.
     * @param string $command The name of the API function to call
     * @param array $params An associative array of function parameters to pass
     * @param string $urloverride A URL to override the default location (typically used by login)
     * @param boolean $post whether to do a POST instead of a GET
     * @param array $files An array of local filenames to attach to a POST request
     * @param bool $returnraw
     * @throws SmartmessagesAPIException
     * @return mixed Whatever comes back from the API call (decoded)
     */
    protected function dorequest(
        $command,
        $params = array(),
        $urloverride = '',
        $post = false,
        $files = array(),
        $returnraw = false
    ) {
        ini_set('arg_separator.output', '&');
        //All commands except login need an accesskey
        if (!empty($this->accesskey)) {
            $params['accesskey'] = $this->accesskey;
        }
        if (empty($urloverride)) {
            if (empty($this->endpoint)) {
                //We can't connect
                throw new SmartmessagesAPIException('Missing Smartmessages API URL');
            } else {
                $url = $this->endpoint;
            }
        } else {
            $url = $urloverride;
        }
        $url .= $command;
        //Make the request (must have fopen wrappers enabled)
        if ($post) {
            if ($this->debug) {
                echo "<h1>POST Request (" . htmlspecialchars($command) . "):</h1><p>" . htmlspecialchars(
                        $url
                    ) . "</p>\n";
            }
            $response = $this->doPostRequest($url, $params, $files);
        } else {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            if ($this->debug) {
                echo "<h1>GET Request (" . htmlspecialchars($command) . "):</h1><p>" . htmlspecialchars(
                        $url
                    ) . "</p>\n";
            }
            $response = file_get_contents($url);
        }
        //If you want to support response types other than serialised PHP, you'll need to write your own,
        //though php is obviously the best fit since we are in it already!
        if ($returnraw) { //Return undecoded response if that was asked for
            return $response;
        }
        $response = unserialize($response);
        if (array_key_exists('status', $response)) {
            $this->laststatus = ($response['status'] == true);
        }
        if (array_key_exists('msg', $response)) {
            $this->message = $response['msg'];
        } else {
            $this->message = '';
        }
        if (array_key_exists('errorcode', $response)) {
            $this->errorcode = $response['errorcode'];
        } else {
            $this->errorcode = '';
        }
        if ($this->debug) {
            echo "<h1>Response:</h1><p>";
            var_dump($response);
            echo "</p>\n";
        }
        if (!$this->laststatus) {
            throw new SmartmessagesAPIException($this->message, $this->errorcode);
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

        if ($this->debug) {
            echo "<h2>POST body:</h2><pre>";
            echo htmlentities(substr($data, 0, 8192)); //Limit size of debug output
            echo "</pre>\n";
        }

        $ctx = stream_context_create($params);
        $fileh = fopen($url, 'rb', false, $ctx);

        if (!$fileh) {
            throw new Exception("Problem with $url, $php_errormsg");
        }

        $response = @stream_get_contents($fileh);
        if ($response === false) {
            throw new Exception("Problem reading data from $url, $php_errormsg");
        }
        return $response;
    }
}

class SmartmessagesAPIException extends \Exception
{
}
