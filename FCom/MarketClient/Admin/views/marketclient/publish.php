<div class="container">
<?php foreach ($this->modules as $modName => $mod): ?>
    <div class="row">
        <form method="post" action="<?=BApp::href('marketclient/publish/module')?>">
            <?php if ($mod['status'] === 'available'): ?>
                <input type="hidden" name="mod_name" value="<?=$modName?>">
                <button type="submit" class="btn btn-primary btn-small" onclick="return confirm('Are you sure you want to publish <?=$modName?> ?')">
                    <?=$this->_('Publish')?>
                </button>
            <?php endif ?>
            <?=$modName?>
        </form>
    </div>
<?php endforeach ?>
</div>
