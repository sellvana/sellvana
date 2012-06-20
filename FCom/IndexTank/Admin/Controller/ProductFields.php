<?php

class FCom_IndexTank_Admin_Controller_ProductFields extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'indextank/product_fields';
    protected $_modelClass = 'FCom_IndexTank_Model_ProductField';
    protected $_mainTableAlias = 'pf';

    public function gridConfig()
    {
        $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->orm()->where("task", "index_all_new")->find_one();
        if ($indexingStatus) {
            BLayout::i()->view('indextank/product_fields')->set('indexing_status', $indexingStatus->info);
        } else {
            BLayout::i()->view('indextank/product_fields')->set('indexing_status', "N/A");
        }
        try {
            $status = FCom_IndexTank_Index_Product::i()->status();
            BLayout::i()->view('indextank/product_fields')->set('status', $status);
        } catch (Exception $e) {
            BLayout::i()->view('indextank/product_fields')->set('status', false);
        }

        $fld = FCom_IndexTank_Model_ProductField::i();
        $config = parent::gridConfig();
        $config['grid']['columns'] += array(
            'field_nice_name' => array('label'=>'Name', 'editable'=>true, 'formatter'=>'showlink', 'formatoptions'=>array(
                'baseLinkUrl' => BApp::href('indextank/product_fields/form'), 'idName' => 'id',
            )),
            'search' => array('label'=>'Search', 'options'=>$fld->fieldOptions('search')),
            'facets' => array('label'=>'Facets', 'options'=>$fld->fieldOptions('facets')),
            'scoring' => array('label'=>'Scoring', 'options'=>$fld->fieldOptions('scoring')),
            'var_number' => array('label'=>'Scoring variable #'),
            'priority' => array('label'=>'Priority'),
            'filter' => array('label'=>'Filter type'),
            'source_type' => array('label'=>'Source type'),
            'source_value' => array('label'=>'Source'),
        );

        return $config;
    }

    public function formViewBefore($args)
    {

        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'title' => $m->id ? 'Edit Product Field: '.$m->field_nice_name : 'Create New Product Field',
        ));
    }

    public function action_form__POST()
    {
        $r = BRequest::i();
        $class = $this->_modelClass;
        $id = $r->params('id', true);
        $model = $id ? $class::i()->load($id) : $class::i()->create();
        if ($model) {
            //clear field in index
            if ($r->post('do')==='DELETE') {
                FCom_IndexTank_Admin::productsIndexAll();
            }
        }
        //remove field from database
        parent::action_form__POST();
    }

}