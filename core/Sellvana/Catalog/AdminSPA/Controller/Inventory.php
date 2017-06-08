<?php

/**
 * Class Sellvana_Catalog_Admin_Controller_Inventory
 *
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_CatalogFields_Model_ProductVariant $Sellvana_CatalogFields_Model_ProductVariant
 */
class Sellvana_Catalog_AdminSPA_Controller_Inventory extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'catalog/inventory';
    protected $_modelClass = 'Sellvana_Catalog_Model_InventorySku';
    protected $_gridHref = 'catalog/inventory';
    protected $_gridTitle = 'Inventory Management';
    protected $_recordName = 'Inventory SKU';
    protected $_mainTableAlias = 's';
    protected $_navPath = 'catalog/inventory';
    protected $_formTitleField = 'inventory_sku';
    #protected $_gridLayoutName = '/catalog/inventory';

    #protected $_defaultGridLayoutName = 'default_grid';
    #protected $_gridPageViewName = 'admin/grid';
    #protected $_gridViewName = 'core/backbonegrid';

    public function getGridConfig()
    {
        $bool = [0 => 'no', 1 => 'Yes'];
        $invHlp = $this->Sellvana_Catalog_Model_InventorySku;
//        $yesNo = [0 => 'no', 1 => 'YES'];
        $manInvOptions = $invHlp->fieldOptions('manage_inventory');
        $backorderOptions = $invHlp->fieldOptions('allow_backorder');
        $packOptions = $invHlp->fieldOptions('pack_separate');
        $config = [
            'id' => 'inventory',
            'data_url' => 'inventory/grid_data'
        ];
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
                ['name' => 'delete'],
            ]],
            ['name' => 'id', 'label' => 'ID', 'width' => 50],
            ['name' => 'title', 'label' => 'Title'],
            ['name' => 'inventory_sku', 'label' => 'SKU'],
            #['name' => 'manage_inventory', 'label' => 'Manage', 'options' => $manInvOptions, 'multirow_edit' => true],
            ['name' => 'allow_backorder', 'label' => 'Allow Backorder', 'options' => $backorderOptions, 'multirow_edit' => true],
            ['name' => 'pack_separate', 'label' => 'Pack Separate', 'options' => $packOptions, 'multirow_edit' => true],
            ['name' => 'qty_in_stock', 'label' => 'Quantity In Stock', 'multirow_edit' => true],
            ['name' => 'qty_reserved', 'label' => 'Qty Reserved', 'multirow_edit' => true],
            ['name' => 'qty_buffer', 'label' => 'Qty Buffer', 'multirow_edit' => true],
            ['name' => 'qty_warn_customer', 'label' => 'Qty to Warn Customer', 'multirow_edit' => true],
            ['name' => 'qty_notify_admin', 'label' => 'Qty to Notify Admin', 'multirow_edit' => true],
            ['name' => 'qty_cart_min', 'label' => 'Min Qty in Cart', 'multirow_edit' => true],
            ['name' => 'qty_cart_max', 'label' => 'Max Qty in Cart', 'multirow_edit' => true],
            ['name' => 'qty_cart_inc', 'label' => 'Cart Increment', 'multirow_edit' => true],
            ['name' => 'unit_cost', 'label' => 'Unit Cost', 'multirow_edit' => true],
            ['name' => 'net_weight', 'label' => 'Net Weight', 'multirow_edit' => true],
            ['name' => 'shipping_weight', 'label' => 'Ship Weight', 'multirow_edit' => true],
            ['name' => 'shipping_size', 'label' => 'Ship Size', 'multirow_edit' => true],
        ];
        $config['actions'] = [
            'edit' => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'id', 'type' => 'number-range'],
            #['field' => 'manage_inventory', 'type' => 'multiselect'],
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
            ['field' => 'qty_cart_max', 'type' => 'number-range'],
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
        $thumbUrl = $this->FCom_Core_Main->resizeUrl($this->BConfig->get('web/media_dir') . '/product/images', ['s' => 80]);
        $data = [];
        if ($model) {
            $data = $this->BDb->many_as_array($this->Sellvana_Catalog_Model_Product->orm('p')
                ->left_outer_join('Sellvana_Catalog_Model_ProductMedia', "p.id=pa.product_id and pa.media_type='" .
                    Sellvana_Catalog_Model_ProductMedia::MEDIA_TYPE_IMG . "'", 'pa')
                ->left_outer_join('FCom_Core_Model_MediaLibrary', 'a.id=pa.file_id', 'a')
                ->where('p.inventory_sku', $model->get('inventory_sku'))
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

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(['actions' => [
            'new' => '<button type="button" id="add_new_sku" class="btn grid-new btn-primary _modal">'
                . $this->_('New Sku') . '</button>']]);
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
        switch ($this->BRequest->post('oper')) {
            case 'mass-edit':
                $p = $this->BRequest->post();
                $ids = $this->BUtil->arrayCleanInt($p['id']);
                $data = $this->BRequest->sanitize($p, [
                    'unit_cost' => 'float',
                    'net_weight' => 'float',
                    'shipping_weight' => 'float',
                    'shipping_size' => 'alnum',
                    'pack_separate' => 'int',
                    'qty_in_stock' => 'int',
                    'qty_warn_customer' => 'int',
                    'qty_notify_admin' => 'int',
                    'qty_cart_min' => 'int',
                    'qty_cart_max' => 'int',
                    'qty_cart_inc' => 'int',
                    'qty_buffer' => 'int',
                    'qty_reserved' => 'int',
                    'allow_backorder' => 'int',
                    #'manage_inventory' => 'int',
                    'origin_country' => 'alnum',
                ]);
                $this->Sellvana_Catalog_Model_InventorySku->update_many($data, $ids);
                $this->BResponse->json(['success' => true]);
                break;
            default:
                $this->_processGridDataPost($this->_modelClass);
                break;
        }
    }
}
