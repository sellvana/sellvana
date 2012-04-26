<?php

class FCom_IndexTank_Admin_Controller_ProductFields extends FCom_Admin_Controller_Abstract_GridForm
{
    //protected $_permission = 'cms/pages';
    protected $_gridHref = 'indextank/product_fields';
    protected $_gridLayoutName = '/indextank/product_fields';
    protected $_formLayoutName = '/indextank/product_fields/form';
    protected $_formViewName = 'indextank/product_fields-form';
    protected $_modelClassName = 'FCom_IndexTank_Model_ProductFields';
    protected $_mainTableAlias = 'pf';

    public function gridConfig()
    {
        $status = FCom_IndexTank_Index_Product::i()->status();
        BLayout::i()->view('indextank/product_fields')->set('status', $status);

        $config = parent::gridConfig();
        $config['grid']['columns'] += array(
            'field_nice_name' => array('label'=>'Name', 'editable'=>true, 'formatter'=>'showlink', 'formatoptions'=>array(
                'baseLinkUrl' => BApp::href('indextank/product_fields/form'), 'idName' => 'id',
            )),
            'search' => array('label'=>'Search'),
            'facets' => array('label'=>'Facets'),
            'scoring' => array('label'=>'Scoring'),
            'var_number' => array('label'=>'Scoring variable #'),
            'priority' => array('label'=>'Priority'),
            'show' => array('label'=>'Display as'),
            'filter' => array('label'=>'Filter type'),
        );
        return $config;
    }


}