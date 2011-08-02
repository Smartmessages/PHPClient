using System;
using System.Data;
using System.Configuration;
using System.Web;
using System.Web.Security;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Web.UI.WebControls.WebParts;
using System.Web.UI.HtmlControls;
using System.Collections;
using System.Collections.Generic;
using System.Net;
using System.IO;
using System.Text;
using System.Security.Cryptography;
using System.Xml;


/// <summary>
/// Summary description for SmartmessagesAPI
/// </summary>
public class SmartmessagesAPI
{
    /// <summary>
    /// The authenticated access key for this session
    /// </summary>
    protected string accesskey = String.Empty;

    /// <summary>
    /// The API endpoint to direct requests at, set during login
    /// </summary>
    protected string endpoint = String.Empty;

    /// <summary>
    /// Whether we have logged in successfully
    /// </summary>
    public bool connected = false;

    /// <summary>
    /// Timestamp of when this session expires
    /// </summary>
    public int expires = 0;

    /// <summary>
    /// The user name used to log in to the API, usually an email address
    /// </summary>
    public string accountname = String.Empty;

    /// <summary>
    /// The most recent status message received from the API - true for success, false otherwise
    /// </summary>
    public bool laststatus = true;

    /// <summary>
    /// The most recent error code received. 0 if no error.
    /// </summary>
    public int errorcode = 0;

    /// <summary>
    /// The most recent message received in an API response. Does not necessarily indicate an error, 
    /// may have some other informational content.
    /// </summary>
    public string message = String.Empty;

    /// <summary>
    /// Whether to run in debug mode. With this enabled, all requests and responses generate descriptive output
    /// </summary>
    public bool debug = false;

    /// <summary>
    /// Constructor, creates a new Smartmessages API instance with debugging set to false
    /// </summary>
    public SmartmessagesAPI()
    {
        this.debug = false;
    }

    /// <summary>
    /// Constructor override, creates a new Smartmessages API instance with debug option
    /// </summary>
    public SmartmessagesAPI(bool debug)
    {
        this.debug = debug;
    }

    /// <summary>
    /// Open a session with the Smartmessages API
    /// Throws an exception if login fails
    /// @param string user         - The user name (usually an email address)
    /// @param string password
    /// @param string apikey       - The API key as shown on the settings page of the smartmessages UI
    /// @return boolean true if login was successful
    /// @access public
    /// </summary>
    public bool login(string user, string pass, string apikey)
    {
        string baseurl = "https://www.smartmessages.net/api/";
        return login(user, pass, apikey, baseurl);
    }

    /// <summary>
    /// Open a session with the Smartmessages API
    /// Throws an exception if login fails
    /// @param string user         - The user name (usually an email address)
    /// @param string password
    /// @param string apikey       - The API key as shown on the settings page of the smartmessages UI
    /// @param string baseurl      - The initial entry point for the Smartmessage API
    /// @return boolean true if login was successful
    /// @access public
    /// </summary>
    public bool login(string user, string pass, string apikey, string baseurl)
    {
        Hashtable request_params = new Hashtable();
        request_params["username"] = user;
        request_params["password"] = pass;
        request_params["apikey"] = apikey;
        request_params["outputformat"] = "xml";
        string response = dorequest("login", request_params, baseurl);
        DataSet ds = new DataSet("login");
        ds.ReadXml(new StringReader(response));
        this.connected = true;
        this.accesskey = ds.Tables["response"].Rows[0]["accesskey"].ToString().Trim();
        this.endpoint = ds.Tables["response"].Rows[0]["endpoint"].ToString().Trim();
        this.expires = int.Parse(ds.Tables["response"].Rows[0]["expires"].ToString().Trim());
        this.accountname = ds.Tables["response"].Rows[0]["accountname"].ToString().Trim();
        return true;
    }

    /// <summary>
    /// Close a session with the Smartmessages API
    /// @access public
    /// </summary>
    public void logout()
    {
        dorequest("logout");
        this.connected = false;
        this.accesskey = String.Empty;
        this.expires = 0;
    }

