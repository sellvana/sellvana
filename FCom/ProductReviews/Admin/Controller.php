<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_ProductReviews_Admin_Controller
 *
 * @property FCom_ProductReviews_Model_Review $FCom_ProductReviews_Model_Review
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 */
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
    protected $_useDefaultLayout = false;
    protected $_permission = 'product_review';

    public function gridConfig($productModel = false)
    {
        //$formUrl = $this->BApp->href("prodreviews/form");
        $reviewConfigs = $this->FCom_ProductReviews_Model_Review->config();
        $config = parent::gridConfig();
        $columns = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'width' => 55, 'hidden' => true],
            ['type' => 'input', 'name' => 'title', 'label' => 'Title', 'width' => 250, 'addable' => true,
                'editable' => true, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'text', 'label' => 'Comment', 'width' => 250, 'addable' => true,
                'editable' => true, 'editor' => 'textarea'],
            ['type' => 'input', 'name' => 'rating', 'label' => 'Total Rating', 'width' => 60, 'addable' => true,
                'editable' => true, 'element_print' => $this->inputRatingHtml('rating'),
                'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating"]+"\'></div>"',
                /*'validation' => array('required' => true, 'number' => true, 'range' => array($reviewConfigs['min'], $reviewConfigs['max']))*/],
            ['type' => 'input', 'name' => 'rating1', 'label' => 'Value Rating', 'width'  => 60, 'hidden' => true,
                'addable' => true, 'editable' => true, 'element_print' => $this->inputRatingHtml('rating1'),
                'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating1"]+"\'></div>"',
                /*'validation' => array('number' => true), 'range' => array($reviewConfigs['min'], $reviewConfigs['max'])*/],
            ['type' => 'input', 'name' => 'rating2', 'label' => 'Features Rating', 'width' => 60, 'hidden' => true,
                'addable' => true, 'editable' => true, 'element_print' => $this->inputRatingHtml('rating2'),
                'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating2"]+"\'></div>"',
                /*'validation' => array('number' => true), 'range' => array($reviewConfigs['min'], $reviewConfigs['max'])*/],
            ['type' => 'input', 'name' => 'rating3', 'label' => 'Quality Rating', 'width' => 60, 'hidden' => true,
                'addable' => true, 'editable' => true, 'element_print' => $this->inputRatingHtml('rating3'),
                'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating3"]+"\'></div>"',
                /*'validation' => array('number' => true), 'range' => array($reviewConfigs['min'], $reviewConfigs['max'])*/],
            ['type' => 'input', 'name' => 'helpful', 'label' => 'Helpful', 'width' => 60, 'addable' => true,
                'editable' => true, 'validation' => ['number' => true]],
            ['type' => 'input', 'name' => 'approved', 'label' => 'Approved', 'addable' => true, 'editable' => true,
                'multirow_edit' => true, 'options' => ['1' => 'Yes', '0' => 'No'], 'editor' => 'select'],
            ['type' => 'input', 'name' => 'product_id', 'label' => 'Product', 'addable' => true, 'hidden' => true,
                'options' => $this->FCom_Catalog_Model_Product->getOptionsData(), 'editor' => 'select',
                'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'customer_id', 'label' => 'Customer', 'addable' => true, 'hidden' => true,
                'options' => $this->FCom_Customer_Model_Customer->getOptionsData(), 'editor' => 'select',
                'validation' => ['required' => true]]
        ];

        $config['filters'] = [
            ['field' => 'title', 'type' => 'text'],
            ['field' => 'text', 'type' => 'text'],
            ['field' => 'rating', 'type' => 'number-range'],
            ['field' => 'rating1', 'type' => 'number-range'],
            ['field' => 'rating2', 'type' => 'number-range'],
            ['field' => 'rating3', 'type' => 'number-range'],
            ['field' => 'helpful', 'type' => 'text'],
            ['field' => 'approved', 'type' => 'multiselect'],
            ['field' => 'product_id', 'type' => 'multiselect'],
            ['field' => 'customer_id', 'type' => 'multiselect'],
            ['field' => 'create_at', 'type' => 'date-range'],
            '_quick' => ['expr' => 'title like ? or id=?', 'args' => ['%?%', '?']]
        ];
        $config['actions'] = [];
        if (!$productModel) {
//            $config['actions']['new'] = array('caption' => 'New Product Review', 'modal' => true);
        }
        $config['actions'] = [
            'export'  => true,
            'delete'  => true,
            'deny'    => ['class' => 'btn btn-warning disabled', 'id' => "prod-reviews-deny", 'caption' => 'Deny'],
            'approve' => ['class' => "btn btn-primary disabled", 'id' => "prod-reviews-approve", 'caption' => 'Approve'],
        ];


        //$config['autowidth'] = false;
        $config['caption'] = 'All review';
        //$config['multiselect'] = false;
        //$config['height'] = '100%';
        $config['columns'] = $columns;
        //$config['navGrid'] = array('add'=>false, 'edit'=>true, 'del'=>true);

        if ($productModel) {
            $config['id'] = 'products_reviews';
            $i = $this->BUtil->arrayFind($config['columns'], ['name' => '_actions']);
            $config['columns'][$i]['data']['edit']['href'] = $this->BApp->href('/prodreviews/form_only?id=');
            $config['columns'][$i]['data']['edit']['async_edit'] = true;
            $config['columns'][] = ['name' => 'customer', 'label' => 'Customer', 'width' => 250];
            $config['data_mode'] = 'local';
            $config['edit_url_required'] = true;
            //$config['filters'][] = array('field'=>'product_name', 'type'=>'text');
            $config['custom'] = ['personalize' => true];
            $orm = $this->FCom_ProductReviews_Model_Review->orm('pr')->where('product_id', $productModel->id())
                ->join('FCom_Catalog_Model_Product', ['p.id', '=', 'pr.product_id'], 'p')
                ->left_outer_join('FCom_Customer_Model_Customer', ['c.id', '=', 'pr.customer_id'], 'c')
                ->select('pr.*')->select('p.product_name')->select_expr('CONCAT_WS(" ", c.firstname, c.lastname) as customer');

            $data = $this->BDb->many_as_array($orm->find_many());
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
            $config['columns'][6]['data']['custom'] = ['caption' => 'Edit...'];
            $config['data'] = $data;
        } else {
            //$config['custom'] = array('personalize'=>true, 'autoresize'=>true, 'hashState'=>true, 'export'=>true, 'dblClickHref'=>$formUrl.'?id=');
            $config['id'] = 'products_reviews_grid';
            $config['columns'][] = ['name' => 'product_name', 'label' => 'Product name', 'width' => 250];
            $config['columns'][] = ['name' => 'customer', 'label' => 'Customer', 'width' => 250];
            $config['columns'][] = ['name' => 'create_at', 'label' => 'Created'];
            $config['orm'] = $this->FCom_ProductReviews_Model_Review->orm('pr')->select('pr.*')
                ->left_outer_join('FCom_Catalog_Model_Product', ['p.id', '=', 'pr.product_id'], 'p')
                ->left_outer_join('FCom_Customer_Model_Customer', ['c.id', '=', 'pr.customer_id'], 'c')
                ->select('p.product_name')->select_expr('CONCAT_WS(" ", c.firstname, c.lastname) as customer');
        }

        $config['columns'][] = ['type' => 'btn_group', 'name' => '_actions', 'label' => 'Actions', 'sortable' => false,
            'buttons' => [['name' => 'edit'], ['name' => 'delete']]];

        $callbacks = '$(".rateit").rateit();
            $("#' . $config['id'] . '-modal-form").on("show.bs.modal", function(){ $(".rateit").rateit(); });';
        $config['callbacks'] = ['after_gridview_render' => $callbacks];
