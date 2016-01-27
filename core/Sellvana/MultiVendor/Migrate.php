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
}