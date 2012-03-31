<div id="categories"></div>
<script>
Admin.tree('#categories', {
    url:'<?=BApp::href('catalog/category/tree')?>',
    checkbox:true
});
</script>