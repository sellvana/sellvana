<?php

/**
 * Class FCom_Shell_Action_Abstract
 *
 * @property FCom_Shell_Shell $FCom_Shell_Shell
 */
abstract class FCom_Shell_Action_Abstract extends BClass
{
    const PARAM_SELF = 0;
    const PARAM_ACTION = 1;
    const PARAM_COMMAND = 2;

    /**
     * Action name, separated with ':'
     *
     * @var string
     */
    static protected $_actionName = null;

    /**
     * Available options for this action ('short' => 'long'). Examples:
     *
     * $_availOptions = [
     *    'v' => 'verbose',   // no option value accepted
     *    'o?' => 'optional', // optional value ( -o OR -o "value")
     *    'r!' => 'required', // required value ( -r "value")
     *    'optional?', // optional value ( -o OR -o "value")
     *    'required?', // required value ( -r "value")
     * ];
     *
     * @var array
     */
    static protected $_availOptions = [];

    /**
     * Calculated options
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Option errors, such as missing required values
     *
     * @var array
     */
    protected $_optionErrors = [];

    public function getActionName()
    {
        return static::$_actionName;
    }

    public function getShortHelp()
    {
        return '';
    }

    public function getLongHelp()
    {
        return 'Help for this action is not available';
    }

    public function getAvailOptions()
    {
        return static::$_availOptions;
    }

    public function run()
    {
        $this->_collectOptions();

        if (!empty($this->_optionErrors)) {
            $this->println('');
            $this->println('Execution stopped due to errors above.');
            return $this;
        }

        $this->_run();

        return $this;
    }

    protected function _run()
    {
        $this->println('{red*}Not implemented{/}');
    }

    protected function _processCommand()
    {
        $cmd = $this->getParam(self::PARAM_COMMAND);
        if (!$cmd) {
            $this->println('{red*}ERROR:{/} No command specified.');
            $cmd = 'help';
        }
        $method = '_' . $cmd . 'Cmd';
        if (!method_exists($this, $method)) {
            $this->println('{red*}ERROR:{/} Unknown command: {red*}' . $cmd . '{/}');
            $method = '_helpCmd';
        }

        $this->{$method}();
    }

    protected function _helpCmd()
    {
        $this->println($this->getLongHelp());
    }

