<?php debug_backtrace() || exit;

define('FULLERON_ROOT_DIR', dirname(__DIR__));
//set_time_limit(2);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

require_once __DIR__.'/Core/Core.php';

/**
* @deprecated left for legacy implementations
*/
class FCom extends BClass
{
    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    static public function area()
    {
        return BApp::i()->get('area');
    }

    static public function rootDir()
    {
        return FULLERON_ROOT_DIR;
    }

    public function init($area)
    {
        FCom_Core::i()->init($area);
    }

    public function run($area)
    {
        FCom_Core::i()->init($area);
        try {
            BApp::i()->run();
        } catch (Exception $e) {
            BDebug::dumpLog();
            BDebug::exceptionHandler($e);
        }
    }
}
