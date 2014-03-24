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
     * @var string
     */
    protected $currentModel;
    protected $currentModelIdField;
    protected $currentConfig;
    protected $currentFields;
    protected $currentRelated;
    protected $importModels;
    protected $defaultSite = false;

    /**
     * @return array
     */
    public function collectExportableModels()
    {
        $modules          = BModuleRegistry::i()->getAllModules();
        $exportableModels = array();
        foreach ( $modules as $module ) {
            /** @var BModule $module */
            if ( $module->run_status == BModule::LOADED ) {
                $exportableModels = BUtil::arrayMerge( $exportableModels, $this->collectModuleModels( $module ) );
            }
        }

        BEvents::i()->fire( __METHOD__ . ':after', array( 'models' => &$exportableModels ) );
        return $exportableModels;
    }

    public function export( $models = array(), $toFile = null )
    {
        $toFile = $this->getFullPath($toFile);

        BUtil::ensureDir( dirname( $toFile ) );
        $fe = fopen( $toFile, 'w' );

        if ( !$fe ) {
            BDebug::log( "Could not open $toFile for writing, aborting export." );
            return false;
        }
        $this->writeLine( $fe, json_encode( array( static::STORE_UNIQUE_ID_KEY => $this->storeUUID() ) ) );
        $exportableModels = $this->collectExportableModels();
        if ( !empty( $models ) ) {
            $diff = array_diff( array_keys( $exportableModels ), $models );
            foreach ( $diff as $d ) {
                unset( $exportableModels[ $d ] );
            }
        }

        $sorted = $this->sortModels( $exportableModels );

        foreach ( $sorted as $s ) {
            /** @var FCom_Core_Model_Abstract $model */
            $model   = $s[ 'model' ];
            if($model == 'FCom_Catalog_Model_Product'){
                // disable custom fields to avoid them adding bunch of fields to export
                FCom_CustomField_Main::i()->disable(true);
            }
            $sample = BDb::ddlFieldInfo($model::table());
            $idField = $model::getIdField();
            $heading = array( static::DEFAULT_MODEL_KEY => $model, static::DEFAULT_FIELDS_KEY => array() );
            foreach ( $sample as $key => $value ) {
                if ( !in_array( $key, $s[ 'skip' ] ) || $idField == $key ) {
                    // always export id column
                    $heading[ static::DEFAULT_FIELDS_KEY ][] = $key;
                }
            }
            $records = $model::i()->orm()->select($heading[ static::DEFAULT_FIELDS_KEY ])->find_many();
            if ( $records ) {
                $this->writeLine( $fe, BUtil::toJson( $heading ) );
                foreach ( $records as $r ) {

                    /** @var FCom_Core_Model_Abstract $r */
                    $data = $r->as_array();
                    $data = array_values($data);

                    $json = BUtil::toJson( $data );
                    $this->writeLine( $fe, $json );
                }

            }
        }

        return true;
    }

    public function import( $fromFile = null )
    {
        $start = microtime(true);
        /** @var FCom_PushServer_Model_Channel $channel */
        $channel = FCom_PushServer_Model_Channel::i()->getChannel('import', true);
        $channel->send( array( 'channel' => 'import', 'signal' => 'start', 'msg' => "Import started." ) );
        $bs = BConfig::i()->get("FCom_Core/import_export/batch_size", 100);

        $fromFile = $this->getFullPath( $fromFile );
        if(!is_readable($fromFile)){
            $channel->send( array( 'channel' => 'import', 'signal' => 'problem',
                                  'problem' => "Could not find file to import.\n$fromFile" ) );
            BDebug::log("Could not find file to import.");
            return false;
        }
        ini_set("auto_detect_line_endings", 1);
        $fi = fopen($fromFile, 'r');
        $ieConfig = $this->collectExportableModels();
        $importID = static::DEFAULT_STORE_ID;
        /** @var FCom_Core_Model_ImportExport_Model $ieHelperMod */
        $ieHelperMod = FCom_Core_Model_ImportExport_Model::i();

        $importMeta = fgets($fi);
        if ( $importMeta ) {
            $meta = json_decode( $importMeta );
            if ( isset( $meta->{static::STORE_UNIQUE_ID_KEY} ) ) {
                $importID = $meta->{static::STORE_UNIQUE_ID_KEY};
                $channel->send( array( 'channel' => 'import', 'signal' => 'info', 'msg' => "Store id: $importID" ) );
            } else {
                $channel->send(
                    array(
                        'channel' => 'import',
                        'signal'  => 'problem',
                        'problem' => "Unique store id is not found, using 'default' as key"
                    )
                );
                BDebug::warning( "Unique store id is not found, using 'default' as key" );
                $this->defaultSite = true;
            }
        }

        $importSite = FCom_Core_Model_ImportExport_Site::i()->load( $importID, 'site_code' );
        if ( !$importSite ) {
            $importSite = FCom_Core_Model_ImportExport_Site::i()->create( array( 'site_code' => $importID ) )->save();
        }
        $this->importId = $importSite->id();

        $this->importModels = $ieHelperMod->orm()->find_many_assoc('model_name');

        $this->currentModel = null;
        $this->currentModelIdField = null;
        $this->currentConfig = null;
        $this->currentFields = array();
        $this->currentRelated = array();

        $batchData = array();
        $cnt = 1;
        while ( ( $line = fgets( $fi ) ) !== false ) {
            $cnt++;
            $isHeading = false;
            /** @var FCom_Core_Model_Abstract $model */
            $model     = null;
            $data      = (array)json_decode( $line );
            if ( !empty( $data[ static::DEFAULT_MODEL_KEY ] ) ) {
                if(!empty($batchData)){
                    $this->importBatch($batchData);
                    $batchData = array();
                }
                $this->currentModel   = $data[ static::DEFAULT_MODEL_KEY ];
                $channel->send( array( 'channel' => 'import', 'signal' => 'info', 'msg' => "Importing: $this->currentModel" ) );
                if ( !isset( $this->importModels[ $this->currentModel ] ) ) {
                    // first time importing this model
                    $tm = $ieHelperMod->load( $this->currentModel, 'model_name' ); // check if it has been created
                    if ( !$tm ) {
                        // if not, create it and add it to list
                        $tm = $ieHelperMod->create( array( 'model_name' => $this->currentModel ) )->save();
                        $this->importModels[ $this->currentModel ] = $tm;
                    }
                }
                $cm = $this->currentModel;
                $this->currentModelIdField = $cm::i()->getIdField();
                $this->currentConfig  = $ieConfig[ $this->currentModel ];
                if ( !$this->currentConfig ) {
                    $channel->send( array( 'channel' => 'import', 'signal' => 'problem',
                                          'problem' => "Could not find I/E config for $this->currentModel." ) );
                    BDebug::warning( "Could not find I/E config for $this->currentModel." );
                    continue;
                }

                $isHeading = true;
            }


            if ( isset( $data[ static::DEFAULT_FIELDS_KEY ] ) ) {
                if ( !empty( $batchData ) ) {
                    $this->importBatch( $batchData );
                    $batchData = array();
                }
                $this->currentFields = $data[ static::DEFAULT_FIELDS_KEY ];
                $isHeading     = true;
            }

            if ( $isHeading ) {
                continue;
            }

            if ( !$this->isArrayAssoc( $data ) ) {
                $data = array_combine( $this->currentFields, $data );
            }

            $id = '';
            foreach ( (array)$this->currentConfig[ 'unique_key' ] as $key ) {
                $id .= $data[ $key ] . '/';
            }

            $batchData[ trim( $id, '/' ) ] = $data;

            if( $cnt % $bs != 0 ){
                continue; // accumulate batch data
            } else {
                $channel->send( array( 'channel' => 'import', 'signal' => 'info', 'msg' => "Importing #$cnt" ) );
            }

            $this->importBatch( $batchData );
        }
        if ( !feof( $fi ) ) {
            $channel->send( array( 'channel' => 'import', 'signal' => 'problem',
                                  'problem' => "Error: unexpected file fail" ) );
            BDebug::debug( "Error: unexpected file fail");
        }
        fclose( $fi );
        $channel->send( array( 'channel' => 'import', 'signal' => 'finished',
                              'msg' => "Done in: " . round( microtime(true) - $start) ) . " sec.");

        return true;
    }

    /**
     * @param array $batchData
     */
    protected function importBatch( $batchData )
    {
        /** @var FCom_Core_Model_ImportExport_Id $ieHelperId */
        $ieHelperId = FCom_Core_Model_ImportExport_Id::i();
        $cm = $this->currentModel;
        $related = array();
        $existing = array();
        foreach ( $batchData as $key => $data ) {
            foreach ( $this->currentConfig[ 'related' ] as $field => $l ) {
                // collect related ids
                if ( isset( $data[ $field ] ) ) {
                    $tmp = $data[ $field ];
                    if ( !isset( $related[ $l ][ $field ] ) ) {
                        $related[$l][ $field ] = $tmp;
                    }
                }
            }
            if ( isset( $this->currentConfig[ 'unique_key' ] ) ) {
                $where = array();
                foreach ( (array) $this->currentConfig[ 'unique_key' ] as $key ) {
                    if ( isset( $data[ $key ] ) ) {
                        $where[ $key ] = $data[ $key ];
                    }
                }
                if(!empty($where)){
                    $existing[] = array('AND'=>$where);
                }
            }

            $batchData[$key] = $data;
        }

        if ( !empty($related) && !$this->defaultSite ) {
            foreach ( $this->currentConfig[ 'related' ] as $r ) {
                if ( isset( $this->currentRelated[ $r ] ) ) {
                    continue;
                }
                list( $relModel, $field ) = explode( '.', $r );
                $tempRel = $ieHelperId::orm()
                                      ->select( array( 'import_id', 'local_id' ) )
                                      ->join(
                                          FCom_Core_Model_ImportExport_Model::table(),
                                          'iem.id=model_id and iem.model_name=' . $relModel,
                                          'iem'
                                      )
                                      ->where( array( 'store_id' => $this->importId ) )
                                      ->where(array('local_id' => $related ))
                                      ->find_many();

                foreach ( $tempRel as $tr ) {
                    $this->currentRelated[ $r ][ $tr[ 'import_id' ] ] = $tr[ 'local_id' ];
                }
            }
        }

        if(!empty($existing)){
            $oldModels = $this->getExistingModels( $cm, $existing );
        }

        foreach ( $batchData as $id => $data ) {
            foreach ( $this->currentConfig[ 'related' ] as $field => $l ) {
                // collect related ids
                if ( isset( $data[ $field ] ) ) {
                    $tmp = $data[ $field ];
                    if ( isset( $this->currentRelated[ $l ][ $tmp ] ) ) {
                        $data[ $field ] = $this->currentRelated[ $l ][ $tmp ];
                    }
                }
            }
            $ieData = array(
                'store_id' => $this->importId,
                'model_id' => $this->importModels[ $this->currentModel ]->id(),
                'import_id' => $data[ $this->currentModelIdField ],
                'local_id' => null,
            );
            unset( $data[ $this->currentModelIdField ] );
            $model = isset( $oldModels[ $id ] ) ? $oldModels[ $id ] : null;

            if ( $model ) {
                $model->set( $data )->save();
            } else {
                $model = $cm::i()->create( $data )->save();
            }

            $ieData[ 'local_id' ] = $model->id();
            $ieHelperId->create( $ieData )->save();
        }
    }
    protected function isArrayAssoc( array $arr )
    {
        return (bool)count( array_filter( array_keys( $arr ), 'is_string' ) );
    }
    /**
     * @param BModule $module
     * @return array
     */
    protected function collectModuleModels( $module )
    {
        $path         = $module->root_dir . '/Model/';
        $modelConfigs = array();
        $files        = BUtil::globRecursive( $path, '*.php' );
        if ( empty( $files ) ) {
            return $modelConfigs;
        }
        foreach ( $files as $file ) {
            $cls = $module->name . '_Model_' . basename( $file, '.php' );
            if ( method_exists( $cls, 'registerImportExport' ) ) { // instanceof does not work with class name
                $cls::i()->registerImportExport( $modelConfigs );
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
    public function sortModels( $models )
    {
        foreach ( $models as $k => $m ) {
            if ( !isset( $m[ 'related' ] ) || empty( $m[ 'related' ] ) ) {
                $this->_exportSorted[ ] = $m; // no dependencies, add to sorted
                $this->_isSorted[ $k ]  = 1;
                continue;
            }
        }

        foreach ( $models as $k => $m ) {
            $this->_sort( $m, $k, $models );
        }

        return $this->_exportSorted;
    }

    protected function _sort( array $model, $name, array $models )
    {
        if ( isset( $this->_tempSorted[ $name ] ) ) {
            BDebug::log( "Circular reference, $name", "ie.log" );
        } else {
            if ( !isset( $this->_isSorted[ $name ] ) ) {
                $this->_tempSorted[ $name ] = 1;
                if ( isset( $model[ 'related' ] ) ) {
                    foreach ( (array)$model[ 'related' ] as $node ) {
                        $t    = explode( '.', $node );
                        $node = $t[ 0 ];
                        if ( isset( $this->_isSorted[ $node ] ) ) {
                            continue;
                        }

                        if ( isset( $models[ $node ] ) ) {
                            $tmpModel = $models[ $node ];
                        } else {
                            if ( method_exists( $node, 'registerImportExport' ) ) {
                                $node::i()->registerImportExport( $models );
                                $tmpModel = $models[ $node ];
                            }
                        }

                        if ( !isset( $tmpModel ) ) {
                            BDebug::log( "Could not find valid configuration for $node", "ie.log" );
                            continue;
                        }
                        $this->_sort( $tmpModel, $node, $models );
                    }
                }
                $this->_isSorted[ $name ] = 1;
                $this->_exportSorted[ ]   = $model;
                unset( $this->_tempSorted[ $name ] );
            }
        }
    }

    protected function storeUUID()
    {
        $sUid = BConfig::i()->get( 'db/store_unique_id' );
        if ( !$sUid ) {
            $sUid = BUtil::randomString( 32 );
            BConfig::i()->set( 'db/store_unique_id', $sUid, false, true );
            FCom_Core_Main::i()->writeConfigFiles();
        }
        return $sUid;
    }

    /**
     * @param $handle
     * @param $line
     */
    protected function writeLine( $handle, $line ) {
        $line = trim( $line );
        $l    = strlen( $line );
        if ( $l < 1 ) {
            return;
        }
        $written = 0;
        while ( $written < $l ) { // check if entire line is written to file, if not try to continue from break point
            $written += fwrite( $handle, trim( substr( $line, $written ) ) . "\n" );

            if ( !$written ) { // if written is false or 0, there has been an error writing.
                BDebug::log( "Writing failed", 'ie.log' );
                break;
            }
        }
    }

    /**
     * @param string $file
     * @return string
     */
    public function getFullPath( $file )
    {
        if ( !$file ) {
            $file = $this->_defaultExportFile;
        }
        $path = BConfig::i()->get( 'fs/storage_dir' );

        $file = $path . '/export/' . trim( $file, '/' );
        if(strpos(realpath(dirname($file)), $path) !== 0){
            return false;
        }
        return $file;
    }

    /**
     * @param $modelName
     * @param $modelKeyConditions
     * @return array
     */
    protected function getExistingModels( $modelName, $modelKeyConditions )
    {
        /** @var BORM $orm */
        $orm = $modelName::i()->orm();
       // foreach ( $modelKeyConditions as $cond ) {
         //   $where = BDb::where($cond);
           // $orm->where(array('OR'=>$where));
        //}

        $orm->where_complex( array('OR'=>$modelKeyConditions), true );
        $models = $orm->find_many();
        $result = array();

        foreach ( $models as $model ) {
            $id = '';
            foreach ( (array)$this->currentConfig[ 'unique_key' ] as $key ) {
                $id .= $model->get( $key ) . '/';
            }
            $result[ trim( $id, '/' ) ] = $model;
        }
        return $result;
    }
}