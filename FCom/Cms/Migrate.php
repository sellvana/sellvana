<?php

class FCom_Cms_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.1', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
    }

    public function install()
    {
        FCom_Cms_Model_Nav::i()->install();
        FCom_Cms_Model_Page::i()->install();
        FCom_Cms_Model_PageHistory::i()->install();
        FCom_Cms_Model_Block::i()->install();
        FCom_Cms_Model_BlockHistory::i()->install();
    }

    public function upgrade_0_1_1()
    {
        $tNav = FCom_Cms_Model_Nav::table();

        BDb::run("
ALTER TABLE `fulleron`.`fcom_cms_nav`
    ADD COLUMN `node_type` VARCHAR(20) NULL AFTER `num_descendants`
    , ADD COLUMN `reference` VARCHAR(255) NULL AFTER `node_type`
    , ADD COLUMN `contents` TEXT NULL AFTER `reference`
    , ADD COLUMN `layout_update` TEXT NULL AFTER `contents`;
        ");
    }
}