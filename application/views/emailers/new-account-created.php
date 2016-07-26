<?php $this->load->view('emailers/email-header'); ?>
<table border="0" class="margtable" style="margin-left:50px; margin-right:50px;">                  
    <tr>
        <td style="font-family:Verdana, Geneva, sans-serif; font-size:14px; padding-top:60px;" class="toppadding10">
            Hi <span style="color:#f75a5f; font-weight:bold;"><?php echo isset($FirstName) ? $FirstName : "There" ?></span>,
        </td>
    </tr>
    <tr>
        <td style="padding-top:10px;">
            <p style="font-family:Verdana, Geneva, sans-serif; font-size:14px; padding-top:60px;">
                We have created your account. Please use following credentials to login.
            </p>
            <p><strong>Email:<?php echo isset($Email) ? $Email : "" ?></strong></p>
            <p><strong>Temp Password:<?php echo isset($TmpPass) ? $TmpPass : "" ?></strong></p>
            <p><strong>Link to download IOS APP: <a href="#">Link</a></strong></p>                                        
        </td>
    </tr>                                
</table>
<?php $this->load->view('emailers/email-footer'); ?>
