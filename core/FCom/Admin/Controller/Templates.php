<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Admin_Controller_Templates
 *
 * @property FCom_Frontend_Main $FCom_Frontend_Main
 */

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
        unset($config['form_url']);
        $config['edit_url'] = $this->BApp->href($this->_gridHref . '/form');
        $config['edit_url_required'] = true;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group',
                'buttons' => [
                    ['name' => 'edit', 'href' => $this->BApp->href('templates/form?id='), 'col' => 'view_name'],
                    ['name' => 'delete', 'caption' => 'Remove/Revert']
                ]
            ],
            //array('name' => 'id', 'label' => 'ID', 'index' => 'm.id', 'width' => 55, 'hidden' => true, 'cell' => 'integer'),
            ['name' => 'view_name', 'label' => 'View Name', 'index' => 'view_name', 'width' => 100, 'overflow' => true],
            ['name' => 'file_ext', 'label' => 'File Ext.', 'index' => 'file_ext', 'width' => 50],
            ['name' => 'module_name', 'label' => 'Module', 'index' => 'module_name', 'width' => 100],
        ];

        $config['state'] = ['s' => 'view_name'];

        $layout = $this->FCom_Frontend_Main->getLayout();
        $data = [];
        foreach ($layout->getAllViews() as $view) {
            $row = [
                'id'          => md5(uniqid('templates_')),
                'view_name'   => $view->param('view_name'),
                'file_ext'    => $view->param('file_ext'),
                'module_name' => $view->param('module_name'),
            ];
            $data[] = $row;
        }
        $config['data'] = $data;
        $config['data_mode'] = 'local';
        $config['filters'] = [
            ['field' => 'view_name', 'type' => 'text'],
            ['field' => 'file_ext', 'type' => 'text'],
            ['field' => 'module_name', 'type' => 'text']
        ];
        $config['actions'] = [
            'delete' => ['caption' => 'Remove/Revert'],
        ];
        $config['events'] = ['delete', 'mass-delete'];
        $config['grid_before_create'] = 'template_grid';
        $config['callbacks'] = [
            'componentDidMount' => 'template_grid'
        ];
        //$config['state'] =array(5,6,7,8);
        return $config;
    }

    public function action_form()
    {
        $tplViewName = $this->BRequest->get('id');
        $this->layout($this->_formLayoutName);
        $areaLayout = $this->FCom_Frontend_Main->getLayout();
        if ($tplViewName) {
            $tplView = $areaLayout->getView($tplViewName);
            $tplViewFile = $tplView->getTemplateFileName();
            $tplContents = file_get_contents($tplViewFile);
        } else {
            $tplViewName = '';
            $tplContents = '';
        }
        $model = new BData([
            'id' => $tplViewName,
            'view_name' => $tplViewName,
            'view_contents' => $tplContents,
        ]);

        $this->formMessages();
        $view = $this->view($this->_formViewName)->set('model', $model);
        $this->formViewBefore(['view' => $view, 'model' => $model]);

        $actions = $view->get('actions');
        $actions['delete'] = '<button type="submit" class="btn btn-warning" name="do" value="DELETE" '
            . 'onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>'
            .  $this->BLocale->_('Remove/Revert') . '</span></button>';
        $view->set('actions', $actions);

        $view->set('tab_view_prefix', $this->_formViewPrefix);
        if ($this->_useDefaultLayout) {
            $this->BLayout->applyLayout('default_form');
        }
        $this->processFormTabs($view, $model, 'edit');
        if ($this->_formTitle && ($head = $this->view('head'))) {
            $head->addTitle($this->_formTitle);
        }
    }

    public function action_form__POST()
    {
        try {
            $r = $this->BRequest;
            $model = $r->post('model');
            if (empty($model['view_name'])) {
                throw new BException('Missing view name');
            }
            $viewName = trim($model['view_name'], '/');
            $cleanViewName = preg_replace('[^a-z0-9_./-]', '', $viewName);
            if ($viewName !== $cleanViewName) {
                throw new BException('Invalid view name');
            }
            $targetDir = $this->BModuleRegistry->module('FCom_CustomModule')->root_dir . '/Frontend/views';
            $layout = $this->FCom_Frontend_Main->getLayout();
            $view = $layout->getView($viewName);
            if ($view->getParam('view_name')) {
                $targetFile = $targetDir . '/' . $view->getParam('view_name') . $view->getParam('file_ext');
            } else {
                $targetFile = $targetDir . '/' . $viewName . '.html.twig';
            }
            if ($r->post('do') === 'DELETE') {
                if (!$view) {
                    throw new BException("Template doesn't exist");
                }
                $viewFile = $view->getTemplateFileName();
                if (!$viewFile) {
                    throw new BException("The view doesn't use template file");
                }
                if (file_exists($targetFile)) {
                    unlink($targetFile);
                    $this->message('Template file was reverted or removed');
                } else {
                    $this->message('Template file is already reverted to original', 'warning');
                }
                $this->BResponse->redirect('templates');
                return;
            }

            $this->BUtil->ensureDir(dirname($targetFile));
            file_put_contents($targetFile, $model['view_contents']);
            $this->message('Updated template file has been saved in custom module');
            $this->BResponse->redirect('templates');
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
            $this->BResponse->redirect('templates');
        }
    }
}
