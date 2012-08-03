<div class="block block-subscribe">
    <div class="block-title">
        <strong><span><?= BLocale::_("Newsletter"); ?></span></strong>
    </div>
    <form action="<?php echo BApp::href('newsletter/subscriber/form/')?>" method="post" id="newsletter-validate-detail">
        <div class="block-content">
            <label for="newsletter"><?= BLocale::_("Sign Up for Our Newsletter"); ?>:</label>
            <div class="input-box">
                <input type="text" name="email" id="newsletter" title="<?= BLocale::_("Sign up for our newsletter"); ?>" class="input-text required-entry validate-email">
            </div>
            <div class="actions">
                <button type="submit" title="<?= BLocale::_("Subscribe"); ?>" class="button"><span><span><?= BLocale::_("Subscribe"); ?></span></span></button>
            </div>
        </div>
    </form>
</div>