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
        $start = microtime(true);
        $config    = BConfig::i();
        $batchSize = $config->get( 'modules/FCom_SampleData/batch_size' );
        if ( !$batchSize ) {
            $batchSize = 100;
        }

        $basePath = $config->get( 'fs/root_dir' ) . '/storage';
        $ds       = DIRECTORY_SEPARATOR;

        $file = $config->get( 'modules/FCom_SampleData/sample_file' );
        if ( !$file ) {
            $file = static::$defaultProductDataFile;
        }

        $path = $config->get( 'modules/FCom_SampleData/sample_path' );
        if ( !$path ) {
            $path = static::$defaultDataPath;
        }
        $path = $basePath . DIRECTORY_SEPARATOR . $path;

        $fileName = rtrim( $path, $ds ) . $ds . ltrim( $file, $ds );
        $fileName = str_replace( '\\', '/', realpath( $fileName ) );
        $fr       = fopen( $fileName, 'r' );
        $headings = fgetcsv( $fr );

        $rows = array();
        $i = 0;
        while ( $line = fgetcsv( $fr ) ) {
            $row = array_combine( $headings, $line );
            if ( $row ) {
                $rows[ ] = $row;
            }
            if($i++ == $batchSize){
                FCom_Catalog_Model_Product::i()->import( $rows );
                $rows = array();
                $i = 1;
            }
        }
        FCom_Catalog_Model_Product::i()->import( $rows );
        BDebug::log("Sample data imported in: " . round(microtime(true) - $start, 4) . " seconds.");
    }
}