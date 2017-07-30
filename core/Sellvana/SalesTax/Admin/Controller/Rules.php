<?php

/**
 * Class Sellvana_SalesTax_Admin_Controller_Rules
 *
 * @property Sellvana_SalesTax_Model_Rule $Sellvana_SalesTax_Model_Rule
 * @property Sellvana_SalesTax_Model_Zone $Sellvana_SalesTax_Model_Zone
 * @property Sellvana_SalesTax_Model_CustomerClass $Sellvana_SalesTax_Model_CustomerClass
 * @property Sellvana_SalesTax_Model_ProductClass $Sellvana_SalesTax_Model_ProductClass
 * @property Sellvana_SalesTax_Model_RuleZone $Sellvana_SalesTax_Model_RuleZone
 * @property Sellvana_SalesTax_Model_RuleCustomerClass $Sellvana_SalesTax_Model_RuleCustomerClass
 * @property Sellvana_SalesTax_Model_RuleProductClass $Sellvana_SalesTax_Model_RuleProductClass
 */
class Sellvana_SalesTax_Admin_Controller_Rules extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    protected $_modelClass = 'Sellvana_SalesTax_Model_Rule';
    protected $_gridHref = 'salestax/rules';
    protected $_gridTitle = (('Tax Rules'));
    protected $_recordName = (('Tax Rule'));
    protected $_formTitleField = 'title';
    protected $_mainTableAlias = 'r';
    protected $_navPath = 'sales/tax/rules';
    protected $_permission = 'sales/tax/rules';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $zones = $this->Sellvana_SalesTax_Model_Zone->getAllZones();
        $custClasses = $this->Sellvana_SalesTax_Model_CustomerClass->getAllTaxClasses();
        $prodClasses = $this->Sellvana_SalesTax_Model_ProductClass->getAllTaxClasses();
        $countries = $this->FCom_Core_Main->getAllowedCountries();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [['name' => 'edit'], ['name' => 'delete']]],
            ['name' => 'id', 'label' => (('ID')), 'width' => 50],
            ['type' => 'input', 'name' => 'title', 'label' => (('Title')),
                'validation' => ['required' => true, 'unique' => $this->BApp->href('salestax/zones/unique')],
                'editable' => true, 'addable' => true],
            ['name' => 'zones_cnt', 'label' => (('Zones Cnt'))],
            ['name' => 'cust_class_cnt', 'label' => (('Customer Classes Cnt'))],
            ['name' => 'prod_class_cnt', 'label' => (('Product Classes Cnt'))],
            ['type' => 'input', 'name' => 'rule_rate_percent', 'label' => (('Rule Rate')),
                'editable' => true, 'addable' => true],
            ['type' => 'input', 'name' => 'fpt_amount', 'label' => (('FPT Amount')),
                'editable' => true, 'addable' => true],
            ['type' => 'multiselect', 'name' => 'zones', 'label' => (('Zones')),
                'options' => $zones, 'editable' => true, 'addable' => true],
            ['type' => 'multiselect', 'name' => 'cust_classes', 'label' => (('Customer Classes')),
                'options' => $custClasses, 'editable' => true, 'addable' => true],
            ['type' => 'multiselect', 'name' => 'prod_classes', 'label' => (('Product Classes')),
                'options' => $prodClasses, 'editable' => true, 'addable' => true],
        ];
        $config['actions'] = [
            'edit' => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'title', 'type' => 'text'],
            ['field' => 'rule_rate_percent', 'type' => 'number-range'],
            ['field' => 'fpt_amount', 'type' => 'number-range'],
        ];
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $tZone = $this->Sellvana_SalesTax_Model_RuleZone->table();
        $tCustClass = $this->Sellvana_SalesTax_Model_RuleCustomerClass->table();
        $tProdClass = $this->Sellvana_SalesTax_Model_RuleProductClass->table();
        $orm->select("(select count(*) from {$tZone} where rule_id=r.id)", 'zones_cnt')
            ->select("(select count(*) from {$tCustClass} where rule_id=r.id)", 'cust_class_cnt')
            ->select("(select count(*) from {$tProdClass} where rule_id=r.id)", 'prod_class_cnt');
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        $model = $args['model'];
        $data = $args['data'];

        if($data['match_all_customer_classes'] == 1) {
            $this->_resetRuleCustomerClasses($model);
        } else if(!empty($data['customer_classes'])) {
            $this->_setRuleCustomerClasses($model, $data['customer_classes']);
        }

        if($data['match_all_product_classes'] == 1) {
            $this->_resetRuleProductClasses($model);
        } else if(!empty($data['product_classes'])) {
            $this->_setRuleProductClasses($model, $data['product_classes']);
        }

        if($data['match_all_zones'] == 1) {
            $this->_resetRuleZones($model);
        } else if(!empty($data['zones'])) {
            $this->_setRuleZones($model, $data['zones']);
        }

    }

    public function addTitle($title = '')
    {
        /* @var $v BViewHead */
        $v = $this->view('head');
        if ($v) {
            $v->addTitle($title);
        }
    }

    public function action_unique__POST()
    {
        $post = $this->BRequest->post();
        $data = each($post);
        $rows = $this->BDb->many_as_array($this->Sellvana_SalesTax_Model_Rule->orm()
            ->where($data['key'], $data['value'])->find_many());
        $this->BResponse->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }

    /**
     * @param $rule Sellvana_SalesTax_Model_Rule
     */
    protected function _resetRuleCustomerClasses($rule)
    {
        $currentCustomerRules = $this->Sellvana_SalesTax_Model_RuleCustomerClass
            ->orm()->where('rule_id', $rule->id())->find_many();
        if($currentCustomerRules){
            /** @var  $cr Sellvana_SalesTax_Model_RuleCustomerClass */
            foreach ($currentCustomerRules as $cr) {
                $cr->delete();
            }
        }
    }

    /**
     * @param $rule Sellvana_SalesTax_Model_Rule
     */
    protected function _resetRuleProductClasses($rule)
    {
        $currentProductRules = $this->Sellvana_SalesTax_Model_RuleProductClass
            ->orm()->where('rule_id', $rule->id())->find_many();
        if ($currentProductRules) {
            /** @var  $pr Sellvana_SalesTax_Model_RuleCustomerClass */
            foreach ($currentProductRules as $pr) {
                $pr->delete();
            }
        }
    }

    /**
     * @param $rule Sellvana_SalesTax_Model_Rule
     */
    protected function _resetRuleZones($rule)
    {
        $currentZoneRules = $this->Sellvana_SalesTax_Model_RuleZone->orm()->where('rule_id', $rule->id())->find_many();
        if ($currentZoneRules) {
            /** @var  $rz Sellvana_SalesTax_Model_RuleCustomerClass */
            foreach ($currentZoneRules as $rz) {
                $rz->delete();
            }
        }
    }

    /**
     * @param $rule Sellvana_SalesTax_Model_Rule
     * @param $customer_classes array
     */
    protected function _setRuleCustomerClasses($rule, $customer_classes)
    {
        $currentCustomerRules = $this->Sellvana_SalesTax_Model_RuleCustomerClass
            ->orm()->where('rule_id', $rule->id())->find_many_assoc('customer_class_id');
        foreach ($customer_classes as $cc) {
            if (!$currentCustomerRules || !isset($currentCustomerRules[$cc])) {
                $this->Sellvana_SalesTax_Model_RuleCustomerClass
                    ->create(['rule_id' => $rule->id(), 'customer_class_id' => (int) $cc])->save();
            }
        }

        if ($currentCustomerRules) {
            /** @var Sellvana_SalesTax_Model_RuleCustomerClass $ccr */
            foreach ($currentCustomerRules as $ccr) {
                if (!in_array($ccr->get('customer_class_id'), $customer_classes)) {
                    $ccr->delete();
                }
            }

        }
    }

    /**
     * @param $rule Sellvana_SalesTax_Model_Rule
     * @param $product_classes array
     */
    protected function _setRuleProductClasses($rule, $product_classes)
    {
        $currentProductRules = $this->Sellvana_SalesTax_Model_RuleProductClass
            ->orm()->where('rule_id', $rule->id())->find_many_assoc('product_class_id');
        foreach ($product_classes as $pc) {
            if (!$currentProductRules || !isset($currentProductRules[$pc])) {
                $this->Sellvana_SalesTax_Model_RuleProductClass
                    ->create(['rule_id' => $rule->id(), 'product_class_id' => (int) $pc])->save();
            }
        }

        if ($currentProductRules) {
            /** @var Sellvana_SalesTax_Model_RuleProductClass $cpr */
            foreach ($currentProductRules as $cpr) {
                if (!in_array($cpr->get('product_class_id'), $product_classes)) {
                    $cpr->delete();
                }
            }

        }
    }

    /**
     * @param $rule Sellvana_SalesTax_Model_Rule
     * @param $zones array
     */
    protected function _setRuleZones($rule, $zones)
    {
        $currentZoneRules = $this->Sellvana_SalesTax_Model_RuleZone
            ->orm()->where('rule_id', $rule->id())->find_many_assoc('zone_id');
        foreach ($zones as $rz) {
            if (!$currentZoneRules || !isset($currentZoneRules[$rz])) {
                $this->Sellvana_SalesTax_Model_RuleZone
                    ->create(['rule_id' => $rule->id(), 'zone_id' => (int) $rz])->save();
            }
        }

        if ($currentZoneRules) {
            /** @var Sellvana_SalesTax_Model_RuleZone $czr */
            foreach ($currentZoneRules as $czr) {
                if (!in_array($czr->get('zone_id'), $zones)) {
                    $czr->delete();
                }
            }

        }
    }
}
