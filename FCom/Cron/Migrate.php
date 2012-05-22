<?php

class FCom_Cron_Migrate extends BClass
{
    public function run()
    {
        BMigrate::i()->install('0.1.0', array($this, 'install'));
    }

    public function install()
    {
        FCom_Cron_Model_Task::i()->install();
    }
}