<?php

class FCom_Cms_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        FCom_Cms_Model_Nav::i()->install();
        FCom_Cms_Model_Page::i()->install();
        FCom_Cms_Model_PageHistory::i()->install();
        FCom_Cms_Model_Block::i()->install();
        FCom_Cms_Model_BlockHistory::i()->install();
    }
}