<?php $p = $this->progress ?>

<?php if (!empty($p['cnt'])): ?>

<div style="border:solid 1px #178d00; width:200px">
<div style="background:#49a804; height:15px; line-height:15px; white-space:nowrap; overflow:visible; width:<?= ($p['cur'] / $p['cnt']) * 100 ?>%">
    <?= $p['cur'] ?> / <?= $p['cnt'] ?>
</div>
</div>

<?php if (!empty($p['modules'])): ?>
<pre>
<?php foreach ($p['modules'] as $modName => $modLine): ?>
<?= $this->q($modLine) . "\n" ?>
<?php endforeach ?>
</pre>
<?php endif ?>

<?php if ($p['status'] === 'DONE'): ?>
<p style="color:#178d00;"><strong>All done.</strong></p>
<?php endif ?>

<?php endif ?>
