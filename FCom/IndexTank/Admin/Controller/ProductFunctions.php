<?php

class FCom_IndexTank_Admin_Controller_ProductFunctions extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'indextank/product_functions';
    protected $_modelClass = 'FCom_IndexTank_Model_ProductFunction';
    protected $_mainTableAlias = 'pf';

    public function gridConfig()
    {
        try {
            $status = FCom_IndexTank_Index_Product::i()->status();
            BLayout::i()->view('indextank/product_functions')->set('status', $status);
        } catch (Exception $e) {
            BLayout::i()->view('indextank/product_functions')->set('status', false);
        }

        $config = parent::gridConfig();
        $config['grid']['columns'] += array(
            'label' => array('label'=>'Frontend Label'),
            'name' => array('label'=>'Name', 'editable'=>true, 'formatter'=>'showlink', 'formatoptions'=>array(
                'baseLinkUrl' => BApp::href('indextank/product_functions/form'), 'idName' => 'id',
            )),
            'number' => array('label'=>'Number'),
            'definition' => array('label'=>'Function definition')
        );

        return $config;
    }

    public function formPostAfter($args)
    {
        $model = $args['model'];

        if ($model) {
            if ( $model->number < 0 || !isset($model->number)) {
                $maxVarField = FCom_IndexTank_Model_ProductFunction::orm()->select_expr("max(number) as max_number")->find_one();
                $model->number = $maxVarField->max_number+1;
                $model->save();
            }

            FCom_IndexTank_Index_Product::i()->updateFunction($model->number, $model->definition);
        }



        parent::formPostAfter($args);
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $fields = $maxVarField = FCom_IndexTank_Model_ProductField::orm()->where('scoring', 1)->order_by_asc('var_number')->find_many();
        $m = $args['model'];
        $m->scoring_fields = $fields;
        $args['view']->set(array(
            'title' => $m->id ? 'Edit Product Function: '.$m->name: 'Create New Product Function',
        ));
    }
}