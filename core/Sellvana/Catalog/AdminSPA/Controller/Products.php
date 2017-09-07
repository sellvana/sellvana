<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_InventorySku Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Catalog_Model_ProductMedia Sellvana_Catalog_Model_ProductMedia
 * @property Sellvana_Catalog_Model_ProductPrice Sellvana_Catalog_Model_ProductPrice
 * @property Sellvana_CustomerGroups_Model_Group Sellvana_CustomerGroups_Model_Group
 * @property Sellvana_MultiSite_Model_Site Sellvana_MultiSite_Model_Site
 * @property Sellvana_MultiCurrency_Main Sellvana_MultiCurrency_Main
 */
class Sellvana_Catalog_AdminSPA_Controller_Products extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    static protected $_origClass = __CLASS__;

    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) {
            return false;
        }
        if ($this->_action === 'form_data' && $this->BRequest->method() === 'POST') {
            $whitelist = [];
            $fields = $this->FCom_Core_Model_Field->orm()->where('field_type', 'product')
                ->where('admin_input_type', 'wysiwyg')->find_many();
            foreach ($fields as $field) {
                $whiltelist["POST/product/{$field->get('field_code')}"] = '*';
            }
            $this->BRequest->addRequestFieldsWhitelist([$this->BRequest->rawPath() => $whitelist]);
        }
        return true;
    }

    public function getGridConfig()
    {
        $bool = [0 => (('no')), 1 => (('Yes'))];
        $countries = $this->BLocale->getAvailableCountries();

        $editPopupConfig = [
            static::TITLE => (('Bulk Update Selected Products')),
            static::FORM => $this->normalizeFormConfig([
                static::CONFIG => [
                    static::FIELDS => [
                        static::DEFAULT_FIELD => [static::TAB => 'main', static::MODEL => 'product'],
                        [static::NAME => 'product_name', static::LABEL => (('Product Name'))],
                        [static::NAME => 'short_description', static::LABEL => (('Short Description')), static::TYPE => 'textarea'],
                        [static::NAME => 'description', static::LABEL => (('Description')), static::TYPE => 'textarea'],
                        [static::NAME => 'is_hidden', static::LABEL => (('Hidden?')), static::TYPE => 'checkbox'],
                        [static::NAME => 'is_featured', static::LABEL => (('Featured?')), static::TYPE => 'checkbox'],
                        [static::NAME => 'is_popular', static::LABEL => (('Popular?')), static::TYPE => 'checkbox'],
                        [static::NAME => 'manage_inventory', static::LABEL => (('Manage Inventory?')), static::TYPE => 'checkbox'],
                        [static::NAME => 'inventory_sku', static::LABEL => (('Inventory SKU')), static::TYPE => 'checkbox'],
                        [static::NAME => 'unit_cost', static::LABEL => (('Unit Cost')), static::MODEL => 'inventory'],
                        [static::NAME => 'net_weight', static::LABEL => (('Net Weight')), static::MODEL => 'inventory'],
                        [static::NAME => 'shipping_weight', static::LABEL => (('Shipping Weight')), static::MODEL => 'inventory'],
                        [static::NAME => 'shipping_size', static::LABEL => (('Shipping Size')), static::MODEL => 'inventory'],
                        [static::NAME => 'pack_separate', static::LABEL => (('Pack Separate?')), static::MODEL => 'inventory', static::TYPE => 'checkbox'],
                        [static::NAME => 'qty_in_stock', static::LABEL => (('Qty In Stock')), static::MODEL => 'inventory'],
                        [static::NAME => 'qty_warn_customer', static::LABEL => (('Qty To Warn Customer')), static::MODEL => 'inventory'],
                        [static::NAME => 'qty_notify_admin', static::LABEL => (('Qty To Notify Admin')), static::MODEL => 'inventory'],
                        [static::NAME => 'qty_cart_min', static::LABEL => (('Minimum Qty In Cart')), static::MODEL => 'inventory'],
                        [static::NAME => 'qty_cart_max', static::LABEL => (('Maximum Qty In Cart')), static::MODEL => 'inventory'],
                        [static::NAME => 'qty_cart_inc', static::LABEL => (('Qty Increment')), static::MODEL => 'inventory'],
                        [static::NAME => 'qty_buffer', static::LABEL => (('Qty Increment')), static::MODEL => 'inventory'],
                        [static::NAME => 'qty_reserved', static::LABEL => (('Qty Increment')), static::MODEL => 'inventory'],
                        [static::NAME => 'allow_backorder', static::LABEL => (('Allow Backorder')), static::MODEL => 'inventory', static::TYPE => 'checkbox'],
                        [static::NAME => 'hs_tariff_number', static::LABEL => (('HS Tariff Number')), static::MODEL => 'inventory'],
                        [static::NAME => 'origin_country', static::LABEL => (('Origin Country')), static::MODEL => 'inventory', static::TYPE => 'select2', static::OPTIONS => $countries],
                    ],
                ],
            ]),
            static::ACTIONS => [
                [static::NAME => 'cancel', static::LABEL => (('Cancel')), 'class' => 'button2'],
                [static::NAME => 'bulk_update', static::LABEL => (('Update Selected Products')), 'class' => 'button1'],
            ],
        ];

        $deletePopupConfig = [
            static::TITLE => (('Are you sure you want to delete selected products?')),
            static::ACTIONS => [
                [static::NAME => 'cancel', static::LABEL => (('Cancel')), 'class' => 'button2'],
                [static::NAME => 'bulk_delete', static::LABEL => (('Delete Selected Products')), 'class' => 'button4'],
            ],
        ];

        return [
            static::ID => 'products',
            static::TITLE => (('Products')),
            static::DATA_URL => 'products/grid_data',
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT, static::WIDTH => 55],
                [static::NAME => 'id', static::LABEL => (('ID')), 'index' => 'p.id', static::WIDTH => 55, static::HIDDEN => true],
                [static::NAME => 'thumb_path', static::LABEL => (('Thumbnail')), static::WIDTH => 48, 'sortable' => false,
                    static::DATACELL_TEMPLATE => '<td><a :href="\'#/catalog/products/form?id=\'+row.id"><img :src="row.thumb_url" :alt="row.product_name"></a></td>'],
                [static::NAME => 'product_name', static::LABEL => (('Name')), static::WIDTH => 250,
                    static::DATACELL_TEMPLATE => '<td><a :href="\'#/catalog/products/form?id=\'+row.id">{{row.product_name}}</a></td>'],
                [static::NAME => 'product_sku', static::LABEL => (('Product SKU')), static::WIDTH => 100],
                [static::NAME => 'short_description', static::LABEL => (('Description')),  static::WIDTH => 200, static::HIDDEN => true],
                [static::NAME => 'is_hidden', static::LABEL => (('Hidden?')), static::WIDTH => 50, static::OPTIONS => $bool, 'multirow_edit' => true],
                [static::NAME => 'manage_inventory', static::LABEL => (('Manage Inv?')), static::WIDTH => 50, static::OPTIONS => $bool, 'multirow_edit' => true],
                //[static::NAME => 'base_price', static::LABEL => 'Base Price',  static::WIDTH => 100, static::HIDDEN => true],
                //[static::NAME => 'sale_price', static::LABEL => 'Sale Price',  static::WIDTH => 100, static::HIDDEN => true],
                [static::NAME => 'net_weight', static::LABEL => (('Net Weight')),  static::WIDTH => 100, static::HIDDEN => true, 'multirow_edit' => true],
                [static::NAME => 'shipping_weight', static::LABEL => (('Ship Weight')),  static::WIDTH => 100, static::HIDDEN => true, 'multirow_edit' => true],
                [static::NAME => 'position', static::LABEL => (('Position')), static::HIDDEN => true],
                [static::NAME => 'create_at', static::LABEL => (('Created')), static::WIDTH => 100, 'cell' => 'datetime'],
                [static::NAME => 'update_at', static::LABEL => (('Updated')), static::WIDTH => 100, 'cell' => 'datetime'],
            ],
            static::FILTERS => [
                [static::NAME => 'id', static::TYPE => 'number'],
                [static::NAME => 'product_name'],
                [static::NAME => 'product_sku'],
                [static::NAME => 'short_description'],
                [static::NAME => 'is_hidden'],
                [static::NAME => 'net_weight', static::TYPE => 'number'],
                [static::NAME => 'ship_weight', static::TYPE => 'number'],
                [static::NAME => 'position', static::TYPE => 'number'],
                [static::NAME => 'create_at', static::TYPE => 'date'],
                [static::NAME => 'update_at', static::TYPE => 'date'],
            ],
            static::EXPORT => true,
            static::PAGER => true,
            static::BULK_ACTIONS => [
                [static::NAME => 'edit_products', static::LABEL => (('Edit Products')), 'popup' => $editPopupConfig],
                [static::NAME => 'delete_products', static::LABEL => (('Delete Products')), 'popup' => $deletePopupConfig],
            ],
            static::PAGE_ACTIONS => [
                [static::NAME => 'new', static::LABEL => (('Add New Product')), static::BUTTON_CLASS => 'button1',
                    static::LINK => '/catalog/products/form', static::GROUP => 'new'],
            ],
        ];
    }

    public function getGridOrm()
    {
        $orm = $this->Sellvana_Catalog_Model_Product->orm('p')->select('p.*')
            ->left_outer_join('Sellvana_Catalog_Model_InventorySku', ['p.inventory_sku', '=', 'i.inventory_sku'], 'i')
            ->select(['i.net_weight', 'i.shipping_weight']);
        return $orm;
    }

    public function processGridPageData($data)
    {
        $this->Sellvana_Catalog_Model_ProductMedia->collectProductsImages($data['rows']);
        foreach ($data['rows'] as $row) {
            $row->set('thumb_url', $row->thumbUrl(48));
        }
        return parent::processGridPageData($data);
    }

    public function action_grid_data__POST()
    {
        try {
            $result = [];
            $post = $this->BRequest->post();
            if (empty($post['do']) || empty($post['ids'])) {
                throw new BException('Invalid request');
            }
            $orm = $this->Sellvana_Catalog_Model_Product->orm('p')->where_in('p.id', $post['ids']);
            switch ($post['do']) {
                case 'bulk_update':
                    if (empty($post['data'])) {
                        throw new BException('Invalid request: missing data');
                    }
                    if (!empty($post['data']['product'])) {
                        $data = $this->getBulkUpdateData('edit_products', 'product', $post);
                        $orm->iterate(function ($r) use ($data) { $r->set($data)->save(); });
                    }
                    if (!empty($post['data']['inventory'])) {
                        $invSkus = $orm->find_many_assoc('inventory_sku', 'inventory_sku');
                        $invOrm = $this->Sellvana_Catalog_Model_InventorySku->orm('i')
                            ->where_in('inventory_sku', array_values($invSkus));
                        $data = $this->getBulkUpdateData('edit_products', 'inventory', $post);
                        $invOrm->iterate(function ($r) use ($data) { $r->set($data)->save(); });
                    }
                    $this->addMessage('Products have been updated successfully.', 'success');
                    break;

                case 'bulk_delete':
                    $orm->iterate(function ($r) { $r->delete(); });
                    $this->addMessage('Products have been deleted successfully.', 'success');
                    break;
            }
            $this->ok();
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond(['result' => $result]);
    }

    public function getFormData($product = null)
    {
        $pId = $this->BRequest->get('id');

        if (!$product) {
            if ($pId === null || $pId === '') {
                $product = $this->Sellvana_Catalog_Model_Product->create();
            } else {
                $product = $this->Sellvana_Catalog_Model_Product->load($pId);
                if (!$product) {
                    throw new BException('Product not found');
                }
            }
        }

        $result = [];

        $result[static::FORM]['product'] = $product->as_array();
        $result[static::FORM][static::CONFIG][static::TITLE] = $product->get('product_name');
        $result[static::FORM][static::CONFIG][static::THUMB_URL] = $product->thumbUrl(100);

        $invModel = $product->getInventoryModel();
        if ($invModel) {
            $result[static::FORM]['inventory'] = $invModel->as_array();
        }

        $this->Sellvana_Catalog_Model_ProductMedia->collectProductsImages([$product]);

        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        $result[static::FORM]['prices'] = $priceHlp->getProductPrices($product);
        $result[static::FORM][static::CONFIG][static::OPTIONS]['price_types'] = $this->BUtil->arrayMapToSeq($product->priceTypeOptions());
        $result[static::FORM][static::CONFIG][static::OPTIONS]['price_relations'] = $priceHlp->fieldOptions('price_relation_options');
        $result[static::FORM][static::CONFIG][static::OPTIONS]['price_operations'] = $priceHlp->fieldOptions('operation_options');
        if ($this->BModuleRegistry->isLoaded('Sellvana_CustomerGroups')) {
            $groups =  $this->Sellvana_CustomerGroups_Model_Group->groupsOptions();;
            if ($groups) {
                $result[static::FORM][static::CONFIG][static::OPTIONS]['customer_groups'] = $this->BUtil->arrayMapToSeq($groups);
            }
        }
        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiSite')) {
            $sites = $this->Sellvana_MultiSite_Model_Site->siteOptions();
            if ($sites) {
                $result[static::FORM][static::CONFIG][static::OPTIONS]['multi_site'] = $this->BUtil->arrayMapToSeq($sites);
            }
        }
        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiCurrency')) {
            $currencies = $this->Sellvana_MultiCurrency_Main->getAvailableCurrencies();
            if ($currencies) {
                $result[static::FORM][static::CONFIG][static::OPTIONS]['multi_currency'] = $this->BUtil->arrayMapToSeq($currencies);
            }
        }

        $result[static::FORM][static::CONFIG][static::PAGE_ACTIONS] = [
            static::DEFAULT_FIELD => [static::MOBILE_GROUP => 'actions'],
            [static::NAME => 'actions', static::LABEL => (('Actions'))],
            [static::NAME => 'back', static::LABEL => (('Back')), static::GROUP => 'back', static::BUTTON_CLASS => 'button2'],
            [static::NAME => 'delete', static::LABEL => (('Delete')), static::DESKTOP_GROUP => 'delete', static::BUTTON_CLASS => 'button4', 'if' => 'product.id'],
            [static::NAME => 'save', static::LABEL => (('Save')), static::DESKTOP_GROUP => 'save', static::BUTTON_CLASS => 'button1'],
            [static::NAME => 'save-continue', static::LABEL => (('Save & Continue')), static::DESKTOP_GROUP => 'save', static::BUTTON_CLASS => 'button1'],
        ];

        $result[static::FORM][static::CONFIG][static::TABS] = '/catalog/products/form';
        $result[static::FORM][static::CONFIG][static::FIELDS] = [
            static::DEFAULT_FIELD => [static::MODEL => 'product'],
            [static::NAME => 'product_name', static::LABEL => (('Product Name')), static::I18N => true, static::REQUIRED => true],
            [static::NAME => 'url_key', static::LABEL => 'URL Key (optional)', static::VALIDATE => ['pattern' => '/^[a-z0-9-]+$/']],
            [static::NAME => 'product_sku', static::LABEL => (('Product SKU')), static::REQUIRED => true],
            [static::NAME => 'short_description', static::TYPE => 'textarea', static::LABEL => (('Short Description')), static::I18N => true, static::REQUIRED => true],
            [static::NAME => 'description', static::TYPE => 'wysiwyg', static::LABEL => (('Long Description')), static::I18N => true, static::REQUIRED => true],
            [static::NAME => 'is_hidden', static::LABEL => (('Hide Product')), static::TYPE => 'checkbox'],
            [static::NAME => 'is_featured', static::LABEL => (('Featured Product')), static::TYPE => 'checkbox'],
            [static::NAME => 'is_popular', static::LABEL => (('Popular Product')), static::TYPE => 'checkbox'],

            [static::NAME => 'manage_inventory', static::LABEL => (('Manage Inventory')), static::TAB => 'inventory', static::TYPE => 'checkbox'],
            [static::NAME => 'inventory_sku', static::LABEL => (('Inventory SKU')), static::TAB => 'inventory', static::NOTES => (('Leave empty to use Product SKU'))],
            [static::NAME => 'qty_in_stock', static::LABEL => (('Qty In Stock')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
            [static::NAME => 'unit_cost', static::LABEL => (('Inventory Unit Cost')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
            [static::NAME => 'allow_backorder', static::LABEL => (('Allow Backorders')), static::MODEL => 'inventory', static::TAB => 'inventory', static::TYPE => 'checkbox'],
            [static::NAME => 'qty_warn_customer', static::LABEL => (('Minimal Qty to warn customer on frontend')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
            [static::NAME => 'qty_notify_admin', static::LABEL => (('Minimal Qty to notify admin')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
            [static::NAME => 'qty_cart_min', static::LABEL => (('Minimum Qty In Cart')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
            [static::NAME => 'qty_cart_max', static::LABEL => (('Maximum Qty In Cart')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
            [static::NAME => 'qty_cart_inc', static::LABEL => (('Qty In Cart Increment')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
            [static::NAME => 'qty_buffer', static::LABEL => (('Buffer Qty In Stock')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
            [static::NAME => 'pack_separate', static::LABEL => (('Pack Separately for Shipment')), static::MODEL => 'inventory', static::TAB => 'inventory', static::TYPE => 'checkbox'],
            [static::NAME => 'net_weight', static::LABEL => (('Net Weight')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
            [static::NAME => 'shipping_weight', static::LABEL => (('Shipping Weight')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
            [static::NAME => 'shipping_size', static::LABEL => (('Shipping Size')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
            [static::NAME => 'hs_tariff_number', static::LABEL => (('Harmonized Tariff Number')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
            [static::NAME => 'origin_country', static::LABEL => (('Country of Origin')), static::MODEL => 'inventory', static::TAB => 'inventory', static::INPUT_TYPE => 'number'],
        ];

        $result[static::FORM]['categories_grid']['config'] = $this->Sellvana_Catalog_AdminSPA_Controller_Products_Categories->getNormalizedGridConfig();

        $result[static::FORM][static::I18N] = 'product';

        return $result;
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            $r = $this->BRequest;
            $data = $r->post('product');
            $id = $r->param('id', true);

            $eventName =  "{$this->origClass()}::action_form_data_POST";
            $this->BEvents->fire("{$eventName}:before", ['data' => &$data, 'model_id' => &$id]);

            if ($id) {
                $model = $this->Sellvana_Catalog_Model_Product->load($id);
                if (!$model) {
                    throw new BException("This item does not exist");
                }
            } else {
                $model = $this->Sellvana_Catalog_Model_Product->create();
            }
            if ($data) {
                $model->set($data);
            }

            $origModelData = $modelData = $model->as_array();
            $validated = $model->validate($modelData, [], 'product');
            if ($modelData !== $origModelData) {
                $model->set($modelData);
            }

            if ($validated) {
                $model->save();

                $this->BEvents->fire("{$eventName}:after", ['data' => $data, static::MODEL => $model]);

                $result = $this->getFormData($model);
                $result[static::FORM] = $this->normalizeFormConfig($result[static::FORM]);
                $this->ok()->addMessage('Product was saved successfully', 'success');
            } else {
                $result = ['status' => 'error'];
                $this->error()->addMessage($this->BValidate->validateErrorsString(), 'error');
            }

        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    public function action_form_delete__POST()
    {
        try {
            $data = $this->BRequest->post();
            $id = $data['id'];
            $model = $this->Sellvana_Catalog_Model_Product->load($id);
            if (!$model) {
                throw new BException("This item does not exist");
            }

            $model->delete();
            $this->ok()->addMessage('Product was deleted successfully', 'success');
            $result = ['status' => true];
            $this->respond($result);
        } catch (Exception $e) {
            $this->addMessage($e);
        }
    }

    public function onHeaderSearch($args)
    {
        $q = $this->BRequest->get('q');
        if (isset($q) && $q != '') {
            $value = '%' . $q . '%';
            $result = $this->Sellvana_Catalog_Model_Product->orm('p')
                ->where(['OR' => [
                    ['p.id like ?', (int)$value],
                    ['p.product_sku like ?', (string)$value],
                    ['p.url_key like ?', (string)$value],
                    ['p.product_name like ?', (string)$value],
                ]])->find_one();
            $args['result']['product'] = null;
            if ($result) {
                $args['result']['product'] = [
                    'priority' => 1,
                    static::LINK => '/catalog/products/form?id=' . $result->id(),
                ];
            }
        }
    }
}