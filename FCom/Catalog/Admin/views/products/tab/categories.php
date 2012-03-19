<div id="categories"></div>
<script>
Admin.tree('#categories', {
    url:'<?=BApp::url('FCom_Catalog', '/api/category_tree')?>',
    checkbox:true
});
</script>