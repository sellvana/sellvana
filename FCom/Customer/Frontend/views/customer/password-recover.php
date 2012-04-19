<div class="portal-login-box-container">
    <div class="portal-login-box">
        <header class="portal-login-header">
            <strong class="logo"><?php echo BConfig::i()->get('modules/FCom_Core/store_name') ?></strong>
        </header>
        <?php echo $this->messagesHtml() ?>
        <!--<div class="msg success-msg">Something went wrong</div>-->
        <form action="<?php echo BApp::href('password_recovery')?>" method="post" id="recovery-form">
            <fieldset class="form-group">
                <ul>
                    <li class="form-row">
                        <div class="form-field">
                            <label for="#">Email</label>
                            <input type="email" name="login[email]" class="required"/>
                        </div>
                    </li>
                </ul>
                <div class="form-buttons">
                    <input type="submit" value="Send Reminder"/>
                </div>
            </fieldset>
        </form>
    </div>
</div>
<script>
head(function() {
    $('#recovery-form').validate();
})
</script>