<?php

class FCom_MultiSite extends BClass
{
    static public function bootstrap()
    {

    }

    static public function beforeBootstrap()
    {
        $siteData = FCom_MultiSite_Model_Site::i()->findByDomain();
        if (!$siteData) {
            return;
        }
        //TODO: implement relevant updates to the environment based on the current site data
    }
}