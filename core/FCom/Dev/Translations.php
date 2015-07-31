<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Dev_Translations
 *
 * @property FCom_LibTwig_Main $FCom_LibTwig_Main
 */

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
    public function collectTranslations($rootDir, $targetFile)
    {
        //find files recursively
        $files = $this->getFilesFromDir($rootDir);
        if (empty($files)) {
            return true;
        }

        //find all BLocale::_ calls and extract first parameter - translation key
        $keys = [];
        foreach ($files as $file) {
            $source = file_get_contents($file);
            $source = $this->_getTwigSource($file, $source);
            $tokens = token_get_all($source);
            $func = 0;
            $class = 0;
            $sep = 0;
            foreach ($tokens as $token) {
                if (empty($token[1])) {
                    continue;
                }
                if ($token[1] == 'BLocale') {
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
                if ($func) {
                    $token[1] = trim($token[1], "'\"");
                    $keys[$token[1]] = '';
                    $func = 0;
                    continue;
                }
            }
        }

        //import translation from $targetFile

        static::$_tr = '';
        $this->addTranslationsFile($targetFile);
        $translations = $this->getTranslations();

        //find undefined translations
        foreach ($keys as $key => $v) {
            if (isset($translations[$key])) {
                unset($keys[$key]);
            }
        }
        //add undefined translation to $targetFile
        $newTranslations = [];
        if ($translations) {
            foreach ($translations as $trKey => $tr) {
                list(, $newTranslations[$trKey]) = each($tr);
            }
        }
        $newTranslations = array_merge($newTranslations, $keys);

        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'php':
                $this->_saveToPHP($targetFile, $newTranslations);
                break;
            case 'csv':
                $this->_saveToCSV($targetFile, $newTranslations);
                break;
            case 'json':
                $this->_saveToJSON($targetFile, $newTranslations);
                break;
            case 'po':
                $this->_saveToJSON($targetFile, $newTranslations);
                break;
            default:
                throw new Exception("Undefined format of translation targetFile. Possible formats are: json/csv/php");
        }

    }

    protected function _saveToPHP($targetFile, $array)
    {
        $code = '';
        foreach ($array as $k => $v) {
            if (!empty($code)) {
                $code .= ",\n";
            }
            $code .= "'{$k}' => '" . addslashes($v) . "'";
        }
        $code = "<?php return array({$code});";
        file_put_contents($targetFile, $code);
    }

    protected function _saveToJSON($targetFile, $array)
    {
        $json = json_encode($array);
        file_put_contents($targetFile, $json);
    }

    protected function _saveToCSV($targetFile, $array)
    {
        $handle = fopen($targetFile, "w");
        foreach ($array as $k => $v) {
            $k = trim($k, '"');
            fputcsv($handle, [$k, $v]);
        }
        fclose($handle);
    }

    protected function _saveToPO($targetFile, $array)
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
    protected function _getTwigSource($file, $source)
    {
        $this->_initTwig();
        $info = pathinfo($file, PATHINFO_EXTENSION);
        if ($info == 'twig') {
            $stringTwig = $this->getTwigEnv();
            try {
                $source = $stringTwig->compile($stringTwig->parse($stringTwig->tokenize($source)));
            } catch (Twig_Error_Syntax $e) {
                $this->BDebug->log(sprintf("\n\n%s: Exception %s in file %s", date("Y-m-d H:i:s"), get_class($e), $file),
                            "translations_error.log");
                $this->BDebug->log($e->getMessage(), "translations_error.log");
                return "";
            }
        }
        return $source;
    }
    protected static $_twig;
    protected function _initTwig()
    {
        if (!static::$_twig) {
            $this->BEvents->on("FCom_LibTwig_Main::init", __CLASS__ . "::setTwigEnv");
            $bDir = $this->BModuleRegistry->module("FCom_Core")->baseDir();
            echo $bDir;
            $this->FCom_LibTwig_Main->init($bDir);
            echo "after Initing twig event\n";
            static::$_twig = 1;
        }
    }

    /**
     * @var Twig_Environment
     */
    protected static $_twigEnv;
    public function setTwigEnv($args)
    {
        static::$_twigEnv = $args['string_adapter'];
    }

    /**
     * @return null|Twig_Environment
     */
    private function getTwigEnv()
    {
        return static::$_twigEnv;
    }

}
