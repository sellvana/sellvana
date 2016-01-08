<?php

class FCom_Core_Shell_Cache extends FCom_Shell_Action_Abstract
{
    static protected $_actionName = 'core:cache';

    static protected $_availOptions = [
        'c' => 'clean',
    ];

    protected function _run()
    {
        if ($this->getOption('c')) {
            $this->println('Starting flushing cache...');
            $cacheDir = $this->BConfig->get('fs/cache_dir');
            $this->BUtil->rmdirRecursive_YesIHaveCheckedThreeTimes($cacheDir);
            $this->println('Flushing complete');
        } else {
            $this->println('No action specified, nothing done.');
        }
    }

    public function getShortHelp()
    {
        return 'Cache management';
    }

    public function getLongHelp()
    {
        return <<<EOT

Cache management

Options:
    {white*}-f
    --flush{/}     Flush cache

EOT;
    }
}