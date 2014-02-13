<?php

class FCom_ProductReviews_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'prodreviews';
    protected $_modelClass = 'FCom_ProductReviews_Model_Review';
    protected $_mainTableAlias = 'pr';
    protected $_gridTitle = 'Product Reviews';
    protected $_recordName = 'Product Review';
    //custom grid view
    protected $_gridViewName = 'prodreviews/grid';

    public function gridConfig($productModel = false)
    {
        //$formUrl = BApp::href("prodreviews/form");
        $reviewConfigs = FCom_ProductReviews_Model_Review::i()->config();
        $config = parent::gridConfig();
        $columns = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name'=>'id','label'=>'ID', 'width'=>55, 'hidden'=>true),
            array('name'=>'title', 'label'=>'Title', 'width'=>250, 'addable' => true, 'editable'=>true, 'validation' => array('required' => true)),
            array('name'=>'text', 'label'=>'Comment', 'width'=>250, 'addable' => true, 'editable'=>true, 'editor' => 'textarea'),
            array('name'=>'rating', 'label' => 'Total Rating', 'width' => 60, 'addable' => true, 'editable' => true,
                  'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating"]+"\'></div>"',
                  'element_print' => $this->inputRatingHtml('rating'),
                  /*'validation' => array('required' => true, 'number' => true, 'range' => array($reviewConfigs['min'], $reviewConfigs['max']))*/),
            array('name'=>'rating1', 'label'=>'Value Rating', 'width'  => 60, 'hidden' => true, 'addable' => true, 'editable' => true,
                  'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating1"]+"\'></div>"',
                  'element_print' => $this->inputRatingHtml('rating1'),
                  /*'validation' => array('number' => true), 'range' => array($reviewConfigs['min'], $reviewConfigs['max'])*/),
            array('name'=>'rating2', 'label'=>'Features Rating', 'width'=>60, 'hidden' => true, 'addable' => true, 'editable' => true,
                  'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating2"]+"\'></div>"',
                  'element_print' => $this->inputRatingHtml('rating2'),
                  /*'validation' => array('number' => true), 'range' => array($reviewConfigs['min'], $reviewConfigs['max'])*/),
            array('name'=>'rating3', 'label'=>'Quality Rating', 'width'=>60, 'hidden' => true, 'addable' => true, 'editable'=>true,
                  'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating3"]+"\'></div>"',
                  'element_print' => $this->inputRatingHtml('rating3'),
                  /*'validation' => array('number' => true), 'range' => array($reviewConfigs['min'], $reviewConfigs['max'])*/),
            array('name'=>'helpful','label'=>'Helpful', 'width'=>60, 'addable' => true, 'editable'=>true, 'validation' => array('number' => true)),
            array('name'=>'approved', 'label'=>'Approved', 'addable' => true, 'editable'=>true, 'mass-editable'=>true,
                  'options'=>array('1'=>'Yes','0'=>'No'),'editor' => 'select'),
            array('name'=>'product_id', 'label'=>'Product', 'addable' => true, 'hidden' => true,
                  'options'=>FCom_Catalog_Model_Product::i()->getOptionsData(), 'editor' => 'select',
                  'validation' => array('required' => true)),
            array('name'=>'customer_id', 'label'=>'Customer', 'addable' => true, 'hidden' => true,
                  'options'=>FCom_Customer_Model_Customer::i()->getOptionsData(), 'editor' => 'select',
                  'validation' => array('required' => true)),
            /*array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'data' => array('edit' => true, 'delete' => true)),*/
        );

        $config['filters'] = array(
            array('field'=>'title', 'type'=>'text'),
            array('field'=>'text', 'type'=>'text'),
            array('field'=>'rating', 'type'=>'number-range'),
            array('field'=>'rating1', 'type'=>'number-range'),
            array('field'=>'rating2', 'type'=>'number-range'),
            array('field'=>'rating3', 'type'=>'number-range'),
            array('field'=>'helpful', 'type'=>'text'),
            array('field'=>'approved', 'type' => 'multiselect'),
            array('field'=>'product_id', 'type' => 'multiselect'),
            array('field'=>'customer_id', 'type' => 'multiselect'),
            array('field'=>'create_at', 'type' => 'date-range'),
            '_quick'=>array('expr'=>'title like ? or id=?', 'args'=>array('%?%', '?'))
        );
        $config['actions'] = array();
        if (!$productModel) {
//            $config['actions']['new'] = array('caption' => 'New Product Review', 'modal' => true);
        }
        $config['actions'] += array(
            'export'  => true,
            'delete'  => true,
            'approve' => array('html' => '<button type="button" class="btn btn-primary disabled" id="prod-reviews-approve"><span>Approve</span></button>'),
            'deny'    => array('html' => '<button type="button" class="btn btn-warning disabled" id="prod-reviews-deny"><span>Deny</span></button>'),
        );

        $config['events'] = array('select-rows');
        //$config['autowidth'] = false;
        $config['caption'] = 'All review';
        //$config['multiselect'] = false;
        //$config['height'] = '100%';
        $config['columns'] = $columns;
        //$config['navGrid'] = array('add'=>false, 'edit'=>true, 'del'=>true);

        if ($productModel) {
            $config['id'] = 'products_reviews';
            $i = BUtil::arrayFind($config['columns'], array('name' => '_actions'));
            $config['columns'][$i]['data']['edit']['href'] = BApp::href('/prodreviews/form_only?id=');
            $config['columns'][$i]['data']['edit']['async_edit'] = true;
            $config['columns'][] = array('name'=>'customer', 'label'=>'Customer', 'width'=>250);
            $config['data_mode'] = 'local';
            //$config['filters'][] = array('field'=>'product_name', 'type'=>'text');
            $config['custom'] = array('personalize'=>true);
            $orm = FCom_ProductReviews_Model_Review::orm('pr')->where('product_id', $productModel->id())
                ->join('FCom_Catalog_Model_Product', array('p.id','=','pr.product_id'), 'p')
                ->left_outer_join('FCom_Customer_Model_Customer', array('c.id','=','pr.customer_id'), 'c')
                ->select('pr.*')->select('p.product_name')->select_expr('CONCAT_WS(" ", c.firstname, c.lastname) as customer');

            $data = BDb::many_as_array($orm->find_many());
            unset($config['orm']);
            /*$columnKeys = array_keys($config['grid']['columns']);
            foreach($data as &$prod){
                foreach($prod as $k=>$p) {
                    if (!in_array($k, $columnKeys)) {
                        unset($prod[$k]);
                    }
                }
            }*/
            //print_r($data);
            unset($config['columns'][6]['data']['edit']);
            $config['columns'][6]['data']['custom']=array('caption'=>'Edit...');
            $config['data'] = $data;
        } else {
            //$config['custom'] = array('personalize'=>true, 'autoresize'=>true, 'hashState'=>true, 'export'=>true, 'dblClickHref'=>$formUrl.'?id=');
            $config['id'] = 'products_reviews_grid';
            $config['columns'][] = array('name'=>'product_name', 'label'=>'Product name', 'width'=>250);
            $config['columns'][] = array('name'=>'customer', 'label'=>'Customer', 'width'=>250);
            $config['columns'][] = array('name'=>'create_at', 'label'=>'Created');
            $config['orm'] = FCom_ProductReviews_Model_Review::i()->orm('pr')->select('pr.*')
                ->left_outer_join('FCom_Catalog_Model_Product', array('p.id','=','pr.product_id'), 'p')
                ->left_outer_join('FCom_Customer_Model_Customer', array('c.id','=','pr.customer_id'), 'c')
                ->select('p.product_name')->select_expr('CONCAT_WS(" ", c.firstname, c.lastname) as customer');
        }

        $config['columns'][] = array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'width' => 80,
                                     'data' => array('edit' => true, 'delete' => true));

        $callbacks = '$(".rateit").rateit();
                      $("#'.$config['id'].'-modal-form").on("show.bs.modal", function(){ $(".rateit").rateit(); });';
        $config['callbacks'] = array('after_gridview_render' => $callbacks);
        $config['new_button'] = '#add_new_product_review';
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);
        $orm->left_outer_join('FCom_Catalog_Model_Product', array('p.id','=','pr.product_id'), 'p')
            ->left_outer_join('FCom_Customer_Model_Customer', array('c.id','=','pr.customer_id'), 'c')
            ->select('p.product_name')->select_expr('CONCAT_WS(" ", c.firstname, c.lastname) as author');
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'title'=>$m->id ? 'Edit Product Review: '.$m->title : 'Create New Product Review',
            'actions'=>array(
                'back'=>'<button type="button" class="st3 sz2 btn" onclick="location.href=\''.BApp::href("prodreviews").'\'"><span>' .  BLocale::_('Back to list') . '</span></button>',
                'delete'=>'<button type="submit" class="st2 sz2 btn" name="do" value="DELETE" onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>' .  BLocale::_('Delete') . '</span></button>',
                'save'=>'<button type="submit" class="st1 sz2 btn btn-primary" onclick="return adminForm.saveAll(this)"><span>' .  BLocale::_('Save') . '</span></button>',
            ),
        ));

    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('prodreviews/grid')->set(array(
                'title' => $this->_gridTitle,
                'actions' => array('new' => '<button id="add_new_product_review" class="btn grid-new btn-primary _modal" type="button">'.BLocale::_('New Product Review').'</button>')
            ));
    }

    public function inputRatingHtml($name)
    {
        $config = FCom_ProductReviews_Model_Review::i()->config();
        return '<input name="' . $name . '" id="' . $name . '" type="range" min="' . $config['min'] . '"
                max="' . $config['max'] . '" step="' . $config['step'] . '" value="" />
                <div class="rateit" data-rateit-backingfld="#' . $name . '"></div>';
    }

    /**
     * get grid config for all reviews of customer
     * @param $customer FCom_Customer_Model_Customer
     * @return array
     */
    public function customerReviewsGridConfig($customer)
    {
        $config = parent::gridConfig();
        $config['id'] = 'customer_reviews_grid_'.$customer->id;
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name'=>'id','label'=>'ID', 'width'=>55, 'hidden'=>true),
            array('name'=>'title', 'label'=>'Title', 'width'=>250, 'addable' => true, 'editable'=>true, 'validation' => array('required' => true)),
            array('name'=>'text', 'label'=>'Comment', 'width'=>250, 'addable' => true, 'editable'=>true, 'editor' => 'textarea'),
            array('name'=>'rating', 'label' => 'Total Rating', 'width' => 60, 'addable' => true, 'editable' => true,
                  'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating"]+"\'></div>"',
                  'element_print' => $this->inputRatingHtml('rating'),
                /*'validation' => array('required' => true, 'number' => true, 'range' => array($reviewConfigs['min'], $reviewConfigs['max']))*/),
            array('name'=>'rating1', 'label'=>'Value Rating', 'width'  => 60, 'hidden' => true, 'addable' => true, 'editable' => true,
                  'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating1"]+"\'></div>"',
                  'element_print' => $this->inputRatingHtml('rating1'),
                /*'validation' => array('number' => true), 'range' => array($reviewConfigs['min'], $reviewConfigs['max'])*/),
            array('name'=>'rating2', 'label'=>'Features Rating', 'width'=>60, 'hidden' => true, 'addable' => true, 'editable' => true,
                  'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating2"]+"\'></div>"',
                  'element_print' => $this->inputRatingHtml('rating2'),
                /*'validation' => array('number' => true), 'range' => array($reviewConfigs['min'], $reviewConfigs['max'])*/),
            array('name'=>'rating3', 'label'=>'Quality Rating', 'width'=>60, 'hidden' => true, 'addable' => true, 'editable'=>true,
                  'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating3"]+"\'></div>"',
                  'element_print' => $this->inputRatingHtml('rating3'),
                /*'validation' => array('number' => true), 'range' => array($reviewConfigs['min'], $reviewConfigs['max'])*/),
            array('name'=>'helpful','label'=>'Helpful', 'width'=>60, 'addable' => true, 'editable'=>true, 'validation' => array('number' => true)),
            array('name'=>'approved', 'label'=>'Approved', 'addable' => true, 'editable'=>true, 'mass-editable'=>true,
                  'options'=>array('1'=>'Yes','0'=>'No'),'editor' => 'select'),
            array('name'=>'product_id', 'label'=>'Product', 'addable' => true, 'hidden' => true,
                  'options'=>FCom_Catalog_Model_Product::i()->getOptionsData(), 'editor' => 'select',
                  'validation' => array('required' => true)),
            array('name'=>'product_name', 'label'=>'Product name', 'width'=>250),
            array('name'=>'create_at', 'label'=>'Created')
        );

        $config['filters'] = array(
            array('field'=>'title', 'type'=>'text'),
            array('field'=>'approved', 'type' => 'multiselect'),
            '_quick'=>array('expr'=>'title like ? or id=?', 'args'=>array('%?%', '?'))
        );

        $config['actions'] = array('delete' => true);

        $config['columns'][] = array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'width' => 80,
                                     'data' => array('edit' => true, 'delete' => true));

        $config['orm'] = FCom_ProductReviews_Model_Review::i()->orm('pr')->select('pr.*')->where('customer_id', $customer->id)
            ->left_outer_join('FCom_Catalog_Model_Product', array('p.id','=','pr.product_id'), 'p')->select('p.product_name');

        $callbacks = '$(".rateit").rateit();
                      $("#'.$config['id'].'-modal-form").on("show.bs.modal", function(){ $(".rateit").rateit(); });';
        $config['callbacks'] = array('after_gridview_render' => $callbacks);

        return array('config' => $config);

    }
}
