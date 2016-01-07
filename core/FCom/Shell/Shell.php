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
    protected $_params = null;

    public function run()
    {
        $this->BModuleRegistry->bootstrap();

        foreach ($this->_actionClasses as $class) {
            /** @var FCom_Shell_Action_Abstract $inst */
            $inst = $this->{$class};
            $this->_actions[$inst->getActionName()] = $inst;
        }
        $name = $this->getParam(1) ?: 'help';
        if (empty($this->_actions[$name])) {
            $name = 'help';
        }
        $this->_actions[$name]->run();
        return $this;
    }

    public function bootstrap()
    {
        $this->registerAction('FCom_Shell_Action_Help');
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
        if (null === $this->_params) {
            $this->_params = [];
            foreach ($GLOBALS['argv'] as $p) {
                if ($p[0] !== '-') {
                    $this->_params[] = $p;
                }
            }
        }
        return isset($this->_params[$num]) ? $this->_params[$num] : null;
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
     * Colorize a string for shell output
     *
     * Format examples:
     *      "{black}{bg_white}Example{/}" - black on white
     *      "{yellow*}Example{/}" - yellow bold
     *      "{cyan_}{bg/red^}Example{/}" - cyan underscored on bright red
     *
     * @param $string
     * @return mixed
     */
    public function colorize($string)
    {
        static $colors = [
            'black' => '30',
            'red' => '31',
            'green' => '32',
            'yellow' => '33',
            'blue' => '34',
            'purple' => '35',
            'cyan' => '36',
            'white' => '37',
            'bg/black' => '40',
            'bg/red' => '41',
            'bg/green' => '42',
            'bg/yellow' => '43',
            'bg/blue' => '44',
            'bg/purple' => '45',
            'bg/cyan' => '46',
            'bg/white' => '47',
            '/' => '0',
        ];
        static $modifiers = [
            '.' => '0', // normal
            '*' => '1', // bold
            '_' => '4', // underline
            '^' => '5', // light background
            '!' => '7', // inverse front and back colors
        ];
        static $re = null;
        if (!$re) {
            $mods = [];
            foreach ($modifiers as $m => $_) {
                $mods[] = preg_quote($m, '#');
            }
            $re = '#\{(' . join('|', array_keys($colors)) . ')(' . join('|', $mods) . ')?\}#';
        }
        static $enabled = null;
        if (null === $enabled) {
            $enabled = strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN';
        }
        $output = preg_replace_callback($re, function($m) use ($colors, $modifiers, $enabled) {
            if (!$enabled) {
                return '';
            }
            $mod = !empty($m[2]) ? ($modifiers[$m[2]] . ';') : '';
            $color = $colors[$m[1]];
            return "\033[{$mod}{$color}m";
        }, $string);
        return $output;
    }
}