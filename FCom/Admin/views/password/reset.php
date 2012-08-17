<?php $storeName = BConfig::i()->get('modules/FCom_Core/store_name') ?>
<section class="adm-login-form">
    <h3 class="app-logo"><?=$this->q($storeName)?></h3>
    <form method="post" action="<?=BApp::href('password/reset')?>">
        <fieldset>
            <header class="section-title">Password Reset</header>
            <?php echo $this->messagesHtml('admin') ?>
            <ul class="form-list">
                <li class="label-l">
                    <label for="#">Password</label>
                    <input type="password" name="password" class="sz1 required"/>
                </li>
                <li class="label-l">
                    <label for="#">Confirm Password</label>
                    <input type="password" name="password_confirm" class="sz1 required"/>
                </li>
            </ul>
            <div class="btns-set">
            	<input type="hidden" name="token" value="<?=$this->q(BRequest::i()->request('token'))?>"/>
            	<input class="btn st1 sz1" type="submit" value="Reset Password"/>
	        </div>
        </fieldset>
    </form>
    <p class="copyright">&copy; <?php echo date("Y")?> <?=$this->q($storeName)?>. All rights reserved.</p>
</section>
<script>
head(function() {
    $('#recovery-form').validate();
})
</script>