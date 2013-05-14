<?php

/**
* Falls back to pecl extensions: yaml, syck
*/
class BYAML extends BCLass
{
    static protected $_peclYaml = null;
    
    static public function bootstrap()
    {
        
    }
    
    static public function load($filename, $cache=true)
    {
        $filename = realpath($filename);
        
        if ($cache) {
            $filemtime = filemtime($filename);
            if (false === $filemtime) {
                return null;
            }
            $cachedFilename = md5($filename).'.php';
            if (file_exists($cachedFilename)) {
                $arrayData = include($filename);
                if (!empty($arrayData['__VERSION__']) && $arrayData['__VERSION__'] === $filemtime) {
                    return $arrayData; //TODO: clear __VERSION__ ?
                }
            }
        }
        
        $yamlData = file_get_contents($filename);
        $arrayData = static::parse($yamlData);

        if ($cache) {
            $cacheData = "<?php return " . var_export($arrayData, 1) . ';';
            $prefixDir = substr($cachedFilename, 0, 2);
            $cacheDir = BConfig::i()->get('fs/storage_dir').'/yaml';
            BUtil::ensureDir($cacheDir.'/'.$prefixDir);
            file_put_contents($cacheDir.'/'.$prefixDir.'/'.$cacheFilename, $cacheData);
        }
        
        return $arrayData;
    }
    
    static public function init()
    {
        if (is_null(static::$_peclYaml)) {
            static::$_peclYaml = function_exists('yaml_parse');

            if (!static::$_peclYaml) {
                require_once(__DIR__.'/spyc.php');
            }
        }
        return true;
    }
    
    static public function parse($yamlData)
    {
        static::init();
        
        if (static::$_peclYaml) {
            return yaml_parse($yamlData);
        }
        
        return Spyc::YAMLLoadString($yamlData);
    }
    
    static public function dump($arrayData)
    {
        static::init();
        
        if (static::$_peclYaml) {
            return yaml_emit($arrayData);
        }
        
        return Spyc::YAMLDump($arrayData);
    }
}