<?php $m = $this->model;?>



<h2><?=$m->module['name']?> (<?=$m->module['mod_name']?>)</h2>
    Version: <?=$m->module['version']?><br/>
    <?= $m->module['description']; ?>
    <br/>
    <?php if($m->local_version): ?>
        Local version is: <?= $m->local_version ?>
    <?php else:?>
        <a href="<?=BApp::href('market/install')?>?id=<?=$m->id?>">Install</a>
    <?php endif; ?>

    <?php if ($m->need_upgrade):?>
        <br/>
        <a href="<?=BApp::href('market/install')?>?id=<?=$m->id?>">Upgrade</a>
    <?php endif; ?>

<script>
head(function() {
adminForm.wysiwygCreate('main-content');
});
</script>