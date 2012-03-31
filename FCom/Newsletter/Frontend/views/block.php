<div class="block block-subscribe">
    <div class="block-title">
        <strong><span>Newsletter</span></strong>
    </div>
    <form action="<?php echo BApp::href('newsletter/subscriber/form/')?>" method="post" id="newsletter-validate-detail">
        <div class="block-content">
            <label for="newsletter">Sign Up for Our Newsletter:</label>
            <div class="input-box">
                <input type="text" name="email" id="newsletter" title="Sign up for our newsletter" class="input-text required-entry validate-email">
            </div>
            <div class="actions">
                <button type="submit" title="Subscribe" class="button"><span><span>Subscribe</span></span></button>
            </div>
        </div>
    </form>
</div>