<?php

class FCom_Email_Migrate extends BClass
{
    public static function run()
    {
        BMigrate::i()->install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        FCom_Email_Model_Pref::i()->install();
    }
}