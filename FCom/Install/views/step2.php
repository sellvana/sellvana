<?php $w = BSession::i()->data('w') ?>
<h1>Step 2</h1>
<form class="wizard" method="post" action="<?=BApp::url('FCom_Install', '/install/step2')?>">

    <fieldset>
        <h3>Admin User</h3>
        <?php echo $this->messagesHtml() ?>
        <label for="admin-firstname">First Name:</label><input type="text" id="admin-firstname" name="w[admin][firstname]" value="<?=$this->q($w['admin']['firstname'])?>"/><br/>
        <label for="admin-lastname">Last Name:</label><input type="text" id="admin-lastname" name="w[admin][lastname]" value="<?=$this->q($w['admin']['lastname'])?>"/><br/>
        <label for="admin-email">Email:</label><input type="text" id="admin-email" name="w[admin][email]" value="<?=$this->q($w['admin']['email'])?>"/><br/>
        <label for="admin-username">User name:</label><input type="text" id="admin-username" name="w[admin][username]" value="<?=$this->q($w['admin']['username'])?>"/><br/>
        <label for="admin-password">Password:</label><input type="password" id="admin-password" name="w[admin][password]" value="<?=$this->q($w['admin']['password'])?>"/><br/>
    </fieldset>

    <fieldset>
        <button type="submit">Proceed to the next step</button>
    </fieldset>
</form>