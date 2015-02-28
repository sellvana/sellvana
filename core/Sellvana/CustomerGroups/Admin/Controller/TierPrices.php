<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomerGroups_Admin_Controller_TierPrices
 *
 * @property Sellvana_CustomerGroups_Model_Group $Sellvana_CustomerGroups_Model_Group
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 */
class Sellvana_CustomerGroups_Admin_Controller_TierPrices
    extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    protected $_gridHref = 'tier-prices';
    protected $_modelClass = 'Sellvana_Catalog_Model_ProductPrice';
    protected $_gridTitle = "Tier Prices";
    protected $_recordName = "Tier Price";
    protected $_mainTableAlias = 'tp';

    /**
     * @param Sellvana_Catalog_Model_Product $model
     * @return array
     */
    public function getTierPricesGrid($model)
    {
        $cgOptions = $this->Sellvana_CustomerGroups_Model_Group->groupsOptions();
        $orm = $this->Sellvana_Catalog_Model_ProductPrice->orm()->where('product_id', $model->id());
        $grid = [
            'config' => [
                'id' => 'tier-prices',
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'hidden' => true],
                    ['type' => 'input', 'name' => 'group_id', 'label' => 'Customer Group', 'options' => $cgOptions,
                        'validation' => ['required' => true], 'editable' => 'inline', 'addable' => true,
                        'editor' => 'select', 'width' => 150, 'default' => 1],
                    ['type' => 'input', 'name' => 'qty', 'label' => 'Minimum Qty', 'editable' => 'inline',
                        'addable' => true, 'width' => 150, 'validation' => ['required' => true, 'number' => true]],
                    ['type' => 'input', 'name' => 'base_price', 'label' => 'Regular Price', 'width' => 150,
                        'validation' => ['required' => true, 'number' => true], 'editable' => 'inline', 'addable' => true],
                    ['type' => 'input', 'name' => 'sale_price', 'label' => 'Special Price', 'width' => 150,
                        'validation' => ['required' => true, 'number' => true], 'editable' => 'inline', 'addable' => true],
                    ['type' => 'btn_group', 'buttons' => [['name' => 'delete']]]
                ],
                'data' => $this->BDb->many_as_array($orm->find_many()),
                'data_mode' => 'local',
                'filters' => [
                    ['field' => 'name', 'type' => 'text'],
                    ['field' => 'group_id', 'type' => 'multiselect']
                ],
                'actions' => [
                    'new' => ['caption' => 'Add New Price'],
                    'delete' => true
                ],
                'grid_before_create' => 'tierPricesGridRegister'
            ]
        ];
        return $grid;
    }

    /**
     * @param string $title
     */
    public function addTitle($title = '')
    {
        /* @var $v BViewHead */
        $v = $this->view('head');
        if ($v) {
            $v->addTitle($title);
        }
    }
}
