<?php

/**
 * Class FCom_AdminSpa_AdminSpa_Controller_Modules
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Core_Model_Module $FCom_Core_Model_Module
 */

class FCom_AdminSPA_AdminSPA_Controller_Modules extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    public function getGridConfig()
    {
        return [
            static::ID => 'modules',
            static::TITLE => (('Modules')),
            static::DATA_URL => 'modules/grid_data',
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT, static::WIDTH => 80],
                [static::NAME => 'toggle', static::LABEL => (('Toggle')), 'datacell_component' => 'sv-page-modules-grid-datacell-run-level', 'sortable' => false],
                [static::NAME => 'run_level', static::LABEL => (('Run Level'))],
                [static::NAME => 'run_status', static::LABEL => (('Status'))],
                [static::NAME => 'name', static::LABEL => (('Module Name'))],
                [static::NAME => 'description', static::LABEL => (('Description'))],
                [static::NAME => 'version', static::LABEL => (('Version'))],
                [static::NAME => 'channel', static::LABEL => (('Channel'))],
                [static::NAME => 'schema_version', static::LABEL => (('DB Version'))],
                [static::NAME => 'requires', static::LABEL => (('Requires')), 'content_overflow' => true],
                [static::NAME => 'required_by', static::LABEL => (('Required By')), 'content_overflow' => true],
                [static::NAME => 'dep_errors', static::LABEL => (('Dependency Errors')), 'content_overflow' => true],
            ],
            static::PAGE_ACTIONS => [
                static::DEFAULT_FIELD => [/*static::MOBILE_GROUP => 'actions', */static::BUTTON_CLASS => 'button1'],
                /*[static::NAME => 'actions', static::LABEL => 'Actions'],*/
                [static::NAME => 'migrate', static::LABEL => (('Run Migrations')), static::GROUP => 'migrate'],
                [static::NAME => 'reset_cache', static::LABEL => (('Reset Cache')), static::GROUP => 'reset_cache'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getModulesData()
    {
        $config = $this->BConfig;
        $coreLevels = $config->get('module_run_levels/FCom_Core');
        $adminLevels = $config->get('module_run_levels/FCom_Admin');
        $frontendLevels = $config->get('module_run_levels/FCom_Frontend');
        $modules = $this->BModuleRegistry->getAllModules();
        $autoRunLevelMods = ['FCom_Core' => 1, 'FCom_Admin' => 1,'FCom_Frontend' => 1, 'FCom_Install' => 1];

        try {
            $schemaVersions = $this->FCom_Core_Model_Module->orm()->find_many_assoc('module_name');
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
        $id = 0;
        foreach ($modules as $modName => $mod) {
            $r = $this->BUtil->arrayMask((array)$mod, 'name,description,version,channel,run_status,run_level,require,children_copy,errors');
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

        $r = (array)$this->BRequest->get('s');

        $gridId = 'modules';
        $pers = $this->FCom_Admin_Model_User->personalize();
        $s = !empty($pers[static::GRID][$gridId]['state']) ? $pers[static::GRID][$gridId]['state'] : [];

        //$this->BDebug->dump($pers); exit;
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

    public function action_grid_data()
    {
        $rows = $this->getModulesData();
        $size = sizeof($rows);
        $result = [
            'rows' => $rows,
            'state' => ['c' => $size, 'p' => 1, 'ps' => $size, 'mp' => 1],
        ];
        $result = $this->processStaticGridData($result);
        $this->respond($result);
    }

    public function action_grid_export()
    {

    }

    public function action_index__POST()
    {
        try {
            $r = $this->BRequest->post();
            foreach ($r['data'] as $key) {
                $this->BConfig->set('module_run_levels/FCom_Core/' . $key['module_name'], $key['run_level_core'], false, true);
                $this->BConfig->writeConfigFiles('core');
            }
            $this->addMessage('Module run levels have been updated, please reload the page to see changes', 'success');
            $this->ok()->respond();
        } catch (Exception $e) {
            $this->addMessage($e)->respond();
        }
    }

    public function action_migrate__POST()
    {
        try {
            $this->BMigrate->migrateModules(true, true, 'modules');
            $this->addMessage('Migrations complete', 'success')->ok();
        } catch (Exception $e) {
            $this->BDebug->logException($e);
            $this->addMessage($e);
        }
        $this->respond();
    }

    public function action_reset_cache__POST()
    {
        $this->BCache->deleteAll();
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        $this->addMessage('Cache was reset successfully.', 'success')->ok()->respond();
    }
}
