<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Admin_Controller_ImportExport
 *
 * @property FCom_Core_ImportExport $FCom_Core_ImportExport
 */
class FCom_Admin_Controller_ImportExport extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'system/importexport';
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'importexport';
    protected $_formViewPrefix = 'importexport/';
    protected $_gridTitle = 'Import Export';
    protected $_formTitle = 'Import Export';
    protected $_recordName = 'Import Export';

    public function getExportConfig()
    {
        $config              = parent::gridConfig();
        $config['id']        = 'ie_export_grid';
        $config['data_mode'] = 'local';

        $config['columns']            = [
            ['type' => 'row_select'],
            ['name' => 'model', 'label' => 'Models to export'],
        ];
        $config['filters']            = [['field' => 'model', 'type' => 'text']];
        $config['grid_before_create'] = 'modelsGridRegister';
        $config['callbacks'] = [
            'componentDidMount' => 'modelsGridRegister'
        ];
        $data                           = $this->FCom_Core_ImportExport->collectExportableModels();
        ksort($data);
        $default         = [
            'model'    => '',
            'parent'   => null,
            'children' => []
        ];
        $fcom            = $default;
        $fcom['id']    = 'FCom';
        $fcom['model'] = 'FCom';
        $gridData        = ['FCom' => $fcom];
        foreach ($data as $id => $d) {
            $module = explode('_', $id, 3);
            array_splice($module, 2);
            $module = join('_', $module);
            if (!isset($gridData[$module])) {
                $mod                                 = $default;
                $mod['id']                         = $module;
                $mod['model']                      = $module;
                $mod['parent']                     = 'FCom';
                $gridData[$module]                 = $mod;
                $gridData['FCom']['children'][] = $module;
            }
            $obj                                  = $default;
            $obj['id']                          = $id;
            $obj['model']                       = $id;
            $obj['parent']                      = $module;
            $gridData[$id]                      = $obj;
            $gridData[$module]['children'][] = $id;
        }

        $config['data'] = $gridData;

        return ['config' => $config];
    }

    /**
     * get config for grid: ie models
     * @param $model FCom_Admin_Model_Role
     * @return array
     */
    public function getIeConfig($model)
    {

        $config                       = parent::gridConfig();
        $config['id']                 = 'role_all_ie_perm_grid_' . $model->id();
        $config['data_mode']          = 'local';
        $config['columns']            = [
            ['name' => 'permission_name', 'label' => 'Permission Name', 'width' => 250],
            [
                'type'     => 'input',
                'name'     => 'import',
                'label'    => 'Import',
                'width'    => 100,
                'editable' => 'inline',
                'editor'   => 'checkbox',
                'cssClass' => 'role-ie',
            ],
            [
                'type'     => 'input',
                'name'     => 'export',
                'label'    => 'Export',
                'width'    => 100,
                'editable' => 'inline',
                'editor'   => 'checkbox',
                'cssClass' => 'role-ie',
            ],
        ];
        $config['actions']            = [
            //'add' => ['caption' => 'Add selected models']
        ];
        $config['filters']            = [
            ['field' => 'permission_name', 'type' => 'text'],
        ];
        $config['events']             = ['add'];
        $config['grid_before_create'] = 'iePermGridRegister';
        $config['callbacks']          = [
            'componentDidMount'  => 'iePermGridRegister',
            'componentDidUpdate' => 'iePermGridRegister'
        ];
        $data                         = $this->FCom_Core_ImportExport->collectExportableModels();
        $permissions                  = array_flip(explode("\n", $model->get('permissions_data')));
        $default                      = [
            'permission_name' => '',
            'import'          => 0,
            'export'          => 0,
            'parent'          => null,
            'children'        => []
        ];
        $fcom                     = $default;
        $fcom['id']               = 'FCom';
        $fcom['permission_name']  = 'FCom';
        $gridData                 = ['FCom' => $fcom];
        foreach ($data as $id => $d) {
            $module = explode('_', $id, 3);
            array_splice($module, 2);
            $module = join('_', $module);
            if (!isset($gridData[$module])) {
                $mod                            = $default;
                $mod['id']                      = $module;
                $mod['permission_name']         = $module;
                $mod['parent']                  = 'FCom';
                $gridData[$module]              = $mod;
                $gridData['FCom']['children'][] = $module;
            }
            $obj                             = $default;
            $obj['id']                       = $id;
            $obj['permission_name']          = $id;
            $obj['parent']                   = $module;
            $gridData[$id]                   = $obj;
            $gridData[$module]['children'][] = $id;
        }

        foreach ($gridData as $id => &$value) {
            $parent = $value['parent'];
            if (isset($permissions[$id . '/import']) || isset($permissions[$parent . '/import'])) {
                $value['import'] = 1;
            }
            if (isset($permissions[$id . '/export']) || isset($permissions[$parent . '/export'])) {
                $value['export'] = 1;
            }
        }

        $config['data'] = $gridData;

        return ['config' => $config];
    }

    public function action_index()
    {
        $model['export_config'] = $this->getExportConfig();
        $model[ 'import_config' ] = $this->getImportConfig();

        $this->layout();

        $this->formMessages();
        $view = $this->view($this->_formViewName)->set('model', $model);

        if ($this->_formTitle && ($head = $this->view('head'))) {
            $head->addTitle($this->_formTitle);
        }

        if (($nav = $this->view('admin/nav'))) {
            $nav->setNav($this->_navPath);
        }

        $this->BLayout->view('admin/form')->set('tab_view_prefix', $this->_formViewPrefix);
        if ($this->_useDefaultLayout) {
            $this->BLayout->applyLayout('default_form');
        }
        $this->BLayout->applyLayout($this->_formLayoutName);

        $this->processFormTabs($view, $model);
    }
    public function action_import__POST()
    {

        if (empty($_FILES) || !isset($_FILES['upload'])) {
            $this->BResponse->json(['msg' => "Nothing found"]);
            return;
        }
        $this->BResponse->setContentType('application/json');
        /** @var FCom_Core_ImportExport $importer */
        $importer = $this->FCom_Core_ImportExport;
        $uploads = $_FILES['upload'];
        $rows    = [];
        try {
            foreach ($uploads['name'] as $i => $fileName) {

                if (!$fileName) {
                    continue;
                }
                $fileName = preg_replace('/[^\w\d_.-]+/', '_', $fileName);

                $fullFileName = $importer->getFullPath($fileName);
                $this->BUtil->ensureDir(dirname($fullFileName));
                $fileSize = 0;
                if ($uploads['error'][$i]) {
                    $error = $uploads['error'][$i];
                } elseif (!@move_uploaded_file($uploads['tmp_name'][$i], $fullFileName)) {
                    $error = $this->_("Problem storing uploaded file.");
                } elseif ($importer->validateImportFile($fullFileName)) {
                    $this->BResponse->startLongResponse(false);
                    //if (function_exists('xdebug_start_trace')) {
                    //    xdebug_start_trace();
                    //}
                    $importer->importFile($fileName);
                    //if (function_exists('xdebug_stop_trace')) {
                    //    xdebug_stop_trace();
                    //}
                    $error    = '';
                    $fileSize = $uploads['size'][$i];
                } else {
                    $error = $this->_("Invalid import file.");
                }

                $row = [
                    'name'   => $fileName,
                    'size'   => $fileSize,
                    'folder' => '.../',
                ];
                if ($error) {
                    $row['error'] = $error;
                }
                $rows[] = $row;
            }
        } catch(Exception $e) {
            $this->BDebug->logException($e);
            $this->BResponse->json(['error' => $e->getMessage()]);
        }
        $this->BResponse->json(['files' => $rows]);
    }
    public function action_export__POST()
    {
        $exportData = $this->BRequest->post('ie_export_grid');
        $toFile = isset($exportData['export_file_name']) ? $exportData['export_file_name'] : null;
        $models = !empty($exportData['checked']) ? array_keys($exportData['checked']) : null;

        if ($models) {
            foreach ($models as $m) {
                if ($m == 'FCom') {
                    // if FCom is selected to export, this means all should be exported.
                    $models = null;
                    break;
                }
            }

        }
        //if(function_exists('xdebug_start_trace')){
        //    xdebug_start_trace();
        //}
        $result = $this->FCom_Core_ImportExport->export($models, $toFile);
        $this->BResponse->json(['result' => $result ? 'Success': 'Failure']);
        //if(function_exists('xdebug_stop_trace')){
        //    xdebug_stop_trace();
        //}
    }

    protected function getImportConfig()
    {
        $config = array(
            'max_import_file_size' => $this->_getMaxUploadSize()
        );

        return $config;
    }

    protected function _getMaxUploadSize()
    {
        $p   = ini_get( 'post_max_size' );
        $u   = ini_get( 'upload_max_filesize' );
        $max = min( $p, $u );
        return $max;
    }

}
