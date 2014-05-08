<?php

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
     * Get user
     * If none is set explicitly, try to get currently logged user.
     * @return FCom_Admin_Model_User
     */
    public function getUser()
    {
        if(empty($this->user)){
            $this->user = FCom_Admin_Model_User::i()->sessionUser();
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
        $modules          = BModuleRegistry::i()->getAllModules();
        $exportableModels = [];
        foreach ($modules as $module) {
            /** @var BModule $module */
            if ($module->run_status == BModule::LOADED) {
                $exportableModels = BUtil::arrayMerge($exportableModels, $this->collectModuleModels($module));
            }
        }

        BEvents::i()->fire(__METHOD__ . ':after', ['models' => &$exportableModels]);
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
            BDebug::log("Could not open $toFile for writing, aborting export.");
            return false;
        }

        $bs = BConfig::i()->get("module/FCom_Core/import_export/batch_size", 100);

        if ($batch && is_numeric($batch)) {
            $bs = $batch;
        }

        $this->writeLine( $fe, json_encode( [ static::STORE_UNIQUE_ID_KEY => $this->storeUID() ] ) );
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
            if($this->getUser()->getPermission($model) == false){
                BDebug::warning(BLocale::_('User: %s, cannot export "%s". Permission denied.',
                    $this->getUser()->get('username'), $model));
                continue;
            }
            if ( !isset( $s[ 'skip' ] ) ) {
                $s[ 'skip' ] = [];
            }
            if ($model == 'FCom_Catalog_Model_Product') {
                // disable custom fields to avoid them adding bunch of fields to export
                FCom_CustomField_Main::i()->disable(true);
            }
            $sample = BDb::ddlFieldInfo($model::table());
            $idField = $model::getIdField();
            $heading = [static::DEFAULT_MODEL_KEY => $model, static::DEFAULT_FIELDS_KEY => []];
            foreach ($sample as $key => $value) {
                if (!in_array($key, $s['skip']) || $idField == $key) {
                    // always export id column
                    $heading[static::DEFAULT_FIELDS_KEY][] = $key;
                }
            }
            $offset = 0;
            $records = $model::i()
                             ->orm()
                             ->select($heading[static::DEFAULT_FIELDS_KEY])
                             ->limit($bs)
                             ->offset($offset)
                             ->find_many();
            if ($records) {
                $this->writeLine($fe, BUtil::toJson($heading));
                while($records) {
                    BEvents::i()->fire(__METHOD__ . ':beforeOutput', ['records' => $records]);
                    foreach ($records as $r) {

                        /** @var FCom_Core_Model_Abstract $r */
                        $data = $r->as_array();
                        $data = array_values($data);

                        $json = BUtil::toJson($data);
                        $this->writeLine($fe, $json);
                    }
                    $offset += $bs;
                    $records = $model::i()
                                     ->orm()
                                     ->select($heading[static::DEFAULT_FIELDS_KEY])
                                     ->limit($bs)
                                     ->offset($offset)
                                     ->find_many();
                }
            }
        }

        return true;
    }

    public function import( $fromFile = null, $batch = null )
    {
        $start = microtime(true);
        /** @var FCom_PushServer_Model_Channel $channel */
        $this->channel = FCom_PushServer_Model_Channel::i()->getChannel('import', true);
        $this->channel->send(['signal' => 'start', 'msg' => "Import started."]);
        $bs = BConfig::i()->get("modules/FCom_Core/import_export/batch_size", 100);
        if($batch && is_numeric($batch)){
                    $bs = $batch;
        }

        $fi = $this->getReadHandle($fromFile);
        if (!$fi) {
            $this->channel->send(['signal' => 'problem',
                                  'problem' => "Could not find file to import.\n$fromFile"]);
            BDebug::log("Could not find file to import.");
            return false;
        }
        $ieConfig = $this->collectExportableModels();
        $importID = static::DEFAULT_STORE_ID;
        /** @var FCom_Core_Model_ImportExport_Model $ieHelperMod */
        $ieHelperMod = FCom_Core_Model_ImportExport_Model::i();

        $importMeta = fgets($fi);
        if ($importMeta) {
            $meta = json_decode($importMeta);
            if (isset($meta-> {static::STORE_UNIQUE_ID_KEY})) {
                $importID = $meta-> {static::STORE_UNIQUE_ID_KEY};
                $this->channel->send(['signal' => 'info', 'msg' => "Store id: $importID"]);
            } else {
                $this->channel->send(
                    [

                        'signal'  => 'problem',
                        'problem' => "Unique store id is not found, using 'default' as key"
                    ]
                );
                BDebug::warning("Unique store id is not found, using 'default' as key");
                $this->defaultSite = true;
            }
        }

        $importSite = FCom_Core_Model_ImportExport_Site::i()->load($importID, 'site_code');
        if (!$importSite) {
            $importSite = FCom_Core_Model_ImportExport_Site::i()->create(['site_code' => $importID])->save();
        }
        $this->importId = $importSite->id();

        $this->importModels = $ieHelperMod->orm()->find_many_assoc('model_name');
        BEvents::i()->fire(
            __METHOD__ . ':meta',
            ['import_id' => $importID, 'import_site' => $importSite, 'import_models' => &$this->importModels]
        );

        $this->currentModel = null;
        $this->currentModelIdField = null;
        $this->currentConfig = null;
        $this->currentFields = [];
        $this->currentRelated = [];

        $batchData = [];
        $cnt = 1;
        while (($line = fgets($fi)) !== false) {
            $cnt++;
            $isHeading = false;
            /** @var FCom_Core_Model_Abstract $model */
            $model     = null;
            $data      = (array)json_decode($line);
            if (!empty($data[static::DEFAULT_MODEL_KEY])) {
                // new model declaration found, import reminder of previous batch
                if (!empty($batchData)) {
                    $this->importBatch($batchData);
                    $batchData = [];
                }

                if ($this->currentModel) {
                    BEvents::i()->fire(
                        __METHOD__ . ':afterModel:' . $this->currentModel,
                        ['import_id' => $importID, 'models' => $this->changedModels]
                    );
                }

                $this->currentModel   = $data[ static::DEFAULT_MODEL_KEY ];
                if($this->getUser()->getPermission($this->currentModel) == false){
                    $this->canImport = false;
                    BDebug::warning(BLocale::_('User: %s, cannot import "%s". Permission denied.', $this->getUser()->get('username'), $model));
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
                $this->currentModelIdField = $cm::i()->getIdField();
                $this->currentConfig  = $ieConfig[$this->currentModel];
                if (!$this->currentConfig) {
                    $this->channel->send(['signal' => 'problem',
                                          'problem' => "Could not find I/E config for $this->currentModel."]);
                    BDebug::warning("Could not find I/E config for $this->currentModel.");
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
            foreach ((array)$this->currentConfig['unique_key'] as $key) {
                $id .= $data[$key] . '/';
            }

            $batchData[trim($id, '/')] = $data;

            if ($cnt % $bs != 0) {
                continue; // accumulate batch data
            } else {
                $this->channel->send(['signal' => 'info', 'msg' => "Importing #$cnt"]);
            }

            $this->importBatch($batchData);
            $batchData = [];
        }

        if (!empty($batchData)) {
            $this->importBatch($batchData);
        }

        BEvents::i()->fire(
            __METHOD__ . ':afterModel:' . $this->currentModel,
            ['import_id' => $importID, 'models' => $this->changedModels]
        );
        if (!feof($fi)) {
            $this->channel->send(['signal' => 'problem',
                                  'problem' => "Error: unexpected file fail"]);
            BDebug::debug("Error: unexpected file fail");
        }
        fclose($fi);
        $this->channel->send([
            'signal' => 'finished',
            'msg'    => "Done in: " . round(microtime(true) - $start) . " sec.",
            'data'   => [
                'new_models'     => BLocale::_("Created %d new models", $this->newModels),
                'updated_models' => BLocale::_("Updated %d models", $this->updatedModels),
                'not_changed'    => BLocale::_("No changes for %d models", $this->notChanged)
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
        $ieHelperId = FCom_Core_Model_ImportExport_Id::i();
        $cm = $this->currentModel;
        $existing = [];
        $this->populateRelated($batchData);

        foreach ($batchData as $key => $data) {
            if (isset($this->currentConfig['unique_key'])) {
                $where = ['AND'];
                foreach ((array)$this->currentConfig['unique_key'] as $ukey) {
                    if (isset($data[$ukey])) {
                        $where[$ukey] = $data[$ukey];
                    }
                }
                if (!empty($where)) {
                    $existing[] = $where;
                }
            }

            $batchData[$key] = $data;
        }

        $oldModels = [];
        if (!empty($existing)) {
            $oldModels = $this->getExistingModels($cm, $existing);
        }

        foreach ($batchData as $id => $data) {
            $ieData = [
                'site_id'   => $this->importId,
                'model_id'  => $this->importModels[$this->currentModel]->id(),
                'import_id' => $data[$this->currentModelIdField],
                'local_id'  => null,
                'relations' => !empty($data['failed_relation']) ? json_encode($data['failed_relation']) : null,
                'update_at' => BDb::i()->now(),
            ];
            unset($data[$this->currentModelIdField], $data['failed_related']);
            /** @var FCom_Core_Model_Abstract $model */
            $model = isset($oldModels[$id]) ? $oldModels[$id] : null;

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
                        $this->updatedModels++;
                    } else {
                        $this->notChanged++;
                    }
                } else {
                    $model = $cm::i()->create($data)->save(false);
                    $this->newModels++;
                }
            } catch (PDOException $e) {
                BDebug::logException($e);
                $this->channel->send(['signal' => 'problem',
                                      'problem' => "Error: unexpected file fail"]);
            }

            if ($model) {
                $ieData['local_id'] = $model->id();
                $ieHelperId->create($ieData)->save(true, true);
                $this->changedModels[$id] = $model;
            } else {
                BDebug::warning("Invalid model: $id");
            }
        }
        BEvents::i()->fire(__METHOD__ . ':afterBatch:' . $cm, ['records' => $this->changedModels]);
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
        $path         = $module->root_dir . '/Model/';
        $modelConfigs = [];
        $files        = BUtil::globRecursive($path, '*.php');
        if (empty($files)) {
            return $modelConfigs;
        }
        foreach ($files as $file) {
            $cls = $module->name . '_Model_' . basename($file, '.php');
            if (method_exists($cls, 'registerImportExport')) { // instanceof does not work with class name
                $cls::i()->registerImportExport($modelConfigs);
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
            BDebug::log("Circular reference, $name", "ie.log");
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
                                $node::i()->registerImportExport($models);
                                $tmpModel = $models[$node];
                            }
                        }

                        if (!isset($tmpModel)) {
                            BDebug::log("Could not find valid configuration for $node", "ie.log");
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
        $sUid = BConfig::i()->get('db/store_unique_id');
        if (!$sUid) {
            $sUid = BUtil::randomString(32);
            BConfig::i()->set('db/store_unique_id', $sUid, false, true);
            FCom_Core_Main::i()->writeConfigFiles();
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
                BDebug::log("Writing failed", 'ie.log');
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
        if (BUtil::isPathAbsolute($file)){
            return $file;
        }
        $path = BConfig::i()->get( 'fs/storage_dir' );
        $ds = DIRECTORY_SEPARATOR;
        $file = $path . $ds . 'export' . $ds . trim( $file, $ds . '\\/' );
        if ( strpos( realpath( dirname( $file ) ), $path ) !== 0 ) {
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
        /** @var BORM $orm */
        $orm = $modelName::i()->orm();
       // foreach ( $modelKeyConditions as $cond ) {
         //   $where = BDb::where($cond);
           // $orm->where(array('OR'=>$where));
        //}

        $orm->where_complex(['OR' => $modelKeyConditions], true);
        $models = $orm->find_many();
        $result = [];

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
                        if (!isset($related[$l][$tmp])) {
                            $related[$l][$field][$tmp] = 1;
                        }
                    }
                } // end foreach data
            } // end foreach related
        } // end if related

        if (!empty($related) && !$this->defaultSite) { // search related ids
            foreach ($this->currentConfig['related'] as $f => $r) {
                if (isset($this->currentRelated[$r])) {
                    continue;
                }
                list($relModel, $field) = explode('.', $r);
                $tempRel = FCom_Core_Model_ImportExport_Id::i()->orm()
                                      ->select(['import_id', 'local_id'])
                                      ->join(
                                          FCom_Core_Model_ImportExport_Model::i()->table(),
                                          'iem.id=model_id and iem.model_name=\'' . $relModel . '\'',
                                          'iem'
                                      )
                                      ->where(['site_id' => $this->importId])
                                      ->where(['local_id' => array_keys($related[$r][$f])])
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
     * @return resource|false
     */
    protected function getWriteHandle($toFile)
    {
        if(is_resource($toFile)){
            return $toFile;
        }

        if(strpos($toFile, 'php://') === 0){
            $path = $toFile; // allow stream writers
        } else {
            $path = $this->getFullPath($toFile);
            BUtil::ensureDir(dirname($toFile));
        }
        if(!is_writable($path)){
            return false;
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
}