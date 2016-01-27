<?php

class FCom_Core_Model_ExternalConfig extends FCom_Core_Model_Abstract
{
    protected static $_table               = 'fcom_external_config';
    protected static $_origClass           = __CLASS__;

    protected static $_importExportProfile = 'THIS.importExportProfile';

    public function importExportProfile()
    {
        $profile = [
            'unique_key' => ['source_type', 'path'],
        ];
        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiSite')) {
            $profile['related'] = [
                'site_id' => 'Sellvana_MultiSite_Model_Site.id'
            ];
            $profile['unique_key'][] = 'site_id';
        }

        return $profile;
    }
}