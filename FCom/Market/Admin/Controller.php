<?php

/**
 *todo:
 *  1. Show modules list from remote server
 *  2. Show module info
 *  3. Install module
 */

class FCom_Market_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'market';
    //protected $_modelClass = '';
    //protected $_mainTableAlias = '';

    public function gridConfig()
    {
        $formUrl = BApp::href($this->_gridHref.'/form');
        $config = array();
        $columns = array(
            'id'=>array('label'=>'id', 'width'=>250, 'editable'=>true),
            'module'=>array('label'=>'Module', 'width'=>250, 'editable'=>true),
            'description' => array('label'=>'Description', 'width'=>250, 'editable'=>true),
            'version' => array('label'=>'Version', 'width'=>250, 'editable'=>true),
            'local_version' => array('label'=>'Local Version', 'width'=>250, 'editable'=>true),
            'notice' => array('label'=>'Notice', 'width'=>250, 'editable'=>true)
        );

        $config['grid']['id'] = 'modules';
        $config['grid']['autowidth'] = false;
        $config['grid']['caption'] = 'All modules';
        $config['grid']['multiselect'] = false;
        $config['grid']['height'] = '100%';
        $config['grid']['columns'] = $columns;
        $config['navGrid'] = array('add'=>false, 'edit'=>true, 'del'=>false);
        $config['grid']['datatype'] = 'local';
        $config['grid']['editurl'] = '';
        $config['grid']['url'] = '';
        $config['custom'] = array('personalize'=>true, 'autoresize'=>true, 'hashState'=>true, 'export'=>true, 'dblClickHref'=>$formUrl.'?id=');

        //$data = BLocale::getTranslations();
        //print_r($data);exit;
        $modules = FCom_Market_Api::i()->getAllModules();
        $modulesInstalled = FCom_Market_Model_Modules::i()->getAllModules();
        
        foreach($modules as $module){
            $notice = 'Get module';
            $localVersion = '';
            if (!empty($modulesInstalled[$module['mod_name']])) {
                $notice = version_compare($module['version'], $modulesInstalled[$module['mod_name']]->version) > 0 ? 'Need upgrade!' : 'Downloaded';
                $localVersion = $modulesInstalled[$module['mod_name']]->version;
            }
            $data[] = array(
                'id' => $module['mod_name'],
                'module' => $module['name'],
                'version' => $module['version'],
                'local_version' => $localVersion,
                'description' => $module['description'],
                'notice' => $notice
            );

        }
        //print_r($data);exit;
        //exit;
        $config['grid']['data'] = $data;
        return $config;
    }

    public function action_form()
    {
        $moduleName = BRequest::i()->params('id', true);

        $modulesList = FCom_Market_Api::i()->getAllModules();

        $module = $modulesList[$moduleName];
        $model = new stdClass();
        $model->id = $moduleName;
        $model->module = $module;

        $modulesInstalled = FCom_Market_Model_Modules::i()->getAllModules();
        $needUpgrade = false;
        $localVersion = '';
        if (!empty($modulesInstalled[$module['mod_name']])) {
            $needUpgrade = version_compare($module['version'], $modulesInstalled[$module['mod_name']]->version) > 0 ? true : false;
            $localVersion = $modulesInstalled[$module['mod_name']]->version;
        }
        $model->local_version = $localVersion;
        $model->need_upgrade = $needUpgrade;

        $view = $this->view($this->_formViewName)->set('model', $model);
        $this->formViewBefore(array('view'=>$view, 'model'=>$model));

        $this->layout($this->_formLayoutName);
        $this->processFormTabs($view, $model, 'edit');
    }

    public function formViewBefore($args)
    {
        $m = $args['model'];
        $args['view']->set(array(
            'form_id' => BLocale::transliterate($this->_formLayoutName),
            'form_url' => BApp::href($this->_formHref).'?id='.$m->id,
            'actions' => array(
                'back' => '<button type="button" class="st3 sz2 btn" onclick="location.href=\''.BApp::href($this->_gridHref).'\'"><span>Back to list</span></button>',
            ),
        ));
        BPubSub::i()->fire(static::$_origClass.'::formViewBefore', $args);
    }

    public function action_install()
    {
        $moduleName = BRequest::i()->params('id', true);

        $filename = FCom_Market_Api::i()->download($moduleName);
        $res = FCom_Market_Api::i()->extract($filename);

        if (!$res) {
            BSession::i()->addMessage("Permissions denied to write into storage dir: ".BConfig::i()->get('fs/storage_dir'));
            BResponse::i()->redirect(BApp::href("market/form")."?id={$moduleName}");
        }

        if ($res) {
            $modExist = FCom_Market_Model_Modules::orm()->where('mod_name', $moduleName)->find_one();
            if (!$modExist) {
                $modulesList = FCom_Market_Api::i()->getAllModules();
                $module = $modulesList[$moduleName];
                $data = array('name' => $module['name'], 'mod_name' => $module['mod_name'],
                    'version' => $module['version'], 'description' => $module['description']);
                FCom_Market_Model_Modules::create($data)->save();
            }
        }

        $this->forward('index');
    }

}