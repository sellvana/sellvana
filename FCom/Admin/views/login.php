<?php $storeName = BConfig::i()->get('modules/FCom_Core/store_name') ?>
<section class="adm-login-form">
	<h3 class="app-logo"><?=$this->q($storeName)?></h3>
	<form method="post" action="<?=BApp::href('login')?>">
	    <fieldset>
	    	<header class="section-title">Log into Account</header>
            <?php echo $this->messagesHtml('admin') ?>
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
            <div class="btns-set">
	        	<input class="btn st1 sz1" type="submit" value="Login"/>
	        	<p><a href="<?=BApp::href('password/recover')?>">Recover your password</a></p>
	        </div>
	    </fieldset>
	</form>
	<p class="copyright">&copy; <?php echo date("Y")?> <?=$this->q($storeName)?>. All rights reserved.</p>
</section>