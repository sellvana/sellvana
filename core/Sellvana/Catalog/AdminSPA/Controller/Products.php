<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia Sellvana_Catalog_Model_ProductMedia
 * @property Sellvana_Catalog_Model_ProductPrice Sellvana_Catalog_Model_ProductPrice
 * @property Sellvana_CustomerGroups_Model_Group Sellvana_CustomerGroups_Model_Group
 * @property Sellvana_MultiSite_Model_Site Sellvana_MultiSite_Model_Site
 * @property Sellvana_MultiCurrency_Main Sellvana_MultiCurrency_Main
 */
class Sellvana_Catalog_AdminSPA_Controller_Products extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig()
    {
        $bool = [0 => 'no', 1 => 'Yes'];
        return [
            'id' => 'products',
            'data_url' => 'products/grid_data',
            'columns' => [
                ['type' => 'row-select', 'width' => 55],
                ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 55, 'hidden' => true],
                ['name' => 'thumb_path', 'label' => 'Thumbnail', 'width' => 48, 'sortable' => false,
                    'datacell_template' => '<td><a :href="\'#/catalog/products/form?id=\'+row.id"><img :src="row.thumb_url" :alt="row.product_name"></a></td>'],
                ['name' => 'product_name', 'label' => 'Name', 'width' => 250,
                    'datacell_template' => '<td><a :href="\'#/catalog/products/form?id=\'+row.id">{{row.product_name}}</a></td>'],
                ['name' => 'product_sku', 'label' => 'Product SKU', 'index' => 'p.product_sku', 'width' => 100],
                ['name' => 'short_description', 'label' => 'Description',  'width' => 200, 'hidden' => true],
                ['name' => 'is_hidden', 'label' => 'Hidden?', 'width' => 50, 'options' => $bool, 'multirow_edit' => true],
                ['name' => 'manage_inventory', 'label' => 'Manage Inv?', 'width' => 50, 'options' => $bool, 'multirow_edit' => true],
                //['name' => 'base_price', 'label' => 'Base Price',  'width' => 100, 'hidden' => true],
                //['name' => 'sale_price', 'label' => 'Sale Price',  'width' => 100, 'hidden' => true],
                ['name' => 'net_weight', 'label' => 'Net Weight',  'width' => 100, 'hidden' => true, 'multirow_edit' => true],
                ['name' => 'ship_weight', 'label' => 'Ship Weight',  'width' => 100, 'hidden' => true, 'multirow_edit' => true],
                ['name' => 'position', 'label' => 'Position', 'index' => 'p.position', 'hidden' => true],
                ['name' => 'create_at', 'label' => 'Created', 'index' => 'p.create_at', 'width' => 100, 'cell' => 'datetime'],
                ['name' => 'update_at', 'label' => 'Updated', 'index' => 'p.update_at', 'width' => 100, 'cell' => 'datetime'],
            ],
            'filters' => [
                ['name' => 'id', 'type' => 'number'],
                ['name' => 'product_name'],
                ['name' => 'product_sku'],
                ['name' => 'short_description'],
                ['name' => 'is_hidden'],
                ['name' => 'net_weight', 'type' => 'number'],
                ['name' => 'ship_weight', 'type' => 'number'],
                ['name' => 'position', 'type' => 'number'],
                ['name' => 'create_at', 'type' => 'date'],
                ['name' => 'update_at', 'type' => 'date'],
            ],
            'export' => true,
            'pager' => true,
            'bulk_actions' => [
                ['name' => 'custom_state', 'label' => 'Change Custom State'],
            ],
        ];
    }

    public function getGridOrm()
    {
        return $this->Sellvana_Catalog_Model_Product->orm('p');
    }

    public function processGridPageData($data)
    {
        $this->Sellvana_Catalog_Model_ProductMedia->collectProductsImages($data['rows']);
        foreach ($data['rows'] as $row) {
            $row->set('thumb_url', $row->thumbUrl(48));
        }
        return parent::processGridPageData($data);
    }

    public function action_grid_delete__POST()
    {

    }

    public function getFormData()
    {
        $pId = $this->BRequest->get('id');

        $product = $this->Sellvana_Catalog_Model_Product->load($pId);
        if (!$product) {
            throw new BException('Product not found');
        }

        $result = [];

        $result['form']['product'] = $product->as_array();
        $result['form']['thumb'] = ['thumb_url' => $product->thumbUrl(100)];

        $invModel = $product->getInventoryModel();
        if ($invModel) {
            $result['form']['inventory'] = $invModel->as_array();
        }

        $this->Sellvana_Catalog_Model_ProductMedia->collectProductsImages([$product]);

        $priceHlp = $this->Sellvana_Catalog_Model_ProductPrice;
        $result['form']['prices'] = $priceHlp->getProductPrices($product);
        $result['form']['config']['options']['price_types'] = $this->BUtil->arrayMapToSeq($product->priceTypeOptions());
        $result['form']['config']['options']['price_relations'] = $priceHlp->fieldOptions('price_relation_options');
        $result['form']['config']['options']['price_operations'] = $priceHlp->fieldOptions('operation_options');
        if ($this->BModuleRegistry->isLoaded('Sellvana_CustomerGroups')) {
            $groups =  $this->Sellvana_CustomerGroups_Model_Group->groupsOptions();;
            if ($groups) {
                $result['form']['config']['options']['customer_groups'] = $this->BUtil->arrayMapToSeq($groups);
            }
        }
        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiSite')) {
            $sites = $this->Sellvana_MultiSite_Model_Site->siteOptions();
            if ($sites) {
                $result['form']['config']['options']['multi_site'] = $this->BUtil->arrayMapToSeq($sites);
            }
        }
        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiCurrency')) {
            $currencies = $this->Sellvana_MultiCurrency_Main->getAvailableCurrencies();
            if ($currencies) {
                $result['form']['config']['options']['multi_currency'] = $this->BUtil->arrayMapToSeq($currencies);
            }
        }

        $result['form']['config']['actions'] = true;

        $result['form']['config']['tabs'] = '/catalog/products/form';
        $result['form']['config']['default_field'] = ['model' => 'product'];
        $result['form']['config']['fields'] = [
            ['name' => 'product_name', 'label' => 'Product Name', 'i18n' => true, 'required' => true],
            ['name' => 'url_key', 'label' => 'URL Key (optional)', 'validate' => ['pattern' => '/^[a-z0-9-]+$/']],
            ['name' => 'product_sku', 'label' => 'Product SKU', 'required' => true],
            ['name' => 'short_description', 'type' => 'textarea', 'label' => 'Short Description', 'i18n' => true, 'required' => true],
            ['name' => 'description', 'type' => 'wysiwyg', 'label' => 'Long Description', 'i18n' => true, 'required' => true],
            ['name' => 'is_hidden', 'label' => 'Hide Product', 'type' => 'checkbox'],
            ['name' => 'is_featured', 'label' => 'Featured Product', 'type' => 'checkbox'],
            ['name' => 'is_popular', 'label' => 'Popular Product', 'type' => 'checkbox'],

            ['name' => 'manage_inventory', 'label' => 'Manage Inventory', 'tab' => 'inventory', 'type' => 'checkbox'],
            ['name' => 'inventory_sku', 'label' => 'Inventory SKU', 'tab' => 'inventory', 'notes' => 'Leave empty to use Product SKU'],
            ['name' => 'qty_in_stock', 'label' => 'Qty In Stock', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'unit_cost', 'label' => 'Inventory Unit Cost', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'allow_backorder', 'label' => 'Allow Backorders', 'model' => 'inventory', 'tab' => 'inventory', 'type' => 'checkbox'],
            ['name' => 'qty_warn_customer', 'label' => 'Minimal Qty to warn customer on frontend', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'qty_notify_admin', 'label' => 'Minimal Qty to notify admin', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'qty_cart_min', 'label' => 'Minimum Qty In Cart', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'qty_cart_max', 'label' => 'Maximum Qty In Cart', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'qty_cart_inc', 'label' => 'Qty In Cart Increment', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'qty_buffer', 'label' => 'Buffer Qty In Stock', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'pack_separate', 'label' => 'Pack Separately for Shipment', 'model' => 'inventory', 'tab' => 'inventory', 'type' => 'checkbox'],
            ['name' => 'net_weight', 'label' => 'Net Weight', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'shipping_weight', 'label' => 'Shipping Weight', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'shipping_size', 'label' => 'Shipping Size', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'hs_tariff_number', 'label' => 'Harmonized Tariff Number', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
            ['name' => 'origin_country', 'label' => 'Country of Origin', 'model' => 'inventory', 'tab' => 'inventory', 'input_type' => 'number'],
        ];

        $result['form']['i18n'] = 'product';

        return $result;
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            $r = $this->BRequest;
            $data = $r->post();
            $id = $r->param('id', true);
            $model = $this->Sellvana_Catalog_Model_Product->load($id);
            if (!$model) {
                throw new BException("This item does not exist");
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
                $result = $this->getFormData();
                $result['form'] = $this->normalizeFormConfig($result['form']);
                $this->ok()->addMessage('Product was saved successfully', 'success');
            } else {
                $result = ['status' => 'error'];
                $this->error()->addMessage('Cannot save data, please fix above errors', 'error');
            }

        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
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
                    'link' => '/catalog/products/form?id=' . $result->id(),
                ];
            }
        }
    }
}