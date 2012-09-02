
<form action="#" post="">
	<fieldset class="login-form">
		<header class="page-title">
			<h1 class="title">Checkout</h1>
		</header>
		<ul class="form-group">
			<li class="row-label"><label for="#">Email</label>
				<input type="text"/></li>
			<li class="row-label"><label for="#">Password</label>
				<input type="text"/></li>
		</ul>
		<p class="checkbox-row">
			<label for="remember-me"><input type="checkbox" id="remember-me"/>Remember Me</label>
		</p>
		<div class="buttons-set">
			<button class="button" type="submit"><span>Login</span></button>
			<a href="#">Forgot your password?</a>
		</div>
		<div class="divider"></div>
		<p>No Account? <strong><a href="<?=BApp::href('checkout')?>"><?= BLocale::_("Checkout as a Guest") ?> &raquo;</strong></a></p>
	</fieldset>
</form>

