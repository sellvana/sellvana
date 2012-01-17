<div id="details-products" class="details-pane grid-container" rel="products"></div>
<script>
Admin.ajaxCache('<?=BApp::m('FCom_Catalog')->baseHref()?>/products/grid/config', function(config) {
    Admin.slick('#details-products', config);
});
</script>