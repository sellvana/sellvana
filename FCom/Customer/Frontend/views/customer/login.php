<div class="portal-login-box-container">
	<div class="portal-login-box">
		<header class="portal-login-header">
			<strong class="logo">Fulleron</strong>
		</header>
        <?php echo $this->messagesHtml() ?>
		<!--<div class="msg success-msg">Something went wrong</div>-->
		<form action="<?php echo BApp::href('login')?>" method="post" id="login-form">
			<fieldset class="form-group">
				<ul>
					<li class="form-row">
						<div class="form-field">
							<label for="#">Email</label>
							<input type="email" name="login[email]" class="required"/>
						</div>
					</li>
					<li class="form-row">
						<div class="form-field">
							<label for="#">Password</label>
							<input type="password" name="login[password]" class="required"/>
						</div>
					</li>
				</ul>
				<div class="form-buttons">
					<input type="submit" value="Login"/>
					<a href="<?php echo BApp::href('customer/password/recover')?>">Recover your password</a>
				</div>
			</fieldset>
		</form>
	</div>
</div>
<script>
head(function() {
    $('#login-form').validate();
})
</script>