    /// <summary>
    /// Does nothing, but keeps a connection open and extends the session expiry time
    /// @access public
    /// </summary>
    public bool ping()
    {
        string response = dorequest("ping");
        DataSet ds = new DataSet("ping");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["response"].Rows[0]["status"].ToString()=="0"?false:true;
    }

    /// <summary>
    /// Subscribe an address to a list
    /// @see getlists()
    /// @param string address The email address
    /// @param integer listid The ID of the list to subscribe the user to
    /// @param string dear A preferred greeting that's not necessarily their actual name, such as 'Scooter', 'Mrs Smith', 'Mr President'
    /// @param string firstname
    /// @param string lastname
    /// @return boolean true if subscribe was successful
    /// @access public
    /// </summary>
    public bool subscribe(string address, string listid, string dear, string firstname, string lastname) {
        if (address.Trim() == "" || int.Parse(listid) <= 0) {
            throw new SmartmessagesAPIException("Invalid subscribe parameters");
        }

        Hashtable request_params = new Hashtable();
        request_params["address"] = address;
        request_params["listid"] = listid;
        request_params["dear"] = dear;
        request_params["firstname"] = firstname;
        request_params["lastname"] = lastname;

        string response = dorequest("subscribe", request_params);

        DataSet ds = new DataSet("subscribe");
        ds.ReadXml(new StringReader(response));

        return ds.Tables["response"].Rows[0]["status"].ToString()=="0"?false:true;
    }

    public bool subscribe(string address, string listid)
    {
        return subscribe(address, listid, "", "", "");
    }

    /// <summary>
    /// Unsubscribe an address from a list
    /// @see getlists()
    /// @param string address The email address
    /// @param integer listid The ID of the list to unsubscribe the user from
    /// @return boolean true if unsubscribe was successful
    /// @access public
    /// </summary>
    public bool unsubscribe(string address, string listid) {
        if (address.Trim() == "" || int.Parse(listid) <= 0) {
            throw new SmartmessagesAPIException("Invalid unsubscribe parameters");
        }
        Hashtable request_params = new Hashtable();
        request_params["address"] = address;
        request_params["listid"] = listid;
        string response = dorequest("unsubscribe", request_params);
        DataSet ds = new DataSet("unsubscribe");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["response"].Rows[0]["status"].ToString()=="0"?false:true;
    }

    /// <summary>
    /// Get the details of all the mailing lists in your account
    /// @return DataTable
    /// @access public
    /// </summary>
    public DataTable getlists() {
        string response = dorequest("getlists");
        DataSet ds = new DataSet("getlists");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["element"];
    }

    /**
     * Get info about a recipient
     * @param string address The email address
     * @return array Info about the user
     * @access public
     */
    public DataSet getuserinfo(string address) {
        if (address.Trim() == "") {
            throw new SmartmessagesAPIException("Invalid email address");
        }
        Hashtable request_params = new Hashtable();
        request_params["address"] = address;
        string response = dorequest("getuserinfo", request_params);
        DataSet ds = new DataSet("getuserinfo");
        ds.ReadXml(new StringReader(response));
        return ds;
    }

    /**
     * Set info about a recipient
     * @see getuserinfo()
     * @param string address The email address
     * @param array userinfo Array of user properties in the same format as returned by getuserinfo()
     * @return boolean true on success
     * @access public
     */
    public bool setuserinfo(string address, string userinfo) {
        if (address.Trim() == "") {
            throw new SmartmessagesAPIException("Invalid email address");
        }
        string request_params = "address=" + address + "&" + userinfo;
        string response = dorequest("setuserinfo", request_params);
        DataSet ds = new DataSet("setuserinfo");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["response"].Rows[0]["status"].ToString() == "0" ? false : true;
    }

