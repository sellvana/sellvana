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

        $bool = [0 => 'no', 1 => 'Yes'];

        return [
            'id' => 'product_inventory_sku',
            'data_url' => 'inventory/form/products/grid_data',
            'caption' => 'Product Inventory SKU',
            'data_mode' => 'local',
            'data' => $data,
            'columns' => [
                ['type' => 'row-select', 'width' => 55],
                ['type' => 'actions', 'actions' => [
                    ['type' => 'edit', 'link' => '/catalog/products/form?id={id}', 'icon_class' => 'fa fa-pencil'],
                    ['type' => 'delete', 'delete_url' => 'categories/form/products/grid_delete?id={id}', 'icon_class' => 'fa fa-trash'],
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
            'pager' => true
        ];
    }
}
