<?php

/**
 * Class FCom_Core_Shell_Import
 *
 * @property FCom_Shell_Shell $FCom_Shell_Shell
 * @property FCom_Core_ImportExport $FCom_Core_ImportExport
 */
class FCom_Core_Shell_DataIo extends FCom_Shell_Action_Abstract
{
    static protected $_origClass = __CLASS__;

    const OPTION_FILE    = 'f';
    const OPTION_VERBOSE = 'v';
    const OPTION_QUIET   = 'q';
    const OPTION_MODEL   = 'm';
    const OPTION_ALL     = 'all';

    static protected $_actionName = 'data-io';

    static protected $_availOptions = [
        'f?' => 'file',
        'v'  => 'verbose',
        'q'  => 'quiet',
        'm?' => 'model',
        'all'
    ];

    protected $_importStarted = 0;
    protected $_bachStarted = 0;
    protected $_memoryStarted = 0;

    /**
     * Short help.
     *
     * @return string
     */
    public function getShortHelp()
    {
        return 'Import\Export management';
    }

    /**
     * Full help
     *
     * @return string
     */
    public function getLongHelp()
    {
        return <<<EOT

Data import/export.

Syntax: {white*}{$this->getParam(self::PARAM_SELF)} {$this->getActionName()} {green*}<command>{/} {red*}[parameters]{/}

Commands:

    {green*}list{/}     List of available files for import
    {green*}import{/}   Import file(s)
    {green*}export{/}   Export to file

    {green*}help{/}     This help

Options:

  File/Model selection:
    {green*}-f {/}{cyan*}<file>{/}
    {green*}--file={/}{cyan*}<file>{/}     File(s) to import / File to export

    {green*}-m {/}{cyan*}<model>{/}
    {green*}--model={/}{cyan*}<model>{/}   Model(s) to export({red}Only for export{/})

  Overwrite control:
    {green*}    --all{/}         Export all models ({red}Only for export, ignore "--model" option{/})

  Informative output:
    {green*}-v, --verbose{/}     Verbose output of the process
    {green*}-s, --silent{/}      Disable all output of the process

Examples:


EOT;
    }

