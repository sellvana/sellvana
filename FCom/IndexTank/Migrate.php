<?php

class FCom_IndexTank_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        FCom_IndexTank_Index_Product::i()->install();
    }
}