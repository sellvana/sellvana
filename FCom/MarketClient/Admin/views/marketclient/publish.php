<?php $modules = FCom_MarketClient_Model_Module::i()->getLocalModules(); ?>
<div class="container">
<?php foreach ($modules as $modName => $mod): ?>
    <div class="control-group">
        <form method="post" action="<?=BApp::href('marketclient/publish/module')?>">
            <input type="hidden" name="mod_name" value="<?=$modName?>">
            <button type="submit" class="btn btn-primary btn-small" onclick="return confirm('Are you sure you want to publish <?=$modName?> ?')">
                <?=$this->_('Publish')?>
            </button>
            <?=$modName?>
        </form>
    </div>
<?php endforeach ?>
</div>
