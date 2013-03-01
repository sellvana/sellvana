<h1>Welcome to Fulleron installation wizard!</h1>

<?php if($this->errors): ?>
<span style="color: red">
    <?php foreach($this->errors as $e):?>
        <?=$e?><br/>
    <?php endforeach; ?>
        <br/>
</span>
<?php endif; ?>

Please review the following agreement:
<form method="POST" action="<?=BApp::href('install/agreement')?>">
    Blah blah...
    <fieldset>
        <input type="submit" name="w[agree]" value="Agree"/>
    </fieldset>
</form>