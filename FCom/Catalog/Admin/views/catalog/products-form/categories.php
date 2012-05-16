<?php
$prodCtrl = FCom_Catalog_Admin_Controller_Products::i();
?>
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
        }/*,
        initially_open: <?php echo BUtil::toJson(array('check_3', 'check_2','check_5','check_7',)) ?>*/
    });
    $('#categories').bind('loaded.jstree', function(event, data) {
        var checked = <?=$prodCtrl->linked_categories_data() ?>;
        for (var id in checked) {
            console.log('check ' + checked[id]);
            data.inst.check_node('#'+checked[id]);
        }
    });
    $('#categories').bind('after_open.jstree', function(event, data) {
        var checked = <?=$prodCtrl->linked_categories_data() ?>;
        for (var id in checked) {
            console.log('check ' + checked[id]);
            data.inst.check_node('#'+checked[id]);
        }
    });

    $('#categories').css({overflowY:'auto'}).resizeWithWindow({initBy:'.adm-content-box'});
})
</script>