<?php

class FCom_Admin_Controller_Backups extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'system/backups';
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'backups';
    protected $_gridTitle = 'Backups';
    protected $_recordName = 'Backup';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = array(
            array('type'=>'multiselect'),
            //array('name' => 'id', 'label' => 'ID', 'index' => 'm.id', 'width' => 55, 'hidden' => true, 'cell' => 'integer'),
            array('name' => 'file_name', 'label' => 'File Name', 'index' => 'file_name', 'width' => 100, 'overflow' => true),
            array('name' => 'create_at', 'label' => 'Created At', 'index' => 'create_at', 'width' => 100),
            array('type'=>'btn_group', 'name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'buttons' => array(
                array('name'=>'delete', 'caption' => 'Remove/Revert'),
            )),
        );

        $config['state'] = array('s' => 'create_time', 'sd' => 'desc');

        $data = array();

        $config['data'] = $data;
        $config['data_mode'] = 'local';

        $config['filters'] = array(
            array('field' => 'file_name', 'type' => 'text'),
            array('field' => 'create_at', 'type' => 'date-range'),
        );
        $config['actions'] = array(
            'delete' => array('caption'=>'Delete'),
        );
        $config['events'] = array('delete', 'mass-delete');

        //$config['state'] =array(5,6,7,8);
        return $config;
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
