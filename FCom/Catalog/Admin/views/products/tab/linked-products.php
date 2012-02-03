<?php
$m = $this->model;
$prodCtrl = FCom_Catalog_Admin_Controller_Products::i();
?>
<div id="linked-products-layout">
    <div class="ui-layout-west">
        <div class="group-container">
            <?=$this->view('jqgrid')->set('config', $prodCtrl->linkedProductGridConfig($m, 'related')) ?>
            <?=$this->view('jqgrid')->set('config', $prodCtrl->linkedProductGridConfig($m, 'similar')) ?>
            <?=$this->view('jqgrid')->set('config', $prodCtrl->linkedProductGridConfig($m, 'family')) ?>
        </div>
    </div>
    <div class="ui-layout-center">
        <?=$this->view('jqgrid')->set('config', $prodCtrl->productLibraryGridConfig()) ?>
    </div>
</div>

<script>
var linkedProductslayout = $('#linked-products-layout').height($('.adm-wrapper').height()).layout({
    useStateCookie: true,
    west__minWidth:400,
    west__spacing_open:20,
    west__closable:false,
    triggerEventsOnLoad: true,
    onresize:function(pane, $Pane, paneState) {
        $('.ui-jqgrid-btable:visible', $Pane).each(function(index) {
            $(this).setGridWidth(paneState.innerWidth - 20);
        });
    }
});
var productLibrary = new FCom_Admin.ProductLibrary({grid:'#products'});

$('.ui-layout-west .ui-jqgrid-btable').each(function(idx, el) { productLibrary.initTargetGrid(el); });

$('#gs_manuf_vendor_name').fcom_autocomplete({url:'<?=BApp::url('Denteva_Admin', '/vendors/autocomplete')?>'});

</script>