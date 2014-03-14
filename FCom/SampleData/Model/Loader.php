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
        $basePath = BConfig::i()->get( 'fs/root_dir' ) . '/storage';
        $ds       = DIRECTORY_SEPARATOR;

        $file     = BConfig::i()->get( 'modules/FCom_SampleData/sample_file' );
        if ( !$file ) {
            $file = static::$defaultProductDataFile;
        }

        $path = BConfig::i()->get( 'modules/FCom_SampleData/sample_path' );
        if ( !$path ) {
            $path = static::$defaultDataPath;
        }
        $path = $basePath . DIRECTORY_SEPARATOR . $path;

        $fileName = rtrim( $path, $ds ) . $ds . ltrim( $file, $ds );
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