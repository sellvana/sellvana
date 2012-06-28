<?php

class FCom_Catalog_Admin_Controller_ProductReviews extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'catalog/reviews';
    protected $_modelClass = 'FCom_Catalog_Model_ProductReview';
    protected $_mainTableAlias = 'pr';

    public function gridConfig($model)
    {
        $formUrl = BApp::href("catalog/reviews/form");
        $config = array();
        $columns = array(
            'id'=>array('label'=>'ID', 'width'=>55),
            'title'=>array('label'=>'Title', 'width'=>250),
            'rating'=>array('label'=>'Rating', 'width'=>60),
            'helpful'=>array('label'=>'Helpful', 'width'=>60,)
        );

        $config['grid']['id'] = 'productreview';
        $config['grid']['url'] = '';
        $config['grid']['datatype'] = 'local';
        $config['grid']['autowidth'] = false;
        $config['grid']['caption'] = 'All review';
        $config['grid']['multiselect'] = false;
        $config['grid']['height'] = '100%';
        $config['grid']['columns'] = $columns;
        $config['navGrid'] = array('add'=>false, 'edit'=>false, 'del'=>false);
        $config['custom'] = array('personalize'=>true, 'autoresize'=>true, 'hashState'=>true, 'export'=>true, 'dblClickHref'=>$formUrl.'?id=');

        $orm = FCom_Catalog_Model_ProductReview::i()->orm()->where('product_id', $model->id());
        $data = BDb::many_as_array($orm->find_many());

        //unset unused columns
        $columnKeys = array_keys($config['grid']['columns']);
        foreach($data as &$prod){
            foreach($prod as $k => $p) {
                if (!in_array($k, $columnKeys)) {
                    unset($prod[$k]);
                }
            }
        }
        //print_r($data);
        $config['grid']['data'] = $data;
        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'title' => $m->id ? 'Edit Product Review: '.$m->title : 'Create New Product Review',
        ));
    }

}