    /// <summary>
    /// Get a list of everyone that has reported messages from you as spam
    /// Only available from some ISPs, notably hotmail and AOL
    /// @return DataSet
    /// @access public
    /// </summary>
    public DataSet getspamreporters() {
        string response = dorequest("getspamreporters");
        DataSet ds = new DataSet("spamreporters");
        ds.ReadXml(new StringReader(response));
        return ds;
    }

    /// <summary>
    /// Get your current default import field order list
    /// @return array
    /// @access public
    /// </summary>
    public DataTable getfieldorder() {
        string response = dorequest("getfieldorder");
        DataSet ds = new DataSet("getfieldorder");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["element"];
    }

    /// <summary>
    /// Get your current default import field order list
    /// The field list MUST include emailaddress
    /// Any invalid or unknown names will be ignored
    /// @see getfieldorder()
    /// @param array $fields Simple array of field names
    /// @return array The array of field names that was set, after filtering
    /// @access public
    /// </summary>
    public DataTable setfieldorder(string fields) {
        if (fields == String.Empty || fields.IndexOf("emailaddress") == -1) {
            throw new SmartmessagesAPIException("Invalid field order");
        }

        Hashtable request_params = new Hashtable();
        request_params["fields"] = fields.Replace(",","%2C");

        string response = dorequest("setfieldorder", request_params);
        DataSet ds = new DataSet("setfieldorder");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["element"];
    }

    /// <summary>
    /// Get a list of everyone that has unsubscribed from the specified mailing list
    /// @return DataTable
    /// @access public
    /// </summary>
    public DataTable getlistunsubs(string listid) {
        if (int.Parse(listid) <= 0) {
            throw new SmartmessagesAPIException("Invalid list id");
        }

        Hashtable request_params = new Hashtable();
        request_params["listid"] = listid;

        string response = dorequest("getlistunsubs", request_params);
        DataSet ds = new DataSet("getlistunsubs");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["element"];
    }

    /// <summary>
    /// Upload a mailing list
    /// @see getlists()
    /// @see getfieldorder()
    /// @see getuploadinfo()
    /// @param string listid The ID of the list to upload into
    /// @param string listfilename A path to a local file containing your mailing list in CSV format (may also be zipped)
    /// @param string source For audit trail purposes, you must populate this with a note of where this list came from
    /// @param boolean definitive If set to true, overwrite any existing data in the fields included in the file, otherwise existing data will not be touched, but recipients will still be added to the list
    /// @param boolean replace Whether to empty the list before uploading this list
    /// @param boolean fieldorderfirstline Set to true if the first line of the file contains field names
    /// @return integer|boolean On success, the upload ID for passing to getuploadinfo(), otherwise boolean false
    /// @access public
    /// </summary>
    public object uploadlist(string listid, string listfilename, string source, bool definitive, bool replace, bool fieldorderfirstline) {
        if (int.Parse(listid) <= 0) {
            throw new SmartmessagesAPIException("Invalid list id");
        }
        if (!File.Exists(listfilename)) {
            throw new SmartmessagesAPIException("File does not exist!");
        }
        if (filesize(listfilename) < 6) { //This is the smallest a single external email address could possibly be
            throw new SmartmessagesAPIException("File does not contain any data!");
        }

        Hashtable request_params = new Hashtable();
        request_params["method"] = "uploadlist";
        request_params["listid"] = listid;
        request_params["source"] = source;
        request_params["definitive"] = definitive==true?"true":"false";
        request_params["replace"] = replace==true?"true":"false";
        request_params["fieldorderfirstline"] = fieldorderfirstline==true?"true":"false";

        Hashtable files = new Hashtable();
        files[listfilename] = listfilename;

        string response = dorequest("uploadlist", request_params, null, true, files);
        DataSet ds = new DataSet("uploadlist");
        ds.ReadXml(new StringReader(response));

        //Return the upload ID on success, or false if it failed
        if(ds.Tables["response"].Rows[0]["status"].ToString()=="0")
            return false;
        else
            return ds.Tables["response"].Rows[0]["uploadid"].ToString();
    }

