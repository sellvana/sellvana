<?php

class FCom_Market_Model_Modules extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_market_modules';

    public function getAllModules()
    {
        $modules = array();
        $modList = $this->orm()->find_many();
        foreach($modList as $mod) {
            $modules[$mod->mod_name] = $mod;
        }
        return $modules;
    }

    /**
     * Checkout remote modules info
     * @param type $args
     */
    static public function jqGridData($args)
    {
        $data = &$args['data'];


        $modules = FCom_Market_MarketApi::i()->getMyModules();
        foreach ($modules as $index => $mod) {
            $modules[$mod['mod_name']] = $mod;
        }

        foreach($data['rows'] as $index => $d) {
            $remote = false;
            if (!empty($modules[$d['mod_name']])) {
                $remote = $modules[$d['mod_name']];
            }
            $notice = 'Get module';
            $latestVersion = '';
            if ($remote) {
                if (version_compare($remote['version'], $d['version']) == 0) {
                    $notice = 'Latest version installed';
                } else {
                    $notice = version_compare($remote['version'], $d['version']) > 0 ? 'Need upgrade!' : 'Downloaded';
                }
                $latestVersion = $remote['version'];
            }
            $data['rows'][$index]['notice'] = $notice;
            $data['rows'][$index]['latest_version'] = $latestVersion;
        }
    }
}
