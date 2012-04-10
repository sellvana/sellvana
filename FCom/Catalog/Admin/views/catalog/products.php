<header class="adm-page-title">
	<span class="title">Products</span>
    <div class="btns-set">
        <button class="st1 sz2 btn" onclick="ajax_index_all_products();"><span>Index All Products</span></button>
        <button class="st1 sz2 btn" onclick="location.href='<?php echo BApp::href('catalog/products/form/')?>'"><span>New Product</span></button>
    </div>
</header>
<?=$this->view('jqgrid')?>


<script type="text/javascript">
    function ajax_index_all_products()
    {
        $.ajax({
            type: "GET",
            url: "/admin/indextank/products/index"
            }).done(function( msg ) {
            alert( msg );
            });
    }
</script>