    /// <summary>
    /// Get info on a previous list upload
    /// @see getlists()
    /// @see getfieldorder()
    /// @see uploadlist()
    /// @param string listid The ID of the list the upload belongs to
    /// @param string uploadid The ID of the upload (as returned from uploadlist())
    /// @return DataTable A list of upload properties. Includes lists of bad or invalid addresses, source tracking field
    /// @access public
    /// </summary>
    public DataTable getuploadinfo(string listid, string uploadid) {
        if (int.Parse(listid) <= 0 || int.Parse(uploadid) <= 0) {
            throw new SmartmessagesAPIException("Invalid getuploadinfo parameters");
        }
        Hashtable request_params = new Hashtable();
        request_params["listid"] = listid;
        request_params["uploadid"] = uploadid;
        string response = dorequest("getuploadinfo", request_params);

        DataSet ds = new DataSet("getuploadinfo");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["element"];
    }

    /// <summary>
    /// Get info on all previous list uploads
    /// Only gives basic info on each upload, more detail can be obtained using getuploadinfo()
    /// @see getlists()
    /// @see uploadlist()
    /// @see getuploadinfo()
    /// @param integer listid The ID of the list the upload belongs to
    /// @return DataTable An array of uploads with properties for each.
    /// @access public
    /// </summary>
    public DataTable getuploads(string listid) {
        if (int.Parse(listid) <= 0) {
            throw new SmartmessagesAPIException("Invalid getuploads parameters");
        }
        Hashtable request_params = new Hashtable();
        request_params["listid"] = listid;
        string response = dorequest("getuploads", request_params);

        DataSet ds = new DataSet("getuploads");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["element"];
    }

    /// <summary>
    /// Cancel a pending or in-progress upload
    /// Cancelled uploads are deleted, so won't appear in getuploads()
    /// Deletions are asynchronous, so won't happen immediately
    /// @see getlists()
    /// @see uploadlist()
    /// @param string listid The ID of the list the upload belongs to
    /// @param string uploadid The ID of the upload (as returned from uploadlist())
    /// @return boolean true on success
    /// @access public
    /// </summary>
    public bool cancelupload(string listid, string uploadid) {
        if (int.Parse(listid) <= 0 || int.Parse(uploadid) <= 0) {
            throw new SmartmessagesAPIException("Invalid getuploadinfo parameters");
        }
        Hashtable request_params = new Hashtable();
        request_params["listid"] = listid;
        request_params["uploadid"] = uploadid;
        string response = dorequest("cancelupload", request_params);

        DataSet ds = new DataSet("cancelupload");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["response"].Rows[0]["status"].ToString()=="0"?false:true;
    }

    /// <summary>
    /// Get the callback URL for your account
    /// Read our support wiki for more details on this
    /// @return string
    /// @access public
    /// </summary>
    public string getcallbackurl() {
        string response = dorequest("getcallbackurl");
        DataSet ds = new DataSet("getcallbackurl");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["response"].Rows[0]["url"].ToString();
    }

    /// <summary>
    /// Set the callback URL for your account
    /// Read our support wiki for more details on this
    /// @param string url The URL of your callback script (this will be on YOUR web server, not ours)
    /// @return true on success
    /// @access public
    /// </summary>
    public bool setcallbackurl(string url) {
        if (url.Trim() == "") {
            throw new SmartmessagesAPIException("Invalid setcallbackurl url");
        }
        Hashtable request_params = new Hashtable();
        request_params["url"] = url;
        string response = dorequest("setcallbackurl", request_params);
        
        DataSet ds = new DataSet("setcallbackurl");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["response"].Rows[0]["status"].ToString()=="0"?false:true;
    }

