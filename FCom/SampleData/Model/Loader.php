<?php

/**
 * Created by pp
 * @project fulleron
 */
class FCom_SampleData_Model_Loader extends BClass
{
    protected static $defaultProductDataFile = 'products.csv';
    protected static $defaultDataPath = 'data';

    public static function loadProducts()
    {
        $file     = BConfig::i()->get( 'modules/FCom_SampleData/sample_file' );
        $module   = BModuleRegistry::i()->currentModule();
        $basePath = '';
        if ( $module ) {
            $basePath = $module->root_dir;
        }
        if ( !$file ) {
            $file = static::$defaultProductDataFile;
        }

        $path = BConfig::i()->get( 'modules/FCom_SampleData/sample_path' );

        if ( !$path ) {
            $path = $basePath . DIRECTORY_SEPARATOR . static::$defaultDataPath;
        }

        $fileName = rtrim( $path, '/' ) . DIRECTORY_SEPARATOR . ltrim( $file, '/' );
        $fileName = str_replace( '\\', '/', realpath( $fileName ) );
        $fr       = fopen( $fileName, 'r' );
        $headings = fgetcsv( $fr );

        $rows = array();

        while ( $line = fgetcsv( $fr ) ) {
            $row = array_combine( $headings, $line );
            if ( $row ) {
                $rows[ ] = $row;
            }
        }
        FCom_Catalog_Model_Product::i()->import( $rows );
        // todo
    }
}