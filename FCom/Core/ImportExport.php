<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project sellvana_core
 */
class FCom_Core_ImportExport extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected $_defaultExportFile = 'export.json';
    const STORE_UNIQUE_ID_KEY = '_store_unique_id';
    const DEFAULT_FIELDS_KEY = '_default_fields';
    const DEFAULT_MODEL_KEY = '_default_model';
    const AUTO_MODEL_ID = 'auto_increment';
    const DEFAULT_STORE_ID = 'default';
    protected $importId;
    /**
     * @var FCom_PushServer_Model_Channel
     */
    protected $channel;
    /**
     * @var string
     */
    protected $currentModel;
    protected $currentModelIdField;
    protected $currentConfig;
    protected $currentFields;
    protected $currentRelated;
    protected $importModels;
    protected $defaultSite = false;
    protected $notChanged = 0;
    protected $newModels = 0;
    protected $updatedModels = 0;
    protected $changedModels;

    /**
     * @var FCom_Admin_Model_User
     */
    protected $user;
    /**
     * Can user import current model
     * @var bool
     */
    protected $canImport;
    /**
     * Has meta data for current import been parsed
     * @var bool
     */
    protected $importMetaParsed;

    /**
     * Actual import code for the site being imported
     * @var string
     */
    protected $importCode;

    /**
     * Get user
     * If none is set explicitly, try to get currently logged user.
     * @return FCom_Admin_Model_User
     */
    public function getUser()
    {
        if (empty($this->user)) {
            $this->user = $this->FCom_Admin_Model_User->sessionUser();
        }
        return $this->user;
    }

    /**
     * Set profile user
     *
     * To be used in any API which uses importer.
     * @param FCom_Admin_Model_User $user
     */
    public function setUser( $user )
    {
        $this->user = $user;
    }

    /**
     * @throws Exception
     * @return array
     */
    public function collectExportableModels()
    {
        $modules          = $this->BModuleRegistry->getAllModules();
        $exportableModels = [];
        foreach ($modules as $module) {
            /** @var BModule $module */
            if ($module->run_status == BModule::LOADED) {
                $exportableModels = $this->BUtil->arrayMerge($exportableModels, $this->collectModuleModels($module));
            }
        }

        $this->BEvents->fire(__METHOD__ . ':after', ['models' => &$exportableModels]);
        return $exportableModels;
    }

    /**
     * @param array $models
     * @param null  $toFile
     * @param null  $batch
     * @return bool
     */
    public function export( $models = [], $toFile = null, $batch = null )
    {
        $fe = $this->getWriteHandle($toFile);

        if (!$fe) {
            $msg = $this->BLocale->_("%s Could not open %s for writing, aborting export.", [$this->BDb->now(), $toFile]);
            $this->BDebug->log($msg);
            return false;
        }

        $bs = $this->BConfig->get("modules/FCom_Core/import_export/batch_size", 100);

        if ($batch && is_numeric($batch)) {
            $bs = $batch;
        }
        $this->beginExport($fe);

        $this->writeLine($fe, json_encode([static::STORE_UNIQUE_ID_KEY => $this->storeUID()]));
        $exportableModels = $this->collectExportableModels();

        if (!empty($models)) {
            $diff = array_diff(array_keys($exportableModels), $models);
            foreach ($diff as $d) {
                unset($exportableModels[$d]);
            }
        }

        $sorted = $this->sortModels($exportableModels);

        foreach ($sorted as $s) {
            /** @var FCom_Core_Model_Abstract $model */
            $model   = $s[ 'model' ];
            $user = $this->getUser();
            if ($user && $user->getPermission($model) == false) {
                $this->BDebug->warning($this->BLocale->_('%s User: %s, cannot export "%s". Permission denied.',
                    [$this->BDb->now(), $this->getUser()->get('username'), $model]));
                continue;
            } else if (empty($user)) {
                $this->BDebug->warning($this->BLocale->_('No user found.'));
                continue;
            }
            if ( !isset( $s[ 'skip' ] ) ) {
                $s[ 'skip' ] = [];
            }
            if ($model == 'FCom_Catalog_Model_Product') {
                // disable custom fields to avoid them adding bunch of fields to export
                $this->FCom_CustomField_Main->disable(true);
            }
            $sample = $this->BDb->ddlFieldInfo($model::table());
            $idField = $this->{$model}->getIdField();
            $heading = [static::DEFAULT_MODEL_KEY => $model, static::DEFAULT_FIELDS_KEY => []];
            foreach ($sample as $key => $value) {
                if (!in_array($key, $s['skip']) || $idField == $key) {
                    // always export id column
                    $heading[static::DEFAULT_FIELDS_KEY][] = $key;
                }
            }
            $offset = 0;
            $records = $this->{$model}->orm()
                             ->select($heading[static::DEFAULT_FIELDS_KEY])
                             ->limit($bs)
                             ->offset($offset)
                             ->find_many();
            if ($records) {
                $this->writeLine($fe, ',' . $this->BUtil->toJson($heading));
                while($records) {
                    $this->BEvents->fire(__METHOD__ . ':beforeOutput', ['records' => $records]);
                    foreach ($records as $r) {

                        /** @var FCom_Core_Model_Abstract $r */
                        $data = $r->as_array();
                        $data = array_values($data);

                        $json = $this->BUtil->toJson($data);
                        $this->writeLine($fe, ',' . $json);
                    }
                    $offset += $bs;
                    $records = $this->{$model}
                                    ->orm()
                                     ->select($heading[static::DEFAULT_FIELDS_KEY])
                                     ->limit($bs)
                                     ->offset($offset)
                                     ->find_many();
                }
                //$this->writeLine($fe, '{__end: true}');
            }
        }
        $this->endExport($fe);
        fclose($fe);
        return true;
    }
    public function importFile($fromFile = null, $batch = null)
    {
        $channel = $this->getChannel();
        $fi = $this->getReadHandle($fromFile);

        if (!$fi) {
            $msg = $this->BLocale->_("%s Could not find file to import. File: %s", [BDb::now(), $fromFile]);
            $channel->send([
                'signal'  => 'problem',
                'problem' => $msg
            ]);
            $this->BDebug->log($msg);
            return false;
        }
        $bs = $this->BConfig->get("modules/FCom_Core/import_export/batch_size", 100);
        if ($batch && is_numeric($batch)) {
            $bs = $batch;
        }
        $cnt       = 1;
        $batchData = [];
        $channel->send(['signal' => 'start', 'msg' => $this->BLocale->_("Import started.")]);
        while(($line = fgets($fi)) !== false) {
            $cnt++;
            $lineData     = (array)json_decode(trim($line, ","));
            if (!empty($lineData)) {
                $batchData[] = $lineData;
                if ($cnt % $bs == 0) {
                    $this->import($batchData, $bs);
                    $batchData = [];
                }
            }
        }

        $this->import($batchData, $bs);
        if (!feof($fi)) {
            $msg = $this->BLocale->_("%s Error: unexpected file fail", BDb::now());
            $channel->send([
                'signal'  => 'problem',
                'problem' => $msg
            ]);
            $this->BDebug->debug($msg);
        }
        fclose($fi);
        if($this->BConfig->get('modules/FCom_Core/import_export/delete_after_import')){
            @unlink($this->getFullPath($fromFile));
        }
        return true;
    }

    public function import($importData = array(), $batch = null)
    {
        $start = microtime(true);
        $channel = $this->getChannel();

        if (!empty($importData)) {
            $channel->send(['signal' => 'start', 'msg' => $this->BLocale->_("Batch started.")]);
        $bs = $this->BConfig->get("modules/FCom_Core/import_export/batch_size", 100);
        if ($batch && is_numeric($batch)) {
            $bs = $batch;
        }

        $ieConfig = $this->collectExportableModels();
        /** @var FCom_Core_Model_ImportExport_Model $ieHelperMod */
        $ieHelperMod = $this->FCom_Core_Model_ImportExport_Model;

            $importID = $this->_prepareImportMeta($importData);

        $batchData = [];
        $cnt = 1;
            foreach($importData as $data) {
            $cnt++;
            $isHeading = false;
            /** @var FCom_Core_Model_Abstract $model */
            $model     = null;
            if (!empty($data[static::DEFAULT_MODEL_KEY])) {
                // new model declaration found, import reminder of previous batch
                if (!empty($batchData)) {
                    $this->importBatch($batchData);
                    $batchData = [];
                }

                if ($this->currentModel) {
                    $this->BEvents->fire(
                        __METHOD__ . ':afterModel:' . $this->currentModel,
                        ['import_id' => $importID, 'models' => $this->changedModels]
                    );
                }

                $this->currentModel   = $data[ static::DEFAULT_MODEL_KEY ];
                if ($this->getUser()->getPermission($this->currentModel) == false) {
                    $this->canImport = false;
                    $this->BDebug->warning($this->BLocale->_('%s User: %s, cannot import "%s". Permission denied.', [$this->BDb->now(),
                        $this->getUser()->get('username'), $model]));
                    continue;
                } else {
                    $this->canImport = true;
                }
                $this->changedModels = [];
                $this->channel->send(['signal' => 'info', 'msg' => "Importing: $this->currentModel"]);
                if (!isset($this->importModels[$this->currentModel])) {
                    // first time importing this model
                    $tm = $ieHelperMod->load($this->currentModel, 'model_name'); // check if it has been created
                    if (!$tm) {
                        // if not, create it and add it to list
                        $tm = $ieHelperMod->create(['model_name' => $this->currentModel])->save();
                        $this->importModels[$this->currentModel] = $tm;
                    }
                }
                $cm = $this->currentModel;
                $this->currentModelIdField = $this->{$cm}->getIdField();
                $this->currentConfig  = $ieConfig[$this->currentModel];
                if(!isset($this->currentConfig[static::AUTO_MODEL_ID])){
                    $this->currentConfig[static::AUTO_MODEL_ID] = true; // default case, id is auto increment
                }
                if (!$this->currentConfig) {
                    $msg = $this->BLocale->_("%s Could not find I/E config for %s.", [$this->BDb->now(), $this->currentModel]);
                    $this->channel->send(['signal' => 'problem',
                                          'problem' => $msg
                    ]);
                    $this->BDebug->warning($msg);
                    continue;
                }

                $isHeading = true;
            }

            if (isset($data[static::DEFAULT_FIELDS_KEY])) {
                if (!empty($batchData)) {
                    $this->importBatch($batchData);
                    $batchData = [];
                }
                $this->currentFields = $data[static::DEFAULT_FIELDS_KEY];
                $isHeading     = true;
            }

            if ( $isHeading || !$this->canImport) {
                continue;
            }

            if (!$this->isArrayAssoc($data)) {
                $data = array_combine($this->currentFields, $data);
            }

            $id = '';

            if (!empty($this->currentConfig['unique_key'])) {
                foreach ((array)$this->currentConfig['unique_key'] as $key) {
                    $id .= $data[$key] . '/';
                }
            } else if (isset($data[$this->currentModelIdField])) {
                $id = $data[$this->currentModelIdField];
            } else {
                // this is fall back, hopefully it shouldn't be used
                $id = $cnt;
            }

            $batchData[trim($id, '/')] = $data;

            if ($cnt % $bs != 0) {
                continue; // accumulate batch data
            } else {
                $this->channel->send(['signal' => 'info', 'msg' => $this->BLocale->_("Importing # %s", $cnt)]);
            }

            $this->importBatch($batchData);
            $batchData = [];
        }

        if (!empty($batchData)) {
            $this->importBatch($batchData);
        }

        $this->BEvents->fire(
            __METHOD__ . ':afterModel:' . $this->currentModel,
            ['import_id' => $importID, 'models' => $this->changedModels]
        );
        }

        $channel->send([
            'signal' => 'finished',
            'msg'    => "Done in: " . round(microtime(true) - $start) . " sec.",
            'data'   => [
                'new_models'     => $this->BLocale->_("Created %d new models", $this->newModels),
                'updated_models' => $this->BLocale->_("Updated %d models", $this->updatedModels),
                'not_changed'    => $this->BLocale->_("No changes for %d models", $this->notChanged)
            ]
        ]);

        return true;
    }

    /**
     * @param array $batchData
     * @throws BException
     * @throws Exception
     */
    protected function importBatch($batchData)
    {
        /** @var FCom_Core_Model_ImportExport_Id $ieHelperId */
        $ieHelperId = $this->FCom_Core_Model_ImportExport_Id;
        $cm = $this->currentModel;
        $existing = [];
        $this->populateRelated($batchData);

        foreach ($batchData as $key => $data) {
            $oldIdKey = '';
            $where = [];
            if (isset($this->currentConfig['unique_key'])) {
                foreach ((array)$this->currentConfig['unique_key'] as $ukey) {
                    if (!empty($data[$ukey])) {
                        $where[$ukey] = $data[$ukey];
                        $oldIdKey .= $data[$ukey] . '/';
                    }
                }
                if (!empty($where)) {
                    array_unshift($where, 'AND');
                    $existing[] = $where;
                }
            }
            $data['oldId'] = trim($oldIdKey, '/');
            $batchData[$key] = $data;
        }

        $oldModels = [];
        if (!empty($existing)) {
            $oldModels = $this->getExistingModels($cm, $existing);
        }

        foreach ($batchData as $id => $data) {
            if(!isset($data[$this->currentModelIdField])){
                $this->BDebug->warning($this->BLocale->_("%s Invalid data: %s", [$this->BDb->now(), print_r($data, 1)]));
            }
            $ieData = [
                'site_id'   => $this->importId,
                'model_id'  => $this->importModels[$this->currentModel]->id(),
                'import_id' => $data[$this->currentModelIdField],
                'local_id'  => null,
                'relations' => !empty($data['failed_relation']) ? json_encode($data['failed_relation']) : null,
                'update_at' => $this->BDb->now(),
            ];

            if ($this->currentConfig[static::AUTO_MODEL_ID] !== false) {
                // country id is not auto increment, should not remove it
                unset($data[$this->currentModelIdField]);
            }
            unset($data['failed_related']);
            /** @var FCom_Core_Model_Abstract $model */
            if (!empty($data['oldId'])) {
                $model = isset($oldModels[$data['oldId']])? $oldModels[$data['oldId']]: null;
            } else {
                $model = isset($oldModels[$id]) ? $oldModels[$id] : null;
            }
            unset($data['oldId']);
            //$this->BDebug->log(sprintf("%s - memory consumption: %.2f MB", $this->BDb->now(), memory_get_usage(1)/1024/1024));
            $modified = false;
            try {
                if ($model) {
                    $import = [];
                    foreach ($data as $k => $v) {
                        $oldValue = $model->get($k);
                        if ($oldValue != $v) {
                            $import[$k] = $v;
                        }
                    }
                    if (!empty($import)) {
                        $model->set($import)->save();
                        $modified = true;
                        $this->updatedModels++;
                    } else {
                        $this->notChanged++;
                    }
                } else {
                    $model = $this->{$cm}->create($data)->save(false);
                    $modified = true;
                    $this->newModels++;
                }
            } catch (PDOException $e) {
                $this->BDebug->logException($e);
                $this->channel->send(['signal' => 'problem',
                                      'problem' => $this->BLocale->_("Error: unexpected file fail")]);
            }

            if ($model) {
                if ($modified) {
                $ieData['local_id'] = $model->id();
                $ieHelperId->create($ieData)->save(true, true);
                $this->changedModels[$model->id()] = $model;
                }
            } else {
                $this->BDebug->warning($this->BLocale->_("%s Invalid model: %s", [$this->BDb->now(), $id]));
            }
        }
        $this->BEvents->fire(__METHOD__ . ':afterBatch:' . $cm, ['records' => $this->changedModels]);
    }
    protected function isArrayAssoc(array $arr)
    {
        return (bool)count(array_filter(array_keys($arr), 'is_string'));
    }
    /**
     * @param BModule $module
     * @return array
     */
    protected function collectModuleModels($module)
    {
        $modelConfigs = [];
        if(!empty($module->noexport)) {
            return $modelConfigs;
        }
        $path         = $module->root_dir . '/Model/';
        $files        = $this->BUtil->globRecursive($path, '*.php');
        if (empty($files)) {
            return $modelConfigs;
        }
        foreach ($files as $file) {
            $cls = $module->name . '_Model_' . basename($file, '.php');
            if (method_exists($cls, 'registerImportExport')) { // instanceof does not work with class name
                $this->{$cls}->registerImportExport($modelConfigs);
            }
        }

        return $modelConfigs;
    }

    protected $_exportSorted;
    protected $_tempSorted;
    protected $_isSorted;

    /**
     * @param array $models
     * @return array
     */
    public function sortModels($models)
    {
        foreach ($models as $k => $m) {
            if (!isset($m['related']) || empty($m['related'])) {
                $this->_exportSorted[] = $m; // no dependencies, add to sorted
                $this->_isSorted[$k]  = 1;
                continue;
            }
        }

        foreach ($models as $k => $m) {
            $this->_sort($m, $k, $models);
        }

        return $this->_exportSorted;
    }

    protected function _sort(array $model, $name, array $models)
    {
        if (isset($this->_tempSorted[$name])) {
            $this->BDebug->log("Circular reference, $name", "ie.log");
        } else {
            if (!isset($this->_isSorted[$name])) {
                $this->_tempSorted[$name] = 1;
                if (isset($model['related'])) {
                    foreach ((array)$model['related'] as $node) {
                        $t    = explode('.', $node);
                        $node = $t[0];
                        if (isset($this->_isSorted[$node])) {
                            continue;
                        }

                        if (isset($models[$node])) {
                            $tmpModel = $models[$node];
                        } else {
                            if (method_exists($node, 'registerImportExport')) {
                                $this->{$node}->registerImportExport($models);
                                $tmpModel = $models[$node];
                            }
                        }

                        if (!isset($tmpModel)) {
                            $this->BDebug->log($this->BLocale->_("%s Could not find valid configuration for %s",
                                [$this->BDb->now(), $node]), "ie.log");
                            continue;
                        }
                        $this->_sort($tmpModel, $node, $models);
                    }
                }
                $this->_isSorted[$name] = 1;
                $this->_exportSorted[]   = $model;
                unset($this->_tempSorted[$name]);
            }
        }
    }

    protected function storeUID()
    {
        $sUid = $this->BConfig->get('db/store_unique_id');
        if (!$sUid) {
            $sUid = $this->BUtil->randomString(32);
            $this->BConfig->set('db/store_unique_id', $sUid, false, true);
            $this->BConfig->writeConfigFiles();
        }
        return $sUid;
    }

    /**
     * @param $handle
     * @param $line
     */
    protected function writeLine($handle, $line) {
        $line = trim($line);
        $l    = strlen($line);
        if ($l < 1) {
            return;
        }
        $written = 0;
        while ($written < $l) { // check if entire line is written to file, if not try to continue from break point
            $written += fwrite($handle, trim(substr($line, $written)) . "\n");

            if (!$written) { // if written is false or 0, there has been an error writing.
                $this->BDebug->log($this->BLocale->_("%s Writing failed", $this->BDb->now()), 'ie.log');
                break;
            }
        }
    }

    /**
     * @param string $file
     * @return string
     */
    public function getFullPath($file)
    {
        if (!$file) {
            $file = $this->_defaultExportFile;
        }
        if ($this->BUtil->isPathAbsolute($file)) {
            return $file;
        }
        $path = $this->BApp->storageRandomDir() . '/export';

        $this->BUtil->ensureDir($path);
        $file = $path . '/' . trim($file, '\\/');
        $realpath = str_replace('\\', '/', realpath(dirname($file)));
        if (strpos($realpath, $path) !== 0) {
            return false;
        }
        return $file;
    }

    /**
     * @param $modelName
     * @param $modelKeyConditions
     * @throws BException
     * @return array
     */
    protected function getExistingModels($modelName, $modelKeyConditions)
    {
        $result = [];
        $models = [];
        try {
            /** @var BORM $orm */
            $orm = $this->{$modelName}->orm();
            // foreach ( $modelKeyConditions as $cond ) {
            //   $where = $this->BDb->where($cond);
            // $orm->where(array('OR'=>$where));
            //}

            $orm->where_complex(['OR' => $modelKeyConditions], true);
            $models = $orm->find_many();
        } catch(Exception $e) {
            $this->BDebug->log($orm->as_sql());
            $this->BDebug->logException($e);
        }

        foreach ($models as $model) {
            $id = '';
            foreach ((array)$this->currentConfig['unique_key'] as $key) {
                $id .= $model->get($key) . '/';
            }
            $result[trim($id, '/')] = $model;
        }
        return $result;
    }

    /**
     * Populated related IDs
     * We need to handle this before attempting to import
     * to ensure unique keys do not get triggered
     * also having related id before parsing unique keys, will allow
     * to use related keys for unique_key
     *
     * @param $batchData
     * @throws BException
     * @throws Exception
     */
    protected function populateRelated(&$batchData)
    {
        $related = [];
        if (isset($this->currentConfig['related'])) {
            foreach ($this->currentConfig['related'] as $field => $l) {
                foreach ($batchData as $data) { // prepare related search
                    // collect related ids
                    if (isset($data[$field])) {
                        $tmp = $data[$field];
                        if (!empty($this->currentRelated[$l][$tmp])) {
                            continue; // if relation for this model and id is fetched, skip it
                        }
                        if (!isset($related[$l][$tmp])) {
                            $related[$l][$field][$tmp] = 1;
                        }
                    }
                } // end foreach data
            } // end foreach related
        } // end if related

        if (!empty($related) && !$this->defaultSite) { // search related ids
            foreach ($this->currentConfig['related'] as $f => $r) {
                list($relModel, $field) = explode('.', $r);
                $tempRel = $this->FCom_Core_Model_ImportExport_Id->orm()
                                      ->select(['import_id', 'local_id'])
                                      ->join(
                                          $this->FCom_Core_Model_ImportExport_Model->table(),
                                          'iem.id=model_id and iem.model_name=\'' . $relModel . '\'',
                                          'iem'
                                      )
                                      ->where(['site_id' => $this->importId])
                                      ->where(['import_id' => array_keys($related[$r][$f])])
                                      ->find_many();

                /** @var FCom_Core_Model_Abstract $tr */
                foreach ($tempRel as $tr) {
                    $this->currentRelated[$r][$tr->get('import_id')] = $tr->get('local_id');
                }
            }
        }

        if (isset($this->currentConfig['related'])) {
            foreach ($this->currentConfig['related'] as $field => $l) {
                foreach ($batchData as &$data) { // populate related data
                    if (isset($data[$field])) {
                        $tmp = $data[$field];
                        if (isset($this->currentRelated[$l][$tmp])) {
                            $data[$field] = $this->currentRelated[$l][$tmp];
                        } else {
                            // if there is no match for needed field
                            // set related field to be null, so that it can be updated after model data is imported.
                            // store relation data
                            $data[$field]                      = null;
                            $data['failed_relation'][$field] = $tmp;
                        }
                    }
                } // end foreach batch data
            } // end foreach ['related']
        } // end if related
    }

    /**
     * @param string|resource $toFile
     * @throws BException
     * @return resource|false
     */
    protected function getWriteHandle($toFile)
    {
        if (is_resource($toFile)) {
            return $toFile;
        }

        if (strpos($toFile, 'php://') === 0) {
            $path = $toFile; // allow stream writers
        } else {
            $path = $this->getFullPath($toFile);
            if (!$path) {
                throw new BException($this->BLocale->_("Could not obtain export location."));
            }
            $this->BUtil->ensureDir(dirname($path));
        }
        $fe = fopen($path, 'w');
        return $fe;
    }

    /**
     * @param $fromFile
     * @return resource|false
     */
    protected function getReadHandle($fromFile)
    {
        if (is_resource($fromFile)) {
            return $fromFile;
        }

        if (strpos($fromFile, '://') !== false) {
            $path = $fromFile; // allow stream readers
        } else {
            $path = $this->getFullPath($fromFile);
        }
        if (!is_readable($path)) {
            return false;
        }
        ini_set("auto_detect_line_endings", 1);
        $fi = fopen($path, 'r');
        return $fi;
    }

    /**
     * @param string $channelName
     * @return FCom_PushServer_Model_Channel $channel
     */
    protected function getChannel($channelName = 'import')
    {
        if (empty($this->channel)) {
            $this->channel = $this->FCom_PushServer_Model_Channel->getChannel($channelName, true);
        }
        return $this->channel;
    }

    /**
     * @param array $data
     * @return string
     */
    protected function _prepareImportMeta(&$data)
    {
        if(!$this->importMetaParsed) {
            $channel    = $this->getChannel();
            $importID   = static::DEFAULT_STORE_ID;
            $importMeta = array_shift($data);
            if ($importMeta) {
                $meta = is_string($importMeta)? json_decode($importMeta, true): $importMeta;
                if (isset($meta[static::STORE_UNIQUE_ID_KEY])) {
                    $importID = $meta[static::STORE_UNIQUE_ID_KEY];
                    $channel->send(['signal' => 'info', 'msg' => "Store id: $importID"]);
                } else {
                    $msg = $this->BLocale->_("%s Unique store id is not found, using '%s' as key", [BDb::now(), $importID]);
                    $channel->send(
                        [
                            'signal'  => 'problem',
                            'problem' => $msg
                        ]
                    );
                    BDebug::warning($msg);
                    $this->defaultSite = true;
                }
            }

            $importSite = $this->FCom_Core_Model_ImportExport_Site->load($importID, 'site_code');
            if (!$importSite) {
                $importSite = $this->FCom_Core_Model_ImportExport_Site->create(['site_code' => $importID])->save();
            }
            $this->importId = $importSite->id();
            $this->importCode = $importID;
            $this->importModels = $this->FCom_Core_Model_ImportExport_Model->orm()->find_many_assoc('model_name');
            $this->BEvents->fire(
                __METHOD__ . ':meta',
                ['import_id' => $importID, 'import_site' => $importSite, 'import_models' => &$this->importModels]
            );

            $this->currentModel        = null;
            $this->currentModelIdField = null;
            $this->currentConfig       = null;
            $this->currentFields       = [];
            $this->currentRelated      = [];
            $this->importMetaParsed    = true;
        }
        return $this->importCode;
    }

    protected $allowedExtensions = ['json'=>1];
    /**
     * @param string $fullFileName
     * @return bool
     */
    public function validateImportFile($fullFileName)
    {
        $ext   = pathinfo($fullFileName, PATHINFO_EXTENSION);
        $valid = true;
        if (!isset($this->allowedExtensions[$ext])) {
            $valid = false;
        }

        $rh = $this->getReadHandle($fullFileName);
        if (!$rh) {
            $valid = false;
        } else {
            $header = fgets($rh);
            $decodedHeader = json_decode($header, true);
            if(!$decodedHeader || !is_array($decodedHeader)){
                $valid = false;
            }
            fclose($rh);
        }

        if (!$valid) {
            @unlink($fullFileName); // make sure invalid files are removed from the system;
        }
        return $valid;
    }

    protected function beginExport($handle)
    {
        $this->writeLine($handle, "[");
    }

    protected function endExport($handle)
    {
        $this->writeLine($handle, "]");
    }
}
