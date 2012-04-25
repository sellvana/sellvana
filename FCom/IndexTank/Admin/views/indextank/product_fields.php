<header class="adm-page-title">
    <span class="title">Product Fields</span>
    <div class="btns-set">
        <button class="st1 sz2 btn" onclick="location.href='<?php echo BApp::href('indextank/product_fields/form/')?>'"><span>New Product Field</span></button>
        <button class="st1 sz2 btn" onclick="ajax_index_all_products();"><span>Index All Products</span></button>
        <button class="st1 sz2 btn" onclick="ajax_products_clear_all();"><span>Clear Products Index</span></button>
    </div>
</header>

<h3>Index <?=$this->status['name']?> (created at <?=date("Y-m-d", strtotime($this->status['date']))?>)
        Status: <?=$this->status['status']?>
</h3>
<h3>Size: <?=$this->status['size']?> documents</h3>

<?php echo $this->view('jqgrid') ?>

<script type="text/javascript">
    function ajax_index_all_products() { $.ajax({ type: "GET", url: "<?=BApp::href('indextank/products/index')?>"})
        .done(function( msg ) { alert( msg ); window.location.reload(); }); }
    function ajax_products_clear_all() { $.ajax({ type: "DELETE", url: "<?=BApp::href('indextank/products/index')?>"})
        .done(function( msg ) { alert( msg ); window.location.reload(); }); }
</script>