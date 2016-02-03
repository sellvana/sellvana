<?php

class FCom_Core_Shell_Cache extends FCom_Shell_Action_Abstract
{
    static protected $_origClass = __CLASS__;

    static protected $_actionName = 'cache';

    static protected $_availOptions = [
        'f' => 'flush',
    ];

    protected function _run()
    {
        if ($this->getOption('f')) {
            $this->println('Starting flushing cache...');
            $this->BCache->deleteAll();
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