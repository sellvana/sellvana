<?php

class FCom_Catalog_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.1', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
        BMigrate::upgrade('0.1.1', '0.1.2', array($this, 'upgrade_0_1_2'));
    }

    public function install()
    {
        FCom_Catalog_Model_Family::i()->install();
        FCom_Catalog_Model_Product::i()->install();
        FCom_Catalog_Model_ProductMedia::i()->install();
        FCom_Catalog_Model_ProductLink::i()->install();
        FCom_Catalog_Model_ProductFamily::i()->install();
        FCom_Catalog_Model_Category::i()->install();
        FCom_Catalog_Model_CategoryProduct::i()->install();
    }

    public function upgrade_0_1_1()
    {
        FCom_Catalog_Model_Family::i()->install();
        FCom_Catalog_Model_ProductMedia::i()->install();
        FCom_Catalog_Model_ProductLink::i()->install();
        FCom_Catalog_Model_ProductFamily::i()->install();
    }

    public function upgrade_0_1_2()
    {
        FCom_Catalog_Model_Product::upgrade_0_1_2();
    }
    
}