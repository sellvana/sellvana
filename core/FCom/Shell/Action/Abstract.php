<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Shell_Action_Abstract
 *
 * @property FCom_Shell_Shell $FCom_Shell_Shell
 */
abstract class FCom_Shell_Action_Abstract extends BClass
{
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

        foreach ($params as $i => $p) { // iterate over cli parameters
            $curOpt = null; // reset
            if ($p[0] !== '-') { // this parameter is not an option
                continue;
            }
            $curOpt = 'unknown'; // unknown option
            $value = null;
            $o = null;
            foreach ($availOptions as $opt => $longOpt) { // iterate over available options
                $o = $opt[0]; // actual short option name
                $ro = !empty($opt[1]) ? $opt[1] : false; // required/optional
                if (!empty($p[1]) && $p[1] === $o) { // this is the current short opt
                    $curOpt = 'short';
                    if (!empty($p[2])) {
                        $value = substr($p, 2);
                        break; // no need to proceed
                    }
                } elseif (!empty($p[1]) && $p[1] === '-') { // this is a long opt
                    $pArr = explode('=', $p, 2);
                    if (substr($pArr[0], 2) === $longOpt) { // this is the current long opt
                        $curOpt = 'long';
                        if (!empty($pArr[1])) { // has value separated by '='
                            $value = $pArr[1];
                            break; // no need to proceed
                        }
                    } else { // not current long opt
                        continue;
                    }
                } else { // not current opt
                    continue;
                }
                if (!$ro) { // not expecting a value
                    $value = true;
                    break;
                } elseif ($ro && $value === null) { // expecting a value and don't have one yet
                    if (isset($params[$i + 1]) && $params[$i + 1][0] !== '-') { // next param is a valid value
                        $value = $params[$i + 1];
                        unset($params[$i + 1]); // remove value from params
                    } elseif ($ro === '?') { // value is optional
                        $value = true;
                    } else { // value is required and missing, add to errors
                        $optName = $curOpt === 'short' ? '-' . $o : '--' . $longOpt;
                        $this->_optionErrors[$o] = [
                            'error' => 'required_value',
                            'type' => $curOpt,
                            'key' => $optName,
                        ];
                        $this->println('Missing value required for option: {red*}' . $optName . '{/}');
                    }
                    break;
                }
            }
            if ($curOpt === false) {
                continue;
            } elseif ($curOpt === 'unknown') {
                $this->_optionErrors[$p] = [
                    'error' => 'unknown_option',
                    'key' => $p,
                ];
                $this->println('Unknown option: {red*}' . $p . '{/}');
            }
            if (empty($this->_options[$o])) { // option doesn't have values yet
                $this->_options[$o] = $value; // set as scalar
            } elseif (!is_array($this->_options[$o])) { // option already has 1 value
                $this->_options[$o] = [$this->_options[$o], $value]; // convert to array
            } else { // option already has multiple values
                $this->_options[$o][] = $value; // add to array
            }
            unset($params[$i]); // remove option from params
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
        echo $this->FCom_Shell_Shell->colorize($string);
        return $this;
    }

    public function println($string, array $params = [])
    {
        if (!empty($params['ts'])) {
            $string  = '{blue*}[' . $this->BDb->now() . ']{/} ' . $string;
        }
        echo $this->FCom_Shell_Shell->colorize($string) . "\r\n";
        return $this;
    }

    public function progress($done, $total = 100, array $params = [])
    {
        $size = isset($params['size']) ? $params['size'] : 50;
        $start = isset($params['start']) ? $params['start'] : '{white*}[{.}';
        $pass = isset($params['pass']) ? $params['pass'] : '=';
        $head = isset($params['head']) ? $params['head'] : '{green*}>';
        $fill = isset($params['fill']) ? $params['fill'] : ' ';
        $end = isset($params['end']) ? $params['end'] : '{white*}]{/}';
        $percent = ceil($done / $total * 100);
        $pos = ceil($percent / 100 * $size);
        $out = $start . str_pad('', $pos, $pass) . $head . str_pad('', $size - $pos, $fill) . $end .
               ' {blue*}' . $done . '/' . $total . ' ' . $percent . '%';
        $this->println($out);
        $this->out($this->FCom_Shell_Shell->cursor('up', 1));
        return $this;
    }
}