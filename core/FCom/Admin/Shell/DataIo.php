<?php

/**
 * Class FCom_Admin_Shell_DataIo
 *
 * @property FCom_Shell_Shell $FCom_Shell_Shell
 * @property FCom_Core_ImportExport $FCom_Core_ImportExport
 */
class FCom_Admin_Shell_DataIo extends FCom_Shell_Action_Abstract
{
    const PARAM_COMMAND = 2;

    const OPTION_FILE = 'f';
    const OPTION_VERBOSE = 'v';
    const OPTION_SILENT = 's';

    static protected $_actionName = 'data-io';

    static protected $_availOptions = [
        'f?' => 'file',
        'v' => 'verbose',
        's' => 'silent'
    ];

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

Import management.

Syntax: {white*}{$this->getParam(self::PARAM_SELF)} {$this->getActionName()} {green*}<command> [parameters]{/}

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
        $cmd = $this->getParam(self::PARAM_COMMAND);
        if (!$cmd) {
            $this->println('{red*}ERROR:{/} No command specified.');
            $cmd = 'help';
        }
        $method = '_' . $cmd . 'Cmd';
        if (!method_exists($this, $method)) {
            $this->println('{red*}ERROR:{/} Unknown command: {red*}' . $cmd . '{/}');
            return;
        }

        $this->{$method}();
    }

    /**
     * List of available files for import
     */
    protected function _listCmd()
    {
        $files = $this->getAllAvailableFiles();

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
                    . '{^purple}' . str_pad($file['name'], $maxLength) . '{/}'
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
        $fileName = $this->getOption(self::OPTION_FILE);
        $external = false;
        if (is_string($fileName)) {
            if (is_file($fileName)) {
                $external = true;
                if (!preg_match('#\.json$#', $fileName)) {
                    $this->println('{red*}ERROR:{/} Unsupported file extension: {red*}' . $fileName . '{/}');
                    return;
                }
            } else {
                $path = dirname($this->FCom_Core_ImportExport->getFullPath('import', 'import'));
                $fileName = $path . '/' . $fileName;

                if (!is_file($fileName)) {
                    $this->println('{red*}ERROR:{/} Unknown file: {red*}' . $fileName . '{/}');
                    return;
                }
            }
            $file = $fileName;
        } else {
            $files = $this->getAllAvailableFiles();

            if (!$files) {
                $this->println('{green*}INFO:{/} No files to import.');
                return;
            }

            $this->println("\n{green}Please select filename from allowed:{/}");
            $ids = array();
            $i = 1;
            foreach ($files as $key => $file) {
                $ids[$i] = $key;
                $this->println('  [' . $i++ . '] {^purple}' . $file['name'] . '{/}');
            }

            $fileId = null;
            while (true) {
                $this->FCom_Shell_Shell->stdout('{yellow}Filename id: {/}', false, '');
                $fileId = $this->FCom_Shell_Shell->stdin();
                if (array_key_exists($fileId, $ids)) {
                    break;
                }
            }
            $file = $files[$ids[$fileId]]['fullpath'];
        }
        try {
            //Fix of memory leak
            $this->BDebug->disableAllLogging();

            $importer = $this->FCom_Core_ImportExport;

            if (!$importer->validateImportFile($file, !$external)) {
                $this->println('{red*}ERROR:{/} Invalid import file.');
                return;
            }
            $importer->importFile($file);

        } catch (Exception $e) {
            $this->BDebug->logException($e);
            $this->println('{red*}FATAL ERROR:{/} ' . $e->getMessage());
        }
    }

    protected function _helpCmd()
    {
        $this->println($this->getLongHelp());
    }

    /**
     * Get array of files in import directory
     *
     * @return array|bool
     */
    protected function getAllAvailableFiles()
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
        if ($this->getOption(self::OPTION_SILENT) === true){
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
        }
    }

    /**
     * @param $args
     */
    public function onBeforeModel($args)
    {
        if ($this->getOption(self::OPTION_SILENT) === true){
            return;
        }
        $this->println('');
    }

    /**
     * @param $args
     */
    public function onAfterBatch($args)
    {
        if ($this->getOption(self::OPTION_SILENT) === true){
            return;
        }
        echo $this->FCom_Shell_Shell->cursor('up', 2);

        if ($this->getOption(self::OPTION_VERBOSE) !== true) {
            $this->println($args['modelName']);
            return;
        }

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
        $this->println($this->BUtil->convertFileSize(memory_get_usage()));

        return;
    }
}