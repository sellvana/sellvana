<?php

class FCom_IndexTank_Admin_Controller_ProductFunctions extends FCom_Admin_Controller_Abstract_GridForm
{
    //protected $_permission = 'cms/pages';
    protected $_gridHref = 'indextank/product_functions';
    protected $_gridLayoutName = '/indextank/product_functions';
    protected $_formLayoutName = '/indextank/product_functions/form';
    protected $_formViewName = 'indextank/product_functions-form';
    protected $_modelClassName = 'FCom_IndexTank_Model_ProductFunctions';
    protected $_mainTableAlias = 'pf';

    public function gridConfig()
    {
        $status = FCom_IndexTank_Index_Product::i()->status();
        BLayout::i()->view('indextank/product_functions')->set('status', $status);

        $config = parent::gridConfig();
        $config['grid']['columns'] += array(
            'name' => array('label'=>'Name', 'editable'=>true, 'formatter'=>'showlink', 'formatoptions'=>array(
                'baseLinkUrl' => BApp::href('indextank/product_functions/form'), 'idName' => 'id',
            )),
            'number' => array('label'=>'Number'),
            'definition' => array('label'=>'Function definition')
        );

        return $config;
    }


    public function action_form__POST()
    {
        $post = BRequest::i()->post('model');
        if (!empty($post)){
            FCom_IndexTank_Index_Product::i()->update_function($post['number'], $post['definition']);
        }


        parent::action_form__POST();
    }

}