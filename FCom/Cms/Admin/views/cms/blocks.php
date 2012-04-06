<?php
    echo $this->view('jqgrid')->set('config', array(
        'grid'=>array(
            'id' => 'cms_blocks',
            'url' => BApp::href('cms/blocks/grid_data'),
            'editurl' => BApp::href('cms/blocks/grid_data'),
            'columns' => array(
                'id' => array('label'=>'ID'),
                'handle' => array('label'=>'Handle', 'editable'=>true, 'formatter'=>'showlink', 'formatoptions'=>array(
                    'baseLinkUrl' => BApp::href('cms/blocks/form/'), 'idName' => 'id',
                )),
                'description' => array('label'=>'Description', 'editable'=>true),
                'version' => array('label'=>'Version'),
                'create_dt' => array('label'=>'Created', 'formatter'=>'date'),
                'update_dt' => array('label'=>'Updated', 'formatter'=>'date'),
            ),
        ),
        'custom'=>array('personalize'=>true),
        'navGrid' => array('add'=>true, 'edit'=>true, 'del'=>true),
        'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
    ));
?>