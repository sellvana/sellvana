<div id="categories"></div>
<script>
head(function() {
    Admin.tree('#categories', {
        url:'<?=BApp::href('catalog/categories/tree_data')?>',
    });
})
</script>