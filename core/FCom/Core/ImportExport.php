<?php

/**
 * Created by pp
 * @project sellvana_core
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Core_Model_ImportExport_Id $FCom_Core_Model_ImportExport_Id
 * @property FCom_Core_Model_ImportExport_Model $FCom_Core_Model_ImportExport_Model
 * @property FCom_Core_Model_ImportExport_Site $FCom_Core_Model_ImportExport_Site
 * @property Sellvana_CatalogFields_Main $Sellvana_CatalogFields_Main
 * @property FCom_PushServer_Model_Channel $FCom_PushServer_Model_Channel
 */
class FCom_Core_ImportExport extends BClass
{
    const STORE_UNIQUE_ID_KEY = '_store_unique_id';
    const DEFAULT_FIELDS_KEY = '_default_fields';
    const DEFAULT_MODEL_KEY = '_default_model';
    const CUSTOM_DATA_KEY = '_custom_data';
    const AUTO_MODEL_ID = 'auto_increment';
    const DEFAULT_STORE_ID = 'default';
    
    protected static $_origClass = __CLASS__;

    protected $_defaultExportFile = 'export.json';

    protected $_importId;
    
    /**
     * @var FCom_PushServer_Model_Channel
     */
    protected $_channel;
    /**
     * Unique Id of this object, used in @see _sendMessage()
     * @var string
     */
    protected $_currentObjectId;
    /**
     * @var string
     */
    protected $_currentModel;
    protected $_currentModelIdField;
    protected $_currentConfig;
    protected $_currentFields;
    protected $_currentRelated;
    protected $_importModels;
    protected $_defaultSite = false;
    protected $_notChanged = 0;
    protected $_newModels = 0;
    protected $_updatedModels = 0;
    protected $_changedModelsIds;
    protected $_batchChangedModelsIds;
    protected $_errors = 0;
    protected $_warnings = 0;
    protected $_modelsStatistics = array();

    /** @var bool Import status */
    protected $_importInProcessing = false;

    protected $_defaultExportBatchSize = 1000;
    protected $_defaultImportBatchSize = 1000;

    protected $_log = [];
    protected $_lastLogSentAt;

    /**
     * @var FCom_Admin_Model_User
     */
    protected $_user;
    /**
     * Can user import current model
     * @var bool
     */
    protected $_canImport;
    /**
     * Has meta data for current import been parsed
     * @var bool
     */
    protected $_importMetaParsed;

    /**
     * Actual import code for the site being imported
     * @var string
     */
    protected $_importCode;

    public function __construct(){
        $this->_currentObjectId = md5(spl_object_hash($this));
    }

    /**
     * Get user
     * If none is set explicitly, try to get currently logged user.
     * @return FCom_Admin_Model_User
     */
    public function getUser()
    {
        if (empty($this->_user)) {
            if ($this->BRequest->area() == 'FCom_Shell') {
                $this->_user = $this->FCom_Admin_Model_User->create(['is_superadmin' => true]);
            } else {
                $this->_user = $this->FCom_Admin_Model_User->sessionUser();
            }
        }
        return $this->_user;
    }

    /**
     * Set profile user
     *
     * To be used in any API which uses importer.
     * @param FCom_Admin_Model_User $user
     */
    public function setUser( $user )
    {
        $this->_user = $user;
    }

    public function log($message, $type = 'info')
    {
        switch ($type) {
            case 'warning':
                $this->_warnings++;
                break;
            case 'error':
                $this->_errors++;
                break;
        }
        if (is_string($message)) {
            $message = ['msg' => $message];
        }

        if (empty($message['signal']) && ($type === 'warning' || $type === 'error')) {
            $message['signal'] = 'problem';
        }
        $this->_sendMessage($message);


        if ($type === 'warning' || $type === 'error') {
            $this->BDebug->log($message, 'ie.log');
        }
        return $this;
    }
    
