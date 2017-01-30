<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia Sellvana_Catalog_Model_ProductMedia
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
                ['type' => 'actions', 'actions' => [
                    ['type' => 'edit', 'link' => '/catalog/products/form?id={id}', 'icon_class' => 'fa fa-pencil'],
                    ['type' => 'delete', 'delete_url' => 'products/grid_delete?id={id}', 'icon_class' => 'fa fa-trash'],
                ]],
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

    public function action_form_data()
    {
        $result = [];
        $pId = $this->BRequest->get('id');
        try {
            $product = $this->Sellvana_Catalog_Model_Product->load($pId);
            if (!$product) {
                throw new BException('Product not found');
            }

            $allCategoriesFlat = [];

            $result['form']['product'] = $product->as_array();
            $result['form']['thumb'] = ['thumb_url' => $product->thumbUrl(100)];

            $result['form']['config']['tabs'] = $this->getFormTabs('/catalog/products/form');
            $result['form']['config']['default_field'] = ['model' => 'product'];
            $result['form']['config']['fields'] = [
                 ['name' => 'product_name', 'label' => 'Product Name', 'i18n' => true],
                 ['name' => 'url_key', 'label' => 'URL Key (optional)'],
                 ['name' => 'product_sku', 'label' => 'Product SKU'],
                 ['name' => 'short_description', 'type' => 'textarea', 'label' => 'Short Description', 'i18n' => true],
                 ['name' => 'description', 'type' => 'wysiwyg', 'label' => 'Long Description', 'i18n' => true],
                 ['name' => 'is_hidden', 'label' => 'Hide Product', 'type' => 'checkbox'],
                 ['name' => 'is_featured', 'label' => 'Featured Product', 'type' => 'checkbox'],
                 ['name' => 'is_popular', 'label' => 'Popular Product', 'type' => 'checkbox'],
            ];

            $result['form']['config']['validation'] = [
                ['field' => 'product_name', 'required' => true, 'url' => true],
                ['field' => 'url_key', 'pattern' => '/^[a-z0-9-]+$/'],
                ['field' => 'product_sku', 'required' => true, 'email' => true],
                ['field' => 'short_description', 'required' => true],
                ['field' => 'description', 'required' => true],
            ];

            $result['form']['i18n'] = $this->getModelTranslations('product', $product->id());

            $result = $this->normalizeFormConfig($result);
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond($result);
    }

    public function action_form_data__POST()
    {
        $result = [];
        try {
            $data = $this->BRequest->post();
            $this->ok()->addMessage('Product was saved successfully', 'success');
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