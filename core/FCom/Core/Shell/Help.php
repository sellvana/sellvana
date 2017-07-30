<?php

/**
 * Class FCom_Core_Shell_Abstract
 *
 * @property FCom_Core_Shell $FCom_Core_Shell
 */
class FCom_Core_Shell_Help extends FCom_Core_Shell_Abstract
{
    static protected $_origClass = __CLASS__;

    static protected $_actionName = 'help';

    static protected $_availOptions = [
        's!' => 'search',
    ];

    protected function _run()
    {
        $shellHlp = $this->FCom_Core_Shell;

        $selfCmd = $shellHlp->getParam(1);
        $invalid = false;
        if ($selfCmd && $selfCmd !== static::$_actionName) { // invalid actions redirect here
            $this->println('Invalid command: {red*}' . $shellHlp->getParam(1) . '{/}');
            $this->println('');
            $invalid = true;
        }

        $cmd = $shellHlp->getParam(2);
        if (!$invalid && $cmd) { // command specified
            $action = $shellHlp->getAction($cmd);
            if (!$action) {
                $this->println('Unknown command: {red*}' . $cmd . '{/}');
                return $this;
            }
            $this->println($action->getLongHelp());
        } else {
            $this->println('Syntax: {white*}' . $shellHlp->getParam(0) . '{/} {red*}[command]{/}');
            $this->println('');
            $this->println('To get help for a specific action: {white*}' . $shellHlp->getParam(0) .
                           '{/} {green*}help{/} {red*}[command]{/}');

            $actions     = $shellHlp->getAllActions();
            $actionHelps = [];
            $maxLen      = 0;
            $search = $this->getOption('s');
            /** @var FCom_Core_Shell_Abstract $action */
            foreach ($actions as $action) {
                $shortHelp = $action->getShortHelp();
                $name = $action->getActionName();
                $class = $action->origClass();
                if ($search && (stripos("{$shortHelp}|{$name}|{$class}", $search) === false)) {
                    continue;
                }
                preg_match('#^[A-Za-z0-9]+_[A-Za-z0-9]+#', $class, $m);
                $maxLen = max($maxLen, strlen($name));
                $actionHelps[$name] = [
                    'help' => $shortHelp,
                    'class' => $class,
                    'module' => $m[0],
                ];
            }
            if ($search && !$actionHelps) {
                $this->println('No matching actions found.');
                return $this;
            }
            uasort($actionHelps, function($a, $b) {
                $a1 = $a['class'];
                $b1 = $b['class'];
                return ($a1 < $b1) ? -1 : (($a1 > $b1) ? 1 : 0);
            });
            $curModule = '';
            foreach ($actionHelps as $name => $data) {
                if ($data['module'] !== $curModule) {
                    $curModule = $data['module'];
                    $this->println("\n{_cyan}{$curModule}:{/}");
                }
                if ($search) {
                    $name = str_replace($search, "{green*}{$search}{white*}", $name);
                    $data['help'] = str_replace($search, "{green*}{$search}{/}", $data['help']);
                }
                $this->println("\t{white*}" . str_pad($name, $maxLen, ' ', STR_PAD_RIGHT) . "{/}\t" . $data['help']);
            }
        }
        return $this;
    }

    public function getShortHelp()
    {
        return (('List all available actions'));
    }

    public function getLongHelp()
    {
        $self = $this->getParam(self::PARAM_SELF);

        return <<<EOT

List all available commands or show help for a specific command

Syntax:

    {white*}{$self}{/} {green*}help{/} {cyan*}-s <string>{/}';
    {white*}{$self}{/} {green*}help{/} {cyan*}[command]{/}';

Options:

    {white*}-s {green*}<string>{white*}
    --search={green*}<string>{/}       Search for commands with a string in name or description

EOT;
    }
}