<header class="adm-page-title">
	<span class="title">Products</span>
    <div class="btns-set">
        <button class="st1 sz2 btn" onclick="location.href='<?php echo BApp::m('FCom_Catalog')->baseHref()?>/products/form/'"><span>New Product</span></button>
    </div>
</header>
<?=$this->view('jqgrid')?>
