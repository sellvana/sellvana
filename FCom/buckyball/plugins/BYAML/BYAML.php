<?php

/**
* Falls back to pecl extensions: yaml, syck
*/
class BYAML extends BCLass
{
    static protected $_peclYaml = null;
    static protected $_peclSyck = null;

    static public function bootstrap()
    {

    }

    static public function load($filename, $cache=true)
    {
        $filename = realpath($filename);

        $filemtime = filemtime($filename);
        if (false === $filemtime) {
            throw new BException('Missing YAML file: '.$filename);
        }

        if ($cache) {
            $cacheData = BCache::i()->load('BYAML--'.$filename);
            if (!empty($cacheData) && !empty($cacheData['v']) && $cacheData['v'] === $filemtime) {
                return $cacheData['d'];
            }
        }

        $yamlData = file_get_contents($filename);
        $arrayData = static::parse($yamlData);

        if ($cache) {
            BCache::i()->save('BYAML--'.$filename, array('v'=>$filemtime, 'd'=>$arrayData));
        }

        return $arrayData;
    }

    static public function init()
    {
        if (is_null(static::$_peclYaml)) {
            static::$_peclYaml = function_exists('yaml_parse');

            if (!static::$_peclYaml) {
                static::$_peclSyck = function_exists('syck_load');
            }

            if (!static::$_peclYaml && !static::$_peclSyck) {
                require_once(__DIR__.'/spyc.php');
                /*
                require_once(__DIR__.'/Yaml/Exception/ExceptionInterface.php');
                require_once(__DIR__.'/Yaml/Exception/RuntimeException.php');
                require_once(__DIR__.'/Yaml/Exception/DumpException.php');
                require_once(__DIR__.'/Yaml/Exception/ParseException.php');
                require_once(__DIR__.'/Yaml/Yaml.php');
                require_once(__DIR__.'/Yaml/Parser.php');
                require_once(__DIR__.'/Yaml/Dumper.php');
                require_once(__DIR__.'/Yaml/Escaper.php');
                require_once(__DIR__.'/Yaml/Inline.php');
                require_once(__DIR__.'/Yaml/Unescaper.php');
                */
            }
        }
        return true;
    }

    static public function parse($yamlData)
    {
        static::init();

        if (static::$_peclYaml) {
            return yaml_parse($yamlData);
        } elseif (static::$_peclSyck) {
            return syck_load($yamlData);
        }

        if (class_exists('Spyc', false)) {
            return Spyc::YAMLLoadString($yamlData);
        } else {
            return Symfony\Component\Yaml\Yaml::parse($yamlData);
        }
    }

    static public function dump($arrayData)
    {
        static::init();

        if (static::$_peclYaml) {
            return yaml_emit($arrayData);
        } elseif (static::$_peclSyck) {
            return syck_dump($arrayData);
        }

        if (class_exists('Spyc', false)) {
            return Spyc::YAMLDump($arrayData);
        } else {
            return Symfony\Component\Yaml\Yaml::dump($arrayData);
        }
    }
}