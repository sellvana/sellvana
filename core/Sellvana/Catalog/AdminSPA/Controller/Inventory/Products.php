<?php

/**
 * Class Sellvana_Catalog_AdminSPA_Controller_Inventory_Products
 *
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia Sellvana_Catalog_Model_ProductMedia
 */
class Sellvana_Catalog_AdminSPA_Controller_Inventory_Products extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    /**
     * @return array
     */
    public function getGridConfig()
    {
        $inventory_sku = $this->BRequest->get('inventory_sku');
        $data = [];
        if ($inventory_sku) {
            $data = $this->BDb->many_as_array($this->Sellvana_Catalog_Model_Product->orm('p')
                ->left_outer_join('Sellvana_Catalog_Model_ProductMedia', "p.id=pa.product_id and pa.media_type='" .
                    Sellvana_Catalog_Model_ProductMedia::MEDIA_TYPE_IMG . "'", 'pa')
                ->left_outer_join('FCom_Core_Model_MediaLibrary', 'a.id=pa.file_id', 'a')
                ->where('p.inventory_sku', $inventory_sku)
                ->select(['p.*', 'pa.*', 'a.folder', 'a.subfolder', 'a.file_name', 'a.file_size'])
                ->select_expr('IF (a.subfolder is null, "", CONCAT("/", a.subfolder))', 'subfolder')
                ->find_many());
        }

        $bool = [0 => (('no')), 1 => (('Yes'))];

        return [
            static::ID => 'product_inventory_sku',
            static::DATA_URL => 'inventory/form/products/grid_data',
            'caption' => (('Product Inventory SKU')),
            'data_mode' => 'local',
            'data' => $data,
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
            static::PAGER => true
        ];
    }
}
