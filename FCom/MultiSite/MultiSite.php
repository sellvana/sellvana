<?php

class FCom_MultiSite extends BClass
{
    static public function bootstrap()
    {

    }

    static public function beforeBootstrap()
    {
        if (($site = FCom_MultiSite_Model_Site::i()->findByDomain())) {
            $site->updateEnvironment();
        }
    }
}