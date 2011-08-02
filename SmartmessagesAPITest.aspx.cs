using System;
using System.Data;
using System.Configuration;
using System.Collections;
using System.Web;
using System.Web.Security;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Web.UI.WebControls.WebParts;
using System.Web.UI.HtmlControls;

public partial class SmartmessagesAPITest : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {
        SmartmessagesAPI sapi = new SmartmessagesAPI(true);
        //Test login
        if (sapi.login("user@example.com", "abc123", "f2a9d4b7a7894d6edff55e060bd67e8a"))
            Response.Write("<h1>Logged In</h1>");
        else
            Response.Write("<h1>:(</h1>");
        //Result: Works!

        //Test ping
        //Response.Write("<br /><br />ping: " + sapi.ping().ToString());
        //Result: Works!

        //Test subscribe
        //Response.Write("<br /><br />subscribe: " + sapi.subscribe("user@example.com", "12345").ToString());
        //Result: Works!

        //Test unsubscribe
        //Response.Write("<br /><br />unsubscribe: " + sapi.unsubscribe("user@example.com", "12345").ToString());
        //Result: Works!

        //Test getlists
        /*DataTable dt = sapi.getlists();
        getlistsGrid.Visible = true;
        getlistsGrid.DataSource = dt;
        getlistsGrid.DataBind();*/
        //Result: Works!

        //Test getuserinfo
        /*DataSet ds = sapi.getuserinfo("user@example.com");
        foreach (DataTable dt in ds.Tables)
            Response.Write("TableName : " + dt.TableName.ToString() + "<br />");
        getuserinfoGrid.DataSource = ds.Tables["userinfo"];
        getuserinfoGrid.DataBind();*/
        //Result: Works!

        //Test setuserinfo
        //sapi.setuserinfo("user@example.com", "userinfo[firstname]=Joe&userinfo[lastname]=User&userinfo[jobtitle]=Emailer");
        //Result: Works!

        //Test getspamreporters
        //ds = sapi.getspamreporters();
        //Result: Couldn't test with empty spam reporters list. Not sure if it is working.

        //Test getfieldorder
        /*getfieldorderGrid.DataSource = sapi.getfieldorder();
        getfieldorderGrid.DataBind();*/
        //Result: Works!

        //Test setfieldorder
        /*sapi.setfieldorder("emailaddress,firstname,lastname");
        setfieldorderGrid.DataSource = sapi.getfieldorder();
        setfieldorderGrid.DataBind();*/
        //Result: Works!

        //Test getlistunsubs
        getlistunsubsGrid.DataSource = sapi.getlistunsubs("12345");
        getlistunsubsGrid.DataBind();
        //Result: Works!

        //Test uploadlist
        /*object result = sapi.uploadlist("12345", "C:/Projects/SmartmessagesAPI/testlist.csv", "API Upload", true, true, false);
        Response.Write("uploadlist:" + result.ToString());*/
        //Result: Works!

        //Test getuploadinfo
        /*getuploadinfoGrid.DataSource = sapi.getuploadinfo("12345", "123");
        getuploadinfoGrid.DataBind();*/
        //Result: Works!

        //Test getuploads
        /*getuploadsGrid.DataSource = sapi.getuploads("12345");
        getuploadsGrid.DataBind();*/
        //Result: Works!

        //Test cancelupload
        /*Response.Write(sapi.cancelupload("12345", "123").ToString());*/
        //Result: Works!

        //Test setcallbackurl
        //Response.Write("setcallbackurl: " + sapi.setcallbackurl("http://foo.com/bar.php") + "<br />");
        //Result: Works!

        //Test getcallbackurl
        //Response.Write("getcallbackurl: " + sapi.getcallbackurl() + "<br />");
        //Result: Works!

        //Test validateaddress
        //Response.Write(sapi.validateaddress("foo@bar.com").ToString());
        //Result: 
    }
}
