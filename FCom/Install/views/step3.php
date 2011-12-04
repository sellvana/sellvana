<?php $w = BSession::i()->data('w') ?>
<h1>Step 3</h1>
<form class="wizard" method="post" action="<?=BApp::m()->baseHref()?>/step3">

    <fieldset>
        <button type="submit">Proceed to the next step</button>
    </fieldset>
</form>