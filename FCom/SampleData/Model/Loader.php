<?php

/**
 * Created by pp
 * @project fulleron
 */
class FCom_SampleData_Model_Loader extends BClass
{
    protected static $defaultProductDataFile = 'products.csv';
    protected static $defaultDataPath = '../data';

    public static function loadProducts()
    {
        $file = BConfig::i()->get('modules/FCom_SampleData/sample_file');
        if(!$file){
            $file = static::$defaultProductDataFile;
        }

        $path = BConfig::i()->get('modules/FCom_SampleData/sample_path');

        if(!$path){
            $path = static::$defaultDataPath;
        }

        $fileName = rtrim( $path, '/' ) . DIRECTORY_SEPARATOR . ltrim( $file, '/' );
        $fileName = str_replace('\\', '/', realpath( $fileName ));

        die($fileName);
        // todo
    }
}