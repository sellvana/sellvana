<?php

/**
 * Class Sellvana_MultiVendor_Migrate
 *
 * @property Sellvana_MultiVendor_Model_Vendor $Sellvana_MultiVendor_Model_Vendor
 * @property Sellvana_MultiVendor_Model_VendorProduct $Sellvana_MultiVendor_Model_VendorProduct
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */

class Sellvana_MultiVendor_Migrate extends BClass
{
    public function install__0_1_0()
    {
        $tVendor = $this->Sellvana_MultiVendor_Model_Vendor->table();
        $tProductVendor = $this->Sellvana_MultiVendor_Model_VendorProduct->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();

        $this->BDb->ddlTableDef($tVendor, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'vendor_name' => 'varchar(255)',
                'notify_type' => "varchar(10) default 'realtime'", // no
                'email_support' => 'varchar(128)',
                'email_notify' => 'varchar(128)',
                'data_serialized' => 'text default null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime default null',
            ],
            BDb::PRIMARY => '(id)',
        ]);

        $this->BDb->ddlTableDef($tProductVendor, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'vendor_id' => 'int unsigned not null',
                'vendor_sku' => 'varchar(50) default null',
                'vendor_product_name' => 'text default null',
                'data_serialized' => 'text default null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime default null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_product_vendor' => '(product_id, vendor_id)',
            ],
            BDb::CONSTRAINTS => [
                'product' => ['product_id', $tProduct],
                'vendor' => ['vendor_id', $tVendor],
            ],
        ]);
    }

    public function install__0_6_0_0__0_6_1_0()
    {
        $tPo = $this->Sellvana_MultiVendor_Model_PurchaseOrder->table();
        $tPoItem = $this->Sellvana_MultiVendor_Model_PurchaseOrder_Item->table();
        $tOrder = $this->Sellvana_Sales_Model_Order->table();
        $tOrderItem = $this->Sellvana_Sales_Model_Order_Item->table();
        $tVendor = $this->Sellvana_MultiVendor_Model_Vendor->table();

        $this->BDb->ddlTableDef($tPo, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'order_id' => 'int unsigned',
                'vendor_id' => 'int unsigned not null',
                'unique_id' => 'varchar(40) no null',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [

            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder],
                'vendor' => ['vendor_id', $tVendor],
            ],
        ]);
        $this->BDb->ddlTableDef($tPoItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'po_id' => 'int unsigned not null',
                'order_item_id' => 'int unsigned',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [

            ],
            BDb::CONSTRAINTS => [
                'po' => ['po_id', $tPo],
                'order_item' => ['order_item_id', $tOrderItem],
            ],
        ]);
    }
}