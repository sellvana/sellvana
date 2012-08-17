<?php $w = BSession::i()->data('w') ?>
<style>
.wizard label { display:block; width:80px; float:left; }
</style>
<h1>Step 1</h1>
<form class="wizard" method="post" action="<?=BApp::href('install/step1')?>">
    <fieldset>
        <h3>DB Connection</h3>
        <?php echo $this->messagesHtml() ?>
        <label for="host">Host:</label><input type="text" id="host" name="w[db][host]" value="<?=$this->q($w['db']['host'])?>"/><br/>
        <label for="dbname">DB name:</label><input type="text" id="dbname" name="w[db][dbname]" value="<?=$this->q($w['db']['dbname'])?>"/><br/>
        <label for="db-username">User name:</label><input type="text" id="db-username" name="w[db][username]" value="<?=$this->q($w['db']['username'])?>"/><br/>
        <label for="db-password">Password:</label><input type="password" id="db-password" name="w[db][password]" value="<?=$this->q($w['db']['password'])?>"/><br/>
        <label for="dbname">Table prefix:</label><input type="text" id="dbname" name="w[db][table_prefix]" value="<?=$this->q($w['db']['table_prefix'])?>"/><br/>
    </fieldset>

    <fieldset>
        <button type="submit">Proceed to the next step</button>
    </fieldset>
</form>