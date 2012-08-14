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