<?php

class FCom_Core_Shell_Module extends FCom_Shell_Action_Abstract
{
    static protected $_actionName = 'module';

    static protected $_availOptions = [
        'e!' => 'enable',
        'd!' => 'disable',
    ];

    protected function _run()
    {
        if ($this->getOption('e')) {
            foreach ((array)$this->getOption('e') as $m) {
                $mod = $this->BModuleRegistry->module($m);
                if (!$mod) {
                    $this->println('Module not found: {red*}' . $m . '{/}');
                } elseif ($mod->run_level === BModule::REQUESTED || $mod->run_level === BModule::REQUIRED) {
                    $this->println('Module {green*}' . $m . '{/} is already enabled');
                } else {
                    $this->BConfig->set('module_run_levels/FCom_Core/' . $m, BModule::REQUESTED, false, true);
                    $this->println('Module {green*}' . $m . '{/} has been enabled');
                }
            }
        } elseif ($this->getOption('d')) {
            foreach ((array)$this->getOption('d') as $m) {
                $mod = $this->BModuleRegistry->module($m);
                if (!$mod) {
                    $this->println('Module not found: {red*}' . $m . '{/}');
                } elseif ($mod->run_level === BModule::DISABLED || $mod->run_level === BModule::ONDEMAND) {
                    $this->println('Module {green*}' . $m . '{/} is already disabled');
                } else {
                    $this->BConfig->set('module_run_levels/FCom_Core/' . $m, BModule::ONDEMAND, false, true);
                    $this->println('Module {green*}' . $m . '{/} has been disabled');
                }
            }
        } else {
            $this->println('No action specified, nothing done.');
        }
        $this->BConfig->writeConfigFiles('core');
    }

    public function getShortHelp()
    {
        return 'Module management';
    }

    public function getLongHelp()
    {
        return <<<EOT

Module management

Options:
    {white*}-e {green*}[module]{white*}
    --enable={green*}[module] {white*}...{/}   Enable a module

    {white*}-d {green*}[module]{white*}
    --disable={green*}[module] {white*}...{/}  Disable a module

EOT;
    }
}