    /**
     * Get a command line option (starts with - or --), allows '=' separated long option values
     *
     * @return $this
     */
    protected function _collectOptions()
    {
        $params =& $this->FCom_Shell_Shell->getAllParams();

        $this->_options = [];
        $this->_optionErrors = [];

        $availOptions = $this->getAvailOptions();
        if (!$availOptions) {
            return $this;
        }

        foreach ($params as $paramIndex => $param) { // iterate over cli parameters
            $curOptType = null; // reset
            if ($param[0] !== '-') { // this parameter is not an option
                continue;
            }
            $curOptType = 'unknown'; // unknown option
            $optionValue = null;
            $shortOptName = null;
            $optName = null;
            foreach ($availOptions as $shortOpt => $longOpt) { // iterate over available options
                $shortOptName = $shortOpt[0]; // actual short option name
                if (gettype($shortOpt) == 'integer'){
                    $shortOptName = false;
                    $optName = $longOpt;
                }
                if ($shortOptName) {
                    $isRequired = !empty($shortOpt[1]) ? $shortOpt[1] : false; // required/optional
                } else {
                    $isRequired = in_array(substr($longOpt, -1), ['!','?']) ? substr($longOpt, -1) : false;
                }
                if (!empty($param[1]) && $param[1] === $shortOptName) { // this is the current short opt
                    $curOptType = 'short';
                    if (!empty($param[2])) {
                        $optionValue = substr($param, 2);
                        break; // no need to proceed
                    }
                } elseif (!empty($param[1]) && $param[1] === '-') { // this is a long opt
                    $pArr = explode('=', $param, 2);
                    if (substr($pArr[0], 2) === $longOpt) { // this is the current long opt
                        $curOptType = 'long';
                        if (!empty($pArr[1])) { // has value separated by '='
                            $optionValue = $pArr[1];
                            break; // no need to proceed
                        }
                    } else { // not current long opt
                        continue;
                    }
                } else { // not current opt
                    continue;
                }
                if (!$isRequired) { // not expecting a value
                    $optionValue = true;
                    break;
                } elseif ($isRequired && $optionValue === null) { // expecting a value and don't have one yet
                    if (isset($params[$paramIndex + 1]) && $params[$paramIndex + 1][0] !== '-') { // next param is a valid value
                        $optionValue = $params[$paramIndex + 1];
                        unset($params[$paramIndex + 1]); // remove value from params
                    } elseif ($isRequired === '?') { // value is optional
                        $optionValue = true;
                    } else { // value is required and missing, add to errors
                        $optName = $curOptType === 'short' ? '-' . $shortOptName : '--' . $longOpt;
                        $this->_optionErrors[$shortOptName] = [
                            'error' => 'required_value',
                            'type' => $curOptType,
                            'key' => $optName,
                        ];
                        $this->println('Missing value required for option: {red*}' . $optName . '{/}');
                    }
                    break;
                }
            }
            if ($curOptType === false) {
                continue;
            } elseif ($curOptType === 'unknown') {
                $this->_optionErrors[$param] = [
                    'error' => 'unknown_option',
                    'key' => $param,
                ];
                $this->println('Unknown option: {red*}' . $param . '{/}');
            }
            if (!$shortOptName) {
                $shortOptName = $optName;
            }
            if (empty($this->_options[$shortOptName])) { // option doesn't have values yet
                $this->_options[$shortOptName] = $optionValue; // set as scalar
            } elseif (!is_array($this->_options[$shortOptName])) { // option already has 1 value
                $this->_options[$shortOptName] = [$this->_options[$shortOptName], $optionValue]; // convert to array
            } else { // option already has multiple values
                $this->_options[$shortOptName][] = $optionValue; // add to array
            }
            unset($params[$paramIndex]); // remove option from params
        }

        return $this;
    }

    public function getParam($num)
    {
        return $this->FCom_Shell_Shell->getParam($num);
    }

    /**
     * Get command line option by option key (can request for multiple keys, as in short and long)
     *
     * @param string $key
     * @return string|array
     */
    public function getOption($key)
    {
        return isset($this->_options[$key]) ? $this->_options[$key] : null;
    }

    public function out($string)
    {
        $shell = $this->FCom_Shell_Shell;
        $shell->stdout($shell->colorize($string), false, '');
        return $this;
    }

    public function println($string, array $params = [])
    {
        if (!empty($params['ts'])) {
            $string  = '{blue*}[' . $this->BDb->now() . ']{/} ' . $string;
        }
        $shell = $this->FCom_Shell_Shell;
        $shell->stdout($shell->colorize($string));
        return $this;
    }

    public function progress($done, $total = 100, array $params = [])
    {
        $size = isset($params['size']) ? $params['size'] : 50;
        $start = isset($params['start']) ? $params['start'] : '{white*}[{.green}';
        $pass = isset($params['pass']) ? $params['pass'] : '=';
        $head = isset($params['head']) ? $params['head'] : '{green*}>';
        $fill = isset($params['fill']) ? $params['fill'] : ' ';
        $end = isset($params['end']) ? $params['end'] : '{white*}]{/}';
        $percent = ceil($done / $total * 100);
        $pos = ceil($percent / 100 * $size);
        $out = $start . str_pad('', $pos, $pass) . $head . str_pad('', $size - $pos, $fill) . $end .
               ' {blue*}' . $done . '/' . $total . ' ' . $percent . '%{/}';
        $this->println($out);
        $this->out($this->FCom_Shell_Shell->cursor(FCom_Shell_Shell::CURSOR_CMD_UP, 1));
        return $this;
    }
}