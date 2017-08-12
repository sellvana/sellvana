<?php

/**
 * Class Sellvana_Sales_AdminSPA_Controller_Orders
 *
 * @property Sellvana_Catalog_Model_Product Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_ProductMedia Sellvana_Catalog_Model_ProductMedia
 * @property Sellvana_Catalog_Model_Category Sellvana_Catalog_Model_Category
 * @property Sellvana_Catalog_Model_CategoryProduct Sellvana_Catalog_Model_CategoryProduct
 */
class Sellvana_Catalog_AdminSPA_Controller_Products_Categories extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    public function getGridConfig()
    {
        $prodId = (int)$this->BRequest->get('id');

        $rows = $this->BDb->many_as_array($this->Sellvana_Catalog_Model_CategoryProduct->orm('cp')
            ->join('Sellvana_Catalog_Model_Category', ['cp.product_id', '=', 'c.id'], 'c')
            ->select(['cp.*', 'c.full_name'])
            ->where('cp.product_id', $prodId)
            ->find_many());

        return [
            static::ID => 'product_categories',
            static::DATA => $rows,
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT, static::WIDTH => 55],
                [static::NAME => 'id', static::LABEL => (('ID')), static::WIDTH => 55, static::HIDDEN => true],
                [static::NAME => 'category_id', static::LABEL => (('Category ID')), static::WIDTH => '200px',
                    static::DATACELL_COMPONENT => 'sv-page-catalog-products-form-categories-dropdown'],
//                [static::NAME => 'full_name', static::LABEL => (('Category Path')), static::WIDTH => 100],
                [static::NAME => 'sort_order', static::LABEL => (('Position')),
                    static::DATACELL_TEMPLATE => '<td><input type="text" class="form-control" v-model="row.sort_order"></td>'],
                [static::NAME => 'create_at', static::LABEL => (('Created')), static::WIDTH => 100, 'cell' => 'datetime'],
                [static::NAME => 'update_at', static::LABEL => (('Updated')), static::WIDTH => 100, 'cell' => 'datetime'],
                [static::NAME => 'row-actions', static::LABEL => (('Remove')), 'sortable' => false,
                    static::DATACELL_TEMPLATE => '<td><button type="button" class="button button4" @click="emitEvent(\'row-action\', {name: \'remove\', col: col, row: row})">X</button></td>']
            ],
            static::FILTERS => [
                [static::NAME => 'id', static::TYPE => 'number'],
                [static::NAME => 'full_name'],
                [static::NAME => 'sort_order', static::TYPE => 'number'],
                [static::NAME => 'create_at', static::TYPE => 'date'],
                [static::NAME => 'update_at', static::TYPE => 'date'],
            ],
            static::PANEL_ACTIONS => [
                [static::NAME => 'add', static::LABEL => (('Add a Category')), static::BUTTON_CLASS => 'button1'],
            ],
            static::BULK_ACTIONS => [
                [static::NAME => 'remove', static::LABEL => (('Remove Categories'))],
            ],
        ];
    }

    public function action_options()
    {
        $q = $this->BRequest->get('q');

        $result['options'] = [['id' => 1, 'text' => 'Test Category']];

        $this->respond($result);
    }
}