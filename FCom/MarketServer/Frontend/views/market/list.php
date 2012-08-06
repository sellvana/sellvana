<h2>Market Modules</h2>

<a href="/market/account">Setup account</a> <br/><br/>

<?php foreach($this->modules as $m): ?>
    <h3><?= $m->product_name; ?> (<?=$m->mod_name?>)</h3>
    Version: <?=$m->version?><br/>
    <?= $m->description; ?>
    <a href="<?=$m->url()?>">View</a>
    <br/> <br/>
<?php endforeach; ?>
