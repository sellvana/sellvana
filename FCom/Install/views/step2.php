<?php $w = BSession::i()->data('w') ?>
<h1>Step 2</h1>
<form class="wizard" method="post" action="<?=BApp::m()->baseUrl()?>/step2">

    <fieldset>
        <h3>Admin User</h3>
        <label for="admin-username">User name:</label><input type="text" id="admin-username" name="w[admin][username]" value="<?=$this->q($w['admin']['username'])?>"/><br/>
        <label for="admin-password">Password:</label><input type="text" id="admin-password" name="w[admin][password]" value="<?=$this->q($w['admin']['password'])?>"/><br/>
    </fieldset>

    <fieldset>
        <button type="submit">Proceed to the next step</button>
    </fieldset>
</form>