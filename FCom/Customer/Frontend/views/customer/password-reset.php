<div class="portal-login-box-container">
    <div class="portal-login-box">
        <header class="portal-login-header">
            <strong class="logo">
                <?= BLocale::_("Reset Your Password") ?>
            </strong>
        </header>
        <?php echo $this->messagesHtml() ?>
        <!--<div class="msg success-msg">Something went wrong</div>-->
        <form action="<?php echo BApp::href('customer/password/reset')?>" method="post" id="reset-form">
            <fieldset>
      				<div class="control-group">
      					<label for="#" class="control-label required"><?= BLocale::_("Password") ?></label>
      					<div class="controls">
      					  <input type="password" name="password" class="required"/>
      					</div>
      				</div>
      				<div class="control-group">
      					<label for="#" class="control-label required"><?= BLocale::_("Confirm Password") ?></label>
      					<div class="controls">
      					  <input type="password" name="password_confirm" class="required"/>
      					</div>
      				</div>
              <div class="btn-group">
                  <input type="hidden" name="token" value="<?=$this->q(BRequest::i()->request('token'))?>"/>
                  <input type="submit" class="btn btn-primary" value="<?= BLocale::_("Reset Password") ?>"/>
              </div>
            </fieldset>
        </form>
    </div>
</div>
<script>
require(['jquery', 'jquery.validate'], function($) {
    $(function() {
        $('#reset-form').validate();
    })
})
</script>
