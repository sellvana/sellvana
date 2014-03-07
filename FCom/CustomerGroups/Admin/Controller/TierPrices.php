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
        $cgOptions = FCom_CustomerGroups_Model_Group::i()->groupsOptions();
        $orm = FCom_CustomerGroups_Model_TierPrice::i()->orm()->where('product_id', $model->id());
        $grid = array(
            'config'=>array(
                'id'=>'tier-prices',
                'columns'=>array(
                    array('type'=>'row_select'),
                    array('name'=>'id', 'label'=>'ID', 'hidden'=>true),
                    array('type'=>'input', 'name'=>'group_id', 'label'=>'Customer Group', 'options'=>$cgOptions,'validation'=>array('required'=>true),
                            'editable'=>'inline', 'addable'=>true, 'editor'=>'select', 'width'=>150, 'default'=>1),
                    array('type'=>'input', 'name'=>'qty', 'label'=>'Minimum Qty', 'editable'=>'inline', 'addable'=>true, 'width'=>150,
                            'validation'=>array('required'=>true, 'number'=>true)),
                    array('type'=>'input', 'name'=>'base_price', 'label'=>'Regular Price','validation'=>array('required'=>true, 'number'=>true),
                             'editable'=>'inline', 'addable'=>true, 'width'=>150),
                    array('type'=>'input', 'name'=>'sale_price', 'label'=>'Special Price','validation'=>array('required'=>true, 'number'=>true),
                            'editable'=>'inline', 'addable'=>true, 'width'=>150),
                    array('type'=>'btn_group', 'buttons'=>array( array('name'=>'delete') ))
                ),
                'data'=>BDb::many_as_array($orm->find_many()),
                'data_mode'=>'local',
                'filters'=>array(
                    array('field'=>'name', 'type'=>'text'),
                    array('field'=>'group_id', 'type'=>'multiselect')
                ),
                'actions'=>array(
                    'new'=>array('caption'=>'Add New Price'),
                    'delete'=>true
                ),
                'register_func'=>'tierPricesGridRegister'
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
