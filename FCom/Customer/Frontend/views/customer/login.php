<div class="page-main-wrapper">
	<div class="page-main">
		<form action="<?php echo BApp::href('login')?>" method="post" id="login-form">
			<fieldset class="login-form">
				<header class="page-title">
					<h1 class="title">Login</h1>
				</header>
		        <?php echo $this->messagesHtml() ?>
				<ul class="form-group">
					<li class="row-label"><label for="#"><?= BLocale::_("Email") ?></label>
						<input type="email" name="login[email]" class="required"/></li>
					<li class="row-label"><label for="#"><?= BLocale::_("Password") ?></label>
						<input type="password" name="login[password]" class="required"/></li>
				</ul>
				<p class="checkbox-row">
					<label for="remember-me"><input type="checkbox" id="remember-me"/>Remember Me</label>
				</p>
				<div class="buttons-set">
					<button class="button" type="submit"><span>Login</span></button>
					<a href="#">Forgot your password?</a>
				</div>
				<div class="divider"></div>
				<p>No Account? <strong><a href="<?php echo BApp::href('customer/register')?>"><?= BLocale::_("Sign up now") ?> &raquo;</strong></a></p>
			</fieldset>
		</form>
	</div>
</div>
<script>
head(function() {
    $('#login-form').validate();
})
</script>