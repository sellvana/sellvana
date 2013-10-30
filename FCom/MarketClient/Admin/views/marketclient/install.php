<form action="<?=BApp::href('marketclient/module/install')?>" method="post">
    <?php foreach ($this->install as $modName => $modInfo): ?>
        <div class="row">
            <?=$this->q($modName)?>:
            <select name="install[<?=$this->q($modName)?>][version]">
                <?php foreach ($modInfo['channels'] as $cn => $c): ?>
                    <option value="<?=$this->q($cn)?>"><?="{$c['name']}: {$c['version']} ({$c['published_at']})"?></option>
                <?php endforeach ?>
            </select>
            <label><?=$this->_('Auto-enable:')?> <input type="checkbox" name="install[<?=$this->q($modName)?>][enable]" value="1"/></label>
        </div>
    <?php endforeach ?>
    <button type="submit" class="btn btn-primary"><?=$this->_('Install')?></button>
</form>
