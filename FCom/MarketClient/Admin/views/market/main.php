<?php $m = $this->model;?>

<?php if ($m->messages) :?>
    <?php foreach($m->messages as $message): ?>
        <?php if ('error' == $message['type']) :?>
            <span style="color:red"><?=$message['msg']?></span><br/>
        <?php else:?>
            <span style="color:green"><?=$message['msg']?></span><br/>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<h2><?=$m->module['name']?> (<?=$m->module['mod_name']?>)</h2>
    <h3>Version</h3> <?=$m->module['version']?><br/><br/>
    <?php if (!empty($m->module['require'])):?>
        <h3>Require</h3>
        <?php foreach($m->module['require'] as $reqType => $reqModules):?>
            <b><?=$reqType?>:</b>
            <ul>
            <?php foreach($reqModules as $reqMod):?>
                <?php if (!empty($reqMod['error'])):?>
                <li style="color:red">
                <?php else:?>
                <li >
                <?php endif; ?>
                    <?=$reqMod['name']?>
                    <?=!empty($reqMod['version'])?'version':''?>
                    <?=!empty($reqMod['version']['from'])?'> '.$reqMod['version']['from']:''?>
                    <?=!empty($reqMod['version']['to'])?'< '.$reqMod['version']['to']:''?>
                    <?=!empty($reqMod['error'])?'('.$reqMod['error'].')':''?>

                </li>
            <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
            <br/>
    <?php endif; ?>

    <h3>Description</h3>
    <?= $m->module['description']; ?>
    <br/>
    <?php if($m->local_version): ?>
        Local version is: <?= $m->local_version ?> <br/>
        <a href="<?=BApp::href('market/install')?>?mod_name=<?=$m->id?>">Re-upload</a>
    <?php else:?>
        <a href="<?=BApp::href('market/install')?>?mod_name=<?=$m->id?>">Install</a>
    <?php endif; ?>

    <?php if ($m->need_upgrade):?>
        <br/>
        <a href="<?=BApp::href('market/install')?>?mod_name=<?=$m->id?>">Upgrade</a>
    <?php endif; ?>

<script>
head(function() {
adminForm.wysiwygCreate('main-content');
});
</script>