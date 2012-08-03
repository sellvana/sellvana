<?php

class FCom_Translation_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'translations';
    //protected $_modelClass = 'FCom_IndexTank_Model_ProductField';
    //protected $_mainTableAlias = 'pf';

    public function gridConfig()
    {
        $formUrl = BApp::href("translations/form");
        $config = array();
        $columns = array(
            'module'=>array('label'=>'Module', 'width'=>250, 'editable'=>true),
            'locale' => array('label'=>'Locale', 'width'=>250, 'editable'=>true),
            'file'=>array('label'=>'File', 'width'=>60, 'editable'=>true)
        );

        $config['grid']['id'] = 'translation';
        $config['grid']['autowidth'] = false;
        $config['grid']['caption'] = 'All translations';
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
        $modules = BModuleRegistry::getAllModules();
        foreach($modules as $module){
            if (!empty($module->translations)) {
                foreach($module->translations as $trlocale => $trfile) {
                    $data[] = array(
                        'module' => $module->name,
                        'locale' => $trlocale,
                        'file' => $trfile,
                        'id'=>$module->name.'/'.$trfile);
                }
            }
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