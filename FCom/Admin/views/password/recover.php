<?php $storeName = BConfig::i()->get('modules/FCom_Core/store_name'); ?>
<section class="adm-login-form">
    <h3 class="app-logo"><?=$this->q($storeName)?></h3>
    <form method="post" action="<?=BApp::href('password/recover')?>">
        <fieldset>
            <header class="section-title">Password Recovery</header>
            <?php echo $this->messagesHtml('admin') ?>
            <ul class="form-list">
                <li class="label-l">
                    <label for="#">Email</label>
                    <input type="text" name="email" class="sz1"/>
                </li>
            </ul>
            <input class="btn st1 sz1" type="submit" value="Send Recovery Instructions"/>
        </fieldset>
    </form>
    <p class="copyright">&copy; <?php echo date("Y")?> <?=$this->q($storeName)?>. All rights reserved.</p>
</section>
<script>
head(function() {
    $('#recovery-form').validate();
})
</script>
