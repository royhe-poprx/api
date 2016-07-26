<?php $this->load->view('emailers/email-header'); ?>
<table border="0" class="margtable" style="margin-left:50px; margin-right:50px;">                  
    <tr>
        <td style="font-family:Verdana, Geneva, sans-serif; font-size:14px; padding-top:60px;" class="toppadding10">
            Hi <span style="color:#f75a5f; font-weight:bold;"><?php echo isset($FirstName) ? $FirstName : "There" ?></span>,
        </td>
    </tr>
    <tr>
        <td style="padding-top:10px;">
            <p style="font-family:Verdana, Geneva, sans-serif; font-size:14px;  line-height:1.4;">
                We received a forgot password request associated with this e-mail address. If you made this request, please follow the instructions below. 
            </p>
            <p>One time password is given below, you can login to your account by using this.</p>
            <p><strong><?php echo isset($TmpPass) ? $TmpPass : "" ?></strong></p>
            <p>If you did not request you can safely ignore this email. Rest assured your account is safe.</p>
        </td>
    </tr>                                
</table>
<?php $this->load->view('emailers/email-footer'); ?>
