<%@ Page Language="C#" AutoEventWireup="true" CodeFile="SmartmessagesAPITest.aspx.cs" Inherits="SmartmessagesAPITest" %>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" >
<head runat="server">
    <title>Untitled Page</title>
</head>
<body>
    <form id="form1" runat="server">
    <div>
    <asp:DataGrid ID="getlistsGrid" runat="server" Visible="false">
    </asp:DataGrid><br />
        <asp:DataGrid ID="getuserinfoGrid" runat="server">
        </asp:DataGrid>
        <asp:DataGrid ID="setuserinfoGrid" runat="server">
        </asp:DataGrid>
        <asp:DataGrid id="getfieldorderGrid" runat="server">
        </asp:DataGrid>
        <asp:DataGrid id="setfieldorderGrid" runat="server">
        </asp:DataGrid>
        <asp:DataGrid id="getlistunsubsGrid" runat="server">
        </asp:DataGrid>
        <asp:DataGrid id="getuploadinfoGrid" runat="server">
        </asp:DataGrid>
        <asp:DataGrid id="getuploadsGrid" runat="server">
        </asp:DataGrid>
    </div>
    </form>
</body>
</html>
