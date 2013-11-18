<?php
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
    public static function i($new = false, array $args = array())
    {
        return parent::i($new, $args);
    }

    /**
     * @param FCom_Catalog_Model_Product $model
     * @return array
     */
    public function getTierPricesGrid($model)
    {
        $orm = FCom_CustomerGroups_Model_TierPrice::i()->orm()->where('product_id', $model->id());
        $grid = array(
            'config'=>array(
                'id'=>'tier-prices',
                'columns'=>array(
                    array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>40),
                    array('name'=>'id', 'label'=>'ID', 'hidden'=>true),
                    array('name'=>'product_id', 'default'=>$model->id(), 'hidden'=>true),
                    array('name'=>'group_id', 'label'=>'Group', 'options'=>FCom_CustomerGroups_Model_Group::i()->groupsOptions(),'validation'=>array('required'=>true), 'editable'=>true, 'addable'=>true, 'editor'=>'select', 'width'=>150),
                    array('name'=>'qty', 'label'=>'Qty','validation'=>array('required'=>true), 'editable'=>true, 'addable'=>true, 'width'=>150, 'validate'=>'number'),
                    array('name'=>'base_price', 'label'=>'Base Price','validation'=>array('required'=>true), 'editable'=>true, 'addable'=>true, 'width'=>150, 'validate'=>'number'),
                    array('name'=>'sale_price', 'label'=>'Sale Price','validation'=>array('required'=>true), 'editable'=>true, 'addable'=>true, 'width'=>150, 'validate'=>'number'),
                    array('name'=>'_actions', 'label'=>'Actions', 'sortable'=>false, 'data'=>array('delete'=>true))
                ),
                'data'=>BDb::many_as_array($orm->find_many()),
                'data_mode'=>'local',
                'filters'=>array(
                                    array('field'=>'name', 'type'=>'text'),
                                    array('field'=>'group_id', 'type'=>'multiselect')
                ),
                'actions'=>array(
                    'new'=>array('caption'=>'Add New Price', 'modal'=>true),
                    'edit'=>true,
                    'delete'=>true
                ),
                'events'=>array(
                    'init', 'edit', 'delete', 'mass-edit', 'mass-delete'
                )
            )
        );
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
