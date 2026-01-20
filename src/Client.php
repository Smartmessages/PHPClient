<?php

/**
 * Smartmessages Client and Exception classes
 * PHP Version 8.0
 * @package Smartmessages\Client
 * @author Marcus Bointon <marcus@synchromedia.co.uk>
 * @copyright 2024 Synchromedia Limited
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/Smartmessages/PHPClient
 */

declare(strict_types=1);

namespace Smartmessages;

use GuzzleHttp\Exception\ClientException;
use Smartmessages\Exception\AuthException;
use Smartmessages\Exception\ConnectionException;
use Smartmessages\Exception\DataException;
use Smartmessages\Exception\ParameterException;

class Client
{
    /**
     * The default base URL for the Smartmessages API.
     * @type string
     */
    public const BASEURL = 'https://www.smartmessages.net/api/';

    /**
     * The authenticated access key for this session.
     * @type string
     */
    protected string $accessKey = '';

    /**
     * The API endpoint to direct requests at, set during login.
     * @type string
     */
    protected string $endpoint = '';

    /**
     * Whether we have logged in successfully.
     */
    protected bool $connected = false;

    /**
     * Timestamp of when this session expires, or 0 if not logged in.
     */
    protected int $expires = 0;

    /**
     * The username used to log in to the API, usually an email address.
     */
    protected string $accountName = '';

    /**
     * The most recent status value received from the API - true for success, false otherwise.
     */
    protected bool $lastStatus = true;

    /**
     * The most recent error code received. 0 if no error.
     */
    protected int $errorCode = 0;

    /**
     * Whether to run in debug mode.
     * With this enabled, all requests and responses generate descriptive output
     */
    public bool $debug = false;

