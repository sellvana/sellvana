<?php

/**
 * Created by pp
 * @project sellvana_core
 */
class FCom_Core_Model_ImportExport extends FCom_Core_Model_Abstract
{
    protected static $_origClass = 'FCom_Core_Model_ImportExport';
    protected $_table = 'fcom_import_info';
    protected $_default_export_file = 'export.json';

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
        if ( !$toFile ) {
            $toFile = $this->_default_export_file;
        }
        $path = BConfig::i()->get( 'fs/storage_dir' );

        $toFile = $path . '/export/' . trim( $toFile, '/' );
        BUtil::ensureDir( dirname( $toFile ) );
        $fe = fopen( $toFile, 'w' );

        if ( !$fe ) {
            BDebug::log( "Could not open $toFile for writing, aborting export." );
            return false;
        }
        $this->writeLine( $fe, json_encode( array( '_store_unique_id' => $this->storeUUID() ) ) );
        $exportableModels = $this->collectExportableModels();
        if ( !empty( $models ) ) {
            $diff = array_diff( array_keys( $exportableModels ), $models );
            foreach ( $diff as $d ) {
                unset( $exportableModels[ $d ] );
            }
        }

        $sorted = $this->sortModels( $exportableModels );

        foreach ( $sorted as $s ) {
            $model   = $s[ 'model' ];
            if($model == 'FCom_Catalog_Model_Product'){
                // disable custom fields to avoid them adding bunch of fields to export
                FCom_CustomField_Main::i()->disable(true);
            }
            $sample = BDb::ddlFieldInfo($model::table());
            $heading = array( '_default_model' => $model, '_default_fields' => array() );
            foreach ( $sample as $key => $value ) {
                if ( !in_array( $key, $s[ 'skip' ] ) ) {
                    $heading['_default_fields'][] = $key;
                }
            }
            $records = $model::i()->orm()->select($heading['_default_fields'])->find_many();
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

    protected $_export_sorted;
    protected $_temp_sorted;
    protected $_is_sorted;

    /**
     * @param array $models
     * @return array
     */
    public function sortModels( $models )
    {
        foreach ( $models as $k => $m ) {
            if ( !isset( $m[ 'related' ] ) || empty( $m[ 'related' ] ) ) {
                $this->_export_sorted[ ] = $m; // no dependencies, add to sorted
                $this->_is_sorted[ $k ]  = 1;
                continue;
            }
        }

        foreach ( $models as $k => $m ) {
            $this->_sort( $m, $k, $models );
        }

        return $this->_export_sorted;
    }

    protected function _sort( array $model, $name, array $models )
    {
        if ( isset( $this->_temp_sorted[ $name ] ) ) {
            BDebug::log( "Circular reference, $name", "ie.log" );
        } else {
            if ( !isset( $this->_is_sorted[ $name ] ) ) {
                $this->_temp_sorted[ $name ] = 1;
                if ( isset( $model[ 'related' ] ) ) {
                    foreach ( (array)$model[ 'related' ] as $node ) {
                        $t    = explode( '.', $node );
                        $node = $t[ 0 ];
                        if ( isset( $this->_is_sorted[ $node ] ) ) {
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
                $this->_is_sorted[ $name ] = 1;
                $this->_export_sorted[ ]   = $model;
                unset( $this->_temp_sorted[ $name ] );
            }
        }
    }

    protected
    function storeUUID()
    {
        $suid = BConfig::i()->get( 'db/store_unique_id' );
        if ( !$suid ) {
            $suid = uniqid( BConfig::i()->get( '' ) );
            BConfig::i()->set( 'db/store_unique_id', $suid, true )->save();
        }
        return $suid;
    }

    /**
     * @param $handle
     * @param $line
     */
    protected
    function writeLine(
        $handle,
        $line
    ) {
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
}