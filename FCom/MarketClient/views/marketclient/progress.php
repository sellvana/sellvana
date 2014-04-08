<?php $p = $this->progress ?>

<?php if ($p['cnt'] > 0): ?>

<div style="border:solid 1px #777; width:100px">
<div style="background:#0F0; height:20px; white-space:nowrap; overflow:visible; width:<?= ($p['cur']/$p['cnt'])*100 ?>%">
    <?= $p['cur'] ?> / <?= $p['cnt'] ?>
</div>
</div>

<pre>
<?php foreach ($p['modules'] as $modName => $modLine): ?>
<?= $this->q($modLine)."\n" ?>
<?php endforeach ?>
</pre>

<?php endif ?>