    protected function _sendMessage($message, $channelName = 'import')
    {
        if (null === $this->_channel) {
            if (!$this->BModuleRegistry->isLoaded('FCom_PushServer')) {
                $this->_channel = false;
                return;
            }
            $this->_channel = $this->FCom_PushServer_Model_Channel->getChannel($channelName, true);
        } elseif (!$this->_channel) {
            return;
        }
        $message = (array)$message;
        $message['object_id'] = $this->_currentObjectId;
        
        $this->_channel->send($message);
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
                $exportableModels = $this->BUtil->arrayMerge($exportableModels, $this->_collectModuleModels($module));
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
    public function export($models = [], $toFile = null, $batch = null)
    {
        $fe = $this->_getWriteHandle($toFile);

        if (!$fe) {
            $this->log([
                'msg' => $this->_('Could not open file for writing, aborting export.'),
                'data' => [
                    'file' => $toFile,
                ],
            ], 'error');
            return false;
        }

        $bs = $this->BConfig->get("modules/FCom_Core/import_export/batch_size", $this->_defaultExportBatchSize);

        if ($batch && is_numeric($batch)) {
            $bs = $batch;
        }
        $this->_beginExport($fe);

        $this->_writeLine($fe, json_encode([static::STORE_UNIQUE_ID_KEY => $this->_storeUID()]));
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
            $model   = $s['model'];
            $user = $this->getUser();
            if ($user && $user->getPermission($model) == false) {
                $this->log([
                    'msg' => $this->_('Cannot export model, permission denied.'),
                    'data' => [
                        'user' => $this->getUser()->get('username'),
                        'model' => $model,
                    ],
                ], 'error');
                continue;
            } elseif (empty($user)) {
                $this->log([
                    'msg' => $this->_('Please re-login'),
                ], 'error');
                break;
            }
            if (!isset($s['skip'])) {
                $s['skip'] = [];
            }
            if ($model == 'Sellvana_Catalog_Model_Product' && $this->BModuleRegistry->isLoaded('Sellvana_CatalogFields')) {
                // disable custom fields to avoid them adding bunch of fields to export
                $this->Sellvana_CatalogFields_Main->disable(true);
            }

            try {
                $sample = $this->BDb->ddlFieldInfo($model::table());
            } catch(Exception $e) {
                $this->log([
                    'msg' => $this->_('Error retrieving table fields info'),
                    'data' => [
                        'model' => $model,
                        'table' => $model::table(),
                    ],
                ], 'error');
                continue;
            }

            $idField = $this->{$model}->getIdField();
            $heading = [static::DEFAULT_MODEL_KEY => $model, static::DEFAULT_FIELDS_KEY => []];
            foreach ($sample as $key => $value) {
                if (!empty($s['custom_data']) && $key == 'data_serialized') {
                    continue;
                }
                if (!in_array($key, $s['skip']) || $idField == $key) {
                    // always export id column
                    $heading[static::DEFAULT_FIELDS_KEY][] = $key;
                }
            }
            $dbFields = $heading[static::DEFAULT_FIELDS_KEY];
            if (!empty($s['custom_data'])) {
                $heading[static::DEFAULT_FIELDS_KEY][] = static::CUSTOM_DATA_KEY;
                $dbFields[] = 'data_serialized';
            }
            $offset = 0;
            $records = $this->{$model}->orm()
                ->select($dbFields)
                ->limit($bs)
                ->offset($offset)
                ->find_many();
            if ($records) {
                $this->_writeLine($fe, ',' . $this->BUtil->toJson($heading));
                while($records) {
                    $this->BEvents->fire(__METHOD__ . ':beforeOutput', ['records' => $records]);
                    foreach ($records as $r) {

                        /** @var FCom_Core_Model_Abstract $r */
                        $data = $r->as_array();
                        unset($data['data_serialized']);
                        $data = array_values($data);
                        if (!empty($s['custom_data'])) {
                            if (is_array($s['custom_data'])) {
                                foreach ($s['custom_data'] as $cdk) {
                                    $cData[$cdk] = $r->getData($cdk);
                                }
                            } else {
                                $cData = $r->getData();
                                $cdks = array_keys($cData);
                                foreach ($cdks as $cdk) {
                                    $cData[$cdk] = $r->getData($cdk);
                                }
                            }
                            $data[] = $cData;
                        }
                        $json = $this->BUtil->toJson($data);
                        $this->_writeLine($fe, ',' . $json);
                    }
                    $offset += $bs;
                    $records = $this->{$model}
                        ->orm()
                        ->select($dbFields)
                        ->limit($bs)
                        ->offset($offset)
                        ->find_many();
                }
                //$this->writeLine($fe, '{__end: true}');
            }
        }
        $this->_endExport($fe);
        fclose($fe);
        return true;
    }

