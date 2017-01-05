<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
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
                    'datacell_template' => '<img :src="row.thumb_url" :alt="row.product_name">'],
                ['name' => 'product_name', 'label' => 'Name', 'width' => 250],
                ['name' => 'product_sku', 'label' => 'SKU', 'index' => 'p.product_sku', 'width' => 100],
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
}