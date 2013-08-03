<div class="page-main-wrapper">
	<div class="page-main">
		<form action="<?php echo BApp::href('login')?>" method="post" id="login-form">
			<fieldset class="login-form">
				<header class="page-title">
					<h1 class="title">Login</h1>
				</header>
		        <?php echo $this->messagesHtml() ?>
				<div class="control-group">
					<label for="#" class="control-label"><?= BLocale::_("Email") ?></label>
					<div class="controls">
					  <input type="email" name="login[email]" class="required"/>
					</div>
				</div>
				<div class="control-group">
					<label for="#" class="control-label"><?= BLocale::_("Password") ?></label>
					<div class="controls">
					  <input type="password" name="login[password]" class="required"/>
					</div>
				</div>
				<div class="checkbox">
					<label for="remember-me"><input type="checkbox" id="remember-me"/>Remember Me</label>
				</div>
				<div class="btn-group">
					<button class="btn btn-primary" type="submit"><span>Login</span></button>
					<a href="<?php echo BApp::href('customer/password/recover')?>">Forgot your password?</a></a>
				</div>
				<div class="divider"></div>
				<p>No Account? <strong><a href="<?php echo BApp::href('customer/register')?>"><?= BLocale::_("Sign up now") ?> &raquo;</strong></a></p>
			</fieldset>
		</form>
	</div>
</div>
<script>
require(['jquery', 'jquery.validate'], function($) {
	$(function() {
	    $('#login-form').validate();
	})
})
</script>
