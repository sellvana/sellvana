<header class="adm-page-title">
    <span class="title">Product Fields</span>
    <div class="btns-set">
        <button class="st1 sz2 btn" onclick="location.href='<?php echo BApp::href('indextank/product_fields/form/')?>'"><span>New Product Field</span></button>
    </div>
</header>
<?php echo $this->view('jqgrid') ?>