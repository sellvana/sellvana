<?php

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
        $config                = parent::gridConfig();
        $config['id']        = 'ie_export_grid';
        $config['data_mode'] = 'local';

        $config['columns']            = [
            ['type' => 'row_select'],
            ['name' => 'model', 'label' => 'Models to export'],
        ];
        $config['filters']            = [['field' => 'model', 'type' => 'text']];
        $config['grid_before_create'] = 'modelsGridRegister';
        $data                           = FCom_Core_ImportExport::i()->collectExportableModels();
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

        $config                         = parent::gridConfig();
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
            ],
            [
                'type'     => 'input',
                'name'     => 'export',
                'label'    => 'Export',
                'width'    => 100,
                'editable' => 'inline',
                'editor'   => 'checkbox',
            ],
        ];
        $config['actions']            = [
            'add' => ['caption' => 'Add selected models']
        ];
        $config['filters']            = [
            ['field' => 'permission_name', 'type' => 'text'],
        ];
        $config['events']             = ['add'];
        $config['grid_before_create'] = 'iePermGridRegister';

        $data                      = FCom_Core_ImportExport::i()->collectExportableModels();
        $permissions               = array_flip(explode("\n", $model->get('permissions_data')));
        $default                   = [
            'permission_name' => '',
            'import'          => 0,
            'export'          => 0,
            'parent'          => null,
            'children'        => []
        ];
        $fcom                      = $default;
        $fcom['id']              = 'FCom';
        $fcom['permission_name'] = 'FCom';
        $gridData                  = ['FCom' => $fcom];
        foreach ($data as $id => $d) {
            $module = explode('_', $id, 3);
            array_splice($module, 2);
            $module = join('_', $module);
            if (!isset($gridData[$module])) {
                $mod                                 = $default;
                $mod['id']                         = $module;
                $mod['permission_name']            = $module;
                $mod['parent']                     = 'FCom';
                $gridData[$module]                 = $mod;
                $gridData['FCom']['children'][] = $module;
            }
            $obj                                  = $default;
            $obj['id']                          = $id;
            $obj['permission_name']             = $id;
            $obj['parent']                      = $module;
            $gridData[$id]                      = $obj;
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
        $this->formMessages();
        $view = $this->view($this->_formViewName)->set('model', $model);

        if ($this->_formTitle && ($head = $this->view('head'))) {
            $head->addTitle($this->_formTitle);
        }

        if (($nav = $this->view('admin/nav'))) {
            $nav->setNav($this->_navPath);
        }

        $this->layout();
        BLayout::i()->view('admin/form')->set('tab_view_prefix', $this->_formViewPrefix);
        if ($this->_useDefaultLayout) {
            BLayout::i()->applyLayout('default_form');
        }
        BLayout::i()->applyLayout($this->_formLayoutName);

        $this->processFormTabs($view, $model);
    }
    public function action_import()
    {
        /** @var FCom_Core_ImportExport $importer */
        $importer = FCom_Core_ImportExport::i();
        $uploads  = $_FILES[ 'upload' ];
        $rows = [];
        foreach ( $uploads[ 'name' ] as $i => $fileName ) {

            if ( !$fileName ) {
                continue;
            }
            $fullFileName = $importer->getFullPath( $fileName );
            BUtil::ensureDir( dirname( $fullFileName ) );
            $fileSize = 0;
            if ( !$uploads[ 'error' ][ $i ] && @move_uploaded_file( $uploads[ 'tmp_name' ][ $i ], $fullFileName ) ) {
                $importer->import( $fileName );
                $error   = '';
                $fileSize = $uploads[ 'size' ][ $i ];
            } else {
                $error = $uploads['error'][$i];
            }

            $row = [
                'name'   => $fileName,
                'size'   => $fileSize,
                'folder' => str_replace(BConfig::i()->get('fs/root_dir'), '...',dirname( $fullFileName )),
            ];
            if($error){
                $row['error'] = $error;
            }
            $rows[] = $row;
        }
        BResponse::i()->json( [ 'files' => $rows ] );
    }
    public function action_export()
    {
        $exportData = BRequest::i()->post('ie_export_grid');
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
        $result = FCom_Core_ImportExport::i()->export($models, $toFile);
        BResponse::i()->json(['result' => $result ? 'Success': 'Failure']);
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
