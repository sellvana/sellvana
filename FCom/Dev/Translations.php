<?php

class FCom_Dev_Translations extends BClass
{

    /**
     * Collect all translation keys & values start from $rootDir and save into $targetFile
     * @param string $rootDir - start directory to look for translation calls BLocale::_
     * @param string $targetFile - output file which contain translation values
     * @throws Exception
     * @return boolean - TRUE on success
     * @example BLocale::collectTranslations('/www/unirgy/fulleron/FCom/Disqus', '/www/unirgy/fulleron/FCom/Disqus/tr.csv');
     */
    static public function collectTranslations($rootDir, $targetFile)
    {
        //find files recursively
        $files = static::getFilesFromDir($rootDir);
        if (empty($files)) {
            return true;
        }

        //find all BLocale::_ calls and extract first parameter - translation key
        $keys = array();
        foreach($files as $file) {
            $source = file_get_contents($file);
            $source = static::getTwigSource($file, $source);
            $tokens = token_get_all($source);
            $func = 0;
            $class = 0;
            $sep = 0;
            foreach($tokens as $token) {
                if (empty($token[1])){
                    continue;
                }
                if ($token[1] =='BLocale') {
                    $class = 1;
                    continue;
                }
                if ($class && $token[1] == '::') {
                    $class = 0;
                    $sep = 1;
                    continue;
                }
                if ($sep && $token[1] == '_') {
                    $sep = 0;
                    $func = 1;
                    continue;
                }
                if($func) {
                    $token[1] = trim($token[1], "'\"");
                    $keys[$token[1]] = '';
                    $func = 0;
                    continue;
                }
            }
        }

        //import translation from $targetFile

        static::$_tr = '';
        static::addTranslationsFile($targetFile);
        $translations = static::getTranslations();

        //find undefined translations
        foreach ($keys as $key => $v) {
            if(isset($translations[$key])) {
                unset($keys[$key]);
            }
        }
        //add undefined translation to $targetFile
        $newTranslations = array();
        if ($translations) {
            foreach($translations as $trKey => $tr){
                list(,$newTranslations[$trKey]) = each($tr);
            }
        }
        $newTranslations = array_merge($newTranslations, $keys);

        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'php':
                static::saveToPHP($targetFile, $newTranslations);
                break;
            case 'csv':
                static::saveToCSV($targetFile, $newTranslations);
                break;
            case 'json':
                static::saveToJSON($targetFile, $newTranslations);
                break;
            case 'po':
                static::saveToJSON($targetFile, $newTranslations);
                break;
            default:
                throw new Exception("Undefined format of translation targetFile. Possible formats are: json/csv/php");
        }

    }

    static protected function saveToPHP($targetFile, $array)
    {
        $code = '';
        foreach($array as $k => $v) {
            if (!empty($code)) {
                $code .= ",\n";
            }
            $code .= "'{$k}' => '".addslashes($v)."'";
        }
        $code = "<?php return array({$code});";
        file_put_contents($targetFile, $code);
    }

    static protected function saveToJSON($targetFile, $array)
    {
        $json = json_encode($array);
        file_put_contents($targetFile, $json);
    }

    static protected function saveToCSV($targetFile, $array)
    {
        $handle = fopen($targetFile, "w");
        foreach ($array as $k => $v) {
            $k = trim($k, '"');
            fputcsv($handle, array($k, $v));
        }
        fclose($handle);
    }

    static protected function saveToPO($targetFile, $array)
    {
        $handle = fopen($targetFile, "w");
        foreach ($array as $k => $v) {
            $v = str_replace("\n", '\n', $v);
            fwrite($handle, "msgid \"{$k}\"\nmsgstr \"{$v}\"\n\n");
        }
        fclose($handle);
    }

    /**
     * @param string $file filename
     * @param string $source file content
     * @return Twig_Node_Module
     */
    protected static function getTwigSource($file, $source)
    {
        static::initTwig();
        $info = pathinfo($file, PATHINFO_EXTENSION);
        if ($info == 'twig') {
            $stringTwig = static::getTwigEnv();
            try {
                $source = $stringTwig->compile($stringTwig->parse($stringTwig->tokenize($source)));
            } catch (Twig_Error_Syntax $e) {
                BDebug::log(sprintf("\n\n%s: Exception %s in file %s",date("Y-m-d H:i:s"), get_class($e), $file),
                            "translations_error.log");
                BDebug::log($e->getMessage(), "translations_error.log");
                return "";
            }
        }
        return $source;
    }
    protected static $twig;
    protected static function initTwig()
    {
        if(!static::$twig){
            BEvents::i()->on("FCom_LibTwig_Main::init", __CLASS__ . "::setTwigEnv");
            $bDir = BModuleRegistry::i()->module("FCom_Core")->baseDir();
            echo $bDir;
            FCom_LibTwig_Main::init($bDir);
            echo "after Initing twig event\n";
            static::$twig = 1;
        }
    }

    /**
     * @var Twig_Environment
     */
    protected static $twigEnv;
    public static function setTwigEnv($args)
    {
        static::$twigEnv = $args['string_adapter'];
    }

    /**
     * @return null|Twig_Environment
     */
    private static function getTwigEnv()
    {
        return static::$twigEnv;
    }

}