    /// <summary>
    /// Simple address validator
    /// It's more efficient to use a function on your own site to do this, but using this will ensure that any address you add to a list will also be accepted by us
    /// If you encounter an address that we reject that you think we shouldn't, please tell us!
    /// Read our support wiki for more details on this
    /// @return boolean
    /// @access public
    /// </summary>
    public bool validateaddress(string address) {
        Hashtable request_params = new Hashtable();
        request_params["address"] = address;
        string response = dorequest("validateaddress", request_params);
        DataSet ds = new DataSet("validateaddress");
        ds.ReadXml(new StringReader(response));
        return ds.Tables["response"].Rows[0]["valid"].ToString() == "0" ? false : true;
    }

    protected string dorequest(string command)
    {
        Hashtable request_params = new Hashtable();
        return dorequest(command, request_params, String.Empty, false, new Hashtable());
    }

    protected string dorequest(string command, Hashtable request_params)
    {
        return dorequest(command, request_params, String.Empty, false, new Hashtable());
    }

    protected string dorequest(string command, Hashtable request_params, string urloverride)
    { 
        return dorequest(command, request_params, urloverride, false, new Hashtable());
    }

    protected string dorequest(string command, Hashtable request_params, string urloverride, bool post, Hashtable files)
    {
        string response = "";

        //All commands except login need an accesskey
        if (this.accesskey != String.Empty) {
            if (!request_params.ContainsKey("accesskey"))
                request_params.Add("accesskey", this.accesskey);
        }

        if(!request_params.ContainsKey("outputformat"))
            request_params.Add("outputformat", "xml"); // XML is default output format

        string url = "";
        if (urloverride == String.Empty || urloverride == null) {
            if (this.endpoint == String.Empty) {
                //We can't connect
                throw new SmartmessagesAPIException("Missing Smartmessages API URL");
            } else {
                url = this.endpoint;
            }
        } else {
            url = urloverride;
        }
        url += command;

        //Make the request
        if (post) {
            if (this.debug) {
               HttpContext.Current.Response.Write("<h1>POST Request (" + htmlspecialchars(command) + "):</h1><p>" + htmlspecialchars(url) + "</p>\n");
            }
            response = do_post_request(url, request_params, files);
        } else {
            if (request_params != null && request_params.Count > 0) {
                url += '?' + http_build_query(request_params);
            }
            if (this.debug) {
                HttpContext.Current.Response.Write("<h1>GET Request (" + htmlspecialchars(command) +"):</h1><p>" + htmlspecialchars(url) + "</p>\n");
            }

            response = url_get_contents(url);
        }

        return response;
    }

    protected string dorequest(string command, string request_params)
    {
        string response = "";

        //All commands except login need an accesskey
        if (request_params.IndexOf("accesskey") == -1)
            request_params += "&accesskey=" + this.accesskey;

        if(request_params.IndexOf("outputformat") == -1)
            request_params += "&outputformat=xml"; // XML is default output format

        string url = "";
        if (this.endpoint == String.Empty) {
            //We can't connect
            throw new SmartmessagesAPIException("Missing Smartmessages API URL");
        } else {
            url = this.endpoint;
        }
        url += command;

        //Make the request

        if (request_params != null && request_params != String.Empty) {
            url += '?' + request_params.Replace("[", "%5B").Replace("]", "%5D");
        }
        if (this.debug) {
            HttpContext.Current.Response.Write("<h1>GET Request (" + htmlspecialchars(command) +"):</h1><p>" + htmlspecialchars(url) + "</p>\n");
        }

        response = url_get_contents(url);

        return response;
    }

