<div id="categories"></div>
<script>
head(function() {

    FCom.Admin.tree('#categories', {
        url:'<?=BApp::href('catalog/categories/tree_data')?>',
        checkbox:true
    });

    $('#categories').css({overflowY:'auto'}).resizeWithWindow({initBy:'.adm-content-box'});
})
</script>