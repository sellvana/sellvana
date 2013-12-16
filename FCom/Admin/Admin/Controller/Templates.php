<?php

class FCom_Admin_Admin_Controller_Templates extends FCom_Admin_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'system/templates';
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'templates';
    protected $_gridTitle = 'Frontend Templates';
    protected $_recordName = 'Template';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40, 'overflow' => true),
            //array('name' => 'id', 'label' => 'ID', 'index' => 'm.id', 'width' => 55, 'hidden' => true, 'cell' => 'integer'),
            array('name' => 'view_name', 'label' => 'View Name', 'index' => 'view_name', 'width' => 100, 'overflow' => true),
            array('name' => 'file_ext', 'label' => 'File Ext.', 'index' => 'file_ext', 'width' => 50),
            array('name' => 'module_name', 'label' => 'Module', 'index' => 'module_name', 'width' => 100),
            array('name'=>'_actions', 'label'=>'Actions', 'sortable'=>false, 'data'=>array(
                'edit' => true,
                'revert' => array('caption' => 'Revert'),
            ))
        );

        $config['state'] = array('s' => 'view_name');

        $layout = $this->getAreaLayout();
        $data = array();
        foreach ($layout->getAllViews() as $view) {
            $row = array(
                'view_name' => $view->param('view_name'),
                'file_ext' => $view->param('file_ext'),
                'module_name' => $view->param('module_name'),
            );
            $data[] = $row;
        }
        $config['data'] = $data;
        $config['data_mode'] = 'local';
        $config['filters'] = array(
            array('field' => 'name', 'type' => 'text'),
            array('field' => 'run_level_core', 'type' => 'multiselect')
        );
        $config['actions'] = array(
            'revert' => array('caption'=>'Revert'),
        );
        $config['events'] = array('revert', 'mass-revert');

        //$config['state'] =array(5,6,7,8);
        return $config;
    }

    public function getAreaLayout($area = 'FCom_Frontend')
    {
        $areaDir = str_replace('FCom_', '', $area);
        $modules = BModuleRegistry::i()->getAllModules();
        $viewDirs = array();
        $layout = BLayout::i(true);
        foreach ($modules as $mod) {
            /** @var BModule $mod */
            $auto = array_flip((array)$mod->auto_use);
            if (isset($auto['all']) || isset($auto['views'])) {
                $dir = $mod->root_dir.'/views';
                if (is_dir($dir)) {
                    $layout->addAllViews($dir, '', $mod);
                }
                $dir = $mod->root_dir.'/'.$areaDir.'/views';
                if (is_dir($dir)) {
                    $layout->addAllViews($dir, '', $mod);
                }
            }
        }
        return $layout;
    }

    public function action_template()
    {
        $viewName = BRequest::i()->get('view_name');
        $layout = $this->getAreaLayout();
        $view = $layout->getView('view_name');
        $viewFile = $view->getTemplateFileName();

    }

    public function action_template__POST()
    {
        $r = BRequest::i();
        $viewName = $r->get('view_name');
        $layout = $this->getAreaLayout();
        $view = $layout->getView('view_name');
        $viewFile = $view->getTemplateFileName();

        if ($r->request('revert')) {
            //unlink()
        }
    }
}
