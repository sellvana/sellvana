<?php

class FCom_Admin_Controller_Templates extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'system/templates';
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'templates';
    protected $_gridTitle = 'Frontend Templates';
    protected $_recordName = 'Template';
    protected $_navPath = 'system/templates';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = array(
            array('type'=>'row_select'),
            //array('name' => 'id', 'label' => 'ID', 'index' => 'm.id', 'width' => 55, 'hidden' => true, 'cell' => 'integer'),
            array('name' => 'view_name', 'label' => 'View Name', 'index' => 'view_name', 'width' => 100, 'overflow' => true),
            array('name' => 'file_ext', 'label' => 'File Ext.', 'index' => 'file_ext', 'width' => 50),
            array('name' => 'module_name', 'label' => 'Module', 'index' => 'module_name', 'width' => 100),
            array('type'=>'btn_group',
				'buttons' => array(
								array('name'=>'edit', 'href'=>BApp::href('templates/form?id='), 'col'=>'view_name'),
								array('name'=>'delete', 'caption' => 'Remove/Revert')
								)
			),
		);

        $config['state'] = array('s' => 'view_name');

        $layout = $this->getAreaLayout();
        $data = array();
        foreach ($layout->getAllViews() as $view) {
            $row = array(
                'view_name' => $view->param('view_name'),
                'file_ext' => $view->param('file_ext'),
                'module_name' => $view->param('module_name')->name,
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
            'delete' => array('caption'=>'Remove/Revert'),
        );
        $config['events'] = array('delete', 'mass-delete');

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

    public function action_form()
    {
        $tplViewName = BRequest::i()->get('id');
        $areaLayout = $this->getAreaLayout();
        $tplView = $areaLayout->getView($tplViewName);
        $tplViewFile = $tplView->getTemplateFileName();
        $tplContents = file_get_contents($tplViewFile);

        $model = new BData(array(
            'id' => $tplViewName,
            'view_name' => $tplViewName,
            'view_contents' => $tplContents,
        ));

        $this->formMessages();
        $view = $this->view($this->_formViewName)->set('model', $model);
        $this->formViewBefore(array('view'=>$view, 'model'=>$model));

        $actions = $view->get('actions');
        $actions['delete'] = '<button type="submit" class="btn btn-warning" name="do" value="DELETE" onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>' .  BLocale::_('Remove/Revert') . '</span></button>';
        $view->set('actions', $actions);

        $this->layout($this->_formLayoutName);
        $view->set('tab_view_prefix', $this->_formViewPrefix);
        if ($this->_useDefaultLayout) {
            BLayout::i()->applyLayout('default_form');
        }
        $this->processFormTabs($view, $model, 'edit');
        if ($this->_formTitle && ($head = $this->view('head'))) {
            $head->addTitle($this->_formTitle);
        }
    }

    public function action_form__POST()
    {
        $r = BRequest::i();
        $viewName = $r->get('view_name');
        $layout = $this->getAreaLayout();
        $view = $layout->getView('view_name');
        $viewFile = $view->getTemplateFileName();

        if ($r->post('do')==='DELETE') {
            echo 'DELETE'; exit;
        }
        var_dump($r->post()); exit;
    }
}
