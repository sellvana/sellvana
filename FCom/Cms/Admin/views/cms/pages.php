<header class="adm-page-title">
    <span class="title">CMS Pages</span>
    <div class="btns-set">
        <button class="st1 sz2 btn" onclick="location.href='<?php echo BApp::href('cms/pages/form/')?>'"><span>New CMS Page</span></button>
    </div>
</header>
<?php
    echo $this->view('jqgrid')->set('config', array(
        'grid'=>array(
            'id' => 'cms_pages',
            'url' => BApp::href('cms/pages/grid_data'),
            'editurl' => BApp::href('cms/pages/grid_data'),
            'columns' => array(
                'id' => array('label'=>'ID'),
                'handle' => array('label'=>'Handle', 'editable'=>true, 'formatter'=>'showlink', 'formatoptions'=>array(
                    'baseLinkUrl' => BApp::href('cms/pages/form/'), 'idName' => 'id',
                )),
                'title' => array('label'=>'Title', 'editable'=>true),
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