    /**
     * Shell GUI of Yes/No
     *
     * @param $question
     * @return bool
     */
    public function askYesNo($question)
    {
        $answer = false;
        $offset = 0;
        while (true) {
            $shell = $this->FCom_Shell_Shell;
            $this->out('{yellow}' . $question . '{/} [Y/N] ' . str_pad('', $offset));
            if ($offset) {
                $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_BACK, $offset));
            }
            $answer = strtolower($shell->stdin());
            $offset = strlen($answer);
            if (in_array($answer, ['y', 'n'])) {
                $answer = ($answer == 'y' ? true : false);
                break;
            }
            $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_UP, 1));
        }
        return $answer;
    }

    /**
     * @param $string
     * @return array
     */
    public function strToIntArray($string)
    {
        $string = preg_replace('/\s+/', '', $string);
        $string = explode(',', str_replace([';', ':'], ',', $string));

        $processed = [];
        foreach ($string as $fileId) {
            if (preg_match('/^[0-9]*$/', $fileId)) {
                $processed[] = (int)$fileId;
                continue;
            }
            $range = explode('-', $fileId);
            if (count($range) == 2) {
                $range = range($range[0], $range[1]);
                $processed = array_merge($processed, $range);
            }
        }

        return array_unique($processed);
    }

    /**
     *
     */
    protected function _run()
    {
        if ($this->getOption(self::OPTION_QUIET)) {
            $this->FCom_Shell_Shell->setOutMode(FCom_Shell_Shell::OUT_MODE_QUIET);
        }
        $this->_processCommand();
    }

    /**
     * List of available files for import
     */
    protected function _listCmd()
    {
        $files = $this->getAllAvailableFilesForImport();

        if ($files) {
            $this->println("\n{green}Available files for import:{/}");

            $maxLength = 0;
            foreach ($files as $file) {
                $maxLength = strlen($file['name']) > $maxLength ? strlen($file['name']) : $maxLength;
            }
            $maxLength += 1;

            $i = 1;
            foreach ($files as $file) {
                $str = '  [' . $i++ . '] '
                    . '{purple*}' . str_pad($file['name'], $maxLength) . '{/}'
                    . $file['file_size'];

                $this->println($str);
            }
            return;
        }

        $this->println('{green*}INFO:{/} No files to import.');
    }

    /**
     * Import file
     */
    protected function _importCmd()
    {
        $files = $this->getFilesForImport();

        if (!count($files)){
            $this->println(PHP_EOL . '{green*}INFO:{/} No files to import.');
            return;
        }

        try {
            //Fix of memory leak
            $this->BDebug->disableAllLogging();
            $this->BDebug->mode(BDebug::MODE_IMPORT);

            $this->_memoryStarted = memory_get_usage();

            if ($this->getOption(self::OPTION_VERBOSE)) {
                $this->println(PHP_EOL . '{green}Files in the queue for importing:{/}');
                foreach ($files as $file) {
                    $this->println('  {purple*}' . $file['name']);
                }
            }

            $importer = $this->FCom_Core_ImportExport;
            foreach ($files as $file) {
                $file = $file['fullpath'];

                $this->println(PHP_EOL . '{green*}START FILE: {/}{purple*}' . $file . '{/}');

                $external = (strpos($file, FULLERON_ROOT_DIR) ===  false);
                if (!$importer->validateImportFile($file, !$external)) {
                    $this->println('{blue*}NOTICE:{/} Invalid import file. Will be skipped.');
                    continue;
                }
                $importer->importFile($file);

                $this->println(PHP_EOL . '{green*}END FILE: {/}{purple*}' . $file . '{/}');
            }
        } catch (Exception $e) {
            $this->BDebug->logException($e);
            $this->println('{red*}FATAL ERROR:{/} ' . $e->getMessage());
            die;
        }
    }

    /**
     * Get file list for import process
     *
     * @return array
     */
    public function getFilesForImport()
    {
        $files = $this->getOption(self::OPTION_FILE);

        if (is_array($files) || is_string($files)) {
            $files = (array)$files;

            $path = dirname($this->FCom_Core_ImportExport->getFullPath('import', 'import'));
            $files = array_map(function ($value) use ($path) {
                if (!is_string($value)) return null;

                $value = trim($value);
                if (!preg_match('/^[^*?"<>|:]*$/', $value) || !preg_match('#\.json$#', $value)) return;

                if (!is_file($value)) $value = $path . '/' . $value;
                if (!is_file($value)) return;

                return [
                    'fullpath' => $value,
                    'name' => $value,
                    'file_size' => $this->BUtil->convertFileSize(filesize($value))
                ];
            }, $files);
            $files = array_filter($files);
            return $files;
        }

        if (null === $files && $this->getOption(self::OPTION_QUIET) !== true) {
            $files = $this->askImportFiles();
            if (count($files)) {
                $this->println(PHP_EOL . '{green}You have selected the following files:{/}');
                foreach ($files as $file) {
                    $this->println('  {purple*}' . $file['name'] . '{/}');
                }
                $answer = $this->askYesNo('Start import?');

                $files = ($answer == true ? $files : []);
            }
        }

        if (is_bool($files)) {
            $files = [];
        }

        return (array)$files;
    }

    /**
     * Shell GUI of getting file list
     *
     * @return array
     */
    public function askImportFiles()
    {
        $files = $this->getAllAvailableFilesForImport();

        if (!$files) {
            return [];
        }

        $this->println(PHP_EOL . '{green}Please select files from allowed:{/}');
        $ids = array();
        $i = 1;
        foreach ($files as $key => $file) {
            $ids[$i] = $key;
            $this->println('  [' . $i++ . '] {purple*}' . $file['name'] . '{/}');
        }

        $processedIds = [];
        $fileId = null;
        $tryCount = 1;
        $offset = 0;
        while (true) {
            $shell = $this->FCom_Shell_Shell;
            $this->out('{yellow}Filename ids: {/}'  . str_pad('', $offset));
            if ($offset) {
                $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_BACK, $offset));
            }
            $fileIds = $shell->stdin();

            $offset = strlen($fileIds);

            $processedIds = array_intersect($this->strToIntArray($fileIds), array_keys($ids));

            if (count($processedIds)){
                break;
            }

            if ($tryCount == 3) {
                break;
            }
            $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_UP, 1));
            $tryCount++;
        }

        $fileList = [];
        foreach ($processedIds as $processedId) {
            $fileList[] = $files[$ids[$processedId]];
        }

        return $fileList;
    }

    /**
     * Get array of files in import directory
     *
     * @return array|bool
     */
    public function getAllAvailableFilesForImport()
    {
        $path = $this->FCom_Core_ImportExport->getFullPath('import', 'import');

        $files = glob(dirname($path) . '/*.json');

        $data = [];
        foreach ($files as $file) {
            $data[] = [
                'fullpath' => $file,
                'name' => (substr($file, (strrpos($file, '/') + 1))),
                'file_size' => $this->BUtil->convertFileSize(filesize($file))
            ];
        }

        return empty($data) ? false : $data;
    }

    /**
     * @param $args
     */
    public function onBeforeImport($args)
    {
        $this->_importStarted = microtime(true);
        if ($this->getOption(self::OPTION_QUIET) === true) {
            return;
        }
        $this->println("");
        if ($this->getOption(self::OPTION_VERBOSE) === true) {
            $keys = ["Unchanged", "New", "Updated", "Total", "Name"];
            $str = '';
            $str2 = '';

            foreach ($keys as $item) {
                $str .= "| {green}" . str_pad($item, 10) . '{/}';
                $str2 .= "| " . str_pad('', 9, '-') . ' ';
            }

            $this->println($str);
            $this->println($str2);
            $this->println('');
        }
    }

    /**
     * @param $args
     */
    public function onBeforeModel($args)
    {
        $this->_bachStarted = microtime(true);
        if ($this->getOption(self::OPTION_QUIET) === true) {
            return;
        }
        $this->println('');
    }

    /**
     * @param $args
     */
    public function onAfterBatch($args)
    {
        if ($this->getOption(self::OPTION_QUIET) === true) {
            return;
        }

        $shell = $this->FCom_Shell_Shell;
        if ($this->getOption(self::OPTION_VERBOSE) !== true) {
            $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_UP, 1));
            $this->println($args['modelName']);
            return;
        }

        $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_UP, 2));

        $statistic = $args["statistic"];

        $keys = ["not_changed", "new_models", "updated_models"];

        $total = 0;
        $statistic['total'] = $total;
        foreach ($keys as $key) {
            $total += (int)$statistic[$key];
        }
        $statistic['total'] = $total;

        $maxLength = 9;
        foreach ($statistic as $item) {
            $maxLength = strlen($item['name']) > $maxLength ? strlen($item['name']) : $maxLength;
        }
        $maxLength += 1;

        $str = '';
        foreach ($statistic as $item) {
            $str .= "| {cyan}" . str_pad($item, $maxLength) . '{/}';
        }
        $str .= "| " . $args['modelName'];

        $this->println($str);
        $this->println('{red*}Debug:{/} {white}'
            . str_pad($this->BUtil->convertFileSize(memory_get_usage() - $this->_memoryStarted), 10)
            . str_pad(sprintf('%2.5f', microtime(true) - $this->_bachStarted) . 's', 10)
            . str_pad(sprintf('%2.5f', microtime(true) - $this->_importStarted) . 's', 10)
            . str_pad($this->BUtil->convertFileSize(memory_get_usage()), 10)
            . '{/}'
        );
        $this->_bachStarted = microtime(true);

        return;
    }


    /**
     * Export to file
     */
    protected function _exportCmd()
    {
        $file = $this->getFileForExport();
        $models = $this->getModelsForExport();
        if ($models === false) {
            $this->println('{green*}INFO:{/} No models for export.');
            return;
        }

        var_dump($this->FCom_Core_ImportExport->export($models,$file));
    }

    /**
     * Get fileName for export process.
     *
     * @return string
     * @throws BException
     */
    public function getFileForExport()
    {
        $filename = $this->getOption(self::OPTION_FILE);
        if (is_array($filename)) {
            throw new BException('Option \'--file\' can be used only once in a single command.');
        }

        if (empty($filename)) {
            if ($this->getOption(self::OPTION_QUIET)) {
                $filename = $this->FCom_Core_ImportExport->getDefaultExportFile();
            } else {
                $filename = $this->askExportFile();
            }
        } elseif (!preg_match('/^[\.\-\w]*$/', $filename)) {
            throw new BException('Filename is invalid.');
        }

        return $filename;
    }

    /**
     * Shell GUI of getting filename
     *
     * @return string
     */
    public function askExportFile()
    {
        $this->println('');

        $defaultName = $this->FCom_Core_ImportExport->getDefaultExportFile();
        $tryCount = 1;
        $offset = 0;
        $error = false;
        while (true) {
            $error = false;
            $shell = $this->FCom_Shell_Shell;
            $this->out(
                '{yellow}Please enter filename for export:{/}{white} ['
                . $defaultName . '] {/}'
                . str_pad('', $offset)
            );
            if ($offset) {
                $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_BACK, $offset));
            }
            $filename = $shell->stdin();

            $offset = strlen($filename);

            if (empty($filename)) {
                break;
            }

            if (!preg_match('/^[\.\-\w]*$/', $filename)) {
                $this->println('{red*}ERROR:{/} Filename is invalid.');
                $error = true;
            }

            if ($tryCount == 3) {
                break;
            }

            $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_UP, 2));

            $tryCount++;
        }
        if ($error) {
            $this->println('{purple*}NOTICE:{/} Filename set to default - "' . $defaultName . '"');
        }

        if (empty($filename) || $error) {
            $filename = $defaultName;
        }

        return $filename;
    }

    /**
     * Get model list for export process
     *
     * @return array
     */
    public function getModelsForExport()
    {
        if ($this->getOption(self::OPTION_ALL)) {
            //empty array means "all available models"
            return [];
        }

        $models = (array)$this->getOption(self::OPTION_MODEL);
        foreach ($models as $key => $model) {
            if(!is_string($model) || empty($model)) {
                unset($models[$key]);
            }
        }

        if(!empty($models)) {
            $allModels = [];
            foreach ($this->getAllAvailableModelsForExport() as $module) {
                $allModels = array_merge($allModels, $module);
            }

            $processedModels = array_map(function ($value) use ($models) {
                foreach ($models as $model) {
                    if (empty($model) || !is_string($model)) {
                        continue;
                    }
                    if (substr($model, -1) == '*' && strpos($value, substr($model, 0, -1)) === 0) {
                        return $value;
                    }
                    if ($model == $value) {
                        return $value;
                    }
                }
                return null;
            }, $allModels);

            $models = array_filter($processedModels);

            return empty($models) ? false : $models;
        }

        if (!$this->getOption(self::OPTION_QUIET)) {
            $models = $this->askExportModels();
            if (!count($models)) {
                return false;
            }

            $this->println(PHP_EOL . '{green}You have selected the following models:{/}');
            foreach ($models as $model) {
                $this->println('  {purple*}' . $model . '{/}');
            }
            return $this->askYesNo('Start Export?') ? $models : false;
        }

        return false;
    }

    /**
     * Get array of modules with models which have IE profile
     * @return array
     */
    public function getAllAvailableModelsForExport()
    {
        $data = $this->FCom_Core_ImportExport->collectExportableModels();
        ksort($data);

        $modules = [];
        foreach ($data as $id => $d) {
            $module = explode('_', $id, 3);
            array_splice($module, 2);
            $module = join('_', $module);
            if (!isset($modules[$module])) {
                $modules[$module] = [];
            }
            $modules[$module][] = $id;
        }

        return $modules;
    }

    /**
     * Shell GUI of getting module list
     *
     * @return array
     */
    public function askExportModels()
    {
        $modules = $this->getAllAvailableModelsForExport();
        $moduleList = [];
        foreach ($modules as $modelCount => $children) {
            $moduleList[$modelCount] = count($children);
        }

        if (empty($moduleList)) {
            return false;
        }

        $this->println(PHP_EOL . '{green}Please select modules from allowed:{/}');
        $ids = array();
        $i = 1;
        foreach ($moduleList as $key => $modelCount) {
            $ids[$i] = $key;
            $this->println(
                '  ' . str_pad('[' . $i++ . ']', 5)
                . '{blue*}models ' . str_pad($modelCount, 3) . '{/} '
                . '{purple*}' . $key . '{/}'
            );
        }

        $processedIds = [];
        $tryCount = 1;
        $offset = 0;
        while (true) {
            $shell = $this->FCom_Shell_Shell;
            $this->out('{yellow}Module ids: {/} [All] '  . str_pad('', $offset));
            if ($offset) {
                $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_BACK, $offset));
            }
            $moduleIds = $shell->stdin();

            $offset = strlen($moduleIds);

            if (empty($moduleIds) || strtolower($moduleIds) == 'all') {
                $moduleIds = '1-' . ($i - 1);
            }

            $processedIds = array_intersect($this->strToIntArray($moduleIds), array_keys($ids));

            if (count($processedIds)){
                break;
            }

            if ($tryCount == 3) {
                break;
            }
            $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_UP, 1));
            $tryCount++;
        }

        $modelList = [];
        foreach ($processedIds as $processedId) {
            $modelList[] = $modules[$ids[$processedId]];
        }

        $tmp = [];
        foreach ($modelList as $models) {
            $tmp = array_merge($tmp, $models);
        }
        $modelList = $tmp;

        return $modelList;
    }
}