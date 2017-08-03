<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia Sellvana_Catalog_Model_ProductMedia
 * @property Sellvana_Catalog_Model_Category Sellvana_Catalog_Model_Category
 */
class Sellvana_Catalog_AdminSPA_Controller_Products_Categories extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig()
    {
        $prodId = $this->BRequest->get('id');
        $bool = [0 => (('no')), 1 => (('Yes'))];
        return [
            static::ID => 'product_categories',
            static::DATA_URL => 'products/form/categories/grid_data?id=' . $prodId,
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT, static::WIDTH => 55],
                [static::NAME => 'id', static::LABEL => (('ID')), 'index' => 'c.id', static::WIDTH => 55, static::HIDDEN => true],
                [static::NAME => 'node_name', static::LABEL => (('Category Name')), 'index' => 'c.node_name', static::WIDTH => 100],
                [static::NAME => 'position', static::LABEL => (('Position')), 'index' => 'c.position', static::HIDDEN => true],
                [static::NAME => 'create_at', static::LABEL => (('Created')), 'index' => 'c.create_at', static::WIDTH => 100, 'cell' => 'datetime'],
                [static::NAME => 'update_at', static::LABEL => (('Updated')), 'index' => 'c.update_at', static::WIDTH => 100, 'cell' => 'datetime'],
            ],
            static::FILTERS => [
                [static::NAME => 'id', static::TYPE => 'number'],
                [static::NAME => 'node_name'],
                [static::NAME => 'position', static::TYPE => 'number'],
                [static::NAME => 'create_at', static::TYPE => 'date'],
                [static::NAME => 'update_at', static::TYPE => 'date'],
            ],
            static::EXPORT => true,
            static::PAGER => true,
        ];
    }

    public function getGridOrm()
    {
        $prodId = $this->BRequest->get('id');
        return $this->Sellvana_Catalog_Model_Category->orm('c')
            ->select('c.*')
            ->join('Sellvana_Catalog_Model_CategoryProduct', ['cp.product_id', '=', 'p.id'], 'cp')
            ->select('cp.sort_order')
            ->where('cp.product_id', $prodId);
    }

}