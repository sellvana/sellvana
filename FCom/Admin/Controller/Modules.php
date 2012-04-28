<?php

class FCom_Admin_Controller_Modules extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'admin/modules';

    public function getModulesData()
    {
        $config = BConfig::i();
        $coreLevels = $config->get('modules/FCom_Core/module_run_level');
        $adminLevels = $config->get('modules/FCom_Admin/module_run_level');
        $frontendLevels = $config->get('modules/FCom_Frontend/module_run_level');
        $modules = BModuleRegistry::i()->debug();

        try {
            $schemaVersions = BDbModule::i()->orm()->find_many_assoc('module_name');
            $schemaModules = array();
            foreach (BMigrate::getMigrationData() as $connection=>$migrationModules) {
                foreach ($migrationModules as $modName=>$migrData) {
                    $schemaModules[$modName] = 1;
                }
            }
        } catch (Exception $e) {
            BDebug::logException($e);
        }

        $data = array();
        $migrate = false;
        foreach ($modules as $modName=>$mod) {
            $r = (array)$mod;
            $deps = array();
            foreach ($r['depends'] as $dep) {
                $deps[] = $dep['name'];
            }
            $r['depends'] = join(', ', $deps);
            $r['required_by'] = join(', ', $mod->children_copy);
            $r['run_level_core'] = !empty($coreLevels[$modName]) ? $coreLevels[$modName] : null;
            $r['run_level_admin'] = !empty($adminLevels[$modName]) ? $adminLevels[$modName] : null;
            $r['run_level_frontend'] = !empty($frontendLevels[$modName]) ? $frontendLevels[$modName] : null;
            $r['schema_version'] = !empty($schemaVersions[$modName]) ? $schemaVersions[$modName]->schema_version : null;
            $r['migration_available'] = !empty($schemaModules[$modName]) && $r['schema_version']!=$r['version'];
            $data[] = $r;
        }
        return $data;
    }

    public function gridConfig()
    {
        $editMode = BRequest::i()->get('edit');
        $coreRunLevelOptions = array(
            BModule::ONDEMAND  => 'ONDEMAND',
            BModule::DISABLED  => 'DISABLED',
            BModule::REQUESTED => 'REQUESTED',
            BModule::REQUIRED  => 'REQUIRED',
        );
        $areaRunLevelOptions = array(
            ''  => '',
            BModule::DISABLED  => 'DISABLED',
            BModule::REQUESTED => 'REQUESTED',
            BModule::REQUIRED  => 'REQUIRED',
        );
        $runStatusOptions = array(
            BModule::IDLE    => 'IDLE',
            BModule::LOADED  => 'LOADED',
            BModule::ERROR   => 'ERROR'
        );
        $config = array(
            'grid' => array(
                'id'          => 'modules',
                'datatype'    => 'local',
                'data'        => $this->getModulesData(),
                'editurl'     => BApp::href('/modules/grid_data'),
                'columns'     => array(
                    'name'        => array('label' => 'Name', 'key'=>true, 'width'=>150),
                    'description' => array('label' => 'Description', 'width'=>250),
                    'version'     => array('label' => 'Code Version', 'width'=>50),
                    'schema_version' => array('label' => 'Schema Version', 'width'=>50, 'formatter'=>new BValue('fmtSchemaVersion')),
                    'run_status'  => array('label' => 'Run Status', 'options'=>$runStatusOptions, 'formatter'=>new BValue('fmtRunStatus'), 'width'=>80),
                    'run_level' => array('label' => 'Run Level', 'options'=>$coreRunLevelOptions, 'formatter'=>new BValue('fmtRunLevel()'), 'width'=>100),
                    'run_level_core' => array('label' => 'Run Level (Core)', 'options'=>$areaRunLevelOptions, 'formatter'=>new BValue('fmtRunLevel("FCom_Core")'), 'width'=>120),
                    'run_level_admin' => array('label' => 'Run Level (Admin)', 'options'=>$areaRunLevelOptions, 'formatter'=>new BValue('fmtRunLevel("FCom_Admin")'), 'width'=>120, 'hidden'=>true),
                    'run_level_frontend' => array('label' => 'Run Level (Frontend)', 'options'=>$areaRunLevelOptions, 'formatter'=>new BValue('fmtRunLevel("FCom_Frontend")'), 'width'=>120, 'hidden'=>true),
                    'depends'     => array('label' => 'Dependencies', 'width'=>250),
                    'required_by' => array('label' => 'Required By', 'width'=>250),
                ),
                'rowNum'      => 200,
                'sortname'    => 'name',
                'sortorder'   => 'asc',
                //'multiselect' => true,

            ),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true),
            'custom' => array('personalize'=>true, 'autoresize'=>true),
        );
        BPubSub::i()->fire('FCom_Admin_Controller_Modules::gridConfig', array('config'=>&$config));
        return $config;
    }

    public function action_index()
    {
        $grid = BLayout::i()->view('jqgrid')->set('config', $this->gridConfig());
        BPubSub::i()->fire('FCom_Admin_Controller_Modules::action_index', array('grid'=>$grid));
        $this->messages('modules')->layout('/modules');
    }

    public function action_index__POST()
    {
        try {
            $areas = array('FCom_Core', 'FCom_Admin', 'FCom_Frontend');
            $levels = BRequest::i()->post('module_run_level');
            foreach ($areas as $area) {
                if (empty($levels[$area])) {
                    continue;
                }
                foreach ($levels[$area] as $modName=>$status) {
                    if (!$status) {
                        unset($levels[$area][$modName]);
                    }
                }
                BConfig::i()->set('modules/'.$area.'/module_run_level', $levels[$area], false, true);
                //BConfig::i()->add(array('modules'=>array($area=>array('module_run_level'=>$levels[$area]))), true);
            }
            FCom_Core::i()->writeLocalConfig();
            BSession::i()->addMessage('Run levels updated', 'success', 'admin');
        } catch (Exception $e) {
            BDebug::logException($e);
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }
        BResponse::i()->redirect(BApp::href('modules'));
    }

    public function action_migrate__POST()
    {
        try {
            BMigrate::i()->migrateModules();
            BSession::i()->addMessage('Migration complete', 'success', 'admin');
        } catch (Exception $e) {
            BDebug::logException($e);
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }
        BResponse::i()->redirect(BApp::href('modules'));
    }
}