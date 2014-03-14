<?php

/**
 * Created by pp
 * @project sellvana_core
 */
class FCom_Core_Model_ImportExport extends FCom_Core_Model_Abstract
{
    protected $_table = 'fcom_import_info';

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

    /**
     * @param array $models
     * @return array
     */
    public function sortModels( $models )
    {
        $circRefsArr = array();
        foreach ( $models as $modelName => $mod ) {
            $circRefs = $this->detectCircularReferences( $mod, $models );
            if ( $circRefs ) {
                foreach ( $circRefs as $circ ) {
                    $circRefsArr[ join( ' -> ', $circ ) ] = 1;

                    $s        = sizeof( $circ );
                    $mod1name = $circ[ $s - 1 ];
                    $mod2name = $circ[ $s - 2 ];
                    $mod1     = $models[ $mod1name ];
                    $mod2     = $models[ $mod2name ];
                    foreach ( $mod1[ 'related' ] as $i => $p ) {
                        if ( $p === $mod2name ) {
                            unset( $mod1[ 'related' ][ $i ] );
                        }
                    }
                }
            }
        }
        foreach ( $circRefsArr as $circRef => $_ ) {
            BDebug::warning( 'Circular reference detected: ' . $circRef );
        }

        // get modules without dependencies
        $rootModules = array();
        foreach ( $models as $modName => $mod ) {
            if ( isset( $mod[ 'related' ] ) || !empty( $mod[ 'related' ] ) ) {
                $rootModules[ $modName ] = $mod;
            }
        }

        // begin algorithm
        $sorted = array();
        while ( $models ) {
            // check for circular reference
            if ( !$rootModules ) {
                BDebug::warning( 'Circular reference detected, aborting module sorting' );
                return false;
            }
            $rKeys = array_keys( $rootModules );
            // remove this node from root modules and add it to the output
            $n             = array_pop( $rootModules );
            $rk            = array_pop( $rKeys );
            $sorted[ $rk ] = $n;

            foreach ( $n[ 'related' ] as $key => $reference ) {
                $refModel = $models[ $reference ];
                unset($n[ 'related' ][$key]);
                // todo
            }

            // for each of its children: queue the new node, finally remove the original
            for ( $i = count( $n[ 'related' ] ) - 1; $i >= 0; $i-- ) {
                // get child module
                $childModule = $modules[ $n->children[ $i ] ];
                // remove child modules from parent
                unset( $n->children[ $i ] );
                // remove parent from child module
                unset( $childModule->parents[ array_search( $n->name, $childModule->parents ) ] );
                // check if this child has other parents. if not, add it to the root modules list
                if ( !$childModule->parents ) {
                    array_push( $rootModules, $childModule );
                }
            }
            // remove processed module from list
            unset( $modules[ $n->name ] );
        }
        // move modules that have load_after=='ALL' to the end of list
        foreach ( $sorted as $modName => $mod ) {
            if ( $mod->load_after === 'ALL' ) {
                unset( $sorted[ $modName ] );
                $sorted[ $modName ] = $mod;
            }
        }
        return $sorted;
    }

    /**
     * Detect circular model dependencies references
     */
    public function detectCircularReferences( $model, $models, $depPathArr = array() )
    {
        $circ = array();
        if ( $model[ 'related' ] ) {
            foreach ( $model[ 'related' ] as $rel ) {
                if ( isset( $depPathArr[ $rel ] ) ) {
                    $found    = false;
                    $circPath = array();
                    foreach ( $depPathArr as $k => $_ ) {
                        if ( $rel === $k ) {
                            $found = true;
                        }
                        if ( $found ) {
                            $circPath[ ] = $k;
                        }
                    }
                    $circPath[ ] = $rel;
                    $circ[ ]     = $circPath;
                } else {
                    $depPathArr1         = $depPathArr;
                    $depPathArr1[ $rel ] = 1;
                    $circ += $this->detectCircularReferences( $models[ $rel ], $models, $depPathArr1 );
                }
            }
        }
        return $circ;
    }
}