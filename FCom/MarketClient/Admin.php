<?php

class FCom_MarketClient_Admin extends BClass
{
    static public function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission(array(
            'market_client' => 'Market Client',
            'market_client/public' => 'Public',
            'market_client/remote' => 'Remote',
        ));
    }

    public static function hookFindModulesForUpdates($args)
    {
        $modulesNotification = &$args['modulesNotification'];
        //find modules which have updates
        $res = FCom_MarketClient_Model_Modules::i()->orm('mm')
            ->join('FCom_Core_Model_Module', array('m.id','=','mm.core_module_id'), 'm')
            ->where('is_upgrade_available', 1)->find_many();
        $data = array();
        foreach($res as $r) {
            $obj = new stdClass();
            $obj->url = 'marketclient/form?id='.$r->id();
            $obj->module = $r->mod_name;
            $obj->text = $r->mod_name . ' have a new version';
            $data[] = $obj;
        }
        if (!empty($data)) {
            $modulesNotification['Updates'] = $data;
        }

        // find modules with dependencies errors
        //todo: probably need to move this code somewhere else
        $modules = BModuleRegistry::i()->getAllModules();
        $data = array();
        foreach($modules as $modName => $mod) {
            if (!empty($mod->errors)) {
                foreach($mod->errors as $error) {
                    $obj = new stdClass();
                    $obj->url = 'modules';
                    $obj->module = $modName;
                    $obj->text = $modName .' have '.$error['type'].' conflict with '.$error['mod'];
                    $data[] = $obj;
                }
            }
        }
        if (!empty($data)) {
            $modulesNotification['Errors'] = $data;
        }

    }

    public static function onModuleGridView($args)
    {
        $grid = $args['view']->get('grid');

        $grid['config']['columns'] = BUtil::arrayInsert($grid['config']['columns'], array(
            array('name' => 'market_version', 'label' => 'Available', 'width' => 80, 'overflow' => true),
        ), 'arr.before.name==version');

        $args['view']->set('grid', $grid);
    }
}