    private string do_post_request(string url, Hashtable postdata, Hashtable files)
    {
        string response = "";
        string data = "";
        string boundary = "---------------------" + md5(rand(0,32000)).Substring(0, 10);

        //Collect Postdata
        foreach(string key in postdata.Keys) {
            data += "--" + boundary + "\n";
            data += "Content-Disposition: form-data; name=\"" + key + "\"\n\n" + postdata[key].ToString() + "\n";
        }

        data += "--" + boundary + "\n";

        //Collect Filedata
        foreach(string key in files.Keys) {
            string file = files[key].ToString();
            string filename = basename(file);
            data += "Content-Disposition: form-data; name=\""+ filename + "\"; filename=\"" + filename + "\"\n";
            data += "Content-Type: application/octet-stream\n"; //Could be anything, so just upload as raw binary stuff
            data += "Content-Transfer-Encoding: binary\n\n";
            data += file_get_contents(file) + "\n";
            data += "--" + boundary + "--\n";
        }

        if (this.debug) {
            HttpContext.Current.Response.Write("<h2>POST body:</h2><pre>");
            if(data.Length > 8192)
                HttpContext.Current.Response.Write(htmlspecialchars(data).Substring(0, 8192)); //Limit size of debug output
            else
                HttpContext.Current.Response.Write(htmlspecialchars(data));

            HttpContext.Current.Response.Write("</pre>\n");
        }

        url = "http://www.smartmessages.net/api/uploadlist";
        url += "?accesskey=" + postdata["accesskey"].ToString() + "&outputformat=xml";
        HttpWebRequest wrq = (HttpWebRequest)HttpWebRequest.Create(url);
        wrq.Method = "POST";
        wrq.ContentType = "multipart/form-data; boundary=" + boundary;
        byte[] bin_data = Encoding.UTF8.GetBytes(data);
        wrq.ContentLength = bin_data.Length;
        Stream writeStream = wrq.GetRequestStream();
        writeStream.Write(bin_data, 0, bin_data.Length);
        writeStream.Close();
        HttpWebResponse wrs = (HttpWebResponse)wrq.GetResponse();
        StreamReader sr = new StreamReader(wrs.GetResponseStream());
        response = sr.ReadToEnd();

        return response;
    }

    private string url_get_contents(string address)
    {
        HttpWebRequest wrq = (HttpWebRequest)HttpWebRequest.Create(address);
        HttpWebResponse wrs = (HttpWebResponse)wrq.GetResponse();
        
        StreamReader sr = new StreamReader(wrs.GetResponseStream());
        return sr.ReadToEnd();
    }

    private string htmlspecialchars(string input)
    {
        input.Replace("&", "&amp;");
        input.Replace("\"", "&quot;");
        input.Replace("'", "&#039;");
        input.Replace("<", "&lt;");
        input.Replace(">", "&gt;");
        return input;
    }

    private string http_build_query(Hashtable request_params)
    {
        string query = "";
        int i = 0;
        foreach (string key in request_params.Keys)
        {
            if (i != 0)
                query += "&" + key + "=" + request_params[key].ToString();
            else
                query += key + "=" + request_params[key].ToString();
            i++;
        }
        return query;
    }

    private string rand(int min, int max)
    {
        Random random = new Random();
        return random.Next(min, max).ToString();
    }
    
    private string md5(string input)
    {
        Encoder enc = System.Text.Encoding.Unicode.GetEncoder();

        byte[] unicodeText = new byte[input.Length * 2];
        enc.GetBytes(input.ToCharArray(), 0, input.Length, unicodeText, 0, true);

        MD5 md5Service = new MD5CryptoServiceProvider();
        byte[] result = md5Service.ComputeHash(unicodeText);

        StringBuilder sb = new StringBuilder();
        for (int i=0;i<result.Length;i++)
        {
            sb.Append(result[i].ToString("X2"));
        }

        return sb.ToString();
    }

    private string basename(string input)
    {
        input = input.Replace("\\", "/"); //Windows style file paths
        string[] _input = input.Split('/');
        return _input[_input.Length - 1];
    }

    private string file_get_contents(string filepath)
    {
        string file_contents = "";
        StreamReader stream = new StreamReader(filepath);
        file_contents = stream.ReadToEnd();
        stream.Dispose();
        stream.Close();
        return file_contents;
    }

    private long filesize(string filepath)
    {
        FileInfo finfo = new FileInfo(filepath);
        return finfo.Length;
    }
}


class SmartmessagesAPIException : ArgumentException {
    public SmartmessagesAPIException() : base() { }
    public SmartmessagesAPIException(string message) : base(message) { }
}
