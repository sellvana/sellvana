<?php
$tabs = $this->sortedTabs();
$m = $this->model;
?>
<section class="info-view-mode">
	<header class="adm-main-header">
	    <div class="btns-set">
	        <button class="st1 sz2 btn" onclick="return adminForm.saveAll(this)"><span>Save</span></button>
	    </div>
	    <h1><?php echo $this->q(str_replace('|', ' > ', $m->full_name)) ?></h1>
	</header>
    <nav class="adm-tabs">
        <ul>
<?php foreach ($tabs as $k=>$tab): ?>
<li <?php if ($k===$this->cur_tab): ?>class="active"<?php endif ?>>
<a href="#tab-<?php echo $this->q($k) ?>"><span class="icon"></span><?php echo $this->q($tab['label']) ?></a>
</li>
<?php endforeach ?>
        </ul>
    </nav>
    <div class="adm-tabs-container">
<?php foreach ($tabs as $k=>$tab): if (!empty($tab['view'])): ?>
        <section id="tab-<?php echo $this->q($k) ?>" class="adm-tabs-content"
            <?php if ($k!==$this->cur_tab): ?>hidden<?php endif ?>
            <?php if (empty($tab['async'])): ?>data-loaded="true"<?php endif ?>
        >
<?php if (empty($tab['async'])) echo $this->view($tab['view']) ?>
        </section>
<?php endif; endforeach ?>
    </div>
</section>