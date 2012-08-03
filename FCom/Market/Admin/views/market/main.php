<?php $m = $this->model;?>
<h2><?=$m->id;?></h2>
<?php if ($m->module): ?>
 Upgrade
<?php else: ?>
 Install
<?php endif; ?>
 <a href="<?=BApp::href('market/install')?>?id=<?=$m->id?>">Install</a>
<script>
head(function() {
adminForm.wysiwygCreate('main-content');
});
</script>