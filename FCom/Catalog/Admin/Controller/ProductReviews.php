<?php

class FCom_Catalog_Admin_Controller_ProductReviews extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'catalog/reviews';
    protected $_modelClass = 'FCom_Catalog_Model_ProductReview';
    protected $_mainTableAlias = 'pr';
    //protected $_formLayoutName = 'catalog/reviews/main';

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
            'actions' => array(
                'back' => '<button type="button" class="st3 sz2 btn" onclick="location.href=\''.BApp::href("catalog/products/form?id=".$m->product_id).'\'"><span>Back to product</span></button>',
                'delete' => '<button type="submit" class="st2 sz2 btn" name="do" value="DELETE" onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>Delete</span></button>',
                'save' => '<button type="submit" class="st1 sz2 btn" onclick="return adminForm.saveAll(this)"><span>Save</span></button>',
            ),
        ));

        //print_r($args);exit;
    }

    public function action_index()
    {
        BResponse::i()->redirect(BApp::href("catalog/products"));
    }

}