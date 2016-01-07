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
     * @var array|null
     */
    protected $_options = null;

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

    /**
     * Get a command line option (starts with - or --)
     *
     * @param $opt
     * @param null $longOpt
     * @return null
     */
    public function getOption($opt, $longOpt = null)
    {
        if (null === $this->_options) {
            $availOptions = '';
            $availLongOptions = [];
            foreach (static::$_availOptions as $opt => $longOpt) {
                $mod = '';
                if (!empty($opt[1])) {
                    if ($opt[1] === '!') {
                        $mod = ':';
                    } elseif ($opt[1] === '?') {
                        $mod = '::';
                    }
                }
                $availOptions .= $opt[0] . $mod;
                $availLongOptions[] = $longOpt . $mod;
            }
            $this->_options = getopt($availOptions, $availLongOptions);
        }
        $value = isset($this->_options[$opt]) ? $this->_options[$opt] : null;
        if (null === $value) {
            $value = isset($this->_options[$longOpt]) ? $this->_options[$longOpt] : null;
        }
        return $value;
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
}