<div id="categories"></div>
<script>
Admin.tree('#categories', {
    url:'<?=BApp::href('catalog/categories/tree_data')?>',
    checkbox:true
});
</script>