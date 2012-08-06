<h2>Market Modules</h2>

<a href="/market/account">Setup account</a> <br/><br/>

<?php foreach($this->modules as $m): ?>
    <h3><?= $m->name; ?></h3>
    <?= $m->description; ?> <a href="<?=Bapp::href('market/view')?>?m=<?=$m->name?>">Install</a> <br/>
    <?php if($m->need_upgrade) :?> <a href="<?=Bapp::href('market/view')?>?m=<?=$m->name?>" style="color: red">Need upgrade!</a> <?php endif; ?>
    <br/>
<?php endforeach; ?>
