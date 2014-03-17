<?php

/**
 * Created by pp
 * @project sellvana_core
 */
class FCom_Core_Model_ImportExport extends FCom_Core_Model_Abstract
{
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
        $this->writeLine( $fe, $this->storeUUID() );
        $exportableModels = $this->collectExportableModels();
        if ( !empty( $models ) ) {
            $diff = array_diff( array_keys( $exportableModels ), $models );
            foreach ( $diff as $d ) {
                unset( $exportableModels[ $d ] );
            }
        }

        $sorted = $this->sortModels( $exportableModels );

        return true;
    }

    /**
     * @param BModule $module
     * @return array
     */
    protected function collectModuleModels( $module )
    {
        $path         = $module->root_dir . '/Model/*.php';
        $modelConfigs = array();
        $files        = glob( $path );
        if ( empty( $files ) ) {
            return $modelConfigs;
        }
        $currentClasses = get_declared_classes();
        $newClasses     = array();
        $baseClass      = $module->name . '_Model_';
        foreach ( $files as $file ) {
            include_once $file;
            $baseName = explode( '.', basename( $file ) );
            $baseName = $baseName[ 0 ];
            $cls      = $baseClass . $baseName;
            if ( class_exists( $cls ) ) {
                $newClasses[ ] = $cls;
            }
        }

        $newClasses = BUtil::arrayMerge( $newClasses, array_diff( get_declared_classes(), $currentClasses ) );
        foreach ( $newClasses as $model ) {
            if ( method_exists( $model, 'modelExportProfile' ) ) {
                $config = $model::modelExportProfile();
                if ( empty( $config ) ) {
                    continue;
                }
                $modelConfigs = array_merge( $modelConfigs, $config );
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
                            if ( class_exists( $node ) ) {
                                $tmpModel        = array_pop( $node::modelExportProfile() );
                                $models[ $node ] = $tmpModel;
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

    protected function storeUUID()
    {
        return '11'; // todo generate unique store id
    }

    /**
     * @param $handle
     * @param $line
     */
    protected function writeLine( $handle, $line )
    {
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