<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Admin_Controller_Modules extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'system/modules';
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'FCom_Core_Model_Module';
    protected $_gridHref = 'modules';
    protected $_gridTitle = 'Modules';
    protected $_recordName = 'Product';
    protected $_mainTableAlias = 'm';
    protected $_gridViewName = 'core/backbonegrid';
    protected $_navPath = 'modules/installed';
    protected $_useDefaultLayout = false;

    public function getModulesData()
    {
        $config = BConfig::i();
        $coreLevels = $config->get('module_run_levels/FCom_Core');
        $adminLevels = $config->get('module_run_levels/FCom_Admin');
        $frontendLevels = $config->get('module_run_levels/FCom_Frontend');
        $modules = BModuleRegistry::i()->getAllModules();
        $autoRunLevelMods = array_flip(explode(',', 'FCom_Core,FCom_Admin,FCom_Frontend,FCom_Install'));

        try {
            $schemaVersions = FCom_Core_Model_Module::i()->orm()->find_many_assoc('module_name');
            $schemaModules = [];
            foreach (BMigrate::getMigrationData() as $connection => $migrationModules) {
                foreach ($migrationModules as $modName => $migrData) {
                    $schemaModules[$modName] = 1;
                }
            }
        } catch (Exception $e) {
            BDebug::logException($e);
        }

        $data = [];
        $migrate = false;
        $id = 0;
        foreach ($modules as $modName => $mod) {
            $r = BUtil::arrayMask((array)$mod, 'name,description,version,channel,run_status,run_level,require,children_copy,errors');
            $reqs = [];
            if (!empty($r['require']['module'])) {
                foreach ($r['require']['module'] as $req) {
                    $reqs[] = $req['name'];
                }
            }
            if (empty($r['channel'])) {
                $r['channel'] = 'alpha';
            }
            $r['requires'] = join(', ', $reqs);
            $r['required_by'] = join(', ', $mod->children_copy);
            $r['auto_run_level'] = isset($autoRunLevelMods[$r['name']]);
            $r['run_level_core'] = $r['auto_run_level'] ? 'AUTO' : (!empty($coreLevels[$modName]) ? $coreLevels[$modName] : '');
            //$r['run_level_admin'] = !empty($adminLevels[$modName]) ? $adminLevels[$modName] : '';
            //$r['run_level_frontend'] = !empty($frontendLevels[$modName]) ? $frontendLevels[$modName] : '';
            $r['schema_version'] = !empty($schemaVersions[$modName]) ? $schemaVersions[$modName]->get('schema_version') : '';
            $r['migration_available'] = !empty($schemaModules[$modName]) && $r['schema_version'] != $r['version'];
            $r['dep_errors'] = '';
            if (!empty($r['errors'])) {
                foreach ($r['errors'] as $e) {
                    $r['dep_errors'] .= $e['mod'] . ': ' . $e['type'] . '; ';
                }
                unset($r['errors']);
            }
            $r['id'] = $id++;
            $r['_selectable'] = !$r['auto_run_level'];
            $data[] = $r;
        }

        $r = (array)BRequest::i()->get('s');

        $gridId = 'modules';
        $pers = FCom_Admin_Model_User::i()->personalize();
        $s = !empty($pers['grid'][$gridId]['state']) ? $pers['grid'][$gridId]['state'] : [];

        //BDebug::dump($pers); exit;
        if (!empty($s['s'])) {
            usort($data, function($a, $b) use($s) {
                $a1 = !empty($a[$s['s']]) ? $a[$s['s']] : '';
                $b1 = !empty($b[$s['s']]) ? $b[$s['s']] : '';
                $sd = empty($s['sd']) || $s['sd'] === 'asc' ? 1 : -1;
                return $a1 < $b1 ? - $sd : ($a1 > $b1 ? $sd : 0);
            });
        }

        return $data;
    }

    public function gridConfig()
    {
        $modules = BModuleRegistry::i()->getAllModules();
        $moduleNames = array_keys($modules);
        $moduleNames = array_combine($moduleNames, $moduleNames);

        $coreRunLevelOptions = FCom_Core_Model_Module::i()->fieldOptions('core_run_level');
        $areaRunLevelOptions = FCom_Core_Model_Module::i()->fieldOptions('core_run_level');
        $runStatusOptions = FCom_Core_Model_Module::i()->fieldOptions('run_status');
        $config = parent::gridConfig();

        $config['columns'] = [
            ['type' => 'row_select'],
            //array('name' => 'id', 'label' => 'ID', 'index' => 'm.id', 'width' => 55, 'hidden' => true, 'cell' => 'integer'),
            ['name' => 'name', 'label' => 'Name', 'index' => 'name', 'width' => 100, 'overflow' => true],
            ['name' => 'description', 'label' => 'Description', 'width' => 150, 'overflow' => true],
            ['name' => 'version', 'label' => 'Version', 'width' => 80, 'overflow' => true],
            ['name' => 'channel', 'label' => 'Channel', 'width' => 80, 'overflow' => true],
            ['name' => 'schema_version', 'label' => 'DB Version', 'width' => 80,
                'cell' => new BValue("FCom.Backgrid.SchemaVersionCell"), 'overflow' => true],
            ['name' => 'run_status', 'label' => 'Status', 'options' => $runStatusOptions, 'width' => 80,
                'cell' => new BValue("FCom.Backgrid.RunStatusCell"), 'overflow' => true],
            ['name' => 'run_level', 'label' => 'Level', 'options' => $coreRunLevelOptions, 'width' => 100,
                'cell' => new BValue("FCom.Backgrid.RunLevelCell"), 'overflow' => true],
            ['type' => 'input', 'name' => 'run_level_core', 'label' => "Run Level (Core)", 'overflow' => true,
                'options' => $areaRunLevelOptions, 'width' => 200,  'validation' => ['required' => true],
                'editable' => true, 'mass-editable-show' => true, 'mass-editable' => true, 'editor' => 'select'],
            ['name' => 'requires', 'label' => 'Requires', 'width' => 250, 'overflow' => true],
            ['name' => 'required_by', 'label' => 'Required By', 'width' => 300, 'overflow' => true],
            ['name' => 'dep_errors', 'label' => 'Dependency Errors', 'width' => 300, 'overflow' => true,
                'hidden' => true],
            ['type' => 'btn_group', 'width' => 115,
                'buttons' => [
                /*
                    array(
                        'type'=>'link','name'=>'required',
                        'href'  => BApp::href($this->_gridHref . '/history?id='), 'col' => 'id',
                        'icon' => 'icon-check-sign', 'type' => 'link', 'title' => $this->_('Required')
                    ),
                    array(
                        'type'=>'link','name'=>'ondemand',
                        'href'  => BApp::href($this->_gridHref . '/history?id='), 'col' => 'id',
                        'icon' => 'icon-check-empty', 'type' => 'link', 'title' => $this->_('On Demand')
                    ),
                */
                    [
                        'type' => 'button', 'name' => 'edit',
                        'icon' => 'glyphicon glyphicon-repeat',
                    ],
                ]
            ],
        ];

        $config['state']['ps'] = 100;
        $config['data'] = $this->getModulesData();
        $config['data_mode'] = 'local';
        $config['filters'] = [
            ['field' => 'name', 'type' => 'text'],
            ['field' => 'run_status', 'type' => 'multiselect'],
            ['field' => 'run_level', 'type' => 'multiselect'],
            //array('field' => 'run_level_core', 'type' => 'multiselect'),
            ['field' => 'requires', 'type' => 'multiselect', 'options' => $moduleNames],
            ['field' => 'required_by', 'type' => 'multiselect', 'options' => $moduleNames],
        ];
        $config['actions'] = [
            'edit' => ['caption' => 'Change Status']
        ];
        $config['events'] = ['edit', 'mass-edit'];
        $config['grid_before_create'] = 'moduleGridRegister';
        $config['local_personalize'] = true;

        //$config['state'] =array(5,6,7,8);
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);

        $view = $args['page_view'];
        $actions = (array)$view->get('actions');
        $actions += [
            'run_migration' => '<button class="btn btn-primary" type="button" onclick="$(\'#util-form\').attr(\'action\', \''
                . BApp::href('modules/migrate') . '\').submit()"><span>' . BLocale::_('Run Migration Scripts')
                . '</span></button>',
        ];
        unset($actions['new']);
        $view->set('actions', $actions);
    }

    /*
    public function action_index()
    {
        BLayout::i()->view('modules')->set('form_url', BApp::href('modules').(BRequest::i()->get('RECOVERY')==='' ? '?RECOVERY' : ''));
        $grid = BLayout::i()->view('core/backgrid')->set('grid', $this->gridConfig());
        BEvents::i()->fire('FCom_Admin_Controller_Modules::action_index', array('grid_view'=>$grid));
        $this->layout('/modules');
    }*/

    public function action_index__POST()
    {
        if (BRequest::i()->xhr()) {
            $r = BRequest::i()->post();
            if (isset($r['async'])) {
                $allModules = BModuleRegistry::i()->getAllModules();
                $data = [];
                foreach ($r['data'] as $arr => $key) {
                    $module = $allModules[$key['module_name']];
                    $tmp = [
                        'module_name' => $key['module_name'],
                        'run_status' => $module->run_status,
                        'run_level' => $module->run_level,
                    ];
                    array_push($data, $tmp);
                }
                BResponse::i()->json(['data' => $data]);
                return;
            }
            if (isset($r['data'])) {
                foreach ($r['data'] as $arr => $key) {
                   BConfig::i()->set('module_run_levels/FCom_Core/' . $key['module_name'], $key['run_level_core'], false, true);
                   FCom_Core_Main::i()->writeConfigFiles('core');
                }
                BResponse::i()->json(['success' => true]);
                return;
            }
        }
        try {
            $areas = ['FCom_Core', 'FCom_Admin', 'FCom_Frontend'];
            $levels = BRequest::i()->post('module_run_levels');
            foreach ($areas as $area) {
                if (empty($levels[$area])) {
                    continue;
                }
                foreach ($levels[$area] as $modName => $status) {
                    if (!$status) {
                        unset($levels[$area][$modName]);
                    }
                }
                BConfig::i()->set('module_run_levels/' . $area, $levels[$area], false, true);
            }
            FCom_Core_Main::i()->writeConfigFiles('core');
            $this->message('Run levels updated');
        } catch (Exception $e) {
            BDebug::logException($e);
            $this->message($e->getMessage(), 'error');
        }
        BResponse::i()->redirect('modules');
    }

    public function action_migrate__POST()
    {
        try {
            BMigrate::i()->migrateModules(true, true);
            $this->message('Migration complete');
        } catch (Exception $e) {
            BDebug::logException($e);
            $this->message($e->getMessage(), 'error');
        }
        BResponse::i()->redirect('modules');
    }
}
