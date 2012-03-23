<div id="categories"></div>
<script>
Admin.tree('#categories', {
    url:'<?=BApp::url('FCom_Catalog', '/catalog/category/tree')?>',
    checkbox:true
});
</script>