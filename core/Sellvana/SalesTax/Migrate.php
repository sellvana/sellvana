<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_SalesTax_Migrate
 *
 * @property Sellvana_SalesTax_Model_CustomerClass $Sellvana_SalesTax_Model_CustomerClass
 * @property Sellvana_SalesTax_Model_CustomerTax $Sellvana_SalesTax_Model_CustomerTax
 * @property Sellvana_SalesTax_Model_ProductClass $Sellvana_SalesTax_Model_ProductClass
 * @property Sellvana_SalesTax_Model_ProductTax $Sellvana_SalesTax_Model_ProductTax
 * @property Sellvana_SalesTax_Model_Zone $Sellvana_SalesTax_Model_Zone
 * @property Sellvana_SalesTax_Model_Rule $Sellvana_SalesTax_Model_Rule
 * @property Sellvana_SalesTax_Model_RuleCustomerClass $Sellvana_SalesTax_Model_RuleCustomerClass
 * @property Sellvana_SalesTax_Model_RuleProductClass $Sellvana_SalesTax_Model_RuleProductClass
 * @property Sellvana_SalesTax_Model_RuleZone $Sellvana_SalesTax_Model_RuleZone
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_SalesTax_Model_CustomerGroupTax $Sellvana_SalesTax_Model_CustomerGroupTax
 * @property Sellvana_CustomerGroups_Model_Group $Sellvana_CustomerGroups_Model_Group
 */
