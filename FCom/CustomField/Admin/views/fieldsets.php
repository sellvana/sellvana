<?php
    $fieldSetsCtrl = FCom_CustomField_Admin_Controller_FieldSets::i();
?>
<header class="adm-page-title">
    <span class="title">Field Sets</span>
</header>
<div id="fieldsets-layout">
    <div class="ui-layout-west">
        <?=$this->view('jqgrid')->set('config', $fieldSetsCtrl->fieldSetsGridConfig()) ?>
    </div>
    <div class="ui-layout-center">
        <?=$this->view('jqgrid')->set('config', $fieldSetsCtrl->fieldsGridConfig()) ?>
    </div>
</div>
<script>
function updateFieldSet(subgrid) {
    var grid = subgrid.parent().closest('.ui-jqgrid-btable'), data = subgrid.jqGrid('getRowData');
    var fields = [], id = subgrid.attr('id').match(/_([0-9])_/)[1];
    for (i=0; i<data.length; i++) {
        fields.push(data[i].id);
    }
    $.post('<?=BApp::url('FCom_CustomField', '/fieldsets/set_field_grid_data')?>',
        {set_id:id, field_ids:fields.join(',')},
        function(data, status, xhr) {
            grid.jqGrid('setRowData', id, {field_codes:data.field_codes});
        }
    );
}

head(function() {
    var linkedProductslayout = $('#fieldsets-layout').height($('.adm-wrapper').height()).layout({
        useStateCookie: true,
        west__minWidth:400,
        west__spacing_open:20,
        west__closable:false,
        triggerEventsOnLoad: true,
        onresize:function(pane, $Pane, paneState) {
            $('.ui-jqgrid-btable:visible', $Pane).each(function(index) {
                if (!this.id.match(/_t$/)) {
                    $(this).setGridWidth(paneState.innerWidth - 20);
                }
            });
        }
    });
})
</script>
