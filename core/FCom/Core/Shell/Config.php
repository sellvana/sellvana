<?php

class FCom_Core_Shell_Config extends FCom_Shell_Action_Abstract
{
    static protected $_actionName = 'config';

    static protected $_availOptions = [
        's!' => 'set',
    ];

    protected function _run()
    {
        $tasksDone = false;
        $set = $this->getOption('s');
        if ($set) {
            foreach ((array)$set as $s) {
                $a = explode('=', $s, 2);
                if (!isset($a[1])) {
                    $this->println("{red*}ERROR:{/} Invalid format: {red*}{$s}{/}, expecting: {green*}<path>=<value>{/}");
                    continue;
                }
                $this->BConfig->set($a[0], $a[1], false, true);
                $this->println("Set {white*}{$a[0]}{/} = {white*}{$a[1]}{/}");
            }
            $this->BConfig->writeConfigFiles();
            $this->println("Configuration files saved.");
            $tasksDone = true;
        }
        if (!$tasksDone) {
            $this->println('No actions specified, nothing done.');
        }
    }

    public function getShortHelp()
    {
        return 'Configuration management';
    }

    public function getLongHelp()
    {
        return <<<EOT

Configuration Management

Syntax: {$this->getParam(0)} config -s {green*}<path>{/}={green*}<value>{/} ...

Options:
    {white*}-s {green*}<path>{/}={green*}<value>{/}
    --set {green*}<path>{/}={green*}<value>{/}     Set configuration path to value

EOT;
    }
}