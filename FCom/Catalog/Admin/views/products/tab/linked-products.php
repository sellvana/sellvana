<?php
$m = $this->model;
$prodCtrl = FCom_Catalog_Admin_Controller_Products::i();
$vUrl = BApp::url('Denteva_Admin', '/vendors/autocomplete?type=manuf');
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

<div id="dialog-family-new" title="Create new family">
    <form method="post" action="#" onsubmit="return false"><fieldset>
    <label for="family_name">Name</label> <input type="text" id="family_name" name="model[family_name]"/><br/>
    <label for="manuf_vendor_name">Manufacturer</label> <input type="text" id="manuf_vendor_name" name="model[manuf_vendor_name]" value="<?=$this->q($m->manuf_vendor_name)?>"/>
    <input type="hidden" id="manuf_vendor_id" name="model[manuf_vendor_id]" value="<?=$m->manuf_vendor_id?>"/>
    </fieldset></form>
</div>

<div id="dialog-family-rename" title="Rename family">
    <form method="post" action="#" onsubmit="return false"><fieldset>
    <label for="family_name">Name</label> <input type="text" id="family_name" name="model[family_name]"/><br/>
    </fieldset></form>
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

    $('#family-autocomplete').fcom_autocomplete({
        url:'<?=BApp::url('FCom_Catalog', '/families/autocomplete')?>',
        field:'#family-id',
        filter:'#family-manuf-id',
        select: function(event, ui) {
            var url = '<?=BApp::url('FCom_Catalog', '/families/product_data')?>?family='+ui.item.id;
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
    $('#family-manuf-autocomplete').fcom_autocomplete({url:'<?=$vUrl?>', field:'#family-manuf-id'});
    $('#dialog-family-new #manuf_vendor_name').fcom_autocomplete({url:'<?=$vUrl?>', field:'#dialog-family-new #manuf_vendor_id'});
    $('#dialog-family-rename #manuf_vendor_name').fcom_autocomplete({url:'<?=$vUrl?>', field:'#dialog-family-rename #manuf_vendor_id'});

    $('#dialog-family-new').dialog({
        autoOpen:false, height:300, width:350, modal:true, buttons: {
            "Create Family": function() {
                $.post('<?=BApp::url('FCom_Catalog', '/families/form/')?>',
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
                $.post('<?=BApp::url('FCom_Catalog', '/families/form/')?>'+$('#family-id').val(),
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
        new FCom_Admin.TargetGrid({source:'#products', target:el});
    });
})
</script>