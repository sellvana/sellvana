<div class="portal-login-box-container portal-register-box-container">
    <div class="portal-login-box">
        <header class="portal-login-header">
			<strong class="logo">Fulleron</strong>
		</header>
        <?php echo $this->messagesHtml() ?>
        <!--<div class="msg success-msg">Something went wrong</div>-->
        <form action="<?php echo BApp::href('customer/register')?>" method="post" id="register-form">
            <fieldset class="form-group">
                <ul>
                    <li class="form-row">
                        <div class="form-field">
                            <label for="#"><?= BLocale::_("First Name") ?> <span class="required">*</span></label>
                            <input type="text" name="model[firstname]" class="required"/>
                        </div>
                        <div class="form-field">
                            <label for="#"><?= BLocale::_("Last Name") ?> <span class="required">*</span></label>
                            <input type="text" name="model[lastname]" class="required"/>
                        </div>
                    </li>
                    <li class="form-row full-width">
                        <div class="form-field">
                            <label for="#"><?= BLocale::_("Address") ?> <span class="required">*</span></label>
                            <input type="text" name="address[street1]" class="required"/>
			</div>
                    </li>
                    <li class="form-row">
                        <div class="form-field">
                            <label for="#"><?= BLocale::_("Email") ?> <span class="required">*</span></label>
                            <input type="email" name="model[email]" class="required"/>
                        </div>
                    </li>
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
                    <input type="submit" class="button st3" value="<?= BLocale::_("Register") ?>"/>
                </div>
            </fieldset>
        </form>
    </div>
</div>
<script>
require(['jquery', 'jquery.validate'], function($) {
    $(function() {
        $('#register-form').validate();
    })
})
</script>
