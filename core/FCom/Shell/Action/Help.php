<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Shell_Shell_Abstract
 *
 * @property FCom_Shell_Shell $FCom_Shell_Shell
 */
class FCom_Shell_Action_Help extends FCom_Shell_Action_Abstract
{
    static protected $_actionName = 'help';

    public function run()
    {
        $shellHlp = $this->FCom_Shell_Shell;

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
            $this->println('');

            $actions     = $shellHlp->getAllActions();
            $actionHelps = [];
            $maxLen      = 0;
            /** @var FCom_Shell_Action_Abstract $action */
            foreach ($actions as $action) {
                $name               = $action->getActionName();
                $maxLen             = max($maxLen, strlen($name));
                $actionHelps[$name] = $action->getShortHelp();
            }
            ksort($actionHelps);
            foreach ($actionHelps as $name => $help) {
                $this->println("\t{white*}" . str_pad($name, $maxLen, ' ', STR_PAD_LEFT) . "{/}\t" . $help);
            }
        }
        return $this;
    }

    public function getShortHelp()
    {
        return 'List all available actions';
    }

    public function getLongHelp()
    {
        return 'Syntax: {white*}' . $this->FCom_Shell_Shell->getParam(0) . '{/} {green*}help{/} {red*}[command]{/}';
    }
}