class Sellvana_SalesTax_Migrate extends BClass
{
    public function install__0_1_2()
    {
        $tCustomerClass = $this->Sellvana_SalesTax_Model_CustomerClass->table();
        $tCustomerTax = $this->Sellvana_SalesTax_Model_CustomerTax->table();
        $tProductClass = $this->Sellvana_SalesTax_Model_ProductClass->table();
        $tProductTax = $this->Sellvana_SalesTax_Model_ProductTax->table();
        $tZone = $this->Sellvana_SalesTax_Model_Zone->table();
        $tRule = $this->Sellvana_SalesTax_Model_Rule->table();
        $tRuleCustomerClass = $this->Sellvana_SalesTax_Model_RuleCustomerClass->table();
        $tRuleProductClass = $this->Sellvana_SalesTax_Model_RuleProductClass->table();
        $tRuleZone = $this->Sellvana_SalesTax_Model_RuleZone->table();

        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();

        $this->BDb->ddlTableDef($tCustomerClass, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'title' => 'varchar(100)',
                'notes' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_title' => 'UNIQUE (title)',
            ],
        ]);

        $this->BDb->ddlTableDef($tCustomerTax, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'customer_id' => 'int unsigned not null',
                'customer_class_id' => 'int unsigned not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_customer_class' => 'UNIQUE (customer_id, customer_class_id)',
            ],
            BDb::CONSTRAINTS => [
                'customer' => ['customer_id', $tCustomer],
                'customer_class' => ['customer_class_id', $tCustomerClass],
            ],
        ]);

        $this->BDb->ddlTableDef($tProductClass, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'title' => 'varchar(100)',
                'notes' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_title' => 'UNIQUE (title)',
            ],
        ]);

        $this->BDb->ddlTableDef($tProductTax, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'product_class_id' => 'int unsigned not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_product_class' => 'UNIQUE (product_id, product_class_id)',
            ],
            BDb::CONSTRAINTS => [
                'product' => ['product_id', $tProduct],
                'product_class' => ['product_class_id', $tProductClass],
            ],
        ]);

        $this->BDb->ddlTableDef($tZone, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'zone_type' => "enum('country', 'region', 'postcode', 'postrange') not null",
                'title' => 'varchar(50) default null',
                'country' => 'char(2) not null',
                'region' => 'varchar(50) default null',
                'postcode_from' => 'varchar(10) default null',
                'postcode_to' => 'varchar(10) default null',
                'zone_rate_percent' => 'decimal(10,4) default null',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_type_country_region_postcode' => '(zone_type, country, region, postcode_from, postcode_to)',
                'IDX_type_country_postcode' => '(zone_type, country, postcode_from, postcode_to)',
            ],
        ]);

        $this->BDb->ddlTableDef($tRule, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'title' => 'varchar(50) default null',
                'match_all_zones' => 'tinyint not null default 0',
                'match_all_customer_classes' => 'tinyint not null default 0',
                'match_all_product_classes' => 'tinyint not null default 0',
                'compound_priority' => 'smallint not null default 0',
                'sort_order' => 'smallint not null default 0',
                'apply_to_shipping' => 'tinyint not null default 0',
                'rule_rate_percent' => 'decimal(10,4) default null',
                'fpt_amount' => 'decimal(12,2) default null',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_title' => 'UNIQUE (title)',
            ],
        ]);

        $this->BDb->ddlTableDef($tRuleCustomerClass, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'rule_id' => 'int unsigned not null',
                'customer_class_id' => 'int unsigned not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_rule_customer_class' => 'UNIQUE (rule_id, customer_class_id)',
            ],
            BDb::CONSTRAINTS => [
                'rule' => ['rule_id', $tRule],
                'customer_class' => ['customer_class_id', $tCustomerClass],
            ],
        ]);

        $this->BDb->ddlTableDef($tRuleProductClass, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'rule_id' => 'int unsigned not null',
                'product_class_id' => 'int unsigned not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_rule_product_class' => 'UNIQUE (rule_id, product_class_id)',
            ],
            BDb::CONSTRAINTS => [
                'rule' => ['rule_id', $tRule],
                'product_class' => ['product_class_id', $tProductClass],
            ],
        ]);

        $this->BDb->ddlTableDef($tRuleZone, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'rule_id' => 'int unsigned not null',
                'zone_id' => 'int unsigned not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_rule_zone' => 'UNIQUE (rule_id, zone_id)',
            ],
            BDb::CONSTRAINTS => [
                'rule' => ['rule_id', $tRule],
                'zone' => ['zone_id', $tZone],
            ],
        ]);

        if ($this->Sellvana_SalesTax_Model_CustomerClass->loadWhere(['title' => 'Retail'])) {
            return;
        }
        // Default tax classes
        $retailClass  = $this->Sellvana_SalesTax_Model_CustomerClass->create(['title' => 'Retail'])->save();
        $taxableClass = $this->Sellvana_SalesTax_Model_ProductClass->create(['title' => 'Taxable Goods'])->save();
        $exemptClass  = $this->Sellvana_SalesTax_Model_ProductClass->create(['title' => 'Exempt'])->save();

        // Sample tax rule for NY State
        $rule = $this->Sellvana_SalesTax_Model_Rule->create(['title' => 'NY State', 'rule_rate_percent' => 4])->save();
        $zone = $this->Sellvana_SalesTax_Model_Zone->create(['zone_type' => 'region', 'country' => 'US', 'region' => 'NY'])->save();
        $this->Sellvana_SalesTax_Model_RuleZone->create(['rule_id' => $rule->id(), 'zone_id' => $zone->id()])->save();
        $this->Sellvana_SalesTax_Model_RuleCustomerClass->create(['rule_id' => $rule->id(), 'customer_class_id' => $retailClass->id()])->save();
        $this->Sellvana_SalesTax_Model_RuleProductClass->create(['rule_id' => $rule->id(), 'product_class_id' => $taxableClass->id()])->save();

        // Sample tax rule for NY City
        $rule = $this->Sellvana_SalesTax_Model_Rule->create(['title' => 'NY City', 'rule_rate_percent' => 8.875])->save();
        $zoneHlp = $this->Sellvana_SalesTax_Model_Zone;
        $ruleZoneHlp = $this->Sellvana_SalesTax_Model_RuleZone;
        foreach ([[10001, 10292], 11217, 11411, [11416, 11417], 11429, 11692] as $postcode) {
            $zone = $zoneHlp->create([
                'zone_type' => is_array($postcode) ? 'postrange' : 'postcode',
                'country' => 'US',
                'region' => null,
                'postcode_from' => is_array($postcode) ? $postcode[0] : $postcode,
                'postcode_to' => is_array($postcode) ? $postcode[1] : $postcode,
            ])->save();
            $ruleZoneHlp->create(['rule_id' => $rule->id(), 'zone_id' => $zone->id()])->save();
        }
        $this->Sellvana_SalesTax_Model_RuleCustomerClass->create(['rule_id' => $rule->id(), 'customer_class_id' => $retailClass->id()])->save();
        $this->Sellvana_SalesTax_Model_RuleProductClass->create(['rule_id' => $rule->id(), 'product_class_id' => $taxableClass->id()])->save();
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tRule = $this->Sellvana_SalesTax_Model_Rule->table();
        $this->BDb->ddlTableDef($tRule, [
            BDb::COLUMNS => [
                'apply_to_shipping' => 'tinyint not null default 0',
            ],
        ]);
        $this->Sellvana_SalesTax_Model_ProductClass->load('Standard', 'title')->set('title', 'Taxable Goods')->save();
        $this->Sellvana_SalesTax_Model_ProductClass->create(['title' => 'Exempt'])->save();
        $this->Sellvana_SalesTax_Model_Rule->load('NY City', 'title')->set('rule_rate_percent', 8.875)->save();
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tRule = $this->Sellvana_SalesTax_Model_Rule->table();
        $this->BDb->ddlTableDef($tRule, [
            BDb::COLUMNS => [
                'fpt_amount' => 'decimal(12,2) default null',
            ],
        ]);
    }

    public function after__Sellvana_CustomerGroups__0_5_0_1()
    {
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tCustomerClass = $this->Sellvana_SalesTax_Model_CustomerClass->table();
        $tCustomerGroup = $this->Sellvana_CustomerGroups_Model_Group->table();
        $tCustomerGroupTax = $this->Sellvana_SalesTax_Model_CustomerGroupTax->table();

        $this->BDb->ddlTableDef($tCustomerGroupTax, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'customer_group_id' => 'int unsigned not null',
                'customer_class_id' => 'int unsigned not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_customer_class' => 'UNIQUE (customer_group_id, customer_class_id)',
            ],
            BDb::CONSTRAINTS => [
                'customer_group' => ['customer_group_id', $tCustomerGroup],
                'customer_class' => ['customer_class_id', $tCustomerClass],
            ],
        ]);

        $this->BDb->ddlTableDef($tCustomer, [
            BDb::COLUMNS => [
                'use_group_tax' => 'tinyint not null default 1',
            ],
        ]);
    }
}