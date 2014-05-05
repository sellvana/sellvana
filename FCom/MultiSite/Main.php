<?php

class FCom_MultiSite_Main extends BClass
{
    static public function beforeBootstrap()
    {
        if (BConfig::i()->get('install_status') === 'installed') {
            $siteData = FCom_MultiSite_Model_Site::i()->findByDomain();
            if (!$siteData) {
                return;
            }
        }
        //TODO: implement relevant updates to the environment based on the current site data
    }
}