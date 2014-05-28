<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Admin_Controller_TierPrices
    extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    protected $_gridHref = 'tier-prices';
    protected $_modelClass = 'FCom_CustomerGroups_Model_TierPrice';
    protected $_gridTitle = "Tier Prices";
    protected $_recordName = "Tier Price";
    protected $_mainTableAlias = 'tp';

    /**
     * @param bool|object $new
     * @param array       $args
     * @return FCom_CustomerGroups_Admin_Controller_TierPrices
     */
    public static function i($new = false, array $args = [])
    {
        return parent::i($new, $args);
    }

    /**
     * @param FCom_Catalog_Model_Product $model
     * @return array
     */
    public function getTierPricesGrid($model)
    {
        $cgOptions = FCom_CustomerGroups_Model_Group::i()->groupsOptions();
        $orm = FCom_CustomerGroups_Model_TierPrice::i()->orm()->where('product_id', $model->id());
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
                'data' => BDb::many_as_array($orm->find_many()),
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

    public function addTitle($title = '')
    {
        /* @var $v BViewHead */
        $v = $this->view('head');
        if ($v) {
            $v->addTitle($title);
        }
    }
}
