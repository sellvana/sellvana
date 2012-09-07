<?php

class FCom_ProductReviews_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'prodreviews';
    protected $_modelClass = 'FCom_ProductReviews_Model_Reviews';
    protected $_mainTableAlias = 'prr';

    public function gridConfig($productModel = false)
    {
        $formUrl = BApp::href("prodreviews/form");
        $config = parent::gridConfig();
        $columns = array(
            'id'=>array('label'=>'ID', 'width'=>55),
            'title'=>array('label'=>'Title', 'width'=>250, 'editable'=>true),
            'rating'=>array('label'=>'Rating', 'width'=>60, 'editable'=>true),
            'helpful'=>array('label'=>'Helpful', 'width'=>60, 'editable'=>true),
            'approved'=>array('label'=>'Approved', 'editable'=>true, 'options' => array('1' => 'Yes','0' => 'No'))
        );

        $config['grid']['id'] = __CLASS__;
        $config['grid']['autowidth'] = false;
        $config['grid']['caption'] = 'All review';
        $config['grid']['multiselect'] = false;
        $config['grid']['height'] = '100%';
        $config['grid']['columns'] = $columns;
        $config['navGrid'] = array('add'=>false, 'edit'=>true, 'del'=>true);

        if ($productModel) {
            $config['grid']['id'] = 'products_reviews';
            $config['grid']['columns']['product_name']=array('label'=>'Product name', 'width'=>250, 'editable'=>false);
            $config['grid']['datatype'] = 'local';
            $config['grid']['editurl'] = '';
            $config['grid']['url'] = '';
            $config['custom'] = array('personalize'=>true);
            $orm = FCom_ProductReviews_Model_Reviews::orm('pr')->where('product_id', $productModel->id())
                ->join('FCom_Catalog_Model_Product', array('p.id','=','pr.product_id'), 'p')
                ->select('pr.*')->select('p.product_name');

            $data = BDb::many_as_array($orm->find_many());
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
        } else {
            $config['custom'] = array('personalize'=>true, 'autoresize'=>true, 'hashState'=>true, 'export'=>true, 'dblClickHref'=>$formUrl.'?id=');
        }

        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'title' => $m->id ? 'Edit Product Review: '.$m->title : 'Create New Product Review',
            'actions' => array(
                'back' => '<button type="button" class="st3 sz2 btn" onclick="location.href=\''.BApp::href("prodreviews").'\'"><span>Back to list</span></button>',
                'delete' => '<button type="submit" class="st2 sz2 btn" name="do" value="DELETE" onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>Delete</span></button>',
                'save' => '<button type="submit" class="st1 sz2 btn" onclick="return adminForm.saveAll(this)"><span>Save</span></button>',
            ),
        ));

    }

}