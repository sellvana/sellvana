<?php

/**
 * @property Sellvana_SalesTax_Model_RuleProductClass $Sellvana_SalesTax_Model_RuleProductClass
 * @property Sellvana_SalesTax_Model_RuleCustomerClass $Sellvana_SalesTax_Model_RuleCustomerClass
 * @property Sellvana_SalesTax_Model_RuleZone $Sellvana_SalesTax_Model_RuleZone
 */
class Sellvana_SalesTax_Model_Rule extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_salestax_rule';
    protected static $_origClass = __CLASS__;

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['title'],
    ];

    public function getProductClassIds() {
        $currentProductRules = $this->getProductClasses();

        $classes = [];

        if($currentProductRules){
            foreach($currentProductRules as $cpr) {
                $classes[] = $cpr->get('product_class_id');
            }
        }
        return $classes;
    }

    public function getCustomerClassIds() {
        $currentCustomerRules = $this->getCustomerClasses();
        $classes = [];

        if ($currentCustomerRules) {
            foreach ($currentCustomerRules as $cpr) {
                $classes[] = $cpr->get('customer_class_id');
            }
        }

        return $classes;
    }

    public function getZoneIds() {
        $currentZoneRules = $this->getZones();
        $zones = [];

        if ($currentZoneRules) {
            foreach ($currentZoneRules as $cpr) {
                $zones[] = $cpr->get('zone_id');
            }
        }

        return $zones;
    }

    /**
     * @return Sellvana_SalesTax_Model_RuleProductClass[]
     */
    public function getProductClasses()
    {
        $currentProductRules = $this->Sellvana_SalesTax_Model_RuleProductClass
            ->orm()->where('rule_id', $this->id())->find_many();

        return $currentProductRules;
    }

    /**
     * @return Sellvana_SalesTax_Model_RuleCustomerClass[]
     */
    public function getCustomerClasses()
    {
        $currentCustomerRules = $this->Sellvana_SalesTax_Model_RuleCustomerClass
            ->orm()->where('rule_id', $this->id())->find_many();

        return $currentCustomerRules;
    }

    /**
     * @return Sellvana_SalesTax_Model_RuleZone[]
     */
    public function getZones()
    {
        $currentZoneRules = $this->Sellvana_SalesTax_Model_RuleZone->orm()->where('rule_id', $this->id())->find_many();

        return $currentZoneRules;
    }

}
