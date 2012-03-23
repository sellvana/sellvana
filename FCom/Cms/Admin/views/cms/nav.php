<div id="cms_nav"></div>
<script>
head(function() {
    Admin.tree('#cms_nav', {
        url:'<?=BApp::href('cms/nav/tree')?>',
    });
})
</script>