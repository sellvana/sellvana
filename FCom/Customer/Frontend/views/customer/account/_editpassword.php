<div class="portal-login-box-container portal-register-box-container">
    <div class="portal-login-box">
        <header class="portal-login-header">
			<strong class="logo">Fulleron</strong>
		</header>
        <?php echo $this->messagesHtml() ?>
        <form action="<?php echo BApp::href('customer/myaccount/editpassword')?>" method="post" id="edit-form">
            <fieldset>
              <div class="row-fluid">
                <div class="control-group span6">
                  <label for="#" class="control-label required"><?= BLocale::_("Password") ?></label>
                  <div class="controls">
                    <input type="password" name="model[password]" class="required" id="model-password"/>
                  </div>
                </div>
                <div class="control-group span6">
                  <label for="#" class="control-label required"><?= BLocale::_("Confirm Password") ?></label>
                  <div class="controls">
                    <input type="password" name="model[password_confirm]" class="required" equalto="#model-password"/>
                  </div>
                </div>
              </div>
              <div class="btn-group">
              	 <span class="required-notice">* <?= BLocale::_("Indicates Required Fields") ?></span>
                  <input type="submit" class="btn btn-primary" value="<?= BLocale::_("Save") ?>"/>
              </div>
            </fieldset>
        </form>
    </div>
</div>
<script>
require(['jquery', 'jquery.validate'], function($) {
    $(function() {
        $('#edit-form').validate();
    })
})
</script>
