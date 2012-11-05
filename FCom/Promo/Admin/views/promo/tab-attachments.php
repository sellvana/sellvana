<?php
$m = $this->model;
$mediaCtrl = FCom_Admin_Controller_MediaLibrary::i();
?>
<div id="attachments-layout">
    <div class="ui-layout-west">

        <input type="hidden" name="_add_attachments" value=""/>
        <input type="hidden" name="_del_attachments" value=""/>
        <?=$this->view('jqgrid')->set('config', array(
            'grid' => array(
                'id' => 'promo_attachments',
                'caption' => 'Promotion Attachments',
                'datatype' => 'local',
                'data' => BDb::many_as_array($m->mediaORM()->select('a.id')->select('a.file_name')->find_many()),
                'colModel' => array(
                    array('name'=>'id', 'label'=>'ID', 'width'=>400, 'hidden'=>true),
                    array('name'=>'file_name', 'label'=>'File Name', 'width'=>400, 'formatter'=>"function(val,opt,obj) {
                        return val+'<input type=\"hidden\" name=\"attachments[]\" value=\"'+obj.id+'\"/>';
                    }"),
                ),
                'multiselect' => true,
                'multiselectWidth' => 30,
                'shrinkToFit' => true,
                'forceFit' => true,
            ),
            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>false),
            array('navButtonAdd', 'caption' => 'Add', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Attachments to Promotion', 'cursor'=>'pointer'),
            array('navButtonAdd', 'caption' => 'Remove', 'buttonicon'=>'ui-icon-trash', 'title' => 'Remove Attachments From Promotion', 'cursor'=>'pointer'),
        )) ?>
    </div>

    <div class="ui-layout-center">
        <?=$this->view('jqgrid')->set('config', $mediaCtrl->gridConfig(array('id'=>'all_attachments', 'folder'=>'media/promo'))) ?>
    </div>
</div>
<script>
head(function() {
    var layout = $('#attachments-layout').height($('.adm-wrapper').height()).layout({
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

    var attachmentsGrid = new FCom.Admin.MediaLibrary({
        grid:'#all_attachments',
        url:'<?=BApp::url('FCom_Admin', '/media/grid')?>',
        folder:'media/promo'
    });

    new FCom.Admin.TargetGrid({source:'#all_attachments', target:'#promo_attachments'});
})
</script>