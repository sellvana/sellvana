<?php
    $p = $this->model;
    $tabs = $this->sortedTabs();
    $formUrl = BApp::href('catalog/products/form/?id='.$p->id)
?>

<script>
head(function() {
    window.adminForm = FCom.Admin.form({
        tabs:     '.adm-tabs-sidebar li',
        panes:    '.adm-tabs-content',
        url_get:  '<?php echo $formUrl ?>',
        url_post: '<?php echo $formUrl ?>'
    });
})
</script>

<form action="<?php echo $formUrl ?>" method="post">
    <input type="hidden" id="tab" name="tab" value="<?=$this->cur_tab?>"/>
    <header class="adm-page-title">
	    <span class="title"><?php echo $this->mode==='create' ? 'Create New Product' : $this->q($p->product_name).' <span class="manuf-sku">#'.$this->q($p->manuf_sku).'</span>' ?></span>
        <div class="btns-set">
            <button class="st1 sz2 btn" onclick="adminForm.saveAll()"><span>Save All</span></button>
        </div>
    </header>
    <?php //echo $this->messagesHtml() ?>
    <div class="adm-content-box info-view-mode">
	    <section class="form-img-sidebar">
	    	<a href="#" class="product-img"><img src="<?php echo $p->thumbUrl(98) ?>" width="98" height="98" alt="<?php echo $this->q($p->product_name) ?>"/></a>
		    <nav class="adm-tabs-sidebar">
			    <ul>
	<?php foreach ($tabs as $k=>$tab): ?>
				    <li <?php if ($k===$this->cur_tab): ?>class="active"<?php endif ?>>
	                    <a href="#tab-<?php echo $this->q($k) ?>"><span class="icon"></span><?php echo $this->q($tab['label']) ?></a>
	                </li>
	<?php endforeach ?>
			    </ul>
		    </nav>
	    </section>
	    <div class="adm-main">
	        <div class="adm-tabs-container">
	<?php foreach ($tabs as $k=>$tab): ?>
	            <section id="tab-<?php echo $this->q($k) ?>" class="adm-tabs-content"
	                <?php if ($k!==$this->cur_tab): ?>hidden<?php endif ?>
	                <?php if (empty($tab['async'])): ?>data-loaded="true"<?php endif ?>
	            >
	<?php if (empty($tab['async'])) echo $this->view($tab['view']) ?>
	            </section>
	<?php endforeach ?>
	        </div>
		</div>
    </div>
</form>