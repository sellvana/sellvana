<?php
$m = $this->model;
$prodCtrl = FCom_Catalog_Admin_Controller_Products::i();
?>
<div id="category-products-layout">
    <div class="ui-layout-west">
        <div class="group-container">
            <?=$this->view('jqgrid')->set('config', FCom_Catalog_Admin_Controller_Categories::i()->categoryProductGridConfig($m)) ?>
        </div>
    </div>
    <div class="ui-layout-center">
        <?=$this->view('jqgrid')->set('config', FCom_Catalog_Admin_Controller_Products::i()->productLibraryGridConfig('products')) ?>
    </div>
</div>

<script>
head(function() {
    var linkedProductslayout = $('#category-products-layout').height($('.adm-wrapper').height()).layout({
        useStateCookie: true,
        west__minWidth:400,
        west__spacing_open:20,
        west__closable:false,
        triggerEventsOnLoad: true,
        onresize:function(pane, $Pane, paneState) {
console.log(pane, $Pane, paneState);
            $('.ui-jqgrid-btable:visible', $Pane).each(function(index) {
                $(this).setGridWidth(paneState.innerWidth - 20);
            });
        }
    });
    $('#category-products-layout').resizeWithWindow();

    $('#category-products-layout .ui-layout-west .ui-jqgrid-btable').each(function(idx, el) {
        new FCom.Admin.TargetGrid({source:'#products', target:el});
    });
})
</script>