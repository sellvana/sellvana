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
        $modulesInstalled = BModuleRegistry::getAllModules();
        foreach($modules as $module){
            $notice = 'Get module';
            if (!empty($modulesInstalled[$module['name']])) {
                $notice = version_compare($module['version'], $modulesInstalled[$module['name']]->version) > 0 ? 'Need upgrade!' : 'Downloaded';
            }
            $data[] = array(
                'id' => $module['name'],
                'module' => $module['name'],
                'version' => $module['version'],
                'local_version' => $modulesInstalled[$module['name']]->version,
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
        $id = BRequest::i()->params('id', true);
        list($module, $file) = explode("/", $id);

        if (!$file) {
            BDebug::error('Invalid Filename: '.$id);
        }
        $moduleClass = BApp::m($module);
        $filename = $moduleClass->baseDir().'/i18n/'.$file;

        $model = new stdClass();
        $model->id = $id;
        $model->source = file_get_contents($filename);
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
                'save' => '<button type="submit" class="st1 sz2 btn" onclick="return adminForm.saveAll(this)"><span>Save</span></button>',
            ),
        ));
        BPubSub::i()->fire(static::$_origClass.'::formViewBefore', $args);
    }

    public function action_form__POST()
    {
        if (empty($_POST)) {
            return;
        }
        $id = $_POST['file'];

        list($module, $file) = explode("/", $id);

        if (!$file) {
            BDebug::error('Invalid Filename: '.$id);
        }
        $moduleClass = BApp::m($module);
        if (!is_object($moduleClass)) {
            BDebug::error('Invalid Module name: '.$id);
        }

        $filename = $moduleClass->baseDir().'/i18n/'.$file;

        if (!is_writable($filename)) {
            BDebug::error('Not writeable filename: '.$filename);
        }

        if (!empty($_POST['source'])) {
            file_put_contents($filename, $_POST['source']);
        }

        BResponse::i()->redirect(BApp::href($this->_gridHref));
    }

}