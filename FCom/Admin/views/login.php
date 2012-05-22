<section class="adm-login-form">
	<!--<h3 class="app-logo">Denteva</h3>-->
	<form method="post" action="<?=BApp::href('login')?>">
	    <fieldset>
	    	<header class="section-title">Log into Account</header>
            <?php echo $this->messagesHtml() ?>
	        <ul class="form-list">
	        	<li class="label-l">
	        		<label for="#">Email/Username</label>
	        		<input type="text" name="login[username]" class="sz1"/>
	        	</li>
	        	<li class="label-l">
	        		<label for="#">Password</label>
	        		<input type="password" name="login[password]" class="sz1"/>
	        	</li>
	        </ul>
	        <input class="btn st1 sz1" type="submit" value="Login"/>
	        <a href="#">Recover your password</a>
	    </fieldset>
	</form>
	<!--<p class="copyright">&copy; <?php echo date("Y")?> Denteva LLC. All rights reserved.</p>-->
</section>