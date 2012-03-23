<?php
    echo $this->view('jqgrid')->set('config', array(
        'grid'=>array(
            'id' => 'cms_pages',
            'url' => BApp::href('cms/pages/grid'),

        ),
    ));
?>