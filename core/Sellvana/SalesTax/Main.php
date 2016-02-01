<?php

/**
 * Class Sellvana_SalesTax_Main
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_SalesTax_Model_CustomerTax $Sellvana_SalesTax_Model_CustomerTax
 * @property Sellvana_SalesTax_Model_CustomerGroupTax $Sellvana_SalesTax_Model_CustomerGroupTax
 * @property Sellvana_SalesTax_Model_ProductTax $Sellvana_SalesTax_Model_ProductTax
 * @property Sellvana_SalesTax_Model_Rule $Sellvana_SalesTax_Model_Rule
 * @property Sellvana_SalesTax_Model_RuleCustomerClass $Sellvana_SalesTax_Model_RuleCustomerClass
 * @property Sellvana_SalesTax_Model_RuleProductClass $Sellvana_SalesTax_Model_RuleProductClass
 * @property Sellvana_SalesTax_Model_RuleZone $Sellvana_SalesTax_Model_RuleZone
 * @property Sellvana_SalesTax_Model_Zone $Sellvana_SalesTax_Model_Zone
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_SalesTax_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'sales/tax' => 'Sales Tax',
            'sales/tax/zones' => 'Sales Tax Zones',
            'sales/tax/rules' => 'Sales Tax Rules',
            'sales/tax/product_classes' => 'Sales Tax Product Classes',
            'sales/tax/customer_classes' => 'Sales Tax Customer Classes',
            'settings/Sellvana_SalesTax' => 'Sales Tax Settings',
        ]);
    }

    public function onCartTaxCalculate($args)
    {
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart       = $args['cart'];
        $result     =& $args['result'];
        $customerId = !empty($args['customer']) ? $args['customer']->id() : $cart->get('customer_id');

        $result['tax_amount'] = 0;
        $result['items']      = [];
        $result['details']    = [];

        $zones = $this->_collectCartZones($cart);
        if (!$zones) {
            return;
        }

        // Get customer for whom the taxes are calculated
        $custClasses = $this->_collectCustomerTaxClasses($customerId);
        if (!$custClasses) {
            // If no Rules for these customer classes found, no further calculations necessary
            return;
        }

        $prodClasses = $this->_collectCartProductTaxClasses($cart);
        if (!$prodClasses) {
            // If no Rules for these product class found, no further calculations necessary
            return;
        }

        $rules = $this->_collectCartRules($zones, $custClasses, $prodClasses);
        if (!$rules) {
            // No matching rules found
            return;
        }

        $result = array_merge($result, $this->_calculateRatesAndAmounts($cart, $rules));
    }

    protected function _collectCartZones($cart)
    {
        $country = $cart->get('shipping_country');
        if (!$country) {
            $defCountry = $this->BConfig->get('modules/FCom_Core/default_country');
            if ($defCountry) {
                $country = $defCountry;
            } else {
                // if no country specified, skip as no address available
                return;
            }
        }

        $region   = $cart->get('shipping_region');
        $postcode = $cart->get('shipping_postcode');

        $orWhere = [
            ['AND', 'zone_type' => 'country', 'region' => null, 'postcode_from' => null, 'postcode_to' => null],
        ];

        if ($postcode) {
            $orWhere[] = ['AND', 'zone_type' => 'postcode', 'postcode_from' => $postcode];
            $orWhere[] =
                ['AND', 'zone_type' => 'postrange', ['postcode_from <= ?', $postcode], ['postcode_to >= ?', $postcode]];
        }
        if ($region) {
            $orWhere[] =
                ['AND', 'zone_type' => 'region', 'region' => $region, 'postcode_from' => null, 'postcode_to' => null];
        }
        $ruleZones = $this->Sellvana_SalesTax_Model_Zone->orm('z')
            ->where('country', $country)->where_complex($orWhere, true)
            ->join('Sellvana_SalesTax_Model_RuleZone', ['rz.zone_id', '=',
                'z.id'], 'rz')
            ->select('z.*')->select('rz.rule_id')
            ->find_many();

        return $ruleZones;
    }

    protected function _collectCustomerTaxClasses($customerId)
    {
        $custClassIds = [];
        if ($customerId) {
            // if cart is associated with customer, retrieve tax classes for this customer
            $custClassIds = $this->Sellvana_SalesTax_Model_CustomerTax->orm()
                ->where('customer_id', $customerId)->find_many_assoc('id', 'customer_class_id');
        }
        if (!$custClassIds) {
            // otherwise use configuration for default guest tax class
            $defCustClassId = $this->BConfig->get('modules/Sellvana_SalesTax/default_guest_class_id');
            $custClassIds   = [$defCustClassId];
        }

        $custClassRules = $this->Sellvana_SalesTax_Model_RuleCustomerClass->orm()
            ->where_in('customer_class_id', $custClassIds)->find_many();

        return $custClassRules;
    }

    protected function _collectCartProductTaxClasses($cart)
    {
        $items = $cart->items();
        if (!$items) {
            return [];
        }

        // Collect product IDs used in this cart
        $pIds = [];
        foreach ($items as $item) {
            $pIds[] = $item->get('product_id');
        }

        // Get default product tax class
        $defProdClassId = $this->BConfig->get('modules/Sellvana_SalesTax/default_product_class_id');
        // Retrieve tax classes for products in cart
        $prodTaxClasses = $this->Sellvana_SalesTax_Model_ProductTax->orm()
            ->where_in('product_id', $pIds)->find_many();

        // Group cart items by tax class
        $itemsByProdClass = [];
        foreach ($items as $item) {
            $assigned = false;
            foreach ($prodTaxClasses as $pt) {
                if ($item->get('product_id') === $pt->get('product_id')) {
                    $assigned                                    = true;
                    $prodClassId                                 = $pt->get('product_class_id');
                    $itemsByProdClass[$prodClassId][$item->id()] = $item;
                }
            }
            if (!$assigned) {
                // if product is not associated with tax classes, use default tax class configuration
                $itemsByProdClass[$defProdClassId][$item->id()] = $item;
            }
        }

        $prodClasses = $this->Sellvana_SalesTax_Model_RuleProductClass->orm()
            ->where_in('product_class_id', array_keys($itemsByProdClass))->find_many();

        if ($prodClasses) {
            foreach ($prodClasses as $pc) {
                $pc->set('items', $itemsByProdClass[$pc->get('product_class_id')]);
            }
        }

        return $prodClasses;
    }

    protected function _collectCartRules($zones, $custClasses, $prodClasses)
    {
        // Combine classes and zones into rules
        $rulesMatch = [];
        foreach ($zones as $r) {
            $rulesMatch[$r->get('rule_id')]['zones'][$r->id()] = $r;
        }
        foreach ($custClasses as $r) {
            $rulesMatch[$r->get('rule_id')]['customer_classes'][$r->get('customer_class_id')] = $r;
        }
        foreach ($prodClasses as $r) {
            $rulesMatch[$r->get('rule_id')]['product_classes'][$r->get('product_class_id')] = $r;
        }

        // Find all rules that were matched by zones or classes
        $rules = $this->Sellvana_SalesTax_Model_Rule->orm()->where_complex(['id' => array_keys($rulesMatch),
            // also load any rules that apply to all zones and classes
            ['AND', 'match_all_zones' => 1, 'match_all_customer_classes' => 1, 'match_all_product_classes' => 1],
        ], true)->find_many_assoc('id');

        if (!$rules) {
            return [];
        }

        // Remove rules that don't apply to relevant zones or classes, if they don't match all
        foreach ($rulesMatch as $rId => $r) {
            if (empty($rules[$rId])
                || empty($r['zones']) && 0 == $rules[$rId]->get('match_all_zones')
                || empty($r['customer_classes']) && 0 == $rules[$rId]->get('match_all_customer_classes')
                || empty($r['product_classes']) && 0 == $rules[$rId]->get('match_all_product_classes')
            ) {
                unset($rulesMatch[$rId], $rules[$rId]);
            } else {
                // Find the most specific zone and remove others
                // TODO: Add configuration to sum zone rates?
                usort($r['zones'], [$this, '_sortZonesCallback']);
                $r['zone'] = $r['zones'][0];

                $rules[$rId]->set($r);
            }
        }

        uasort($zones, [$this, '_sortRulesCallback']);

        return $rules;
    }

    protected function _sortZonesCallback($z1, $z2)
    {
        static $priority = ['country' => 3, 'region' => 2, 'postrange' => 1, 'postcode' => 0];
        $p1 = $priority[$z1->get('zone_type')];
        $p2 = $priority[$z2->get('zone_type')];
        return $p1 < $p2 ? -1 : ($p1 > $p2 ? 1 : 0);
    }

    protected function _sortRulesCallback($r1, $r2)
    {
        $p1 = $r1->get('compound_priority');
        $p2 = $r2->get('compound_priority');
        return $p1 < $p2 ? -1 : ($p1 > $p2 ? 1 : 0);
    }

    protected function _calculateRatesAndAmounts($cart, $rules)
    {
        $rulesPerItem = [];
        foreach ($rules as $rId => $rule) {
            if (!$rule->get('product_classes')) {
                continue;
            }
            foreach ($rule->get('product_classes') as $pc) {
                foreach ($pc->get('items') as $item) {
                    $rulesPerItem[$item->id()][$rule->get('compound_priority')][$rId] = $rule;
                }
            }
        }
        $result = [
            'tax_amount' => 0,
            'details' => [],
            'items' => [],
        ];

        if (!$rulesPerItem) {
            return $result;
        }

        $ratesByRule = [];
        foreach ($cart->items() as $item) {
            $itemId = $item->id();
            if (empty($rulesPerItem[$itemId])) {
                $item->set('row_tax', 0);
                continue;
            }
            $prevCompPriority = null;
            $taxableAmount    = $item->get('row_total');
            foreach ($rulesPerItem[$itemId] as $compoundPriority => $itemRules) {
                $compoundAmount = 0;
                foreach ($itemRules as $rId => $rule) {
                    if ($rule->get('fpt_amount')) {
                        $itemRuleAmount = $rule->get('fpt_amount');
                        $rate = null;
                    } else {
                        $rate                =
                            (float)($rule->get('rule_rate_percent') ?: $rule->get('zone')->get('zone_rate_percent'));
                        $ratesByRule[$rId][] = $rate;
                        $itemRuleAmount      = ceil($taxableAmount * $rate) / 100;
                    }
                    if (empty($result['details']['rules'][$rId])) {
                        $result['details']['rules'][$rId] = ['amount' => 0];
                    }
                    $result['details']['rules'][$rId]['amount'] += $itemRuleAmount;
                    $result['items'][$itemId]['details']['rules'][$rId] =
                        ['rate' => $rate, 'amount' => $itemRuleAmount];
                    $compoundAmount += $itemRuleAmount;
                }
                $taxableAmount += $compoundAmount;
            }
            $rowTaxAmount                        = $taxableAmount - $item->get('row_total');
            $result['items'][$itemId]['row_tax'] = $rowTaxAmount;
            $result['tax_amount'] += $rowTaxAmount;
        }

        foreach ($ratesByRule as $rId => $rates) {
            $result['details']['rules'][$rId]['title']    = $rules[$rId]->get('title');
            $result['details']['rules'][$rId]['avg_rate'] = array_sum($rates) / sizeof($rates);
        }

        return $result;
    }
    public function onProductAfterSave($args)
    {
        $model = $args['model'];
        $pId = $model->id();
        $hlp = $this->Sellvana_SalesTax_Model_ProductTax;
        $existingTaxIds = $hlp->orm()->where('product_id', $pId)->find_many_assoc('product_class_id', 'id');
        $newTaxIds = $model->get('tax_class_ids');

        if ($existingTaxIds) {
            $deleteIds = [];
            foreach ($existingTaxIds as $tcId => $tId) {
                if ($newTaxIds && !in_array($tcId, $newTaxIds)) {
                    $deleteIds[] = $tId;
                }
            }
            if ($deleteIds) {
                $hlp->delete_many(['id' => $deleteIds]);
            }
        }

        if ($newTaxIds) {
            foreach ($newTaxIds as $tcId) {
                if (empty($existingTaxIds[$tcId])) {
                    $hlp->create(['product_id' => $pId, 'product_class_id' => $tcId])->save();
                }
            }
        }
    }

    public function onCustomerAfterSave($args)
    {
        $model = $args['model'];
        $cId = $model->id();
        $hlp = $this->Sellvana_SalesTax_Model_CustomerTax;
        $existingTaxIds = $hlp->orm()->where('customer_id', $cId)->find_many_assoc('customer_class_id', 'id');
        $newTaxIds = $model->get('tax_class_ids') ?: [];

        if ($existingTaxIds) {
            $deleteIds = [];
            foreach ($existingTaxIds as $tcId => $tId) {
                if (!in_array($tcId, $newTaxIds)) {
                    $deleteIds[] = $tId;
                }
            }
            if ($deleteIds) {
                $hlp->delete_many(['id' => $deleteIds]);
            }
        }

        if ($newTaxIds) {
            foreach ($newTaxIds as $tcId) {
                if (empty($existingTaxIds[$tcId])) {
                    $hlp->create(['customer_id' => $cId, 'customer_class_id' => $tcId])->save();
                }
            }
        }
    }

    public function onCustomerGroupAfterSave($args)
    {
        $model = $args['model'];
        $cId = $model->id();
                $hlp = $this->Sellvana_SalesTax_Model_CustomerGroupTax;
        $existingTaxIds = $hlp->orm()->where('customer_group_id', $cId)->find_many_assoc('customer_class_id', 'id');
        $newTaxIds = $model->get('tax_class_ids') ?: [];

        if ($existingTaxIds) {
            $deleteIds = [];
            foreach ($existingTaxIds as $tcId => $tId) {
                if (!in_array($tcId, $newTaxIds)) {
                    $deleteIds[] = $tId;
                }
            }
            if ($deleteIds) {
                $hlp->delete_many(['id' => $deleteIds]);
            }
        }

        if ($newTaxIds) {
            foreach ($newTaxIds as $tcId) {
                if (empty($existingTaxIds[$tcId])) {
                    $hlp->create(['customer_group_id' => $cId, 'customer_class_id' => $tcId])->save();
                }
            }
        }
    }
}