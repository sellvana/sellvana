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
                foreach($module->translations as $trfile) {
                    $data[] = array('module' => $module->name, 'file' => $trfile);
                }
            }
        }
        //print_r($data);exit;
        //exit;
        $config['grid']['data'] = $data;
        return $config;
    }
/*
    public function formViewBefore($args)
    {

        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'title' => $m->id ? 'Edit Product Field: '.$m->field_nice_name : 'Create New Product Field',
        ));
    }

    public function action_form__POST()
    {
        $r = BRequest::i();
        $class = $this->_modelClass;
        $id = $r->params('id', true);

    }
*/
}