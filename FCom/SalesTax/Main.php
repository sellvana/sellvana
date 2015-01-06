<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_SalesTax_Main
 *
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 * @property FCom_SalesTax_Model_CustomerTax $FCom_SalesTax_Model_CustomerTax
 * @property FCom_SalesTax_Model_ProductTax $FCom_SalesTax_Model_ProductTax
 * @property FCom_SalesTax_Model_Rule $FCom_SalesTax_Model_Rule
 * @property FCom_SalesTax_Model_RuleCustomerClass $FCom_SalesTax_Model_RuleCustomerClass
 * @property FCom_SalesTax_Model_RuleProductClass $FCom_SalesTax_Model_RuleProductClass
 * @property FCom_SalesTax_Model_RuleZone $FCom_SalesTax_Model_RuleZone
 * @property FCom_SalesTax_Model_Zone $FCom_SalesTax_Model_Zone
 */
class FCom_SalesTax_Main extends BClass
{
    public function onCartTaxCalculate($args)
    {
        /** @var FCom_Sales_Model_Cart $cart */
        $cart = $args['cart'];
        $result =& $args['result'];

        $result['tax_amount'] = 0;

        /////////// GEO LOCATION

        $country = $cart->get('shipping_country');
        if (!$country) {
            $defCountry = $this->BConfig->get('modules/FCom_SalesTax/default_country');
            if ($defCountry) {
                $country = $defCountry;
            } else {
                // if no country specified, skip as no address available
                return;
            }
        }

        $region = $cart->get('shipping_region');
        $postcode = $cart->get('shipping_postcode');

        $orWhere = [
            ['AND', 'zone_type' => 'country', 'region' => null, 'postcode_from' => null, 'postcode_to' => null],
        ];

        if ($postcode) {
            $orWhere[] = ['AND', 'zone_type' => 'postcode', 'postcode_from' => $postcode];
            $orWhere[] = ['AND', 'zone_type' => 'postrange', ['postcode_from <= ?', $postcode], ['postcode_to >= ?', $postcode]];
            /*
            $postcodeZone = $this->FCom_SalesTax_Model_Zone->orm()->where('country', $country)
                ->where_lte('postcode_from', $postcode)->where_gte('postcode_to', $postcode)
                ->find_one();
            if ($postcodeZone && $postcodeZone->get('region')) {
                $region = $postcodeZone->get('region');
            }
            */
        }
        if ($region) {
            $orWhere[] = ['AND', 'zone_type' => 'region', 'region' => $region, 'postcode_from' => null, 'postcode_to' => null];
            /*
            $regionZone = $this->FCom_SalesTax_Model_Zone->orm()->where('country', $country)
                ->where('region', $region)->where_null('postcode_from')->where_null('postcode_to')
                ->find_one();
            */
        }
        /*
        $countryZone = $this->FCom_SalesTax_Model_Zone->orm()->where('country', $country)
            ->where_null('region')->where_null('postcode_from')->where_null('postcode_to')
            ->find_one();
        */
        $ruleZones = $this->FCom_SalesTax_Model_Zone->orm('z')
            ->where('country', $country)->where($orWhere, true)
            ->join('FCom_SalesTax_Model_RuleZone', ['rz.zone_id', '=', 'z.id'], 'rz')
            ->select('z.*')->select('rz.rule_id')
            ->find_many();
        if (!$ruleZones) {
            return;
        }

        ///////// CUSTOMER TAX CLASSES

        // Get customer for whom the taxes are calculated
        $customerId = !empty($args['customer']) ? $args['customer']->id() : $cart->get('customer_id');
        $custClassIds = [];
        if ($customerId) {
            // if cart is associated with customer, retrieve tax classes for this customer
            $custClassIds = $this->FCom_SalesTax_Model_CustomerTax->orm()
                ->where('customer_id', $customerId)->find_many_assoc('id', 'customer_class_id');
        }
        if (!$custClassIds) {
            // otherwise use configuration for default guest tax class
            $defCustClassId = $this->BConfig->get('modules/FCom_SalesTax/default_guest_class_id');
            $custClassIds = [$defCustClassId];
        }

        $custClassRules = $this->FCom_SalesTax_Model_RuleCustomerClass->orm()
            ->where_in('customer_class_id', $custClassIds)->find_many();
        if (!$custClassRules) {
            // If no Rules for these class Rules found, no further calculations necessary
            return;
        }

        ///////// PRODUCT TAX CLASSES

        // Collect product IDs used in this cart
        $pIds = [];
        foreach ($cart->items() as $item) {
            $pIds[] = $item->get('product_id');
        }

        // Get default product tax class
        $defProdClassId = $this->BConfig->get('modules/FCom_SalesTax/default_product_class_id');
        // Retrieve tax classes for products in cart
        $prodTaxClasses = $this->FCom_SalesTax_Model_ProductTax->orm()
            ->where_in('product_id', $pIds)->find_many();

        // Group cart items by tax class
        $itemsByProdClass = [];
        foreach ($cart->items() as $item) {
            $assigned = false;
            foreach ($prodTaxClasses as $pt) {
                if ($item->get('product_id') === $pt->get('product_id')) {
                    $assigned = true;
                    $itemsByProdClass[$pt->get('product_class_id')][$item->id()] = $item;
                }
            }
            if (!$assigned) {
                // if product is not associated with tax classes, use default tax class configuration
                $itemsByProdClass[$defProdClassId][$item->id()] = $item;
            }
        }

        $prodClassRules = $this->FCom_SalesTax_Model_RuleProductClass->orm()
            ->where_in('product_class_id', array_keys($itemsByProdClass))->find_many();
        if (!$prodClassRules) {
            // If no Rules for these class Rules found, no further calculations necessary
            return;
        }

        /////////// RULES

        // Combine classes and zones into rules
        $rulesMatch = [];
        foreach ($ruleZones as $r) {
            $rulesMatch[$r->get('rule_id')]['zones'][$r->id()] = $r;
        }
        foreach ($custClassRules as $r) {
            $rulesMatch[$r->get('rule_id')]['customer_classes'][$r->get('customer_class_id')] = $r;
        }
        foreach ($prodClassRules as $r) {
            $rulesMatch[$r->get('rule_id')]['product_classes'][$r->get('product_class_id')] = $r;
        }

        $rules = $this->FCom_SalesTax_Model_Rule->orm()->where(['OR' => [
            'id' => array_keys($rulesMatch),
            ['AND', 'match_all_zones' => 1, 'match_all_customer_classes' => 1, 'match_all_product_classes' => 1],
        ]])->find_many_assoc('id');

        foreach ($rulesMatch as $rId => $r) {
            if (empty($r['zones']) && 0 == $rules[$rId]->get('match_all_zones')
                || empty($r['customer_classes']) && 0 == $rules[$rId]->get('match_all_customer_classes')
                || empty($r['product_classes']) && 0 == $rules[$rId]->get('match_all_product_classes')
            ) {
                unset($rulesMatch[$rId], $rules[$rId]);
            }
        }
        if (!$rules) {
            // No matching rules found
            return;
        }

        $totalTaxAmount = 0;
        $taxDetails = [];
        foreach ($rules as $rule) {

        }

        $cart->set('tax_amount', 11);
        $cart->add('grand_total', 11);
    }
}