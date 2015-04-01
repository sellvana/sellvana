<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Core_Model_Module extends BDbModule
{
    static protected $_table = 'fcom_module';
    static protected $_origClass = __CLASS__;

    static protected $_fieldOptions = [
        'core_run_level' => [
            BModule::ONDEMAND  => 'ONDEMAND',
            BModule::DISABLED  => 'DISABLED',
            BModule::REQUESTED => 'REQUESTED',
            BModule::REQUIRED  => 'REQUIRED',
        ],
        'area_run_level' => [
            ''  => '',
            BModule::DISABLED  => 'DISABLED',
            BModule::REQUESTED => 'REQUESTED',
            BModule::REQUIRED  => 'REQUIRED',
        ],
        'run_status' => [
            BModule::IDLE    => 'IDLE',
            BModule::LOADED  => 'LOADED',
            BModule::ERROR   => 'ERROR',
        ],
    ];

    protected static $_importExportProfile = [
        'unique_key' => ['module_name',],
    ];

    public function getModulesData()
    {
        $config = $this->BConfig->get('module_run_levels');
        $coreLevels = $config['FCom_Core'];
        $adminLevels = $config['FCom_Admin'];
        $frontendLevels = $config['FCom_Frontend'];
        $modules = $this->BModuleRegistry->getAllModules();

        try {
            $schemaVersions = $this->orm()->find_many_assoc('module_name');
            $schemaModules = [];
            foreach ($this->BMigrate->getMigrationData() as $connection => $migrationModules) {
                foreach ($migrationModules as $modName => $migrData) {
                    $schemaModules[$modName] = 1;
                }
            }
        } catch (Exception $e) {
            $this->BDebug->logException($e);
        }

        $data = [];
        $migrate = false;
        foreach ($modules as $modName => $mod) {
            $r = $this->BUtil->arrayMask((array)$mod, 'name,description,version,run_status,run_level,require,children_copy');
            $reqs = [];
            if (!empty($r['require']['module'])) {
                foreach ($r['require']['module'] as $req) {
                    $reqs[] = $req['name'];
                }
            }
            $r['requires'] = join(', ', $reqs);
            $r['required_by'] = join(', ', $mod->children_copy);
            $r['run_level_core'] = !empty($coreLevels[$modName]) ? $coreLevels[$modName] : null;
            $r['run_level_admin'] = !empty($adminLevels[$modName]) ? $adminLevels[$modName] : null;
            $r['run_level_frontend'] = !empty($frontendLevels[$modName]) ? $frontendLevels[$modName] : null;
            $r['schema_version'] = !empty($schemaVersions[$modName]) ? $schemaVersions[$modName]->schema_version : null;
            $r['migration_available'] = !empty($schemaModules[$modName]) && $r['schema_version'] != $r['version'];
            $data[] = $r;
        }
        return $data;
    }
}

