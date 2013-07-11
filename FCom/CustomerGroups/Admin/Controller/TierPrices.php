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

    public function gridConfig()
    {
        $gridId = "tier-prices";
        $config  = array(
            'grid' => array(
                'id'      => $gridId,
                'caption' => BLocale::_("Tier prices"),
                'url'     => BApp::href('tier-prices/grid_data'),
                'editurl' => BApp::href('tier-prices/grid_data/?id='),
//                'datatype'=> 'local',
                'columns' => array(
                    'id'         => array(
                        'label' => BLocale::_("ID"),
                        'width' => 30, 'index' => $this->_mainTableAlias . '.id'
                    ),
                    'product_id'=> array(
                        'label' => BLocale::_("Product ID"),
                        'width' => 30,
                        'index' => $this->_mainTableAlias . '.product_id',
                        'hidden'=>true,
                        'editable' => true,
                        'edittype' => 'custom',
                        'editoptions' => array('custom_value' => "function (elem, op, value) {
                            if(op === 'get') {
                                var v = $(elem).val();
                                if(undefined == v || null == v || '' == v) {
                                    var cols = $('#$gridId').jqGrid('getGridParam', 'colModel');
                                    $(cols).each(function(i, c) {
                                        if (c.name == 'product_id'){
                                            console.log(c.editoptions.value)
                                            return c.editoptions.value
                                        }
                                    });
                                }
                                return v;
                            } else if(op === 'set') {
                                $(elem).val(value);
                            }
                        }"
                        ),
                    ),
                    'group_id'    => array(
                        'label' => BLocale::_('Group'),
                        'index' => $this->_mainTableAlias . '.group_id', 'width' => 200,
                        'options' => FCom_CustomerGroups_Model_Group::i()->groupsOptions(),
                        'editable' => true,
                        'edittype' => 'select'
                    ),
                    'base_price'      => array(
                        'label' => BLocale::_('Regular Price'),
                        'index' => $this->_mainTableAlias . '.base_price', 'width' => 200,
                        'editable' => true
                    ),
                    'sale_price' => array(
                        'label' => BLocale::_('Sale Price'),
                        'index' => $this->_mainTableAlias . '.sale_price', 'width' => 200,
                        'editable' => true
                    ),
                    'qty'        => array(
                        'label' => BLocale::_('Qty'),
                        'index' => $this->_mainTableAlias . '.qty', 'width' => 200,
                        'editable' => true
                    ),
                ),

                'multiselect' => true,
            ),
            'navGrid' => array('add'=>true, 'addtext'=>'Add New', 'addtitle'=>'Add new price', 'edit'=>true, 'del'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
        );
        return $config;
    }

    /**
     * @param FCom_Catalog_Model_Product $model
     * @return array
     */
    public function getTierPricesGrid($model)
    {
        $orm = FCom_CustomerGroups_Model_TierPrice::i()->orm()->where('product_id', $model->id);
        $grid = array(
            'config' => array(
                'id' => 'tier-prices',
                'columns' => array(
                    array('name' => 'id', 'label' => 'ID'),
                    array('name' => 'product_id', 'label' => 'Product'),
                    array('name' => 'group_id', 'label' => 'Group', 'options' => FCom_CustomerGroups_Model_Group::i()->groupsOptions(), 'editable' => true),
                    array('name' => 'qty', 'label' => 'Qty', 'editable' => true),
                    array('name' => 'base_price', 'label' => 'Base Price', 'editable' => true),
                    array('name' => 'sale_price', 'label' => 'Sale Price', 'editable' => true),
                ),
                'collection' => BDb::many_as_array($orm->find_many()),
            ),
        );
        return $grid;
        $config = $this->gridConfig();
        if(!$model){
            return $config;
        }
        $config['grid']['columns']['product_id']['editoptions']['value'] = $model->id;
        $config['grid']['columns']['product_id']['editoptions']['custom_element'] = "function (value, options) {
                            value = value || options.value
                            var el = $('<span>' + value +'</span><input type=\"hidden\" name=\"product_id\">');
                            el.val(value);

                            return el;
                        }";
        return $config;
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