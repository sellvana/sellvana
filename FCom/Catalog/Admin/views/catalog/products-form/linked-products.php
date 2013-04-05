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
        <?=$this->view('jqgrid')->set('config', $prodCtrl->productLibraryGridConfig('linked-products-all')) ?>
    </div>
</div>

<script>
head(function() {
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
    $('#linked-products-layout').resizeWithWindow();

    $('#family-autocomplete').fcom_autocomplete({
        url:'<?=BApp::href('catalog/families/autocomplete')?>',
        field:'#family-id',
        filter:'#family-manuf-id',
        select: function(event, ui) {
            var url = '<?=BApp::href('catalog/families/product_data')?>?family='+ui.item.id;
            $.get(url, function(data, status, xhr) {
                var grid = $('#linked_products_family'), container = grid.parents('.ui-jqgrid');
                container.find('input[name="grid[linked_products_family][add]"]').val('');
                container.find('input[name="grid[linked_products_family][del]"]').val('');
                grid.jqGrid('clearGridData');
                for (var i=0; i<data.length; i++) {
                    grid.jqGrid('addRowData', data[i].id, data[i]);
                }
            });
        }
    });
    $('#dialog-family-new').dialog({
        autoOpen:false, height:300, width:350, modal:true, buttons: {
            "Create Family": function() {
                $.post('<?=BApp::href('catalog/families/form/')?>',
                    $('form', this).serialize(),
                    function(data, status, xhr) {
                        //$('#family-id').val(data.model.id);
                        //$('#family-autocomplete').val(data.model.family_name);
                    }
                );
                $(this).dialog('close');
            },
            Cancel: function() {
                $(this).dialog('close');
            }
        }
    });

    $('#dialog-family-rename').dialog({
        autoOpen:false, height:300, width:350, modal:true, buttons: {
            "Rename Family": function() {
                if (!$('#dialog-family-rename #family_name').val()) {
                    alert('Please enter a valid name');
                    return;
                }
                $.post('<?=BApp::href('catalog/families/form/')?>?id='+$('#family-id').val(),
                    $('form', this).serialize(),
                    function(data, status, xhr) {
                        $('#family-id').val(data.model.id);
                        $('#family-autocomplete').val(data.model.family_name);
                    }
                );
                $(this).dialog('close');
            },
            Cancel: function() {
                $(this).dialog('close');
            }
        }
    });

    $('#family-new').click(function(ev) {
        $('#dialog-family-new').dialog('open');
    });
    $('#family-rename').click(function(ev) {
        $('#dialog-family-rename').dialog('open');
        $('#dialog-family-rename #family_name').val($('#family-autocomplete').val())[0].select();
    });

    $('#linked-products-layout .ui-layout-west .ui-jqgrid-btable').each(function(idx, el) {
        new FCom.Admin.TargetGrid({source:'#linked-products-all', target:el});
    });
})
</script>
<?php echo $this->hook('catalog/products/tab/linked-products', array('model'=>$this->model)) ?>