<?php
    $p = $this->model;
    $baseHref = BApp::m('FCom_Catalog')->baseHref();
?>

<script>
head(function() {
    window.adminForm = Admin.form({
        tabs:     '.adm-tabs-left li',
        panes:    '.adm-tabs-content',
        url_get:  '<?php echo $baseHref.'/products/form_tab/'.$p->id ?>',
        url_post: '<?php echo $baseHref.'/products/form/'.$p->id ?>'
    });
})
</script>

<form action="<?php echo $baseHref.'/products/form/'.$p->id ?>" method="post">
    <input type="hidden" id="tab" name="tab" value="<?=$this->cur_tab?>"/>
    <header class="adm-page-title">
	    <span class="title"><?php echo $this->mode==='create' ? 'Create New' : 'View' ?> Product</span>
        <div style="float:right">
            <button class="st1 sz2 btn" onclick="adminForm.saveAll()"><span>Save All</span></button>
        </div>
    </header>
    <?php //echo $this->messagesHtml() ?>
    <section class="adm-content-box info-view-mode">
    <?php if ($this->mode!=='create'): ?>
	    <section class="adm-product-summary adm-section-group">
		    <div class="btns-set"><button class="btn st2 sz2 btn-edit"><span>Edit</span></button></div>
		    <a href="#" class="product-image"><img src="<?php echo $p->thumbUrl(98) ?>" width="98" height="98" alt="<?php echo $this->q($p->product_name) ?>"/></a>
		    <h1><?php echo $this->q($p->product_name) ?></h1>
		    <span class="manuf-name attr-item"><?php echo $this->q($p->manuf()->vendor_name) ?></span>
		    <span class="manuf-sku attr-item"># <?php echo $this->q($p->manuf_sku) ?></span>
	    </section>
    <?php endif ?>
	    <div class="adm-content-inner">
		    <div class="adm-tabs-left-bg"></div>
		    <nav class="adm-tabs-left">
			    <ul>
    <?php foreach ($this->tabs as $k=>$tab): ?>
				    <li <?php if ($k===$this->cur_tab): ?>class="active"<?php endif ?>>
                        <a href="#tab-<?php echo $this->q($k) ?>"><span class="icon"></span><?php echo $this->q($tab['label']) ?></a>
                    </li>
    <?php endforeach ?>
			    </ul>
		    </nav>
            <div class="adm-tabs-container">
    <?php foreach ($this->tabs as $k=>$tab): ?>
                <section id="tab-<?php echo $this->q($k) ?>" class="adm-tabs-content"
                    <?php if ($k!==$this->cur_tab): ?>hidden<?php endif ?>
                    <?php if (empty($tab['async'])): ?>data-loaded="true"<?php endif ?>
                >
    <?php if (empty($tab['async'])) echo $this->view($tab['view']) ?>
                </section>
    <?php endforeach ?>
            </div>
	    </div>
    </section>
</form>