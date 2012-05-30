<div class="portal-login-box-container">
    <div class="portal-login-box">
        <header class="portal-login-header">
            <strong class="logo">
                Reset Your Password
            </strong>
        </header>
        <?php echo $this->messagesHtml() ?>
        <!--<div class="msg success-msg">Something went wrong</div>-->
        <form action="<?php echo BApp::href('customer/password/reset')?>" method="post" id="reset-form">
            <fieldset class="form-group">
                <ul>
                    <li class="form-row">
                        <div class="form-field">
                            <label for="#">Password</label>
                            <input type="password" name="password" class="required"/>
                        </div>
                    </li>
                    <li class="form-row">
                        <div class="form-field">
                            <label for="#">Confirm</label>
                            <input type="password" name="password_confirm" class="required"/>
                        </div>
                    </li>
                </ul>
                <div class="form-buttons">
                    <input type="hidden" name="token" value="<?=$this->q(BRequest::i()->request('token'))?>"/>
                    <input type="submit" value="Reset Password"/>
                </div>
            </fieldset>
        </form>
    </div>
</div>
<script>
head(function() {
    $('#reset-form').validate();
})
</script>