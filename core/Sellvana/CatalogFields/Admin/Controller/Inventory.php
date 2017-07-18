<?php

/**
 * Class Sellvana_Catalog_Admin_Controller_Inventory
 *
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_CatalogFields_Model_ProductVariant $Sellvana_CatalogFields_Model_ProductVariant
 */
class Sellvana_CatalogFields_Admin_Controller_Inventory extends FCom_Admin_Controller_Abstract_GridForm
{
    /**
     * @param Sellvana_Catalog_Model_InventorySku $model
     * @return array
     */
    public function gridConfig($model = null)
    {
        $data = [];
        if ($model) {
            $data = $this->BDb->many_as_array(
                $this->Sellvana_CatalogFields_Model_ProductVariant->orm('pv')
                    ->join('Sellvana_Catalog_Model_Product', ['pv.product_id', '=', 'p.id'], 'p')
                    ->where('pv.inventory_sku', $model->get('inventory_sku'))
                    ->select('pv.*')
                    ->select_expr('p.product_name', 'product')
                    ->find_many());
        }

        $config = [
            'config' => [
                'id' => 'variable-field-grid',
                'caption' => (('Variable Field Grid')),
                'data_mode' => 'local',
                'data' => $data,
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => (('ID')), 'width' => 30, 'hidden' => true],
                    ['name' => 'product', 'label' => (('Product')), 'width' => 300, 'sortable' => false],
                    ['name' => 'product_sku', 'label' => (('Product SKU')), 'width' => 300],
                    ['name' => 'inventory_sku', 'label' => (('Inventory SKU')), 'width' => 300],
                    ['name' => 'variant_price', 'label' => (('Price')), 'width' => 300]
                ],
                'filters' => [
                    ['field' => 'product_sku', 'type' => 'text'],
                    ['field' => 'inventory_sku', 'type' => 'text'],
                    ['field' => 'variant_price', 'type' => 'number-range']
                ],
                'callbacks' => [
                    'componentDidMount' => 'variantInventoryRegister'
                ]
            ]
        ];

        return $config;
    }
}
