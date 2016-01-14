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

    const OPTION_FILE = 'f';
    const OPTION_VERBOSE = 'v';
    const OPTION_QUIET = 'q';

    static protected $_actionName = 'data-io';

    static protected $_availOptions = [
        'f?' => 'file',
        'v' => 'verbose',
        'q' => 'quiet',
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
        return 'Import management';
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
    {green*}import{/}   Import file

    {green*}help{/}     This help

Options:

  Device selection and switching:
    {green*}-f {/}{cyan*}<file>{/}
    {green*}--file={/}{cyan*}<file>{/}     File to import

  Informative output:
    {green*}-v, --verbose{/}     Verbose output of the process
    {green*}-s, --silent{/}      Disable all output of the process

Examples:


EOT;
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
        $files = $this->getAllAvailableFilesForImpor();

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
     * Get file list for import process;
     *
     * @return array
     */
    protected function _getFilesForImport()
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
                $answer = false;
                $offset = 0;
                while (true) {
                    $shell = $this->FCom_Shell_Shell;
                    $this->out('{yellow}Start import?{/} [Y/N] ' . str_pad('', $offset));
                    if ($offset) {
                        $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_BACK, $offset));
                    }
                    $answer = strtoupper($shell->stdin());
                    $offset = strlen($answer);
                    if (in_array($answer, ['Y', 'N'])) {
                        $answer = ($answer == 'Y' ? true : false);
                        break;
                    }
                    $this->out($shell->cursor(FCom_Shell_Shell::CURSOR_CMD_UP, 1));
                }

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
        $files = $this->getAllAvailableFilesForImpor();

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

            $fileIds = preg_replace('/\s+/', '', $fileIds);
            $fileIds = explode(',', str_replace([',', ';', ':'], ',', $fileIds));

            $processedIds = [];
            foreach ($fileIds as $fileId) {
                if (preg_match('/^[0-9]*$/', $fileId)){
                    $processedIds[] = (int)$fileId;
                    continue;
                }
                $range = explode('-', $fileId);
                if (count($range) == 2) {
                    $range = range($range[0], $range[1]);
                    $processedIds = array_merge($processedIds, $range);
                }
            }

            $processedIds = array_unique($processedIds);
            $processedIds = array_intersect($processedIds, array_keys($ids));

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
     * Import file
     */
    protected function _importCmd()
    {
        $files = $this->_getFilesForImport();

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
     * Get array of files in import directory
     *
     * @return array|bool
     */
    public function getAllAvailableFilesForImpor()
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
}