    /**
     * Constructor, creates a new Smartmessages API instance
     * @param bool $debug Whether to activate debug output
     */
    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
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
     * @param string $user The username (usually an email address)
     * @param string $pass
     * @param string $apikey The API key as shown on the settings page of the smartmessages UI
     * @param string $baseurl The initial entry point for the Smartmessages API
     * @return bool True if login was successful
     * @throws AuthException
     * @throws Exception
     * @access public
     */
    public function login(
        string $user,
        string $pass,
        string $apikey,
        string $baseurl = self::BASEURL
    ): bool {
        $this->endpoint = $baseurl;
        $response = $this->get(
            'login',
            ['username' => $user, 'password' => $pass, 'apikey' => $apikey, 'outputformat' => 'php']
        );
        if (!$response['status']) {
            if ((int)$response['errorcode'] === 1) {
                throw new AuthException($response['msg'], $response['errorcode']);
            }
            throw new Exception($response['msg'], $response['errorcode']);
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
     * @throws AuthException
     * @throws ConnectionException
     * @throws DataException
     */
    public function logout(): void
    {
        $this->get('logout');
        $this->connected = false;
        $this->accessKey = '';
        $this->expires = 0;
    }

    /**
     * Does nothing, but keeps a connection open and extends the session expiry time.
     * @access public
     * @return bool
     */
    public function ping(): bool
    {
        $res = $this->get('ping');
        return $res['status'];
    }

    /**
     * Subscribe an address to a list.
     * @param string $address The email address
     * @param int $listId The ID of the list to subscribe the user to
     * @param string $dear A preferred greeting that's not necessarily their actual name,
     *  such as 'Scooter', 'Mrs Smith', 'Mr President'
     * @param string $firstname The subscriber's first name
     * @param string $lastname The subscriber's first name
     * @return bool
     * @throws ParameterException
     * @access public
     * @see getLists()
     */
    public function subscribe(
        string $address,
        int $listId,
        string $dear = '',
        string $firstname = '',
        string $lastname = ''
    ): bool {
        if ($listId <= 0 || trim($address) === '') {
            throw new ParameterException('Invalid subscribe parameters');
        }
        $res = $this->get(
            'subscribe',
            [
                'address'   => trim($address),
                'listid'    => $listId,
                'name'      => $dear,
                'firstname' => $firstname,
                'lastname'  => $lastname
            ]
        );
        return $res['status'];
    }

    /**
     * Unsubscribe an address from a list.
     * @param string $address The email address
     * @param int $listid The ID of the list to unsubscribe the user from
     * @return bool
     * @throws ParameterException
     * @access public
     * @see getLists()
     */
    public function unsubscribe(string $address, int $listid): bool
    {
        if ($listid <= 0 || trim($address) === '') {
            throw new ParameterException('Invalid unsubscribe parameters');
        }
        $res = $this->get('unsubscribe', ['address' => trim($address), 'listid' => $listid]);
        return $res['status'];
    }

    /**
     * Add an existing subscriber to another list.
     * Similar to subscribe, but without the associated semantics,
     * simply adds them to a list without notifications or verification.
     * @param string $address The email address
     * @param int $listid The ID of the list to add the user to
     * @param string $note A description of why this change was made, e.g. 'submitted competition form'
     * @return bool
     * @throws ParameterException
     * @access public
     * @see getLists()
     */
    public function addSubscription(string $address, int $listid, string $note = ''): bool
    {
        if ($listid <= 0 || trim($address) === '') {
            throw new ParameterException('Invalid add subscription parameters');
        }
        $res = $this->get(
            'addsubscription',
            [
                'address' => trim($address),
                'listid'  => $listid,
                'note'    => $note
            ]
        );
        return $res['status'];
    }

    /**
     * Delete an address from a list.
     * Does the same as unsubscribe, but without the associated semantics,
     * simply deletes them from a list without notifications, creating suppressions etc
     * @param string $address The email address
     * @param int $listid The ID of the list to delete the user from
     * @return bool
     * @throws ParameterException
     * @access public
     * @see getLists()
     */
    public function deleteSubscription(string $address, int $listid): bool
    {
        if ($listid <= 0 || trim($address) === '') {
            throw new ParameterException('Invalid delete subscription parameters');
        }
        $res = $this->get(
            'deletesubscription',
            [
                'address' => trim($address),
                'listid'  => $listid
            ]
        );
        return $res['status'];
    }

    /**
     * Get the details of all the mailing lists in your account.
     * @param bool $showall Whether to get all lists or just those set to visible
     * @return array
     * @access public
     */
    public function getLists(bool $showall = false): array
    {
        $res = $this->get('getlists', ['showall' => $showall]);
        return $res['mailinglists'];
    }

    /**
     * Get the details of your designated test list.
     * @return array
     * @access public
     */
    public function getTestList(): array
    {
        return $this->get('gettestlist');
    }

    /**
     * Download a complete mailing list.
     * Gets a complete list of recipients on a mailing list.
     * By default, the response to this call returns data in CSV format, which is smaller,
     * faster and easier to handle (just save it directly to a file) than other formats.
     * We strongly recommend sticking with that format as the response can be extremely large
     * in PHP, JSON, or XML formats, extending to hundreds of megabytes for large lists,
     * taking a correspondingly long time to download, and possibly causing memory problems
     * in client code.
     * @param int $listid The ID of the list to fetch
     * @param bool $ascsv Whether to get the list as CSV,
     *      as opposed to the currently selected format (e.g. JSON or XML)
     * @return string|array
     * @access public
     */
    public function getList(int $listid, bool $ascsv = true): array|string
    {
        $res = $this->get(
            'getlist',
            ['listid' => $listid, 'ascsv' => $ascsv],
            $ascsv
        );
        if ($ascsv) {
            return $res;
        }

        return $res['list'];
    }

    /**
     * Add a mailing list.
     * @param string $name The name of the new list (max 100 chars)
     * @param string $description The description of the new list (max 255 chars)
     * @param bool $visible Whether this list is publicly visible or not
     * @return int The ID of the newly created list
     * @access public
     */
    public function addList(string $name, string $description = '', bool $visible = true): int
    {
        $res = $this->get(
            'addlist',
            ['name' => trim($name), 'description' => trim($description), 'visible' => $visible]
        );
        return $res['listid'];
    }

    /**
     * Update all the properties of a mailing list.
     * Note that all params are required, you can't just set one
     * @param int $listid The ID of the list to update
     * @param string $name The new name of the list (max 100 chars)
     * @param string $description The new description of the list (max 255 chars)
     * @param bool $visible Whether this list is publicly visible or not
     * @return bool True on success
     * @access public
     */
    public function updateList(int $listid, string $name, string $description, bool $visible): bool
    {
        $res = $this->get(
            'updatelist',
            [
                'listid'      => $listid,
                'name'        => trim($name),
                'description' => trim($description),
                'visible'     => $visible
            ]
        );
        return $res['status'];
    }

    /**
     * Delete a mailing list.
     * Note that deleting a mailing list will also delete all mailshots that have used it
     * @param int $listid The ID of the list to delete
     * @return bool True on success
     * @access public
     */
    public function deleteList($listid): bool
    {
        $res = $this->get('deletelist', ['listid' => (int)$listid]);
        return $res['status'];
    }

    /**
     * Get info about a recipient.
     * @param string $address The email address
     * @return array
     * @throws ParameterException
     * @access public
     */
    public function getUserInfo(string $address): array
    {
        if (trim($address) === '') {
            throw new ParameterException('Invalid email address');
        }
        $res = $this->get('getuserinfo', ['address' => $address]);
        return $res['userinfo'];
    }

    /**
     * Set info about a recipient.
     * @param string $address The email address
     * @param array $userinfo Array of user properties in the same format as returned by getuserinfo()
     * @return bool
     * @throws ParameterException
     * @access public
     * @see getUserInfo()
     */
    public function setUserInfo(string $address, array $userinfo): bool
    {
        if (trim($address) === '') {
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
    public function getSpamReporters(): array
    {
        $res = $this->get('getspamreporters');
        return $res['spamreporters'];
    }

    /**
     * Get your current default import field order list.
     * @return array
     * @access public
     */
    public function getFieldOrder(): array
    {
        $res = $this->get('getfieldorder');
        return $res['fields'];
    }

    /**
     * Set your default import field order list.
     * The field list MUST include emailaddress
     * Any invalid or unknown names will be ignored
     * @param array $fields Simple array of field names
     * @return array
     * @throws ParameterException
     * @access public
     * @see getFieldOrder()
     */
    public function setFieldOrder(array $fields): array
    {
        if (empty($fields) || !in_array('emailaddress', $fields, true)) {
            throw new ParameterException('Invalid field order');
        }
        $fieldstring = implode(',', $fields);
        $res = $this->get('setfieldorder', ['fields' => $fieldstring]);
        return $res['fields'];
    }

    /**
     * Get a list of everyone that has unsubscribed from the specified mailing list.
     * @param int $listid The list ID
     * @return array
     * @throws ParameterException
     * @access public
     */
    public function getListUnsubs(int $listid): array
    {
        if ($listid <= 0) {
            throw new ParameterException('Invalid list id');
        }
        $res = $this->get('getlistunsubs', ['listid' => $listid]);
        return $res['unsubscribes'];
    }

    /**
     * Upload a mailing list.
     * @param int $listid The ID of the list to upload into
     * @param string $listfilename A path to a local file containing your mailing list in CSV format
     *      (may also be zipped)
     * @param string $source For audit trail purposes, you must populate this with a description
     *      of where this list came from
     * @param bool $definitive If set to true, overwrite any existing data in the fields included
     *      in the file, otherwise existing data will not be touched, but recipients will still be added to the list
     * @param bool $replace Whether to empty the list before uploading this list
     *      (actually deletes anyone not in this upload so history is maintained)
     * @param bool $fieldorderfirstline Set to true if the first line of the file contains field names
     * @return bool|int
     * @throws ParameterException
     * @access public
     * @see getLists()
     * @see getFieldOrder()
     * @see getUploadInfo()
     */
    public function uploadList(
        int $listid,
        string $listfilename,
        string $source,
        bool $definitive = false,
        bool $replace = false,
        bool $fieldorderfirstline = false
    ): bool|int {
        if ($listid <= 0) {
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
                'method'              => 'uploadlist',
                'listid'              => $listid,
                'source'              => $source,
                'definitive'          => $definitive,
                'replace'             => $replace,
                'fieldorderfirstline' => $fieldorderfirstline
            ],
            [$listfilename]
        ); //This one requires a POST request for the list file attachment
        return ($res['status'] ? $res['uploadid'] : false); //Return the upload ID on success, or false if it failed
    }

    /**
     * Get info on a previous list upload.
     * @param int $listid The ID of the list the upload belongs to
     * @param int $uploadid The ID of the upload (as returned from uploadlist())
     * @return array
     * @throws ParameterException
     * @access public
     * @see getFieldOrder()
     * @see uploadList()
     * @see getLists()
     */
    public function getUploadInfo(int $listid, int $uploadid): array
    {
        if ($listid <= 0 || $uploadid <= 0) {
            throw new ParameterException('Invalid getuploadinfo parameters');
        }
        $res = $this->get(
            'getuploadinfo',
            [
                'listid'   => $listid,
                'uploadid' => $uploadid
            ]
        );
        return $res['upload'];
    }

    /**
     * Get info on all previous list uploads.
     * Only gives basic info on each upload, more detail can be obtained using getuploadinfo()
     * @param int $listid The ID of the list the upload belongs to
     * @return array
     * @throws ParameterException
     * @access public
     * @see getLists()
     * @see uploadList()
     * @see getUploadInfo()
     */
    public function getUploads(int $listid): array
    {
        if ($listid <= 0) {
            throw new ParameterException('Invalid getuploads parameters');
        }
        $res = $this->get('getuploads', ['listid' => $listid]);
        return $res['uploads'];
    }

    /**
     * Cancel a pending or in-progress upload.
     * Cancelled uploads are deleted, so won't appear in getuploads()
     * Deletions are asynchronous, so won't happen immediately
     * @param int $listid The ID of the list the upload belongs to
     * @param int $uploadid The ID of the upload (as returned from uploadlist())
     * @return bool
     * @throws ParameterException
     * @access public
     * @see getLists()
     * @see uploadList()
     */
    public function cancelUpload(int $listid, int $uploadid): bool
    {
        if ($listid <= 0 || $uploadid <= 0) {
            throw new ParameterException('Invalid getuploadinfo parameters');
        }
        $res = $this->get(
            'cancelupload',
            [
                'listid'   => $listid,
                'uploadid' => $uploadid
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
    public function getCallbackURL(): array
    {
        $res = $this->get('getcallbackurl');
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
    public function setCallbackURL(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
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
     * @param bool $remote Whether to do the validation locally (saving a round trip) or remotely
     * @return bool
     * @access public
     */
    public function validateAddress(string $address, bool $remote = false): bool
    {
        if (!$remote) {
            return (bool)filter_var($address, FILTER_VALIDATE_EMAIL);
        }
        $res = $this->get('validateaddress', ['address' => $address]);
        return (bool)$res['valid'];
    }

    /**
     * Get a list of campaign folder names and IDs.
     * @return array
     * @access public
     */
    public function getCampaigns(): array
    {
        $res = $this->get('getcampaigns');
        return $res['campaigns'];
    }

    /**
     * Create a campaign folder.
     * Note that folder names do NOT need to be unique, but we suggest you make them so
     * @param string $name The name for the new campaign folder - up to 100 characters long
     * @return int The ID of the new campaign
     * @access public
     */
    public function addCampaign(string $name): int
    {
        $res = $this->get('addcampaign', ['name' => $name]);
        return $res['campaignid'];
    }

    /**
     * Update the name of a campaign folder.
     * @param int $campaignid The ID of the campaign folder to update
     * @param string $name The new name of the campaign folder (max 100 chars)
     * @return bool True on success
     * @access public
     */
    public function updateCampaign(int $campaignid, string $name): bool
    {
        $res = $this->get(
            'updatecampaign',
            [
                'campaignid' => $campaignid,
                'name'       => trim($name)
            ]
        );
        return $res['status'];
    }

    /**
     * Delete a campaign folder.
     * Note that deleting a campaign will also delete all mailshots that it contains
     * @param int $campaignid The ID of the campaign folder to delete
     * @return bool True on success
     * @access public
     */
    public function deleteCampaign(int $campaignid): bool
    {
        $res = $this->get('deletecampaign', ['campaignid' => $campaignid]);
        return $res['status'];
    }


    /**
     * Get a list of mailshots within a campaign folder.
     * Contains sufficient info to populate list displays, so you don't need to call getmailshot() on each one
     * Note that message_count will only be populated for sending or completed mailshots
     * @param int $campaignid The ID of the campaign you want to get mailshots from
     * @return array
     * @access public
     */
    public function getCampaignMailshots(int $campaignid): array
    {
        $res = $this->get('getcampaignmailshots', ['campaignid' => $campaignid]);
        return $res['mailshots'];
    }

    /**
     * Get detailed info about a single mailshot.
     * @param int $mailshotid The ID of the mailshot you want to get info on
     * @return array
     * @access public
     */
    public function getMailshot(int $mailshotid): array
    {
        $res = $this->get('getmailshot', ['mailshotid' => $mailshotid]);
        return $res['mailshot'];
    }

    /**
     * Get clicks generated by a single mailshot.
     * @param int $mailshotid The ID of the mailshot you want to get clicks for
     * @param bool $ascsv Set to true if you'd like the response in CSV instead of the currently selected format
     * @return array|string
     * @access public
     */
    public function getMailshotClicks(int $mailshotid, bool $ascsv = false): array|string
    {
        $res = $this->get(
            'getmailshotclicks',
            [
                'mailshotid' => $mailshotid,
                'ascsv'      => $ascsv
            ],
            $ascsv
        );
        if ($ascsv) {
            return $res;
        }

        return $res['clicks'];
    }

    /**
     * Get opens relating to a single mailshot.
     * @param int $mailshotid The ID of the mailshot you want to get opens for
     * @param bool $ascsv Set to true if you'd like the response in CSV instead of the currently selected format
     * @return array|string
     * @access public
     */
    public function getMailshotOpens(int $mailshotid, bool $ascsv = false): array|string
    {
        $res = $this->get(
            'getmailshotopens',
            [
                'mailshotid' => $mailshotid,
                'ascsv'      => $ascsv
            ],
            $ascsv
        );
        if ($ascsv) {
            return $res;
        }

        return $res['opens'];
    }

    /**
     * Get unsubs relating to a single mailshot.
     * @param int $mailshotid The ID of the mailshot you want to get unsubs for
     * @param bool $ascsv Set to true if you'd like the response in CSV instead of the currently selected format
     * @return array|string
     * @access public
     */
    public function getMailshotUnsubs(int $mailshotid, bool $ascsv = false): array|string
    {
        $res = $this->get(
            'getmailshotunsubs',
            [
                'mailshotid' => $mailshotid,
                'ascsv'      => $ascsv
            ],
            $ascsv
        );
        if ($ascsv) {
            return $res;
        }

        return $res['unsubs'];
    }

    /**
     * Get bounces relating to a single mailshot.
     * @param int $mailshotid The ID of the mailshot you want to get bounces for
     * @param bool $ascsv Set to true if you'd like the response in CSV instead of the currently selected format
     * @return array|string
     * @access public
     */
    public function getMailshotBounces(int $mailshotid, bool $ascsv = false): array|string
    {
        $res = $this->get(
            'getmailshotbounces',
            [
                'mailshotid' => $mailshotid,
                'ascsv'      => $ascsv
            ],
            $ascsv
        );
        if ($ascsv) {
            return $res;
        }

        return $res['bounces'];
    }

    /**
     * Get a list of all available templates.
     * @param bool $includeglobal Whether to include standard smartmessages templates
     * @param bool $includeinherited Whether to include inherited templates
     * @return array
     * @access public
     */
    public function getTemplates(bool $includeglobal = false, bool $includeinherited = true): array
    {
        $res = $this->get(
            'gettemplates',
            ['includeglobal' => $includeglobal, 'includeinherited' => $includeinherited]
        );
        return $res['templates'];
    }

    /**
     * Get detailed info about a single template.
     * @param int $templateid The ID of the template you want to get
     * @return array
     * @access public
     */
    public function getTemplate(int $templateid): array
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
     * @param bool $generateplain Whether to generate a plain text version from the HTML version
     *      (if set, will ignore the value of $plain)
     * @param bool $importimages Whether to do a one-off import and URL conversion
     *      of images referenced in the template
     * @param bool $convertformat Set to true to automatically identify and convert from other template formats
     * @param bool $inline Set to true to automatically convert style sheets into inline styles when sending
     * @return int|bool Returns the ID of the new template or false on failure
     * @access public
     */
    public function addTemplate(
        string $name,
        string $html,
        string $plain,
        string $subject,
        string $description = '',
        bool $generateplain = false,
        bool $importimages = false,
        bool $convertformat = false,
        bool $inline = false
    ): bool|int {
        $res = $this->post(
            'addtemplate',
            [
                'name'          => $name,
                'plain'         => $plain,
                'html'          => $html,
                'subject'       => $subject,
                'description'   => $description,
                'generateplain' => $generateplain,
                'importimages'  => $importimages,
                'convertformat' => $convertformat,
                'inline'        => $inline
            ]
        );
        //Return the new template ID on success, or false if it failed
        return ($res['status'] ? $res['templateid'] : false);
    }

    /**
     * Update an existing template.
     * All string params should use UTF-8 character set
     * @param int $templateid
     * @param string $name The name of the template
     * @param string $html The HTML version of the template
     * @param string $plain The plain text version of the template
     * @param string $subject The default subject template
     * @param string $description A plain-text description of the template
     * @param bool $generateplain Whether to generate a plain text version from the HTML version
     *      (if set, will ignore the value of $plain), defaults to false
     * @param bool $importimages Whether to do a one-off import and URL conversion
     *      of images referenced in the template
     * @param bool $convertformat Set to true to automatically identify and convert from other template formats
     * @param bool $inline Set to true to automatically convert style sheets into inline styles when sending
     * @return bool
     * @access public
     */
    public function updateTemplate(
        int $templateid,
        string $name,
        string $html,
        string $plain,
        string $subject,
        string $description = '',
        bool $generateplain = false,
        bool $importimages = false,
        bool $convertformat = false,
        bool $inline = false
    ): bool {
        //Use a post request to cope with large content
        $res = $this->post(
            'updatetemplate',
            [
                'templateid'    => $templateid,
                'name'          => $name,
                'plain'         => $plain,
                'html'          => $html,
                'subject'       => $subject,
                'description'   => $description,
                'generateplain' => $generateplain,
                'importimages'  => $importimages,
                'convertformat' => $convertformat,
                'inline'        => $inline
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
     * @param bool $importimages Whether to do a one-off import and URL conversion
     *      of images referenced in the template
     * @param bool $convertformat Set to true to automatically identify and convert from other template formats
     * @param bool $inline Set to true to automatically convert style sheets into inline styles when sending
     * @return int|bool Returns the ID of the new template or false on failure
     * @throws ParameterException
     * @access public
     */
    public function addTemplateFromURL(
        string $name,
        string $url,
        string $subject,
        string $description = '',
        bool $importimages = false,
        bool $convertformat = false,
        bool $inline = false
    ): bool|int {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ParameterException('Invalid template URL');
        }
        $res = $this->get(
            'addtemplatefromurl',
            [
                'name'          => $name,
                'url'           => $url,
                'subject'       => $subject,
                'description'   => $description,
                'importimages'  => $importimages,
                'convertformat' => $convertformat,
                'inline'        => $inline
            ]
        );
        //Return the new template ID on success, or false if it failed
        return ($res['status'] ? (int)$res['templateid'] : false);
    }

    /**
     * Delete a template.
     * Note that deleting a template will also delete any mailshots that used it,
     * and all records and reports relating to it
     * To delete inherited templates you need to connect using the account they are inherited from
     * @param int $templateid The template id to delete
     * @return bool
     * @access public
     */
    public function deleteTemplate(int $templateid): bool
    {
        $res = $this->get('deletetemplate', ['templateid' => $templateid]);
        return $res['status']; //Return true on success, or false if it failed
    }

    /**
     * Create and optionally send a new mailshot.
     * All string params should use UTF-8 character set
     * @param int $templateid The id of the template to send
     * @param int $listid The id of the mailing list to send to
     * @param string $title The name of the new mailshot (if left blank, one will be created automatically)
     * @param int $campaignid The id of the campaign folder to store the mailshot in (test campaign by default)
     * @param string $subject The subject template (if left blank, template subject will be used)
     * @param string $fromaddr The address the mailshot will be sent from (account default or your login address)
     * @param string $fromname The name the mailshot will be sent from
     * @param string $replyto If you want replies to go somewhere other than the from address, supply one here
     * @param string $when When to send the mailshot, the string 'now' (or empty) for immediate send,
     *      or an ISO-format UTC date ('yyyy-mm-dd hh:mm:ss')
     * @param bool $continuous Is this a continuous mailshot? (never completes, existing subs are ignored,
     *      new subscriptions are sent a message immediately, ideal for 'welcome' messages)
     * @param bool $inline Set to true to automatically convert style sheets into inline styles when sending
     * @return int|bool ID of the new mailshot id, or false on failure
     * @access public
     */
    public function sendMailshot(
        int $templateid,
        int $listid,
        string $title = '',
        int $campaignid = 0,
        string $subject = '',
        string $fromaddr = '',
        string $fromname = '',
        string $replyto = '',
        string $when = 'now',
        bool $continuous = false,
        bool $inline = false
    ): bool|int {
        $res = $this->get(
            'sendmailshot',
            [
                'templateid' => $templateid,
                'listid'     => $listid,
                'title'      => $title,
                'campaignid' => $campaignid,
                'subject'    => $subject,
                'fromaddr'   => $fromaddr,
                'fromname'   => $fromname,
                'replyto'    => $replyto,
                'when'       => $when,
                'continuous' => $continuous,
                'inline'     => $inline
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
     * @param bool $returnraw Whether to decode the response (default) or return it as-is
     * @return mixed
     * @throws AuthException
     * @throws ConnectionException
     * @throws DataException
     */
    protected function request(
        string $verb,
        string $command,
        array $params = [],
        array $files = [],
        bool $returnraw = false
    ): mixed {
        //All commands except login need an accessKey
        if (!empty($this->accessKey)) {
            $params['accesskey'] = $this->accessKey;
        }
        //Check whether the session has expired before making the request
        //unless we're logging in
        if ($command !== 'login' && $this->expires <= time()) {
            throw new AuthException('Session has expired; Please log in again.');
        }
        if (empty($this->endpoint)) {
            //We can't connect
            throw new ConnectionException('Missing Smartmessages API URL');
        }
        $client = new \GuzzleHttp\Client(['base_uri' => $this->endpoint]);
        if ($this->debug) {
            echo "# $verb Request, command = ", $command, ":\n", $this->endpoint, "\n";
            echo "\n## Params: ", var_export($params, true), "\n";
            if (!empty($files)) {
                echo "\n## Files: ", var_export($files, true), "\n";
            }
            echo "\n";
        }
        //Make the request
        try {
            if ($verb === 'get') {
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
                    $form_files[] = ['name' => basename($file), 'contents' => fopen($file, 'rb')];
                }
                $response = $client->post(
                    $command,
                    [
                        'form_params' => $params,
                        'form_files'  => $form_files
                    ]
                );
            }
        } catch (ClientException $e) {
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
        //Response should be serialised PHP, so try to decode it
        $decodedResponse = @unserialize($body);
        if ($decodedResponse === false) {
            throw new DataException('Failed to unserialize PHP data', 0);
        }
        if (array_key_exists('status', $decodedResponse)) {
            $this->lastStatus = (bool)$decodedResponse['status'];
        }
        if (array_key_exists('errorcode', $decodedResponse)) {
            $this->errorCode = (int)$decodedResponse['errorcode'];
        } else {
            $this->errorCode = 0;
        }
        if (array_key_exists('expires', $decodedResponse)) {
            $this->expires = (int)$decodedResponse['expires'];
        }
        if ($this->debug) {
            echo "# Response:\n", var_export($decodedResponse, true), "\n";
        }
        return $decodedResponse;
    }

    /**
     * Send an HTTP GET request to the API
     * @param string $command
     * @param array $params
     * @param bool $returnraw
     * @return mixed
     * @throws AuthException
     * @throws ConnectionException
     * @throws DataException
     */
    protected function get(
        string $command,
        array $params = [],
        bool $returnraw = false
    ): mixed {
        return $this->request('get', $command, $params, [], $returnraw);
    }

    /**
     * Send an HTTP POST request to the API.
     * @param string $command
     * @param array $params
     * @param array $files Files to upload
     * @param bool $returnraw
     * @return mixed
     * @throws AuthException
     * @throws ConnectionException
     * @throws DataException
     */
    protected function post(
        string $command,
        array $params = [],
        array $files = [],
        bool $returnraw = false
    ): mixed {
        return $this->request('post', $command, $params, $files, $returnraw);
    }
}
