<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Shell_Shell extends BClass
{
    protected $_actionClasses = [];

    protected $_actions = [];

    /**
     * Calculated parameters
     *
     * @var array|null
     */
    protected $_params = [];

    /**
     * @var array String foreground and background colors.
     */
    static protected $_colorCodes = [
        'normal' => '0',
        'reset' => '0',
        'bold' => '1',
        'underline' => '4',
        'light' => '5',
        'inverse' => '7',

        'black' => '30',
        'red' => '31',
        'green' => '32',
        'yellow' => '33',
        'blue' => '34',
        'purple' => '35',
        'cyan' => '36',
        'white' => '37',

        'bg-black' => '40',
        'bg-red' => '41',
        'bg-green' => '42',
        'bg-yellow' => '43',
        'bg-blue' => '44',
        'bg-purple' => '45',
        'bg-cyan' => '46',
        'bg-white' => '47',
    ];

    /**
     * @var array String styling.
     */
    static protected $_shortMods = [
        '.' => '0', // normal
        '/' => '0', // reset (normal)
        '*' => '1', // bold
        '_' => '4', // underline
        '^' => '5', // light background
        '!' => '7', // inverse front and back colors
    ];

    /**
     * Calculated colors regex
     *
     * @var string
     */
    static protected $_colorsRegex;

    /**
     * Use colors
     *
     * @var bool
     */
    static protected $_colorsEnabled;

    public function run()
    {
        $this->initColors();

        // collect parameters and options from cli
        $this->_params = $GLOBALS['argv'];

        // bootstrap all modules
        $this->BModuleRegistry->bootstrap();

        // register all actions from manifests
        foreach ($this->BModuleRegistry->getAllModules() as $mod) {
            if (!empty($mod->custom['actions'])) {
                $this->registerAction($mod->custom['actions']);
            }
        }

        // collect action classes and convert to instances
        foreach ($this->_actionClasses as $class) {
            /** @var FCom_Shell_Action_Abstract $inst */
            $inst = $this->{$class};
            $this->_actions[$inst->getActionName()] = $inst;
        }

        // get correct current action name
        $name = $this->getParam(1) ?: 'help';
        if (empty($this->_actions[$name])) {
            $name = 'help';
        }

        // run action logic
        $this->_actions[$name]->run();

        return $this;
    }

    public function getAction($name)
    {
        return !empty($this->_actions[$name]) ? $this->_actions[$name] : null;
    }

    public function getAllActions()
    {
        return $this->_actions;
    }

    /**
     * Get a command line parameter (not option), ordered by position in the command line call
     *
     * @param $num
     * @return string|null
     */
    public function getParam($num)
    {
        return isset($this->_params[$num]) ? $this->_params[$num] : null;
    }

    /**
     * Get all parameters (used for retrieving action specific options, and removing them from params)
     *
     * @return array
     */
    public function &getAllParams()
    {
        return $this->_params;
    }

    /**
     * Add a command with options and description
     *
     * $options = [
     *   'v' => [
     *     'help' => 'Option help description',
     *     'full' => 'verbose',
     *   ],
     * ];
     *
     * $params = [
     *   'help' => 'Command help description',
     * ];
     *
     * @param array|string $class
     * @return $this
     */
    public function registerAction($class)
    {
        if (is_array($class)) {
            foreach ($class as $c) {
                $this->registerAction($c);
            }
            return $this;
        }
        $this->_actionClasses[$class] = $class;
        return $this;
    }

    /**
     * Enable the use of coloring
     *
     * @return bool
     */
    public function enableColors()
    {
        static::$_colorsEnabled = strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN';

        if (extension_loaded('posix') && !posix_isatty(STDOUT)) {
            static::$_colorsEnabled = false;
            return false;
        }

        return true;
    }

    /**
     * Disable the use of coloring
     */
    public function disableColors()
    {
        static::$_colorsEnabled = false;
    }

    /**
     * Calculate colors regex
     */
    public function initColors()
    {
        if (null !== static::$_colorsRegex) {
            return;
        }

        static::$_colorsEnabled = strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN';

        if (extension_loaded('posix') && !posix_isatty(STDOUT)) {
            static::$_colorsEnabled = false;
        }

        $mods = [];
        foreach (static::$_shortMods as $m => $_) {
            $mods[] = preg_quote($m, '#');
        }
        $modsRe = '[' . join('', $mods) . ']*';
        $colorsRe = '(' . join('|', array_keys(static::$_colorCodes)) . ')';
        static::$_colorsRegex = "#\\{({$modsRe})({$colorsRe}(;{$colorsRe})*)?({$modsRe})\\}#";
    }

    /**
     * Colorize a string for shell output
     *
     * Format examples:
     *      "{black;bg-white}Example{/}" - black on white
     *      "{yellow*}Example{/}" - yellow bold
     *      "{yellow;bold}Example{/}" - yellow bold
     *      "{_*blue;bg-red^}Example{/}" - blue bold and underscored on a bright red
     *
     * @param $string
     * @return mixed
     */
    public function colorize($string)
    {
        if (null !== static::$_colorsRegex){
            $this->initColors();
        }
        return preg_replace_callback(static::$_colorsRegex, function($m) {
            if (!static::$_colorsEnabled) {
                return '';
            }
            $colors = [];
            if ($m[1]) {
                foreach (str_split($m[1]) as $c) {
                    $colors[] = static::$_shortMods[$c];
                }
            }
            if ($m[2]) {
                foreach (explode(';', $m[2]) as $c) {
                    $colors[] = static::$_colorCodes[$c];
                }
            }
            if (!empty($m[6])) {
                foreach (str_split($m[6]) as $c) {
                    $colors[] = static::$_shortMods[$c];
                }
            }
            return "\033[" . join(';', $colors) . "m";
        }, $string);
    }

    /**
     * Strip a string of ansi-control codes.
     *
     * @param string $string String to strip
     * @return string
     */
    public function strip($string)
    {
        return preg_replace('/\033\[(\d+)(;\d+)*m/', '', $string);
    }

    /**
     * Get from STDIN
     *
     * @param bool $raw If set to true, returns the raw string without trimming
     * @return string
     */
    public function stdin($raw = false)
    {
        return $raw ? fgets(STDIN) : rtrim(fgets(STDIN), PHP_EOL);
    }

    /**
     * Print to STDOUT.
     *
     * @param string $string
     * @param bool $raw
     * @param string $eol
     * @return int
     */
    public function stdout($string, $raw = false, $eol = PHP_EOL)
    {
        $string .= $eol;
        if ($raw) {
            return fwrite(STDOUT, $string);
        }  else {
            return fwrite(STDOUT, $this->colorize($string));
        }
    }
}