    /**
     * Import data from file
     *
     * Currently requires each record to be on separate line in file (by default output from Export of this class)
     * @todo Implement JSON streaming parser (salsify/jsonstreamingparser or shevron/ext-jsonreader)
     *
     * @param string $fromFile
     * @param int $batch
     * @return bool
     * @throws BException
     */
    public function importFile($fromFile = null, $batch = null)
    {
        $fi = $this->_getReadHandle($fromFile, 'import');

        if (!$fi) {
            $this->log([
                'msg' => $this->_("Could not find file to import"),
                'data' => ['file_name' => $fromFile],
            ], 'error');
            return false;
        }
        $bs = $this->BConfig->get("modules/FCom_Core/import_export/batch_size", $this->_defaultImportBatchSize);
        if ($batch && is_numeric($batch)) {
            $bs = $batch;
        }
        $cnt       = 1;
        $batchData = [];
        $this->log([
            'signal' => 'start',
            'msg' => $this->_("Import started."),
            'data' => ['file_name' => $fromFile]
        ], 'info');

        $this->_importInProcessing = true;

        $this->BEvents->fire(self::$_origClass . "::beforeImport");

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

        $this->BEvents->fire(self::$_origClass . "::afterImport");

        if (!feof($fi)) {
            $this->log([
                'msg' => $this->_("Error: unexpected file fail"),
                'line' => $line,
                'cnt' => $cnt,
            ], 'error');
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

        $origImportStatus = $this->_importInProcessing;

        if ($origImportStatus != true) {
            $this->_importInProcessing = true;
            $this->BEvents->fire(self::$_origClass . "::beforeImport");
        }

        if (empty($importData)) {
            if ($origImportStatus != true) {
                $this->_importInProcessing = $origImportStatus;
                $this->BEvents->fire(self::$_origClass . "::afterImport");
            }
            return true;
        }
//        $this->log([
//            'signal' => 'start',
//            'msg' => $this->BLocale->_("Batch started.")
//        ], 'info');
        $bs = $this->BConfig->get("modules/FCom_Core/import_export/batch_size", $this->_defaultImportBatchSize);
        if ($batch && is_numeric($batch)) {
            $bs = $batch;
        }

        $ieConfig = $this->collectExportableModels();
        /** @var FCom_Core_Model_ImportExport_Model $ieHelperMod */
        $ieHelperMod = $this->FCom_Core_Model_ImportExport_Model;

        $importID = $this->_prepareImportMeta($importData);

        $batchData = [];
        $cnt = 0;
        foreach ($importData as $data) {
            $cnt++;
            $isHeading = false;
            /** @var FCom_Core_Model_Abstract $model */
            $model     = null;
            if (!empty($data[static::DEFAULT_MODEL_KEY])) {
                // new model declaration found, import remainder of previous batch
                if (!empty($batchData)) {
                    $this->_importBatch($batchData);
                    $batchData = [];
                }

                if ($this->_currentModel) {
                    $this->BEvents->fire(
                        __METHOD__ . ':afterModel:' . $this->_currentModel,
                        ['import_id' => $importID, 'models' => $this->_changedModelsIds]
                    );
                    $this->BEvents->fire(
                        __METHOD__ . ':afterModel',
                        ['import_id' => $importID, 'modelName' => $this->_currentModel,
                            'models' => $this->_changedModelsIds]
                    );
                }

                $cm = $this->_currentModel = $data[ static::DEFAULT_MODEL_KEY ];
                if (empty($ieConfig[$cm])) {
                    $this->_currentModel = null;
                    continue; // model does not have import/export configuration
                }
                $this->BEvents->fire(
                    __METHOD__ . ':beforeModel:' . $this->_currentModel,
                    ['import_id' => $importID]
                );

                $this->BEvents->fire(
                    __METHOD__ . ':beforeModel',
                    ['import_id' => $importID, 'modelName' => $this->_currentModel]
                );

                $this->_modelsStatistics[$cm] = [
                    'not_changed' => 0,
                    'new_models' => 0,
                    'updated_models' => 0
                ];
                if ($this->getUser()->getPermission($cm) == false) {
                    $this->_canImport = false;
                    $this->log([
                        'msg' => $this->_('Permission denied.'),
                        'data' => [
                            'user' => $this->getUser()->get('username'),
                            'model' => $model,
                        ],
                    ], 'error');
                    continue;
                } else {
                    $this->_canImport = true;
                }
                $this->_changedModelsIds = [];
                $this->log([
                    'signal' => 'info',
                    'msg' => "Importing: {$cm}",
                    'data' => ['start_model' => $cm]
                ], 'info');
                if (!isset($this->_importModels[$cm])) {
                    // first time importing this model
                    $tm = $ieHelperMod->load($cm, 'model_name'); // check if it has been created
                    if (!$tm) {
                        // if not, create it and add it to list
                        $tm = $ieHelperMod->create(['model_name' => $cm])->save();
                        $this->_importModels[$this->_currentModel] = $tm;
                    }
                }
                $this->_currentModelIdField = $this->{$cm}->getIdField();
                $this->_currentConfig  = $ieConfig[$cm];
                if(!isset($this->_currentConfig[static::AUTO_MODEL_ID])){
                    $this->_currentConfig[static::AUTO_MODEL_ID] = true; // default case, id is auto increment
                }
                if (!array_key_exists('unique_key_not_null', $this->_currentConfig)
                    && array_key_exists('unique_key', $this->_currentConfig)
                ) {
                    $table = $this->{$cm}->table();
                    $fields = BDb::ddlFieldInfo($table);
                    foreach ($fields as $fieldName => $field) {
                        if (!in_array($fieldName, (array)$this->_currentConfig['unique_key'])
                            || $field->orm->get('Null') == 'YES'
                        ) {
                            continue;
                        }
                        $this->_currentConfig['unique_key_not_null'][] = $fieldName;
                    }
                }
                if (!$this->_currentConfig) {
                    $this->log([
                        'msg' => $msg = $this->_("Could not find I/E config for model."),
                        'data' => [
                            'model' => $cm,
                        ],
                    ], 'error');
                    continue;
                }

                $isHeading = true;
            }

            if (isset($data[static::DEFAULT_FIELDS_KEY])) {
                if (!empty($batchData)) {
                    $this->_importBatch($batchData);
                    $batchData = [];
                }
                $this->_currentFields = $data[static::DEFAULT_FIELDS_KEY];
                $isHeading     = true;
            }

            if ( $isHeading || !$this->_canImport || !$this->_currentModel) {
                continue;
            }

            if (!$this->_isArrayAssoc($data)) {
                $fieldsCnt = sizeof($this->_currentFields);
                $dataCnt = sizeof($data);
                if ($dataCnt < $fieldsCnt) {
                    $data = array_pad($data, sizeof($this->_currentFields), null);
                } elseif ($dataCnt > $fieldsCnt) {
                    $data = array_slice($data, 0, $fieldsCnt);
                }
                //$this->BDebug->warning('Invalid data: ' . print_r($this->_currentFields, 1) . ' | ' . print_r($data, 1));
                $data = array_combine($this->_currentFields, $data);
            }

            $id = '';

            if (!empty($this->_currentConfig['unique_key'])) {
                //TODO: add checking for existance of data for unique_key and proper handle it
                foreach ((array)$this->_currentConfig['unique_key'] as $key) {
                    if (!array_key_exists($key, $data)) {
                        $this->log('Key is empty: ' . $key . ' | ' . print_r($this->_currentConfig, 1) . ' | ' . print_r($data, 1), 'warning');
                    }
                    $id .= $data[$key] . '/';
                }
            } elseif (isset($data[$this->_currentModelIdField])) {
                $id = $data[$this->_currentModelIdField];
            } else {
                // this is fall back, hopefully it shouldn't be used
                $this->log('KEY FALLBACK USED', 'warning');
                $id = $cnt;
            }

            $batchData[trim($id, '/')] = $data;

            if ($cnt % $bs === 0) {
                $this->log([
                    'signal' => 'info',
                    'msg' => $this->_("Importing %s rows", $cnt),
                    'data' => [
                        'current_model' => $this->_currentModel,
                        'models_statistics' => $this->_modelsStatistics[$this->_currentModel],
                    ],
                ], 'info');
                $this->_importBatch($batchData);
                $batchData = [];
            }
        }

        if (!empty($batchData)) {
            $this->_importBatch($batchData);
        }

        $this->BEvents->fire(
            __METHOD__ . ':afterModel:' . $this->_currentModel,
            ['import_id' => $importID, 'models' => $this->_changedModelsIds]
        );

        $this->BEvents->fire(
            __METHOD__ . ':afterModel',
            ['import_id' => $importID, 'modelName' => $this->_currentModel, 'models' => $this->_changedModelsIds]
        );

        $this->log([
            'signal' => 'finished',
            'msg'    => "Done in: " . round(microtime(true) - $start) . " sec.",
            'data'   => [
                'new_models'     => $this->_("Created %d new models", $this->_newModels),
                'updated_models' => $this->_("Updated %d models", $this->_updatedModels),
                'not_changed'    => $this->_("No changes for %d models", $this->_notChanged),
                'models_statistics' => $this->_modelsStatistics
            ]
        ], 'info');

        if ($origImportStatus != true) {
            $this->_importInProcessing = $origImportStatus;
            $this->BEvents->fire(self::$_origClass . "::afterImport");
        }

        return true;
    }

    /**
     * @param array $batchData
     * @throws BException
     * @throws Exception
     */
    protected function _importBatch($batchData)
    {
        $this->_batchChangedModelsIds = [];
        /** @var FCom_Core_Model_ImportExport_Id $ieHelperId */
        $ieHelperId = $this->FCom_Core_Model_ImportExport_Id;
        $cm = $this->_currentModel;
        $existing = [];
        $this->_populateRelated($batchData);

        foreach ($batchData as $key => $data) {
            $oldIdKey = '';
            $where = [];
            if (isset($this->_currentConfig['unique_key'])) {
                foreach ((array)$this->_currentConfig['unique_key'] as $ukey) {
                    if(array_key_exists($ukey, $data)){
//                    if (!empty($data[$ukey])) {
                        $where[$ukey] = $data[$ukey];
                        $oldIdKey .= $data[$ukey] . '/';
                    }
                }
                if (!empty($where)) {
                    array_unshift($where, 'AND');
                    $existing[] = $where;
                }
            }
            $data['old_id'] = trim($oldIdKey, '/');
            $batchData[$key] = $data;
        }

        $oldModels = [];
        if (!empty($existing)) {
            $oldModels = $this->_getExistingModels($cm, $existing);
        }

        $ieAbsentIds = [];
        if (!empty($oldModels)){
            $oldModelsIds = [];
            foreach($oldModels as $key => $model){
                $oldModelsIds[$key] = $model->{$this->_currentModelIdField};
            }

            $ieOrm = $ieHelperId->orm('i')->select(['i.local_id'])
                ->where('site_id', $this->_importId)
                ->where_in('local_id', array_values($oldModelsIds))
                ->where('model_id', $this->_importModels[$this->_currentModel]->id())
                ;
            $res = $ieOrm->find_many_assoc('local_id', 'local_id');

            $ieAbsentIds = array_diff(array_values($oldModelsIds), $res);
        }

        foreach ($batchData as $id => $data) {
            if (!isset($data[$this->_currentModelIdField])) {
                $this->_errors++;
                $this->log([
                    'msg' => $this->_("Invalid data"),
                    'data' => [
                        'id' => $id,
                        'row' => $data,
                    ],
                ], 'error');
                continue;
            }
            $ieData = [
                'site_id'   => $this->_importId,
                'model_id'  => $this->_importModels[$this->_currentModel]->id(),
                'import_id' => $data[$this->_currentModelIdField],
                'local_id'  => null,
                'relations' => !empty($data['failed_relation']) ? json_encode($data['failed_relation']) : null,
                'update_at' => $this->BDb->now(),
            ];

            if ($this->_currentConfig[static::AUTO_MODEL_ID] !== false) {
                // country id is not auto increment, should not remove it
                unset($data[$this->_currentModelIdField]);
            }
            unset($data['failed_related']);
            /** @var FCom_Core_Model_Abstract $model */
            if (!empty($data['old_id'])) {
                $model = isset($oldModels[$data['old_id']])? $oldModels[$data['old_id']]: null;
            } else {
                $model = isset($oldModels[$id]) ? $oldModels[$id] : null;
            }
            unset($data['old_id']);
            //$this->BDebug->log(sprintf("%s - memory consumption: %.2f MB", $this->BDb->now(), memory_get_usage(1)/1024/1024));
            $modified = false;
            try {
                if (!empty($this->_currentConfig['unique_key'])
                    && !empty($this->_currentConfig['unique_key_not_null'])
                ) {
                    foreach ((array)$this->_currentConfig['unique_key_not_null'] as $k) {
                        if (!(array_key_exists($k, $data) && $data[$k] !== null)) {
                            /*
                            $this->BDebug->log($this->BLocale->_("Empty primary key fields: %s",
                                print_r(['id' => $id, 'data' => $data, 'config' => $this->_currentConfig], 1)), 'ie.log');
                            */
                            /*
                            $this->log([
                                'msg' => $this->BLocale->_("Empty primary key fields"),
                                'data' => [
                                    'id' => $id,
                                    'row' => $data,
                                    'config' => $this->_currentConfig,
                                ],
                            ], 'error');
                            */
                            continue 2;
                        }
                    }
                }
                $import = [];
                if (!empty($this->_currentConfig['skip'])) {
                    foreach ($this->_currentConfig['skip'] as $skipField) {
                        unset($data[$skipField]);
                    }
                }
                if ($model) {
                    foreach ($data as $k => $v) {
                        $oldValue = $model->get($k);
                        if ($oldValue != $v) {
                            $import[$k] = $v;
                        }
                    }

                    $customDataUpdate = false;
                    if (!empty($import[static::CUSTOM_DATA_KEY])) {
                        $cData = (array)$import[static::CUSTOM_DATA_KEY];
                        $merge = true;
                        if (isset($cData['_merge'])) {
                            $merge = $cData['_merge'];
                            unset($cData['_merge']);
                        }
                        if ($merge) {
                            $customDataUpdate = true;
                        }
                        foreach ($cData as $cdk => $cdkData) {
                            if (!($merge && $customDataUpdate)) {
                                if ($cdkData != $model->getData($cdk)) {
                                    $customDataUpdate = true;
                                }
                            }
                            $model->setData($cdk, $cdkData, $merge);
                        }
                        unset($import[static::CUSTOM_DATA_KEY]);
                    }
                    if (!empty($import) || $customDataUpdate) {
                        $model->set($import)->save();
                        $modified = true;
                        $this->_updatedModels++;
                        $this->_modelsStatistics[$this->_currentModel]['updated_models']++;
                    } else {
                        if (in_array($model->id(), $ieAbsentIds)){
                            $modified = true;
                        }
                        $this->_notChanged++;
                        $this->_modelsStatistics[$this->_currentModel]['not_changed']++;
                    }
                } else {
                    $cData = null;
                    if (!empty($data[static::CUSTOM_DATA_KEY])) {
                        $cData = (array)$data[static::CUSTOM_DATA_KEY];
                        unset($data[static::CUSTOM_DATA_KEY]);
                    }
                    /** @var FCom_Core_Model_Abstract $model */
                    $model = $this->{$cm}->create($data);
                    if (!empty($cData)) {
                        $merge = true;
                        if(isset($cData['_merge'])) {
                            $merge = $cData['_merge'];
                            unset($cData['_merge']);
                        }
                        foreach ($cData as $cdk => $cdkData) {
                            if ($cdkData == '[]') {
                                $cdkData = null;
                            }
                            $model->setData($cdk, $cdkData, $merge);
                        }
                    }
                    $model->saveImport();
                    $modified = true;
                    $this->_newModels++;
                    $this->_modelsStatistics[$this->_currentModel]['new_models']++;
                }
            } catch (PDOException $e) {
                //$this->BDebug->logException($e);
                $this->log([
                    'msg' => $this->_("Exception during batch process"),
                    'data' => [
                        'msg' => $e->getMessage(),
                        'config' => $this->_currentConfig,
                        'row' => $data,
                        'update' => $import,
                        'model' => $model,
                    ]
                ], 'error');
            }

            if ($model) {
                if ($modified) {
                    $ieData['local_id'] = $model->id();
                    $ieHelperId->create($ieData)->save(true, true);
                    $this->_changedModelsIds[$model->id()] = $model->id();
//                    $this->_changedModelsIds[$model->id()] = $model;
                    $this->_batchChangedModelsIds[$model->id()] = $model->id();
                }
            } else {
                $this->log([
                    'msg' => $this->_("Invalid model"),
                    'data' => [
                        'id' => $id,
                        'row' => $ieData,
                    ]
                ], 'error');
            }
        }
        $this->BEvents->fire(__METHOD__ . ':afterBatch:' . $cm, [
            'import_id' => $this->_importCode,
            'records' => $this->_batchChangedModelsIds
        ]);
        $this->BEvents->fire(__METHOD__ . ':afterBatch', [
            'import_id' => $this->_importCode,
            'records' => $this->_batchChangedModelsIds,
            'modelName' => $cm,
            'statistic' => $this->_modelsStatistics[$cm]
        ]);
        $this->_currentRelated = [];
    }
    protected function _isArrayAssoc(array $arr)
    {
        return (bool)count(array_filter(array_keys($arr), 'is_string'));
    }
    /**
     * @param BModule $module
     * @return array
     */
    protected function _collectModuleModels($module)
    {
        $modelConfigs = [];
        if(!empty($module->noexport)) {
            return $modelConfigs;
        }
        $path         = $module->root_dir . '/Model';
        $files        = $this->BUtil->globRecursive($path, '*.php');
        if (empty($files)) {
            return $modelConfigs;
        }
        foreach ($files as $file) {
            $className = str_replace('/', '_', str_replace($module->root_dir, '', $file));
            $cls = $module->name . basename($className, '.php');
            if (class_exists($cls)) {
                $refl = new ReflectionClass($cls);
                if (!$refl->isAbstract() && $refl->hasMethod('registerImportExport')) { // instanceof does not work with class name
                    $this->{$cls}->registerImportExport($modelConfigs);
                }
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
            $this->log([
                'msg' => $this->_("Circular reference"),
                'data' => [
                    'name' => $name,
                ],
            ], 'error');
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
                                if (isset($models[$node])) {
                                    $tmpModel = $models[$node];
                                }
                            }
                        }

                        if (!isset($tmpModel)) {
                            $this->log([
                                'msg' => $this->_("Could not find valid configuration for model"),
                                'data' => [
                                    'model' => $node,
                                ],
                            ], "error");
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

    protected function _storeUID()
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
    protected function _writeLine($handle, $line) {
        $line = trim($line);
        $l    = strlen($line);
        if ($l < 1) {
            return;
        }
        $written = 0;
        while ($written < $l) { // check if entire line is written to file, if not try to continue from break point
            $written += fwrite($handle, trim(substr($line, $written)) . "\n");

            if (!$written) { // if written is false or 0, there has been an error writing.
                $this->log([
                    'msg' => $this->_("Writing failed"),
                    'data' => [
                        'line' => $line,
                    ],
                ], 'error');
                break;
            }
        }
    }

    /**
     * @param string $file
     * @param string $type
     * @return string
     */
    public function getFullPath($file, $type = 'export')
    {
        if (!in_array($type, ['export', 'import'])){
            $type = 'export';
        }
        if (!$file) {
            $file = $this->_defaultExportFile;
        }
        if ($this->BUtil->isPathAbsolute($file)) {
            return $file;
        }
        $path = $this->BApp->storageRandomDir() . '/' . $type;
        $this->BUtil->ensureDir($path);
        $file = $path . '/' . trim($file, '\\/');
        $realpath = str_replace('\\', '/', realpath(dirname($file)));
        if (strpos($realpath, $path) !== 0) {
            return false;
        }
        return $file;
    }

    /**
     * @return string
     */
    public function getDefaultExportFile()
    {
        return $this->_defaultExportFile;
    }

    /**
     * @param $modelName
     * @param $modelKeyConditions
     * @throws BException
     * @return array
     */
    protected function _getExistingModels($modelName, $modelKeyConditions)
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
            $this->log([
                'msg' => $this->_('SQL error during getExistingModels()'),
                'data' => [
                    'sql' => $orm->as_sql(),
                    'error' => $e->getMessage(),
                ],
            ], 'error');
        }

        foreach ($models as $model) {
            $id = '';
            foreach ((array)$this->_currentConfig['unique_key'] as $key) {
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
    protected function _populateRelated(&$batchData)
    {
        $related = [];
        if (isset($this->_currentConfig['related'])) {
            foreach ($this->_currentConfig['related'] as $field => $l) {
                foreach ($batchData as $data) { // prepare related search
                    // collect related ids
                    if (isset($data[$field])) {
                        $tmp = $data[$field];
                        if (!empty($this->_currentRelated[$l][$tmp])) {
                            continue; // if relation for this model and id is fetched, skip it
                        }
                        if (!isset($related[$l][$tmp])) {
                            $related[$l][$field][$tmp] = 1;
                        }
                    }
                } // end foreach data
            } // end foreach related
        } // end if related

        if (!empty($related) && !$this->_defaultSite) { // search related ids
            foreach ($this->_currentConfig['related'] as $f => $r) {
                if (empty($related[$r][$f])) {
                    #$this->BDebug->log('IMPORT: Empty related (' . $r . ', ' . $f . ')', 'ie.log');
                    /*
                    $this->log([
                        'msg' => 'IMPORT: Empty related (' . $r . ', ' . $f . ')',
                        'data' => [
                            'r' => $r,
                            'f' => $f,
                            'config' => $this->_currentConfig,
                            'current_related' => $this->_currentRelated,
                            'related' => $related,
                            'batch' => $batchData,
                        ],
                    ], 'error');
                    */
                    continue;
                }

                list($relModel, $field) = explode('.', $r);
                $tempRel = $this->FCom_Core_Model_ImportExport_Id->orm()
                    ->select(['import_id', 'local_id'])
                    ->join(
                        $this->FCom_Core_Model_ImportExport_Model->table(),
                        'iem.id=model_id and iem.model_name=\'' . $relModel . '\'',
                        'iem'
                    )
                    ->where(['site_id' => $this->_importId])
                    ->where(['import_id' => array_keys($related[$r][$f])])
                    ->find_many();

                /** @var FCom_Core_Model_Abstract $tr */
                foreach ($tempRel as $tr) {
                    $this->_currentRelated[$r][$tr->get('import_id')] = $tr->get('local_id');
                }
            }
        }

        if (isset($this->_currentConfig['related'])) {
            foreach ($this->_currentConfig['related'] as $field => $l) {
                foreach ($batchData as &$data) { // populate related data
                    if (isset($data[$field])) {
                        $tmp = $data[$field];
                        if (isset($this->_currentRelated[$l][$tmp])) {
                            $data[$field] = $this->_currentRelated[$l][$tmp];
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
    protected function _getWriteHandle($toFile)
    {
        if (is_resource($toFile)) {
            return $toFile;
        }

        if (strpos($toFile, 'php://') === 0) {
            $path = $toFile; // allow stream writers
        } else {
            $path = $this->getFullPath($toFile);
            if (!$path) {
                throw new BException($this->_("Could not obtain export location."));
            }
            $this->BUtil->ensureDir(dirname($path));
        }
        $fe = fopen($path, 'w');
        return $fe;
    }

    /**
     * @param $fromFile
     * @param $type
     * @return resource|false
     */
    protected function _getReadHandle($fromFile, $type = 'export')
    {
        if (is_resource($fromFile)) {
            return $fromFile;
        }

        if (strpos($fromFile, '://') !== false) {
            $path = $fromFile; // allow stream readers
        } else {
            $path = $this->getFullPath($fromFile, $type);
        }
        if (!is_readable($path)) {
            return false;
        }
        ini_set("auto_detect_line_endings", 1);
        $fi = fopen($path, 'r');
        return $fi;
    }

    /**
     * @param array $data
     * @return string
     */
    protected function _prepareImportMeta(&$data)
    {
        if(!$this->_importMetaParsed) {
            $importID   = static::DEFAULT_STORE_ID;
            $importMeta = array_shift($data);
            if ($importMeta) {
                $meta = is_string($importMeta)? json_decode($importMeta, true): $importMeta;
                if (isset($meta[static::STORE_UNIQUE_ID_KEY])) {
                    $importID = $meta[static::STORE_UNIQUE_ID_KEY];
                    $this->log([
                        'signal' => 'info',
                        'msg' => "Store id: $importID",
                        'data' => ['store_id' => $importID],
                    ]);
                } else {
                    $this->log([
                        'msg' => $this->_("Unique store id is not found, using default as key"),
                        'data' => [
                            'id' => $importID
                        ]
                    ], 'warning');
                    $this->_defaultSite = true;
                }
            }

            $importSite = $this->FCom_Core_Model_ImportExport_Site->load($importID, 'site_code');
            if (!$importSite) {
                $importSite = $this->FCom_Core_Model_ImportExport_Site->create(['site_code' => $importID])->save();
            }
            $this->_importId = $importSite->id();
            $this->_importCode = $importID;
            $this->_importModels = $this->FCom_Core_Model_ImportExport_Model->orm()->find_many_assoc('model_name');
            $this->BEvents->fire(
                __METHOD__ . ':meta',
                ['import_id' => $importID, 'import_site' => $importSite, 'import_models' => &$this->_importModels]
            );

            $this->_currentModel        = null;
            $this->_currentModelIdField = null;
            $this->_currentConfig       = null;
            $this->_currentFields       = [];
            $this->_currentRelated      = [];
            $this->_importMetaParsed    = true;
        }
        return $this->_importCode;
    }

    protected $_allowedExtensions = ['json' => 1];
    /**
     * @param string $fullFileName
     * @param bool $unlink
     * @return bool
     */
    public function validateImportFile($fullFileName, $unlink = true)
    {
        $ext   = pathinfo($fullFileName, PATHINFO_EXTENSION);
        $valid = true;
        if (!isset($this->_allowedExtensions[$ext])) {
            $valid = false;
        }

        $rh = $this->_getReadHandle($fullFileName, 'import');
        if (!$rh) {
            $valid = false;
        } else {
            $header = fgets($rh);
            if(trim($header) == "["){
                $header = fgets($rh);
            }
            $decodedHeader = json_decode($header, true);
            if(!$decodedHeader || !is_array($decodedHeader)){
                $valid = false;
            }
            fclose($rh);
        }

        if (!$valid && $unlink) {
            @unlink($fullFileName); // make sure invalid files are removed from the system;
        }
        return $valid;
    }

    protected function _beginExport($handle)
    {
        $this->_writeLine($handle, "[");
    }

    protected function _endExport($handle)
    {
        $this->_writeLine($handle, "]");
    }
}
