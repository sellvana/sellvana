<?php

class FCom_Core_Migrate extends BClass
{
    public function run()
    {
        BMigrate::i()->install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        FCom_Core_Model_MediaLibrary::i()->install();
    }
}