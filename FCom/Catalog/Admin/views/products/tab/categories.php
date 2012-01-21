<div id="categories"></div>
<script>
Admin.tree('#categories', {
    url:'<?=BApp::m('Denteva_Merge')->baseHref()?>/api/category_tree',
    checkbox:true
});
</script>