//        $config['new_button'] = '#add_new_product_review';

        $config['grid_before_create'] = $config['id'] . '_register';
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);
        $orm->left_outer_join('FCom_Catalog_Model_Product', ['p.id', '=', 'pr.product_id'], 'p')
            ->left_outer_join('FCom_Customer_Model_Customer', ['c.id', '=', 'pr.customer_id'], 'c')
            ->select('p.product_name')->select_expr('CONCAT_WS(" ", c.firstname, c.lastname) as author');
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set([
            'title' => $m->id ? 'Edit Product Review: ' . $m->title : 'Create New Product Review',
            'actions' => [
                'back' => '<button type="button" class="st3 sz2 btn" onclick="location.href=\''
                    . $this->BApp->href("prodreviews") . '\'"><span>' .  $this->BLocale->_('Back to list') . '</span></button>',
                'delete' => '<button type="submit" class="st2 sz2 btn" name="do" value="DELETE" '
                    . 'onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>'
                    . $this->BLocale->_('Delete') . '</span></button>',
                'save' => '<button type="submit" class="st1 sz2 btn btn-primary" onclick="return adminForm.saveAll(this)"><span>'
                    . $this->BLocale->_('Save') . '</span></button>',
            ],
        ]);

    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->BLayout->applyLayout('prodreviews');
        $this->view('prodreviews/grid')->set([
            'title' => $this->_gridTitle,
            'actions' => []
        ]);
    }

    public function inputRatingHtml($name)
    {
        $config = $this->FCom_ProductReviews_Model_Review->config();
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
        $config['id'] = 'customer_reviews_grid_' . $customer->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'width' => 55, 'hidden' => true],
            ['type' => 'input', 'name' => 'title', 'label' => 'Title', 'width' => 250, 'addable' => true,
                'editable' => true, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'text', 'label' => 'Comment', 'width' => 250, 'addable' => true,
                'editable' => true, 'editor' => 'textarea'],
            ['type' => 'input', 'name' => 'rating', 'label' => 'Total Rating', 'width' => 60, 'addable' => true,
                'editable' => true, 'element_print' => $this->inputRatingHtml('rating'),
                'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating"]+"\'></div>"',
                /*'validation' => array('required' => true, 'number' => true, 'range' => array($reviewConfigs['min'], $reviewConfigs['max']))*/],
            ['type' => 'input', 'name' => 'rating1', 'label' => 'Value Rating', 'width'  => 60, 'hidden' => true,
                'addable' => true, 'editable' => true, 'element_print' => $this->inputRatingHtml('rating1'),
                'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating1"]+"\'></div>"',
                /*'validation' => array('number' => true), 'range' => array($reviewConfigs['min'], $reviewConfigs['max'])*/],
            ['type' => 'input', 'name' => 'rating2', 'label' => 'Features Rating', 'width' => 60, 'hidden' => true,
                'addable' => true, 'editable' => true, 'element_print' => $this->inputRatingHtml('rating2'),
                'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating2"]+"\'></div>"',
                /*'validation' => array('number' => true), 'range' => array($reviewConfigs['min'], $reviewConfigs['max'])*/],
            ['type' => 'input', 'name' => 'rating3', 'label' => 'Quality Rating', 'width' => 60, 'hidden' => true,
                'addable' => true, 'editable' => true, 'element_print' => $this->inputRatingHtml('rating3'),
                'print' => '"<div class=\'rateit\' data-rateit-readonly=\'true\' data-rateit-value=\'"+rc.row["rating3"]+"\'></div>"',
                /*'validation' => array('number' => true), 'range' => array($reviewConfigs['min'], $reviewConfigs['max'])*/],
            ['type' => 'input', 'name' => 'helpful', 'label' => 'Helpful', 'width' => 60, 'addable' => true,
                'editable' => true, 'validation' => ['number' => true]],
            ['type' => 'input', 'name' => 'approved', 'label' => 'Approved', 'addable' => true, 'editable' => true,
                'multirow_edit' => true, 'options' => ['1' => 'Yes', '0' => 'No'], 'editor' => 'select'],
            ['type' => 'input', 'name' => 'product_id', 'label' => 'Product', 'addable' => true, 'hidden' => true,
                  'options' => $this->FCom_Catalog_Model_Product->getOptionsData(), 'editor' => 'select',
                  'validation' => ['required' => true]],
            ['name' => 'product_name', 'label' => 'Product name', 'width' => 250],
            ['name' => 'create_at', 'label' => 'Created']
        ];

        $config['filters'] = [
            ['field' => 'title', 'type' => 'text'],
            ['field' => 'approved', 'type' => 'multiselect'],
            '_quick' => ['expr' => 'title like ? or id=?', 'args' => ['%?%', '?']]
        ];

        $config['actions'] = ['delete' => true];

        $config['columns'][] = ['name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'width' => 80,
            'data' => ['edit' => true, 'delete' => true]];

        $config['orm'] = $this->FCom_ProductReviews_Model_Review->orm('pr')->select('pr.*')->where('customer_id', $customer->id)
            ->left_outer_join('FCom_Catalog_Model_Product', ['p.id', '=', 'pr.product_id'], 'p')->select('p.product_name');

        $callbacks = '$(".rateit").rateit();
            $("#' . $config['id'] . '-modal-form").on("show.bs.modal", function(){ $(".rateit").rateit(); });';
        $config['callbacks'] = ['after_gridview_render' => $callbacks];

        return ['config' => $config];

    }
}
