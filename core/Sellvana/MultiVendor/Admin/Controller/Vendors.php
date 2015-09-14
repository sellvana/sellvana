<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiVendor_Admin_Controller
 *
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 * @property Sellvana_MultiVendor_Model_Vendor $Sellvana_MultiVendor_Model_Vendor
 * @property Sellvana_MultiVendor_Model_VendorProduct $Sellvana_MultiVendor_Model_VendorProduct
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_MultiVendor_Admin_Controller_Vendors extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'multivendor/vendors';
    protected $_modelClass = 'Sellvana_MultiVendor_Model_Vendor';
    protected $_gridTitle = 'Multi Vendors';
    protected $_recordName = 'Vendor';
    protected $_mainTableAlias = 'v';
    protected $_permission = 'multi_vendor';
    protected $_formViewPrefix = 'multivendor/vendors-form/';
    protected $_navPath = 'catalog/multivendor';

    public function gridConfig()
    {
        $notifyTypeOptions = $this->Sellvana_MultiVendor_Model_Vendor->fieldOptions('notify_type');
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
                ['name' => 'delete', 'edit_inline' => false],
            ]],
            ['name' => 'id', 'label' => 'ID', 'index' => 'v.id'],
            ['name' => 'vendor_name', 'label' => 'Vendor Name', 'index' => 'v.vendor_name'],
            ['name' => 'notify_type', 'label' => 'Notification', 'options' => $notifyTypeOptions],
            ['name' => 'email_notify', 'label' => 'Email for Notification', 'index' => 'v.email_notify'],
            ['name' => 'email_support', 'label' => 'Email for Support', 'index' => 'v.email_support'],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 'v.create_at', 'formatter' => 'date'],
            ['name' => 'update_at', 'label' => 'Updated', 'index' => 'v.update_at', 'formatter' => 'date'],
        ];
        $config['actions'] = [
            'delete' => true,
        ];
        $config['filters'] = [
            ['field' => 'id', 'type' => 'number-range'],
            ['field' => 'vendor_name', 'type' => 'text'],
            ['field' => 'notify_type', 'type' => 'select'],
            ['field' => 'email_notify', 'type' => 'text'],
            ['field' => 'email_support', 'type' => 'text'],
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'update_at', 'type' => 'date-range'],
        ];
        return $config;
    }
    /**
     * main grid on category/product tab
     * @param $model Sellvana_Catalog_Model_Category
     * @return array
     */
    public function getVendorProdConfig($model)
    {
        $orm = $this->Sellvana_Catalog_Model_Product->orm('p')
            ->select(['p.id', 'p.product_name', 'p.product_sku'])
            ->join('Sellvana_MultiVendor_Model_VendorProduct', ['vp.product_id', '=', 'p.id'], 'vp')
            ->where('vp.vendor_id', $model ? $model->id() : 0)
        ;

        $config = parent::gridConfig();

        // TODO for empty local grid, it throws exception
        unset($config['orm']);
        $config['config']['data'] = $orm->find_many();
        $config['config']['id'] = 'vendor_prods_grid_' . $model->id;
        $config['config']['data_mode'] = 'local';
        $config['config']['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 80, 'hidden' => true],
            ['name' => 'product_name', 'label' => 'Name', 'index' => 'p.product_name', 'width' => 400],
            ['name' => 'product_sku', 'label' => 'SKU', 'index' => 'p.product_sku', 'width' => 200]
        ];
        $config['config']['actions'] = [
            #'add' => ['caption' => 'Add products'],
            'add-product' => [
                'caption'  => 'Add Products',
                'type'     => 'button',
                'id'       => 'add-product-from-grid',
                'class'    => 'btn-primary',
                'callback' => 'showModalToAddProduct'
            ],
            'delete' => ['caption' => 'Remove']
        ];
        $config['config']['filters'] = [
            ['field' => 'product_name', 'type' => 'text'],
            ['field' => 'product_sku', 'type' => 'text']
        ];
        $config['config']['data_mode'] = 'local';
        $config['config']['grid_before_create'] = 'vendorProdGridRegister';

        $config['config']['callbacks'] = [
            'componentDidMount' => 'setVendorProdMainGrid'
        ];

        return $config;
    }

    public function formPostAfter($args) {
        parent::formPostAfter($args);

        $model = $args['model'];
        $addedIds = $removedIds = [];
        if (!empty($model->product_ids_add)) {
            $addedIds = explode(',', $model->product_ids_add);
            $this->_processAddVendorProduct($addedIds, $model);
        }

        if (!empty($model->product_ids_remove)) {
            $removedIds = explode(',', $model->product_ids_remove);
            $this->_processRemovedVendorProduct($removedIds, $model);
        }
    }

    protected function _processAddVendorProduct($pIds, $vendorModel) {
        $vId = $vendorModel->id();
        $vpHlp = $this->Sellvana_MultiVendor_Model_VendorProduct;

        foreach ($pIds as $pId) {
            $p = $this->Sellvana_Catalog_Model_Product->load($pId);
            $model = $vpHlp->orm('vp')->where_complex(['vendor_id' => $vId, 'product_id' => $pId])->find_one();
            if (!$model) {
                $model = $vpHlp->create();
            }

            $vpData = [
                'product_id' => $p->id(),
                'vendor_id' => $vId,
                'vendor_sku' => $p->product_sku,
                'vendor_product_name' => $vendorModel->vendor_name
            ];

            $model->set($vpData)->save();
        }
    }

    protected function _processRemovedVendorProduct($pIds, $vendorModel) {
        foreach ($pIds as $pId) {
            $model = $this->Sellvana_MultiVendor_Model_VendorProduct
            ->orm('vp')
            ->where_complex(['product_id' => $pId, 'vendor_id' => $vendorModel->id()])
            ->find_one();
            if ($model) {
                $model->delete();
            }
        }
    }

}
