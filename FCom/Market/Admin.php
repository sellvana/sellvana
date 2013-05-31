<?php

class FCom_Market_Admin extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views');
        BEvents::i()
            ->on('BLayout::theme.load.after', 'FCom_Market_Admin::layout')
            ->on('BLayout::hook.hook_modules_notification', 'FCom_Market_Admin.hookFindModulesForUpdates')
        ;

        BRouting::i()
            ->get('/market', 'FCom_Market_Admin_Controller.index')
            ->any('/market/.action', 'FCom_Market_Admin_Controller')

        ;
        if (!BConfig::i()->get('modules/FCom_Market/market_url')) {
            BConfig::i()->set('modules/FCom_Market/market_url', 'http://fulleron.com');
        }
    }

    public function hookFindModulesForUpdates($args)
    {
        $modulesNotification = &$args['modulesNotification'];
        //find modules which have updates
        try {
            if (!BDb::ddlFieldInfo(FCom_Market_Model_Modules::table(), 'need_upgrade')) {
                return;
            }
            $res = FCom_Market_Model_Modules::orm()->where('need_upgrade', 1)->find_many();
        } catch (Exception $e) {
            return;
        }
        $data = array();
        foreach($res as $r) {
            $obj = new stdClass();
            $obj->url = 'market/form?id='.$r->id;
            $obj->module = $r->mod_name;
            $obj->text = $r->mod_name . ' have a new version';
            $data[] = $obj;
        }
        if (!empty($data)) {
            $modulesNotification['Updates'] = $data;
        }

        // find modules with dependencies errors
        //todo: probably need to move this code somewhere else
        $modules = BModuleRegistry::i()->debug();
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

    static public function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'admin/header', 'do'=>array(
                    array('addNav', 'market', array('label'=>'Market', 'pos'=>100)),
                    array('addNav', 'market/market', array('label'=>'Market Center', 'href'=>BApp::href('market/market'))),
                    array('addNav', 'market/index', array('label'=>'My modules', 'href'=>BApp::href('market/index'))),
                )),
            ),
            '/market'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('admin/grid')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'market'))),
                ),
             '/market/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('admin/form')),
                    array('view', 'admin/form', 'set'=>array(
                        'tab_view_prefix' => 'market/',
                    ), 'do'=>array(
                        array('addTab', 'main', array('label'=>'Market', 'pos'=>10))
                    )),
             ),
            '/market/market'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('market/market')),
            ),
        ));
    }
}