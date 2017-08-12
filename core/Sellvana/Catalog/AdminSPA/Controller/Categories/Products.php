<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia Sellvana_Catalog_Model_ProductMedia
 */
class Sellvana_Catalog_AdminSPA_Controller_Categories_Products extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig()
    {
        $catId = $this->BRequest->get('id');
        $products = $this->Sellvana_Catalog_Model_Product->orm('p')
            ->select('p.*')
            ->join('Sellvana_Catalog_Model_CategoryProduct', ['cp.product_id', '=', 'p.id'], 'cp')
            ->select('cp.sort_order')
            ->where('cp.category_id', $catId)
            ->find_many();
        if ($products) {
            $this->Sellvana_Catalog_Model_ProductMedia->collectProductsImages($products);
            foreach ($products as $row) {
                $row->set('thumb_url', $row->thumbUrl(48));
            }
        }
        $bool = [0 => (('no')), 1 => (('Yes'))];
        return [
            static::ID => 'category_products',
            static::DATA => $products ? $this->BDb->many_as_array($products) : [],
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT, static::WIDTH => 55],
                [static::NAME => 'id', static::LABEL => (('ID')), 'index' => 'p.id', static::WIDTH => 55, static::HIDDEN => true],
                [static::NAME => 'thumb_path', static::LABEL => (('Thumbnail')), static::WIDTH => 48, 'sortable' => false,
                    static::DATACELL_TEMPLATE => '<td><a :href="\'#/catalog/products/form?id=\'+row.id"><img :src="row.thumb_url" :alt="row.product_name"></a></td>'],
                [static::NAME => 'product_name', static::LABEL => (('Name')), static::WIDTH => 250,
                    static::DATACELL_TEMPLATE => '<td><a :href="\'#/catalog/products/form?id=\'+row.id">{{row.product_name}}</a></td>'],
                [static::NAME => 'product_sku', static::LABEL => (('Product SKU')), 'index' => 'p.product_sku', static::WIDTH => 100],
                [static::NAME => 'short_description', static::LABEL => (('Description')),  static::WIDTH => 200, static::HIDDEN => true],
                [static::NAME => 'is_hidden', static::LABEL => (('Hidden?')), static::WIDTH => 50, static::OPTIONS => $bool, 'multirow_edit' => true],
                [static::NAME => 'manage_inventory', static::LABEL => (('Manage Inv?')), static::WIDTH => 50, static::OPTIONS => $bool, 'multirow_edit' => true],
                //[static::NAME => 'base_price', static::LABEL => 'Base Price',  static::WIDTH => 100, static::HIDDEN => true],
                //[static::NAME => 'sale_price', static::LABEL => 'Sale Price',  static::WIDTH => 100, static::HIDDEN => true],
                [static::NAME => 'net_weight', static::LABEL => (('Net Weight')),  static::WIDTH => 100, static::HIDDEN => true, 'multirow_edit' => true],
                [static::NAME => 'ship_weight', static::LABEL => (('Ship Weight')),  static::WIDTH => 100, static::HIDDEN => true, 'multirow_edit' => true],
                [static::NAME => 'position', static::LABEL => (('Position')), 'index' => 'p.position', static::HIDDEN => true],
                [static::NAME => 'create_at', static::LABEL => (('Created')), 'index' => 'p.create_at', static::WIDTH => 100, 'cell' => 'datetime'],
                [static::NAME => 'update_at', static::LABEL => (('Updated')), 'index' => 'p.update_at', static::WIDTH => 100, 'cell' => 'datetime'],
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
        ];
    }
}