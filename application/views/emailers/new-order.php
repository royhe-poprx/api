<?php $this->load->view('emailers/email-header'); ?>
<table border="0" class="margtable" style="margin-left:50px; margin-right:50px;">                  
    <tr>
        <td style="font-family:Verdana, Geneva, sans-serif; font-size:14px; padding-top:60px;" class="toppadding10">
            Hi <span style="color:#f75a5f; font-weight:bold;"><?php echo $ToUser['FirstName'] ?></span>,
        </td>
    </tr>
    <tr>
        <td style="padding-top:10px;">
            <p style="font-family:Verdana, Geneva, sans-serif; font-size:14px;  line-height:1.4;">
                This Is to notify you, <?php echo $Message; ?>
            </p>
        </td>
    </tr>                                    
</table>
<?php $this->load->view('emailers/email-footer'); ?>
