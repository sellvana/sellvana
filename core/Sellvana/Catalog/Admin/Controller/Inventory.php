<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Admin_Controller_Inventory
 *
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_CatalogFields_Model_ProductVariant $Sellvana_CatalogFields_Model_ProductVariant
 */
class Sellvana_Catalog_Admin_Controller_Inventory extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'catalog/inventory';
    protected $_modelClass = 'Sellvana_Catalog_Model_InventorySku';
    protected $_gridHref = 'catalog/inventory';
    protected $_gridTitle = 'Inventory Management';
    protected $_recordName = 'Inventory SKU';
    protected $_mainTableAlias = 's';
    protected $_navPath = 'catalog/inventory';
    #protected $_gridLayoutName = '/catalog/inventory';

    #protected $_defaultGridLayoutName = 'default_grid';
    #protected $_gridPageViewName = 'admin/grid';
    #protected $_gridViewName = 'core/backbonegrid';

    public function gridConfig()
    {
        $invHlp = $this->Sellvana_Catalog_Model_InventorySku;
//        $yesNo = [0 => 'no', 1 => 'YES'];
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
                ['name' => 'delete'],
            ]],
            ['name' => 'id', 'label' => 'ID', 'width' => 50],
            ['name' => 'title', 'label' => 'Title'],
            ['name' => 'inventory_sku', 'label' => 'SKU'],
            ['name' => 'manage_inventory', 'label' => 'Manage', 'options' => $invHlp->fieldOptions('manage_inventory')],
            ['name' => 'allow_backorder', 'label' => 'Allow Backorder', 'options' => $invHlp->fieldOptions('allow_backorder')],
            ['name' => 'qty_in_stock', 'label' => 'Quantity In Stock'],
            ['name' => 'qty_reserved', 'label' => 'Qty Reserved'],
            ['name' => 'qty_buffer', 'label' => 'Qty Buffer'],
            ['name' => 'unit_cost', 'label' => 'Unit Cost'],
            ['name' => 'net_weight', 'label' => 'Net Weight'],
            ['name' => 'shipping_weight', 'label' => 'Ship Weight'],
            ['name' => 'shipping_size', 'label' => 'Ship Size'],
            ['name' => 'pack_separate', 'label' => 'Pack Separate', 'options' => $invHlp->fieldOptions('pack_separate')],
            ['name' => 'qty_warn_customer', 'label' => 'Qty to Warn Customer'],
            ['name' => 'qty_notify_admin', 'label' => 'Qty to Notify Admin'],
            ['name' => 'qty_cart_min', 'label' => 'Min Qty in Cart'],
            ['name' => 'qty_cart_inc', 'label' => 'Cart Increment'],
        ];
        $config['actions'] = [
            'edit' => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'id', 'type' => 'number-range'],
            ['field' => 'manage_inventory', 'type' => 'multiselect'],
            ['field' => 'allow_backorder', 'type' => 'multiselect'],
            ['field' => 'title', 'type' => 'text'],
            ['field' => 'inventory_sku', 'type' => 'text'],
            ['field' => 'unit_cost', 'type' => 'number-range'],
            ['field' => 'net_weight', 'type' => 'number-range'],
            ['field' => 'shipping_weight', 'type' => 'number-range'],
            ['field' => 'qty_in_stock', 'type' => 'number-range'],
            ['field' => 'qty_reserved', 'type' => 'number-range'],
            ['field' => 'qty_buffer', 'type' => 'number-range'],
            ['field' => 'qty_warn_customer', 'type' => 'number-range'],
            ['field' => 'qty_notify_admin', 'type' => 'number-range'],
            ['field' => 'qty_cart_min', 'type' => 'number-range'],
            ['field' => 'qty_cart_inc', 'type' => 'number-range'],
            ['field' => 'pack_separate', 'type' => 'multiselect'],
        ];
        #$config['grid_before_create'] = 'stockGridRegister';
        #$config['new_button'] = '#add_new_sku';
        return $config;
    }

    /**
     * @param Sellvana_Catalog_Model_InventorySku $model
     * @return array
     */
    public function prodInventoryConfig($model = null)
    {
        $downloadUrl = $this->BApp->href('/media/grid/download?folder=media/product/images&file=');
        $thumbUrl = $this->FCom_Core_Main->resizeUrl($this->BConfig->get('web/media_dir') . '/product/images', ['s' => 100]);
        $data = [];
        if ($model) {
            $data = $this->BDb->many_as_array($this->Sellvana_Catalog_Model_Product->orm('p')
                ->join('Sellvana_Catalog_Model_ProductMedia', ['p.id', '=', 'pa.file_id'], 'pa')
                ->join('FCom_Core_Model_MediaLibrary', ['a.id', '=', 'pa.file_id'], 'a')
                ->where('p.inventory_sku', $model->get('inventory_sku'))
                ->where('pa.media_type', Sellvana_Catalog_Model_ProductMedia::MEDIA_TYPE_IMG)
                ->where_raw('p.id = pa.product_id')
                ->select(['p.*', 'pa.*', 'a.folder', 'a.subfolder', 'a.file_name', 'a.file_size'])
                ->select_expr('IF (a.subfolder is null, "", CONCAT("/", a.subfolder))', 'subfolder')
                ->find_many());
        }

        $config = [
            'config' => [
                'id' => 'product_inventory_sku',
                'caption' => 'Product Inventory SKU',
                'data_mode' => 'local',
                'data' => $data,
                'columns' => [
                    ['type' => 'row_select', 'width' => 55],
                    ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 55, 'hidden' => true],
                    ['name' => 'prev_img', 'label' => 'Preview', 'width' => 110, 'display' => 'eval',
                        'print' => '"<a href=\''.$downloadUrl.'"+rc.row["subfolder"]+"/"+rc.row["file_name"]+"\'>'
                            . '<img src=\''.$thumbUrl.'"+rc.row["subfolder"]+"/"+rc.row["file_name"]+"\' '
                            . 'alt=\'"+rc.row["file_name"]+"\' ></a>"',
                        'sortable' => false],
                    ['name' => 'product_name', 'label' => 'Name', 'width' => 250],
                    ['name' => 'product_sku', 'label' => 'SKU', 'index' => 'p.product_sku', 'width' => 100],
                    ['name' => 'short_description', 'label' => 'Description',  'width' => 200],
                    ['name' => 'create_at', 'label' => 'Created', 'index' => 'p.create_at', 'width' => 100],
                    ['name' => 'update_at', 'label' => 'Updated', 'index' => 'p.update_at', 'width' => 100],
                ],
                'filters' => [
                    ['field' => 'product_name', 'type' => 'text'],
                    ['field' => 'product_sku', 'type' => 'text'],
                    ['field' => 'short_description', 'type' => 'text'],
                    ['field' => 'create_at', 'type' => 'date-range'],
                    ['field' => 'update_at', 'type' => 'date-range'],
                    '_quick' => ['expr' => 'product_name like ? or product_sku like ? or p.id=?', 'args' => ['?%', '%?%', '?']]
                ],
                'callbacks' => [
                    'componentDidMount' => 'prodInventoryRegister'
                ],
                'state' => [
                    's' => 'product_name',
                    'sd' => 'asc'
                ]
            ]
        ];

        return $config;
    }

    public function productStockPolicy($model)
    {
        $stock_policy = [
            'manage_stock' => 0,
            'stock_qty' => '',
            'out_stock' => 'keep_selling',
            'maximum_quantity_shopping' => '',
            'quantity_items_status' => '',
            'notify_administrator_quantity' => '',
        ];
        if (isset($model->data_serialized)) {
            $data = $this->BUtil->objectToArray(json_decode($model->data_serialized));
            if (isset($data['stock_policy'])) {
                $stock_policy = $data['stock_policy'];
            }
        }
        return $stock_policy;
    }

    public function action_restore_stock_policy()
    {
        $post = $this->BRequest->post();
        $config = $this->BConfig->get('modules/Sellvana_Catalog');
        $result = '';
        if (isset($post['restore'])) {
            switch($post['restore']) {
                case 'maximum_quantity_shopping':case 'quantity_items_status':case 'notify_administrator_quantity':
                    if ($config) {
                        $result = $config[$post['restore']];
                    }
                     break;
                case 'out_stock':
                    $result = 'back_order';
                    break;
                default:
                    break;
            }
        }
        $this->BResponse->json(['result' => $result]);
    }
    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(['actions' => [
            'new' => '<button type="button" id="add_new_sku" class="btn grid-new btn-primary _modal">'
                . $this->BLocale->_('New Sku') . '</button>']]);
    }

    /**
     * ajax check code is unique
     */
    public function action_unique__POST()
    {
        try {
            $post = $this->BRequest->post();
            $data = each($post);
            if (!isset($data['key']) || !isset($data['value'])) {
                throw new BException('Invalid post data');
            }
            $key = $this->BDb->sanitizeFieldName($data['key']);
            $value = $data['value'];
            $exists = $this->Sellvana_Catalog_Model_InventorySku->load($value, $key);
            $result = ['unique' => !$exists, 'id' => !$exists ? -1 : $exists->id()];
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
        }
        $this->BResponse->json($result);
    }

    public function action_grid_data__POST()
    {
        $r = $this->BRequest;
        $p = $r->post();
        switch ($p['oper']) {
            case 'edit':
                // avoid error when edit
                $p['tmp_cost'] = $p['cost'];
                unset($p['oper']);
                if (isset($p['sku'])) {
                    $prod = $this->Sellvana_Catalog_Model_Product->load($p['sku'], 'product_sku');
                    $this->Sellvana_Catalog_Model_InventorySku->load($p['id'])->set('status', $p['status'])->save();
                    if ($prod) {
                        $data_serialized = $this->BUtil->objectToArray(json_decode($prod->get('data_serialized')));
                        if (!isset($data_serialized['stock_policy']))  {
                            $data_serialized['stock_policy'] = ['stock_qty' => $p['stock_qty'], 'out_stock' => $p['out_stock'], 'manage_stock' => $p['manage_stock']];
                        } else {
                            $data_serialized['stock_policy']['stock_qty'] = $p['stock_qty'];
                            $data_serialized['stock_policy']['out_stock'] = $p['out_stock'];
                            $data_serialized['stock_policy']['manage_stock'] = $p['status'];
                        }
                        $prod->setData('stock_policy', $data_serialized['stock_policy']);
                        $prod->set('cost', $p['cost']);
                        $prod->save();
                    }
                }
                if ($p['cost'] != '') {
                    $p['cost'] = $this->BLocale->currency($p['cost']);
                }
                $this->BResponse->json($p);
                break;
            case 'mass-edit':
                $id = $p['id'];
                $args['ids'] = explode(',', $id);
                $data = $p;
                $hlp = $this->Sellvana_Catalog_Model_InventorySku;
                $models = $hlp->orm()->where_in('id', $args['ids'])->find_many_assoc();
                $skus = $this->BUtil->arrayToOptions($models, 'sku');
                $products = $this->Sellvana_Catalog_Model_Product->orm()->where_in('product_sku', $skus)->find_many_assoc('product_sku');
                foreach ($models as $stock) {
                    /** @var Sellvana_Catalog_Model_InventorySku $stock */
                    $stock->set('status', $data['status'])->save();
                    if (!empty($products[$stock->get('sku')])) {
                        /** @var Sellvana_Catalog_Model_Product $prod */
                        $prod = $products[$stock->get('sku')];
                        $data_serialized = $this->BUtil->objectToArray(json_decode($prod->get('data_serialized')));
                        if (!isset($data_serialized['stock_policy']))  {
                            $data_serialized['stock_policy'] = ['out_stock' => $p['out_stock'], 'manage_stock' => $p['status']];
                        } else {
                            $data_serialized['stock_policy']['out_stock'] = $p['out_stock'];
                            $data_serialized['stock_policy']['manage_stock'] = $p['status'];
                        }
                        $prod->setData('stock_policy', $data_serialized['stock_policy']);
                        $prod->save();
                    }
                }
                $this->BResponse->json(['success' => true]);
                break;
            default:
                $this->_processGridDataPost($this->_modelClass);
                break;
        }
    }
}
