<?php
$prodCtrl = FCom_Catalog_Admin_Controller_Products::i();
?>
<div id="categories_unset"></div>
<div id="categories"></div>
<script>
head(function() {

    FCom.Admin.tree('#categories', {
        url:'<?=BApp::href('catalog/categories/tree_data')?>',
        checkbox: {
            override_ui:true,
            checked_parent_open:true,
            real_checkboxes:true,
            two_state: true
        },
        initially_open: <?=$prodCtrl->linked_categories_data($this->model) ?>
    });
    $('#categories').bind('loaded.jstree', function(event, data) {
        var checked = <?=$prodCtrl->linked_categories_data($this->model) ?>;
        for (var id in checked) {
            jQuery("<input>").attr("type", "hidden").attr("name", checked[id]).val(0).appendTo('#categories_unset');
            data.inst.check_node('#'+checked[id]);
        }
    });
    $('#categories').bind('after_open.jstree', function(event, data) {
        var checked = <?=$prodCtrl->linked_categories_data($this->model) ?>;
        for (var id in checked) {
            data.inst.check_node('#'+checked[id]);
        }
    });

    $('#categories').css({overflowY:'auto'}).resizeWithWindow({initBy:'.adm-content-box'});
})

</script>