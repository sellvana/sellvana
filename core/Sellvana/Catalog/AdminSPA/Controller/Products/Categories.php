<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia Sellvana_Catalog_Model_ProductMedia
 */
class Sellvana_Catalog_AdminSPA_Controller_Products_Categories extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig()
    {
        $prodId = $this->BRequest->get('id');
        $bool = [0 => 'no', 1 => 'Yes'];
        return [
            'id' => 'product_categories',
            'data_url' => 'products/categories/grid_data?id=' . $prodId,
            'columns' => [
                ['type' => 'row-select', 'width' => 55],
                ['type' => 'actions', 'actions' => [
                    ['type' => 'edit', 'link' => '/catalog/categories/form?id={id}', 'icon_class' => 'fa fa-pencil'],
                    ['type' => 'delete', 'delete_url' => 'products/form/categories/grid_delete?id={id}', 'icon_class' => 'fa fa-trash'],
                ]],
                ['name' => 'id', 'label' => 'ID', 'index' => 'c.id', 'width' => 55, 'hidden' => true],
                ['name' => 'node_name', 'label' => 'Category Name', 'index' => 'c.node_name', 'width' => 100],
                ['name' => 'position', 'label' => 'Position', 'index' => 'c.position', 'hidden' => true],
                ['name' => 'create_at', 'label' => 'Created', 'index' => 'c.create_at', 'width' => 100, 'cell' => 'datetime'],
                ['name' => 'update_at', 'label' => 'Updated', 'index' => 'c.update_at', 'width' => 100, 'cell' => 'datetime'],
            ],
            'filters' => [
                ['name' => 'id', 'type' => 'number'],
                ['name' => 'node_name'],
                ['name' => 'position', 'type' => 'number'],
                ['name' => 'create_at', 'type' => 'date'],
                ['name' => 'update_at', 'type' => 'date'],
            ],
            'export' => true,
            'pager' => true,
        ];
    }

    public function getGridOrm()
    {
        $prodId = $this->BRequest->get('id');
        return $this->Sellvana_Catalog_Model_Category->orm('c')
            ->select('c.*')
            ->join('Sellvana_Catalog_Model_CategoryProduct', ['cp.product_id', '=', 'p.id'], 'cp')
            ->select('cp.position')
            ->where('cp.product_id', $prodId);
    }

}