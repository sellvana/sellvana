<?php

class FCom_IndexTank_Admin_Controller_ProductFunctions extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'indextank/product_functions';
    protected $_modelClass = 'FCom_IndexTank_Model_ProductFunction';
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

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'title' => $m->id ? 'Edit Product Function: '.$m->name: 'Create New Product Function',
        ));
    }
}