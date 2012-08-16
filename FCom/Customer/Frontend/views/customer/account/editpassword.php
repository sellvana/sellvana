<div class="portal-login-box-container portal-register-box-container">
    <div class="portal-login-box">
        <header class="portal-login-header">
			<strong class="logo">Fulleron</strong>
		</header>
        <?php echo $this->messagesHtml() ?>
        <form action="<?php echo BApp::href('customer/myaccount/editpassword')?>" method="post" id="edit-form">
            <fieldset class="form-group">
                <ul>
                    <li class="form-row">
                        <div class="form-field">
                            <label for="#"><?= BLocale::_("Password") ?> <span class="required">*</span></label>
                            <input type="password" name="model[password]" class="required" id="model-password"/>
                        </div>
                        <div class="form-field">
                            <label for="#"><?= BLocale::_("Confirm Password") ?> <span class="required">*</span></label>
                            <input type="password" name="model[password_confirm]" class="required" equalto="#model-password"/>
                        </div>
                    </li>

                </ul>
                <div class="form-buttons">
                	<span class="required-notice">* <?= BLocale::_("Indicates Required Fields") ?></span>
                    <input type="submit" class="button st3" value="<?= BLocale::_("Save") ?>"/>
                </div>
            </fieldset>
        </form>
    </div>
</div>
<script>
head(function() {
    $('#edit-form').validate();
})
</script>