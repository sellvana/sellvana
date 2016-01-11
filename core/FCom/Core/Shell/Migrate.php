<?php

class FCom_Core_Shell_Migrate extends FCom_Shell_Action_Abstract
{
    static protected $_actionName = 'migrate';

    static protected $_availOptions = [
        'f' => 'force',
        'm!' => 'modules',
    ];

    protected function _run()
    {
        $this->println('Starting migration...');
        $modules = $this->getOption('m');
        $force = $this->getOption('f');
        $this->BMigrate->migrateModules($modules, $force);
        $this->println('Migration complete');
    }

    public function getShortHelp()
    {
        return 'Run pending DB migration scripts';
    }

    public function getLongHelp()
    {
        return <<<EOT

Run pending DB migration scripts.

Options:
    {white*}-f
    --force{/}     Force migration for all modules

    {white*}-m
    --modules{/}   Specify which modules to migrate

EOT;
    }
}