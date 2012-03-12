<?php $w = BSession::i()->data('w') ?>
<h1>Step 3</h1>
<form class="wizard" method="post" action="<?=BApp::url('FCom_Install', '/install/step3')?>">

    <fieldset>
        <?php echo $this->messagesHtml() ?>
        <button type="submit">Proceed to the next step</button>
    </fieldset>
</form>