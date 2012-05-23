<!--{ title: Dealer Login Password Recovery }-->
<!--{ meta_title: Dealer Login Password Recovery }-->
<!--{ meta_description: Password Recovery for Authorized XP Dealers. }-->

<div class="breadcrumbs"><a href="<?php echo BApp::href('')?>">Home</a> / Recover Your Password</div>
<div class="portal-login-box-container">
    <div class="portal-login-box">
        <header class="portal-login-header">
			<strong class="logo">
				<span>XPMetalDetectorsAmericas.com</span>
				Recover Your Password
			</strong>
        </header>
        <?php echo $this->messagesHtml() ?>
        <!--<div class="msg success-msg">Something went wrong</div>-->
        <form action="<?php echo BApp::href('dealer/password/recover')?>" method="post" id="recovery-form">
            <fieldset class="form-group">
                <ul>
                    <li class="form-row">
                        <div class="form-field">
                            <label for="#">Email</label>
                            <input type="email" name="email" class="required"/>
                        </div>
                    </li>
                </ul>
                <div class="form-buttons">
                    <input type="submit" value="Send Recovery Instructions"/>
                </div>
            </fieldset>
        </form>
    </div>
</div>
<script>
head(function() {
    $('#recovery-form').validate();
})
</script>