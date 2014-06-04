<?php defined('BUCKYBALL_ROOT_DIR') || die();

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

        $config['columns'] = [
            ['type' => 'row_select'],
            //array('name' => 'id', 'label' => 'ID', 'index' => 'm.id', 'width' => 55, 'hidden' => true, 'cell' => 'integer'),
            ['name' => 'file_name', 'label' => 'File Name', 'index' => 'file_name', 'width' => 100, 'overflow' => true],
            ['name' => 'create_at', 'label' => 'Created At', 'index' => 'create_at', 'width' => 100],
            ['type' => 'btn_group',
                'buttons' => [
                    ['name' => 'delete', 'caption' => 'Remove/Revert'],
                ]],
        ];

        $config['state'] = ['s' => 'create_time', 'sd' => 'desc'];

        $data = [];

        $config['data'] = $data;
        $config['data_mode'] = 'local';

        $config['filters'] = [
            ['field' => 'file_name', 'type' => 'text'],
            ['field' => 'create_at', 'type' => 'date-range'],
        ];
        $config['actions'] = [
            'delete' => ['caption' => 'Delete'],
        ];
        $config['events'] = ['delete', 'mass-delete'];

        //$config['state'] =array(5,6,7,8);
        return $config;
    }

    public function action_form()
    {
        $tplViewName = $this->BRequest->get('id');
        $areaLayout = $this->getAreaLayout();
        $tplView = $areaLayout->getView($tplViewName);
        $tplViewFile = $tplView->getTemplateFileName();
        $tplContents = file_get_contents($tplViewFile);

        $model = new BData([
            'id' => $tplViewName,
            'view_name' => $tplViewName,
            'view_contents' => $tplContents,
        ]);

        $this->formMessages();
        $view = $this->view($this->_formViewName)->set('model', $model);
        $this->formViewBefore(['view' => $view, 'model' => $model]);

        $actions = $view->get('actions');
        $actions['delete'] = '<button type="submit" class="btn btn-warning" name="do" value="DELETE" onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>' .  $this->BLocale->_('Remove/Revert') . '</span></button>';
        $view->set('actions', $actions);

        $this->layout($this->_formLayoutName);
        $this->processFormTabs($view, $model, 'edit');
        if ($this->_formTitle && ($head = $this->view('head'))) {
            $head->addTitle($this->_formTitle);
        }
    }

    public function action_form__POST()
    {
        $r = $this->BRequest;
        $viewName = $r->get('view_name');
        $layout = $this->getAreaLayout();
        $view = $layout->getView('view_name');
        $viewFile = $view->getTemplateFileName();

        if ($r->post('do') === 'DELETE') {
            echo 'DELETE'; exit;
        }
        var_dump($r->post()); exit;
    }
}
