<div class="portal-login-box-container">
    <div class="portal-login-box">
        <header class="portal-login-header">
            <strong class="logo"><?php echo BConfig::i()->get('modules/FCom_Core/store_name') ?></strong>
        </header>
        <?php echo $this->messagesHtml() ?>
        <!--<div class="msg success-msg">Something went wrong</div>-->
        <form action="<?php echo BApp::href('customer/password/recover')?>" method="post" id="recovery-form">
            <fieldset>
                <div class="control-group">
                  <label for="#" class="control-label required"><?= BLocale::_("Email") ?></label>
                  <div class="controls">
                    <input type="email" name="email" class="required"/>
                  </div>
                </div>
                <div class="btn-group">
                    <input class="btn btn-primary" type="submit" value="<?= BLocale::_("Send Recovery Instructions") ?>"/>
                </div>
            </fieldset>
        </form>
    </div>
</div>
<script>
require(['jquery', 'jquery.validate'], function($) {
    $(function() {
        $('#recovery-form').validate();
    })
})
</script>
