<div id="categories"></div>
<script>
head(function() {
    Admin.tree('#categories', {
        url:'<?=BApp::url('FCom_Catalog', '/api/category_tree')?>',
    });
})
</script>