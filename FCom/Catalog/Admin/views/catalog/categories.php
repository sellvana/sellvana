<header class="adm-page-title">
    <span class="title">Categories</span>
    <div class="btns-set">
    </div>
</header>
<div id="categories"></div>
<script>
head(function() {
    Admin.tree('#categories', {
        url:'<?=BApp::href('catalog/categories/tree_data')?>',
    });
})
</script>