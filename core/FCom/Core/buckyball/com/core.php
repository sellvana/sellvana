<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
* Copyright 2014 Boris Gurvich
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*
* @package BuckyBall
* @link http://github.com/unirgy/buckyball
* @author Boris Gurvich <boris@sellvana.com>
* @copyright (c) 2010-2014 Boris Gurvich
* @license http://www.apache.org/licenses/LICENSE-2.0.html
*/

/**
* Base class that allows easy singleton/instance creation and method overrides (decorator)
*
* This class is used for all BuckyBall framework base classes
*
* @see BClassRegistry for invokation
*
* core
* @property BApp $BApp
* @property BException $BException
* @property BConfig $BConfig
* @property BClassRegistry $BClassRegistry
* @property BClassAutoload $BClassAutoload
* @property BEvents $BEvents
* @property BSession $BSession
*
* controller
* @property BRequest $BRequest
* @property BResponse $BResponse
* @property BRouting $BRouting
*
* layout
* @property BLayout $BLayout
* @property BView $BView
* @property BViewEmpty $BViewEmpty
* @property BViewHead $BViewHead
* @property BViewList $BViewList
*
* db
* @property BDb $BDb
*
* locale
* @property BLocale $BLocale
*
* module
* @property BModuleRegistry $BModuleRegistry
* @property BModule $BModule
* @property BMigrate $BMigrate
* @property BDbModule $BDbModule
*
* cache
* @property BCache $BCache
*
* misc
* @property BUtil $BUtil
* @property BHtml $BHtml
* @property BUrl $BUrl
* @property BEmail $BEmail
* @property BValue $BValue
* @property BData $BData
* @property BDebug $BDebug
* @property BLoginThrottle $BLoginThrottle
* @property BYAML $BYAML
* @property BValidate $BValidate
* @property BValidateViewHelper $BValidateViewHelper
* @property Bcrypt $Bcrypt
* @property BRSA $BRSA
*/
class BClass
{
    /**
    * Original class to be used as event prefix to remain constant in overridden classes
    *
    * Usage:
    *
    * class Some_Class extends BClass
    * {
    *    static protected $_origClass = __CLASS__;
    * }
    *
    * @var string
    */
    static protected $_origClass;

    /**
     * Lazy DI configuration
     *
     * [
     *    '_env' => 'BEnv',
     * ]
     *
     * @var array
     */
    static protected $_diConfig = [
        #'_env' => 'BEnv',
        '*' => 'ALL',
    ];

    /**
     * Lazy DI Global Instances
     *
     * @var array
     */
    static protected $_diGlobal = [];

    /**
     * Lazy DI Local Instances
     *
     * @var array
     */
    protected $_diLocal = [];

    /**
    * Retrieve original class name
    *
    * @return string
    */
    static public function origClass()
    {
        return static::$_origClass;
    }

    /**
    * Fallback singleton/instance factory
    *
    * @param bool|object $new if true returns a new instance, otherwise singleton
    *                         if object, returns singleton of the same class
    * @param array $args
    * @return BClass
    */
    static public function i($new = false, array $args = [])
    {
        if (is_object($new)) {
            $class = get_class($new);
            $new = false;
        } else {
            $class = get_called_class();
        }
        return BClassRegistry::instance($class, $args, !$new);
    }

    public function __call($name, $args)
    {
        return $this->BClassRegistry->callMethod($this, $name, $args, static::$_origClass);
    }

    static public function __callStatic($name, $args)
    {
        return BClassRegistry::i()->callStaticMethod(get_called_class(), $name, $args, static::$_origClass);
    }

    public function __get($name)
    {
        if (isset($this->_diLocal[$name])) {
            return $this->_diLocal[$name];
        }
        $di = $this->getGlobalDependencyInstance($name, static::$_diConfig);
        if ($di) {
            #$this->_diLocal[$name] = $di;
            return $di;
        }
        BDebug::notice('Invalid property name: ' . $name);
        return null;
    }

    public function setDependencyInstances(array $instances)
    {
        foreach ($instances as $name => $instance) {
            $this->_diLocal[$name] = $instance;
        }
        return $this;
    }

    public function getGlobalDependencyInstance($name, $diConfig)
    {
        if (!ctype_upper($name[0])) {
            return false;
        }
        if (isset(static::$_diGlobal[$name])) {
            return static::$_diGlobal[$name];
        }
        if (empty($diConfig[$name])) {
            $class = $name;
            if (isset($diConfig['*']) && class_exists($class)) {
                static::$_diGlobal[$class] = BClassRegistry::instance($class, [], true);
            } else {
                static::$_diGlobal[$class] = false;
            }
        } else {
            if (is_array($diConfig[$name])) {
                $class = $diConfig[$name][0];
                //TODO: do we need to validate preconfigured class for interface?
            } else {
                $class = $diConfig[$name];
            }
            static::$_diGlobal[$class] = BClassRegistry::instance($class, [], true);
        }
        return static::$_diGlobal[$class];
    }
}

/**
* Main BuckyBall Framework class
*
*/
class BApp extends BClass
{
    /**
    * Registry of supported features
    *
    * @var array
    */
    protected static $_compat = [];

    /**
    * Global app vars registry
    *
    * @var array
    */
    protected $_vars = [];

    /**
    * Flags whether vars shouldn't be changed
    *
    * @var mixed
    */
    protected $_isConst = [];

    /**
    * Verify if a feature is currently supported. Features:
    *
    * - PHP5.3
    *
    * @param mixed $feature
    * @return boolean
    */
    public function compat($feature)
    {
        if (!empty(static::$_compat[$feature])) {
            return static::$_compat[$feature];
        }
        switch ($feature) {
        case 'PHP5.3':
            $compat = version_compare(phpversion(), '5.3.0', '>=');
            break;

        default:
            BDebug::error($this->BLocale->_('Unknown feature: %s', $feature));
        }
        static::$_compat[$feature] = $compat;
        return $compat;
    }

    /**
     * Shortcut to help with IDE autocompletion
     *
     * @todo Run multiple applications within the same script
     *       This requires to decide which registries should be app specific
     *
     * @param bool  $new
     * @param array $args
     * @return BApp
     */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    /**
    * Application constructor
    *
    * Starts debugging session for timing
    *
    * @return BApp
    */
    public function __construct()
    {
        BDebug::i();
        umask(0);
    }

    /**
     * Shortcut to add configuration, used mostly from bootstrap index file
     *
     * @param array|string $config If string will load configuration from file
     * @return $this
     */
    public function config($config)
    {
        if (is_array($config)) {
            $this->BConfig->add($config);
        } elseif (is_string($config) && is_file($config)) {
            $this->BConfig->addFile($config);
        } else {
            BDebug::error("Invalid configuration argument");
        }
        return $this;
    }

    /**
     * Shortcut to scan folders for module manifest files
     *
     * @param string|array $folders Relative path(s) to manifests. May include wildcards.
     * @return $this
     */
    public function load($folders = '.')
    {
#echo "<pre>"; print_r(debug_backtrace()); echo "</pre>";
        if (is_string($folders)) {
            $folders = explode(',', $folders);
        }
        $modules = $this->BModuleRegistry;
        foreach ($folders as $folder) {
            $modules->scan($folder);
        }
        return $this;
    }

    /**
     * The last method to be ran in bootstrap index file.
     *
     * Performs necessary initializations and dispatches requested action.
     *
     */
    public function run()
    {
        // load session variables
        $this->BSession->open();

#echo "<pre>"; var_dump($this->BConfig->get('cookie'), $_SESSION); exit;
        // bootstrap modules
        $this->BModuleRegistry->bootstrap();

        // run module migration scripts if necessary
        $this->BMigrate->migrateModules(true);

        // dispatch requested controller action
        $this->BRouting->dispatch();

        // If session variables were changed, update session
        $this->BSession->close();

        return $this;
    }

    /**
    * Shortcut for translation
    *
    * @param string $string Text to be translated
    * @param string|array $args Arguments for the text
    * @return string
    */
    public function t($string, $args = [])
    {
        return $this->BLocale->_($string, $args);
    }

    /**
    * Shortcut to get a current module or module by name
    *
    * @param string $modName
    * @return BModule
    */
    public function m($modName = null)
    {
        $reg = $this->BModuleRegistry;
        return null === $modName ? $reg->currentModule() : $reg->module($modName);
    }

    const USE_CONFIG = 1;
    const USE_ENTRY_URI = 2;
    /**
     * Shortcut for base URL to use in views and controllers
     *
     * @param bool $full whether the URL should include schema and host
     * @param int  $method
     *   1 : use config for full url - const USE_CONFIG
     *   2 : use entry point for full url - const USE_ENTRY_URI
     * @return string
     */
    public function baseUrl($full = true, $method = self::USE_CONFIG)
    {
        static $baseUrl = [];
        $full = (int)$full;
        $key  = $full . '|' . $method;
        if (empty($baseUrl[$key])) {
            /** @var BRequest */
            $r          = $this->BRequest;
            $c          = $this->BConfig;
            $scriptName = $r->scriptName();
            if (substr($scriptName, -1) === '/') {
                $scriptPath = ['dirname' => $scriptName, 'basename' => basename($_SERVER['SCRIPT_FILENAME'])];
            } else {
                $scriptPath = pathinfo($r->scriptName());
            }
            switch ($method) {
                case self::USE_CONFIG:
                    $url = $c->get('web/base_href');
                    if (!$url) {
                        $url = $scriptPath['dirname'];
                    }
                    break;
                case self::USE_ENTRY_URI:
                    $url = $scriptPath['dirname'];
                    break;
            }

            if (!($this->BUrl->hideScriptName() && $this->BRequest->area() !== 'FCom_Admin')) {
                $url = rtrim($url, "\\"); //for windows installation
                $url = rtrim($url, '/') . '/' . $scriptPath['basename'];
            }
            if ($full) {
                $url = $r->scheme() . '://' . $r->httpHost() . $url;
            }

            $baseUrl[$key] = rtrim($url, '/') . '/';
        }

        return $baseUrl[$key];
    }

    public function href($url = '', $full = true, $method = self::USE_CONFIG)
    {
        return $this->BApp->baseUrl($full, $method)
               . $this->BRouting->processHref($url);
    }

    public function adminHref($url = '')
    {
        static $baseAdminHref;
        if (!$baseAdminHref) {
            $conf = $this->BConfig;
            $r = $this->BRequest;
            $adminHref = $conf->get('web/admin_href');
            if (!$adminHref) {
                $adminHref = rtrim($this->BConfig->get('web/base_store'), '/') . '/admin/index.php';
                $conf->set('web/admin_href', $adminHref);
            }
            if (!$this->BUtil->isUrlFull($adminHref)) {
                $adminHref = $r->scheme() . '://' . $r->httpHost() . $adminHref;
            }
            $baseAdminHref = rtrim($adminHref, '/') . '/';
        }
        return $baseAdminHref . ltrim($url, '/');
    }

    public function frontendHref($url = '')
    {
        static $baseStoreHref;
        if (!$baseStoreHref) {
            $r = $this->BRequest;
            $c = $this->BConfig;
            $storeHref = $c->get('web/base_store');
            if (!$this->BUrl->hideScriptName()) {
                if ($storeHref === '' || $storeHref === '/') {
                    $storeHref = '/index.php/';
                } else {
                    $storeHref .= '/index.php/';
                }
            }
            if (!$this->BUtil->isUrlFull($storeHref)) {
                $storeHref = $r->scheme() . '://' . $r->httpHost() . $storeHref;
            }
            $baseStoreHref = rtrim($storeHref, '/') . '/';
        }
        return $baseStoreHref . ltrim($url, '/');
    }

    /**
     * Shortcut to generate URL with base src (js, css, images, etc)
     *
     * @param string $url
     * @param string $method
     * @return string
     */
    public function src($url = '', $method = 'baseSrc', $full = true)
    {
        if ($url[0] === '@') {
            list($modName, $url) = explode('/', substr($url, 1), 2);
        }
        if (empty($modName)) {
            $r = $this->BRequest;
            $webRoot = $this->BConfig->get('web/base_src');
            if (!$webRoot) {
                $webRoot = $r->webRoot();
            }
            return $r->scheme() . '://' . $r->httpHost() . $webRoot . '/' . $url;
        }
        $m = $this->BModuleRegistry->module($modName);
        if (!$m) {
            BDebug::error('Invalid module: ' . $modName);
            return '';
        }
        return $m->$method($full) . '/' . rtrim($url, '/');
    }

    public function file($path)
    {
        if ($path[0] === '@') {
            list($modName, $path) = explode('/', substr($path, 1), 2);
        }
        if (empty($modName)) {
            if ($this->BUtil->isPathAbsolute($path)) {
                return $path;
            }
            $rootDir = $this->BConfig->get('fs/root_dir');
            return $rootDir . '/' . $path;
        }
        $m = $this->BModuleRegistry->module($modName);
        if (!$m) {
            BDebug::error('Invalid module: ' . $modName);
            return '';
        }
        return $m->root_dir . '/' . $path;
    }

    public function set($key, $val, $const = false)
    {
        if (!$const && !empty($this->_isConst[$key])) {
            BDebug::warning('Trying to reset a constant var: ' . $key . ' = ' . $val);
            return $this;
        }
        $this->_vars[$key] = $val;
        if ($const) {
            $this->_isConst[$key] = true;
        }
        return $this;
    }

    public function get($key, $default = null)
    {
        return isset($this->_vars[$key]) ? $this->_vars[$key] : $default;
    }

    /**
     * Helper to get class singletons and instances from templates like Twig
     *
     * @param string $class
     * @param boolean $new
     * @param array $args
     * @return BClass
     */
    public function instance($class, $new = false, $args = [])
    {
        return BClassRegistry::instance($class, $args, !$new);
    }

    public function storageRandomDir()
    {
        $c = $this->BConfig;
        return $c->get('fs/storage_dir') . '/' . $c->get('core/storage_random_dir');
    }
}


/**
* Bucky specialized exception
*/
class BException extends Exception
{
    /**
    * Logs exceptions
    *
    * @param string $message
    * @param int $code
    * @return BException
    */
    public function __construct($message = "", $code = 0)
    {
        parent::__construct($message, $code);
        //$this->BApp->log($message, array(), array('event'=>'exception', 'code'=>$code, 'file'=>$this->getFile(), 'line'=>$this->getLine()));
    }
}

/**
* Global configuration storage class
*/
class BConfig extends BClass
{
    /**
    * Configuration storage
    *
    * @var array
    */
    protected $_config = [];

    /**
    * Configuration that will be saved on request
    *
    * @var array
    */
    protected $_configToSave = [];

    /**
    * Enable double data storage for saving?
    *
    * @var boolean
    */
    protected $_enableSaving = true;

    protected $_encryptedPaths = [];

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return BConfig
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    /**
    * Add configuration fragment to global tree
    *
    * @param array $config
    * @param boolean $toSave whether this config should be saved in file
    * @return BConfig
    */
    public function add(array $config, $toSave = false)
    {
        $this->_config = $this->BUtil->arrayMerge($this->_config, $config);
        if ($this->_enableSaving && $toSave) {
            $this->_configToSave = $this->BUtil->arrayMerge($this->_configToSave, $config);
        }
        return $this;
    }

    /**
    * Add configuration from file
    *
    * @param string $filename
    */
    public function addFile($filename, $toSave = false)
    {
        if (preg_match('#^@([^/]+)(.*)#', $filename, $m)) {
            $module = $this->BModuleRegistry->module($m[1]);
            if (!$module) {
                BDebug::error($this->BLocale->_('Invalid module name: %s', $m[1]));
            }
            $filename = $module->root_dir . $m[2];
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
#echo "<pre>"; print_r($this); echo "</pre>";
        if (!$this->BUtil->isPathAbsolute($filename)) {
            $configDir = $this->get('fs/config_dir');
            if (!$configDir) {
                $configDir = $this->BConfig->get('fs/config_dir');
            }
            $filename = $configDir . '/' . $filename;
        }
        if (!is_readable($filename)) {
            BDebug::error($this->BLocale->_('Invalid configuration file name: %s', $filename));
        }

        switch ($ext) {
        case 'php':
            if ($this->BDebug->is(['DEBUG', 'DEVELOPMENT']) && function_exists('opcache_invalidate')) {
                opcache_invalidate($filename);
            }
            $config = include($filename);
            break;

        case 'yml':
            $config = $this->BYAML->load($filename);
            break;

        case 'json':
            $config = $this->BUtil->fromJson(file_get_contents($filename));
            break;
        }
        if (!is_array($config)) {
            BDebug::error($this->BLocale->_('Invalid configuration contents: %s', $filename));
        }
        $this->add($config, $toSave);
        return $this;
    }

    public function setPathEncrypted($path)
    {
        $this->_encryptedPaths[$path] = true;
        return $this;
    }

    public function shouldBeEncrypted($path)
    {
        return !empty($this->_encryptedPaths[$path]);
    }

    /**
     * Set configuration data in $path location
     *
     * @param string  $path slash separated path to the config node
     * @param mixed   $value scalar or array value
     * @param boolean $merge merge new value to old?
     * @param bool    $toSave
     * @return $this
     */
    public function set($path, $value, $merge = false, $toSave = false)
    {
        if (is_string($toSave) && $toSave === '_configToSave') { // limit?
            $node =& $this->{$toSave};
        } else {
            $node =& $this->_config;
        }
        if ($this->shouldBeEncrypted($path)) {

        }
        foreach (explode('/', $path) as $key) {
            $node =& $node[$key];
        }
        if ($merge) {
            $node = $this->BUtil->arrayMerge((array)$node, (array)$value);
        } else {
            $node = $value;
        }
        if ($this->_enableSaving && true === $toSave) {
            $this->set($path, $value, $merge, '_configToSave');
        }
        return $this;
    }

    /**
    * Get configuration data using path
    *
    * Ex: $this->BConfig->get('some/deep/config')
    *
    * @param string $path
    * @param mixed $default return if node not found
    * @param boolean $toSave if true, get the configuration from config tree to save
    * @return mixed
    */
    public function get($path = null, $default = null, $toSave = false)
    {
        $node = $toSave ? $this->_configToSave : $this->_config;
        if (null === $path) {
            return $node;
        }
        foreach (explode('/', $path) as $key) {
            if (!isset($node[$key])) {
                return $default;
            }
            $node = $node[$key];
        }
        return $node;
    }

    public function writeFile($filename, $config = null, $format = null)
    {
        if (null === $config) {
            $config = $this->_configToSave;
        }
        if (null === $format) {
            $format = pathinfo($filename, PATHINFO_EXTENSION);
        }
        switch ($format) {
            case 'php':
                $contents = "<?php return " . var_export($config, 1) . ';';

                // Additional check for allowed tokens

                if ($this->isInvalidManifestPHP($contents)) {
                    throw new BException('Invalid tokens in configuration found');
                }

                // a small formatting enhancement
                $contents = preg_replace('#=> \n\s+#', '=> ', $contents);
                break;

            case 'yml':
                $contents = $this->BYAML->dump($config);
                break;

            case 'json':
                $contents = $this->BUtil->toJson($config);
                break;
        }

        if (!$this->BUtil->isPathAbsolute($filename)) {
            $configDir = $this->get('fs/config_dir');
            if (!$configDir) {
                $configDir = $this->BConfig->get('fs/config_dir');
            }
            $filename = $configDir . '/' . $filename;
        }
        $this->BUtil->ensureDir(dirname($filename));
        // Write contents
        if (!file_put_contents($filename, $contents, LOCK_EX)) {
            BDebug::error('Error writing configuration file: ' . $filename);
        }
    }

    public function writeConfigFiles($files = null)
    {
        //TODO: make more flexible, to account for other (custom) file names
        if (null === $files) {
            $files = ['core', 'db', 'local'];
        }
        if (is_string($files)) {
            $files = explode(',', strtolower($files));
        }

        $c      = $this->get(null, null, true);

        if (in_array('core', $files)) {
            // configuration necessary for core startup
            unset($c['module_run_levels']['request']);

            $core = [
                'install_status' => !empty($c['install_status'])? $c['install_status']: null,
                'core' => !empty($c['core'])? $c['core']: null,
                'module_run_levels' => !empty($c['module_run_levels'])? $c['module_run_levels']: [],
                'recovery_modules' => !empty($c['recovery_modules'])? $c['recovery_modules']: null,
                'mode_by_ip' => !empty($c['mode_by_ip'])? $c['mode_by_ip']: [],
                'cache' => !empty($c['cache'])? $c['cache']: [],
            ];
            $this->writeFile('core.php', $core);
        }
        if (in_array('db', $files)) {
            // db connections
            $db = !empty($c['db'])? ['db' => $c['db']]: [];
            $this->writeFile('db.php', $db);
        }
        if (in_array('local', $files)) {
            // the rest of configuration
            $local = $this->BUtil->arrayMask($c,
                'db,install_status,module_run_levels,recovery_modules,mode_by_ip,cache,core',
                true);
            $this->writeFile('local.php', $local);
        }
        return $this;
    }

    public function unsetConfig()
    {
        $this->_config = [];
    }

    public function isInvalidManifestPHP($contents)
    {
        $tokens = token_get_all($contents);
        $allowed = [T_OPEN_TAG => 1, T_RETURN => 1, T_WHITESPACE => 1, T_COMMENT => 1,
                    T_ARRAY => 1, T_CONSTANT_ENCAPSED_STRING => 1, T_DOUBLE_ARROW => 1,
                    T_DNUMBER => 1, T_LNUMBER => 1, T_STRING => 1,
                    '(' => 1, ',' => 1, ')' => 1, ';' => 1];
        $denied = [];
        foreach ($tokens as $t) {
            if (is_string($t) && !isset($t)) {
                $denied[] = $t;
            } elseif (is_array($t) && !isset($allowed[$t[0]])) {
                $denied[] = token_name($t[0]) . ': ' . $t[1]
                    . (!empty($t[2]) ? ' (' . $t[2] . ')' : '');
            }
        }
        if (count($denied)) {
            return $denied;
        }
        return false;
    }
}

/**
* Registry of classes, class overrides and method overrides
*/
class BClassRegistry extends BClass
{
    /**
    * Self instance for singleton
    *
    * @var BClassRegistry
    */
    static protected $_instance;

    /**
    * Class overrides
    *
    * @var array
    */
    static protected $_classes = [];

    /**
    * Registry of singletons
    *
    * @var array
    */
    static protected $_singletons = [];

    /**
    * Classes that require decoration because of overridden methods
    *
    * @var array
    */
    static protected $_decoratedClasses = [];

    /**
    * Method overrides and augmentations
    *
    * @var array
    */
    static protected $_methods = [];

    /**
    * Cache for method callbacks
    *
    * @var array
    */
    static protected $_methodOverrideCache = [];

    /**
    * Property setter/getter overrides and augmentations
    *
    * @var array
    */
    static protected $_properties = [];

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @param bool $new
    * @param array $args
    * @param bool $forceRefresh force the recreation of singleton
    * @return BClassRegistry
    */
    static public function i($new = false, array $args = [], $forceRefresh = false)
    {
        if (!static::$_instance) {
            static::$_instance = new BClassRegistry;
        }
        if (!$new && !$forceRefresh) {
            return static::$_instance;
        }
        $class = get_called_class();
        return static::$_instance->instance($class, $args, !$new);
    }

    /**
     * Override a class
     *
     * Usage: $this->BClassRegistry->overrideClass('BaseClass', 'MyClass');
     *
     * Overridden class should be called one of the following ways:
     * - BClassRegistry::instance('BaseClass')
     * - BaseClass:i() -- if it extends BClass or has the shortcut defined
     *
     * Remembering the module that overrode the class for debugging
     *
     * @todo figure out how to update events on class override
     *
     * @param string $class Class to be overridden
     * @param string|null $newClass New class or clear class override
     * @param bool $replaceSingleton If there's already singleton of overridden class, replace with new one
     * @throws BException
     * @return BClassRegistry
     */
    public function overrideClass($class, $newClass, $replaceSingleton = false)
    {
        if (is_string($newClass)) {
            static::$_classes[$class] = [
                'class_name' => $newClass,
                'module_name' => $this->BModuleRegistry->currentModuleName(),
            ];
            BDebug::debug('OVERRIDE CLASS: ' . $class . ' -> ' . $newClass);
        } elseif (null === $newClass) {
            if (empty(static::$_classes[$class])) {
                return;
            }
            $newClass = $class;
            $class = static::$_classes[$class]['class_name'];
            unset(static::$_classes[$newClass]);
            BDebug::debug('CLEAR CLASS OVERRIDE: ' . $class . ' -> ' . $newClass);
        } else {
            throw new BException('Invalid argument type: ' . print_r($newClass, 1));
        }
        if ($replaceSingleton && !empty(static::$_singletons[$class])
            && get_class(static::$_singletons[$class]) !== $newClass
        ) {
            static::$_singletons[$class] = static::instance($newClass);
        }
    }

    /**
    * Dynamically add a class method
    *
    * @param string $class
    *   - '*' - will add method to all classes
    *   - 'extends AbstractClass' - will add method to all classes extending AbstractClass
    *   - 'implements Interface' - will add method to all classes implementing Interface
    * @param string $name
    * @param callback $callback
    * @return BClassRegistry
    */
    public function addMethod($class, $method, $callback, $static = false)
    {
        $arr = explode(' ', $class);
        if (!empty($arr[1])) {
            $rel = $arr[0];
            $class = $arr[1];
        } else {
            $rel = 'is';
        }
        static::$_methods[$method][$static ? 1 : 0]['override'][$rel][$class] = [
            'module_name' => $this->BModuleRegistry->currentModuleName(),
            'callback' => $callback,
        ];
    }

    /**
    * Dynamically override a class method (decorator pattern)
    *
    * Already existing instances of the class will not be affected.
    *
    * Usage: $this->BClassRegistry->overrideMethod('BaseClass', 'someMethod', array('MyClass', 'someMethod'));
    *
    * Overridden class should be called one of the following ways:
    * - BClassRegistry::instance('BaseClass')
    * - BaseClass:i() -- if it extends BClass or has the shortcut defined
    *
    * Callback method example (original method had 2 arguments):
    *
    * class MyClass {
    *   public function someMethod($origObject, $arg1, $arg2)
    *   {
    *       // do some custom stuff before call to original method here
    *
    *       $origObject->someMethod($arg1, $arg2);
    *
    *       // do some custom stuff after call to original method here
    *
    *       return $origObject;
    *   }
    * }
    *
    * Remembering the module that overrode the method for debugging
    *
    * @param string $class Class to be overridden
    * @param string $method Method to be overridden
    * @param mixed $callback Callback to invoke on method call
    * @param bool $static Whether the static method call should be overridden
    * @return BClassRegistry
    */
    public function overrideMethod($class, $method, $callback, $static = false)
    {
        static::addMethod($class, $method, $callback, $static);
        static::$_decoratedClasses[$class] = true;
    }

    /**
    * Dynamically augment class method result
    *
    * Allows to change result of a method for every invocation.
    * Syntax similar to overrideMethod()
    *
    * Callback method example (original method had 2 arguments):
    *
    * class MyClass {
    *   public function someMethod($result, $origObject, $arg1, $arg2)
    *   {
    *       // augment $result of previous object method call
    *       $result['additional_info'] = 'foo';
    *
    *       return $result;
    *   }
    * }
    *
    * A difference between overrideModule and augmentModule is that
    * you can override only with one another method, but augment multiple times.
    *
    * If augmented multiple times, each consecutive callback will receive result
    * changed by previous callback.
    *
    * @param string $class
    * @param string $method
    * @param mixed $callback
    * @param boolean $static
    * @return BClassRegistry
    */
    public function augmentMethod($class, $method, $callback, $static = false)
    {
        static::$_methods[$method][$static ? 1 : 0]['augment']['is'][$class][] = [
            'module_name' => $this->BModuleRegistry->currentModuleName(),
            'callback' => $callback,
        ];
        static::$_decoratedClasses[$class] = true;
    }

    /**
    * Augment class property setter/getter
    *
    * $this->BClassRegistry->augmentProperty('SomeClass', 'foo', 'set', 'override', 'MyClass::newSetter');
    * $this->BClassRegistry->augmentProperty('SomeClass', 'foo', 'get', 'after', 'MyClass::newGetter');
    *
    * class MyClass {
    *   public function newSetter($object, $property, $value)
    *   {
    *     $object->$property = myCustomProcess($value);
    *   }
    *
    *   public function newGetter($object, $property, $prevResult)
    *   {
    *     return $prevResult+5;
    *   }
    * }
    *
    * @param string $class
    * @param string $property
    * @param string $op {set|get}
    * @param string $type {override|before|after} get_before is not implemented
    * @param mixed $callback
    * @return BClassRegistry
    */
    public function augmentProperty($class, $property, $op, $type, $callback)
    {
        if ($op !== 'set' && $op !== 'get') {
             BDebug::error($this->BLocale->_('Invalid property augmentation operator: %s', $op));
        }
        if ($type !== 'override' && $type !== 'before' && $type !== 'after') {
            BDebug::error($this->BLocale->_('Invalid property augmentation type: %s', $type));
        }
        $entry = [
            'module_name' => $this->BModuleRegistry->currentModuleName(),
            'callback' => $callback,
        ];
        if ($type === 'override') {
            static::$_properties[$class][$property][$op . '_' . $type] = $entry;
        } else {
            static::$_properties[$class][$property][$op . '_' . $type][] = $entry;
        }
        //have to be added to redefine augmentProperty Setter/Getter methods
        static::$_decoratedClasses[$class] = true;
    }

    public function findMethodInfo($class, $method, $static = 0, $type = 'override')
    {
        //static::$_methods[$method][$static ? 1 : 0]['override'][$rel][$class]
        if (!empty(static::$_methods[$method][$static][$type]['is'][$class])) {
            //return $class;
            return static::$_methods[$method][$static][$type]['is'][$class];
        }
        $cacheKey = $class . '|' . $method . '|' . $static . '|' . $type;
        if (!empty(static::$_methodOverrideCache[$cacheKey])) {
            return static::$_methodOverrideCache[$cacheKey];
        }
        if (!empty(static::$_methods[$method][$static][$type]['extends'])) {
            $parents = class_parents($class);
#echo "<pre>"; echo $class.'::'.$method.';'; print_r($parents); print_r(static::$_methods[$method][$static][$type]['extends']); echo "</pre><hr>";
            foreach (static::$_methods[$method][$static][$type]['extends'] as $c => $v) {
                if (isset($parents[$c])) {
#echo ' * ';
                    static::$_methodOverrideCache[$cacheKey] = $v;
                    return $v;
                }
            }
        }
        if (!empty(static::$_methods[$method][$static][$type]['implements'])) {
            $implements = class_implements($class);
            foreach (static::$_methods[$method][$static][$type]['implements'] as $i => $v) {
                if (isset($implements[$i])) {
                    static::$_methodOverrideCache[$cacheKey] = $v;
                    return $v;
                }
            }
        }
        if (!empty(static::$_methods[$method][$static][$type]['is']['*'])) {
            $v = static::$_methods[$method][$static][$type]['is']['*'];
            static::$_methodOverrideCache[$cacheKey] = $v;
            return $v;
        }
        return null;
    }

    /**
    * Check if the callback is callable, accounting for dynamic methods
    *
    * @param mixed $cb
    * @return boolean
    */
    public function isCallable($cb)
    {
        if (is_string($cb)) { // plain string callback?
            $cb = explode('::', $cb);
            if (empty($cb[1])) { // not static?
                $cb = $this->BUtil->extCallback($cb); // account for special singleton syntax
            }
        } elseif (!is_array($cb)) { // unknown?
            return is_callable($cb);
        }
        if (empty($cb[1])) { // regular function?
            return function_exists($cb[0]);
        }
        if (method_exists($cb[0], $cb[1])) { // regular method?
            return true;
        }
        if (is_object($cb[0])) { // instance
            if (!$cb[0] instanceof BClass) { // regular class?
                return false;
            }
            return (bool)static::findMethodInfo(get_class($cb[0]), $cb[1]);
        } elseif (is_string($cb[0])) { // static?


            return (bool)static::findMethodInfo($cb[0], $cb[1], 1);
        } else { // unknown?
            return false;
        }
    }

    /**
    * Call overridden method
    *
    * @param object $origObject
    * @param string $method
    * @param mixed $args
    * @return mixed
    */
    public function callMethod($origObject, $method, array $args = [], $origClass = null)
    {
        //$class = $origClass ? $origClass : get_class($origObject);
        $class = get_class($origObject);
        // here $class is the overriding object class, and config for methods
        // is keyed with overridden class name, so findMethodInfo will never return true, unless
        // overriding and overridden class are the same!
        if (($info = static::findMethodInfo($class, $method, 0, 'override'))) {
            $callback = $info['callback'];
            array_unshift($args, $origObject);
            $overridden = true;
        } elseif (method_exists($origObject, $method)) {
            $callback = [$origObject, $method];
            $overridden = false;
        } else {
            BDebug::error('Invalid method: ' . get_class($origObject) . '::' . $method);
            return null;
        }

        $result = call_user_func_array($callback, $args);

        if (($info = static::findMethodInfo($class, $method, 0, 'augment'))) {
            if (!$overridden) {
                array_unshift($args, $origObject);
            }
            array_unshift($args, $result);
            foreach ($info as $augment) {
                $result = call_user_func_array($augment['callback'], $args);
                $args[0] = $result;
            }
        }
        return $result;
    }

    /**
    * Call static overridden method
    *
    * Static class properties will not be available to callbacks
    *
    * @todo decide if this is needed
    *
    * @param string $class
    * @param string $method
    * @param array $args
    */
    public function callStaticMethod($class, $method, array $args = [], $origClass = null)
    {
        if (($info = static::findMethodInfo($class, $method, 1, 'override'))) {
            $callback = $info['callback'];
        } else {
            if (method_exists($class, $method)) {
                $callback = [$class, $method];
            } else {
                throw new Exception('Invalid static method: ' . $class . '::' . $method);
            }
        }

        $result = call_user_func_array($callback, $args);

        if (($info = static::findMethodInfo($class, $method, 1, 'augment'))) {
            array_unshift($args, $result);
            foreach ($info as $augment) {
                $result = call_user_func_array($augment['callback'], $args);
                $args[0] = $result;
            }
        }

        return $result;
    }

    /**
    * Call augmented property setter
    *
    * @param object $origObject
    * @param string $property
    * @param mixed $value
    */
    public function callSetter($origObject, $property, $value)
    {
        $class = get_class($origObject);
//print_r(static::$_properties);exit;
        if (!empty(static::$_properties[$class][$property]['set_before'])) {
            foreach (static::$_properties[$class][$property]['set_before'] as $entry) {
                call_user_func($entry['callback'], $origObject, $property, $value);
            }
        }

        if (!empty(static::$_properties[$class][$property]['set_override'])) {
            $callback = static::$_properties[$class][$property]['set_override']['callback'];
            call_user_func($callback, $origObject, $property, $value);
        } else {
            $origObject->$property = $value;
        }

        if (!empty(static::$_properties[$class][$property]['set_after'])) {
            foreach (static::$_properties[$class][$property]['set_after'] as $entry) {
                call_user_func($entry['callback'], $origObject, $property, $value);
            }
        }
    }

    /**
    * Call augmented property getter
    *
    * @param object $origObject
    * @param string $property
    * @return mixed
    */
    public function callGetter($origObject, $property)
    {
        $class = get_class($origObject);

        // get_before does not make much sense, so is not implemented

        if (!empty(static::$_properties[$class][$property]['get_override'])) {
            $callback = static::$_properties[$class][$property]['get_override']['callback'];
            $result = call_user_func($callback, $origObject, $property);
        } else {
            $result = $origObject->$property;
        }

        if (!empty(static::$_properties[$class][$property]['get_after'])) {
            foreach (static::$_properties[$class][$property]['get_after'] as $entry) {
                $result = call_user_func($entry['callback'], $origObject, $property, $result);
            }
        }

        return $result;
    }

    /**
    * Get actual class name for potentially overridden class
    *
    * @param mixed $class
    * @return mixed
    */
    static public function className($class)
    {
        return !empty(static::$_classes[$class]) ? static::$_classes[$class]['class_name'] : $class;
    }

    /**
    * Get a new instance or a singleton of a class
    *
    * If at least one method of the class if overridden, returns decorator
    *
    * @param string $class
    * @param mixed $args
    * @param bool $singleton
    * @return object
    */
    static public function instance($class, array $args = [], $singleton = false)
    {
        // if singleton is requested and already exists, return the singleton
        if ($singleton && !empty(static::$_singletons[$class])) {
            return static::$_singletons[$class];
        }

        // get original or overridden class instance
        $className = static::className($class);
        if (!class_exists($className, true)) {
            BDebug::error(BLocale::i()->_('Invalid class name: %s', $className));
        }
        $args = static::processDI($className, $args);
        if ($className == 'BClassDecorator' && !empty($args)) {
            $args = [$args];
        }
        $reflClass = new ReflectionClass($className);
        $instance = $reflClass->newInstanceArgs($args);

        // if any methods are overridden or augmented, get decorator
        if (!empty(static::$_decoratedClasses[$class])) {
            $instance = static::instance('BClassDecorator', [$instance]);
        }

        // if singleton is requested, save
        if ($singleton) {
            static::$_singletons[$class] = $instance;
        }

        return $instance;
    }

    static public function processDI($className, $args = [])
    {
        static $paramsCache = [], $diStack = [];

        if (!isset($paramsCache[$className])) {
            $class = new ReflectionClass($className);
            $params = [];
            $constructor = $class->getConstructor();
            if ($constructor) {
                $constructorParams = $constructor->getParameters();
                if ($constructorParams) {
                    foreach ($constructorParams as $i => $param) {
                        $paramClass = $param->getClass();
                        $params[$i] = $paramClass ? $paramClass->getName() : false;
                    }
                }
            }
            $paramsCache[$className] = $params;
        } else {
            $params = $paramsCache[$className];
        }

        foreach ($params as $i => $paramClassName) {
            if (empty($args[$i]) && is_string($paramClassName)) {
                if (!empty($diStack[$paramClassName])) {
                    throw new BException('DI circular reference detected: ' . $className . ' -> ' . $paramClassName);
                }
                $diStack[$paramClassName] = 1;
                $args[$i] = static::instance($paramClassName, [], true);
                unset($diStack[$paramClassName]);
            }
        }

        return $args;
    }

    public function unsetInstance()
    {
        static::$_instance = null;
    }
}

/**
* Decorator class to allow easy method overrides
*
*/
class BClassDecorator
{
    /**
    * Contains the decorated (original) object
    *
    * @var object
    */
    protected $_decoratedComponent;

    /**
     * @var BClassRegistry BClassRegistry
     */
    protected $BClassRegistry;
    /**
    * Decorator constructor, creates an instance of decorated class
    *
    * @param array(object|string $class)
    * @return BClassDecorator
    */
    public function __construct($args)
    {
//echo '1: '; print_r($class);
        $class = array_shift($args);
        $this->_decoratedComponent = is_string($class) ? BClassRegistry::instance($class, $args) : $class;
        $this->BClassRegistry = BClassRegistry::i();
    }

    public function __destruct()
    {
        unset($this->_decoratedComponent);
    }

    /**
    * Method override facility
    *
    * @param string $name
    * @param array $args
    * @return mixed Result of callback
    */
    public function __call($name, array $args)
    {
        return $this->BClassRegistry->callMethod($this->_decoratedComponent, $name, $args);
    }

    /**
    * Static method override facility
    *
    * @param mixed $name
    * @param mixed $args
    * @return mixed Result of callback
    */
    static public function __callStatic($name, array $args)
    {
        return BClassRegistry::i()->callStaticMethod(get_called_class(), $name, $args);
    }

    /**
    * Proxy to set decorated component property or a setter
    *
    * @param string $name
    * @param mixed $value
    */
    public function __set($name, $value)
    {
        //$this->_decoratedComponent->$name = $value;
        $this->BClassRegistry->callSetter($this->_decoratedComponent, $name, $value);
    }

    /**
    * Proxy to get decorated component property or a getter
    *
    * @param string $name
    * @return mixed
    */
    public function __get($name)
    {
        //return $this->_decoratedComponent->$name;
        return $this->BClassRegistry->callGetter($this->_decoratedComponent, $name);
    }

    /**
    * Proxy to unset decorated component property
    *
    * @param string $name
    */
    public function __unset($name)
    {
        unset($this->_decoratedComponent->$name);
    }

    /**
    * Proxy to check whether decorated component property is set
    *
    * @param string $name
    * @return boolean
    */
    public function __isset($name)
    {
        return isset($this->_decoratedComponent->$name);
    }

    /**
    * Proxy to return decorated component as string
    *
    * @return string
    */
    public function __toString()
    {
        return (string)$this->_decoratedComponent;
    }

    /**
    * Proxy method to serialize decorated component
    *
    */
    public function __sleep()
    {
        if (method_exists($this->_decoratedComponent, '__sleep')) {
            return $this->_decoratedComponent->__sleep();
        }
        return [];
    }

    /**
    * Proxy method to perform for decorated component on unserializing
    *
    */
    public function __wakeup()
    {
        if (method_exists($this->_decoratedComponent, '__wakeup')) {
            $this->_decoratedComponent->__wakeup();
        }
    }

    /**
    * Proxy method to invoke decorated component as a method if it is callable
    *
    */
    public function __invoke()
    {
        if (is_callable($this->_decoratedComponent)) {
            return $this->_decoratedComponent(func_get_args());
        }
        return null;
    }

    /**
     * Return object of decorated class
     * @return object
     */
    public function getDecoratedComponent()
    {
        return $this->_decoratedComponent;
    }
}

class BClassAutoload extends BClass
{
    protected $_path;
    protected $_moduleName;

    protected $_pools = [];

    public function register($path, $moduleName = null)
    {
        $this->_path = $path;
        $this->_moduleName = $moduleName;
        spl_autoload_register([$this, 'callbackSingle']);
        BDebug::debug('AUTOLOAD.register: ' . $path . '(' . $moduleName . ')');
        return $this;
    }

    public function addPath($path, $moduleName = null, $filenameCb = null, $single = true)
    {
        if ($single) {
            $inst = new BClassAutoload;
            $inst->register($path, $moduleName);
            return $this;
        }

        if (!$this->_pools) {
            spl_autoload_register([$this, 'callbackMulti']);
        }
        $item = [
            'path' => $path,
            'module_name' => $moduleName,
            'filename_cb' => $filenameCb,
        ];
        $this->_pools[] = $item;
        BDebug::debug('AUTOLOAD.addPath: ' . print_r($item, 1));
        return $this;
    }

    public function callbackSingle($class)
    {
        $file = $this->_path . '/' . str_replace(['_', '\\'], ['/', '/'], $class) . '.php';
        if (file_exists($file)) {
            include($file);
        }
    }

    /**
    * Default autoload callback
    *
    * @param string $class
    */
    public function callbackMulti($class)
    {
#echo $this->root_dir.' : '.$class.'<br>';
        foreach ($this->_pools as $pool) {
            if (!empty($pool['filename_cb'])) {
                $file = call_user_func($pool['filename_cb'], $class);
            } else {
                $file = str_replace(['_', '\\'], ['/', '/'], $class) . '.php';
            }
            if ($file) {
                if ($file[0] !== '/' && $file[1] !== ':') {
                    $file = $pool['path'] . '/' . $file;
                }
                if (file_exists($file)) {
                    include ($file);
                    break;
                }
            }
        }
    }
}

/**
* Events and observers registry
*/
class BEvents extends BClass
{
    /**
    * Stores events and observers
    *
    * @todo figure out how to update events on class override
    *
    * @var array
    */
    protected $_events = [];

    /**
     * Shortcut to help with IDE autocompletion
     *
     * @param bool  $new
     * @param array $args
     * @return BEvents
     */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    /**
    * Declare event with default arguments in bootstrap function
    *
    * This method is optional and currently not used.
    *
    * @param string|array $eventName accepts multiple events in form of non-associative array
    * @param array|object $args
    * @return BEvents
    */
    public function event($eventName, $args = [])
    {
        if (is_array($eventName)) {
            foreach ($eventName as $event) {
                $this->event($event[0], !empty($event[1]) ? $event[1] : []);
            }
            return $this;
        }
        $eventName = strtolower($eventName);
        $this->_events[$eventName] = [
            'observers' => [],
            'args' => $args,
        ];
        return $this;
    }

    /**
     * Declare observers in bootstrap function
     *
     * observe|watch|on|sub|subscribe ?
     *
     * @param string|array $eventName accepts multiple observers in form of non-associative array
     * @param mixed        $callback
     * @param array|object $args
     * @param null         $alias
     * @return BEvents
     */
    public function on($eventName, $callback = null, $args = [], $alias = null)
    {
        if (is_array($eventName)) {
            foreach ($eventName as $obs) {
                $this->on($obs[0], $obs[1], !empty($obs[2]) ? $obs[2] : []);
            }
            return $this;
        }
        if (null === $alias && is_string($callback)) {
            $alias = $callback;
        }
        $observer = ['callback' => $callback, 'args' => $args, 'alias' => $alias];
        if (($moduleName = $this->BModuleRegistry->currentModuleName())) {
            $observer['module_name'] = $moduleName;
        }
        //TODO: create named observers
        $eventName = strtolower($eventName);
        $this->_events[$eventName]['observers'][] = $observer;
        BDebug::debug('SUBSCRIBE ' . $eventName, 1);
        return $this;
    }

    /**
     * Run callback on event only once, and remove automatically
     *
     * @param string|array $eventName accepts multiple observers in form of non-associative array
     * @param mixed        $callback
     * @param array|object $args
     * @param null         $alias
     * @return BEvents
     */
    public function once($eventName, $callback = null, $args = [], $alias = null)
    {
        if (is_array($eventName)) {
            foreach ($eventName as $obs) {
                $this->once($obs[0], $obs[1], !empty($obs[2]) ? $obs[2] : []);
            }
            return $this;
        }
        $this->on($eventName, $callback, $args, $alias);
        $lastId = sizeof($this->_events[$eventName]['observers']);
        $this->on($eventName, function() use ($eventName, $lastId) {
            $this->BEvents
                ->off($eventName, $lastId-1) // remove the observer
                ->off($eventName, $lastId) // remove the remover
            ;
        });
        return $this;
    }

    /**
     * Disable all observers for an event or a specific observer
     *
     * @param string $eventName
     * @param null   $alias
     * @return BEvents
     */
    public function off($eventName, $alias = null)
    {
        $eventName = strtolower($eventName);
        if (true === $alias) { //TODO: null too?
            unset($this->_events[$eventName]);
            return $this;
        }
        if (is_numeric($alias)) {
            unset($this->_events[$eventName]['observers'][$alias]);
            return $this;
        }
        if (!empty($this->_events[$eventName]['observers'])) {
            foreach ($this->_events[$eventName]['observers'] as $i => $observer) {
                if (!empty($observer['alias']) && $observer['alias'] === $alias) {
                    unset($this->_events[$eventName]['observers'][$i]);
                }
            }
        }
        return $this;
    }

    /**
    * Dispatch event observers
    *
    * dispatch|fire|notify|pub|publish ?
    *
    * @param string $eventName
    * @param array|object $args
    * @return array Collection of results from observers
    */
    public function fire($eventName, $args = [])
    {
        $eventName = strtolower($eventName);
        $profileStart = BDebug::debug('FIRE ' . $eventName . (empty($this->_events[$eventName]) ? ' (NO SUBSCRIBERS)' : ''), 1);
        $result = [];
        if (empty($this->_events[$eventName])) {
            return $result;
        }
        $observers =& $this->_events[$eventName]['observers'];
        // sort order observers
        do {
            $dirty = false;
            foreach ($observers as $i => $observer) {
                if (!empty($observer['args']['position']) && empty($observer['ordered'])) {
                    unset($observers[$i]);
                    $observer['ordered'] = true;
                    $observers = $this->BUtil->arrayInsert($observers, $observer, $observer['position']);
                    $dirty = true;
                    break;
                }
            }
        } while ($dirty);

        foreach ($observers as $i => $observer) {
            if (!empty($this->_events[$eventName]['args'])) {
                $args = array_merge($this->_events[$eventName]['args'], $args);
            }
            if (!empty($observer['args'])) {
                $args = array_merge($observer['args'], $args);
            }

            // Set current module to be used in observer callback
            if (!empty($observer['module_name'])) {
                $this->BModuleRegistry->pushModule($observer['module_name']);
            }

            $cb = $observer['callback'];

            // For cases like BView
            if (is_object($cb) && !$cb instanceof Closure) {
                if (method_exists($cb, 'set')) {
                    $cb->set($args);
                }
                $result[] = (string)$cb;
                continue;
            }

            // Special singleton syntax
            if (is_string($cb)) {
                foreach (['.', '->'] as $sep) {
                    $r = explode($sep, $cb);
                    if (sizeof($r) == 2) {
if (!class_exists($r[0]) && $this->BDebug->is('DEBUG')) {
    echo "<pre>"; BDebug::cleanBacktrace(); echo "</pre>";
}
                        $cb = [$r[0]::i(), $r[1]];
                        $observer['callback'] = $cb;
                        // remember for next call, don't want to use &$observer
                        $observers[$i]['callback'] = $cb;
                        break;
                    }
                }
            }

            // Invoke observer
            if (is_callable($cb)) {
                BDebug::debug('ON ' . $eventName/*.' : '.var_export($cb, 1)*/, 1);
                $result[] = $this->BUtil->call($cb, $args);
            } else {
                BDebug::warning('Invalid callback: ' . var_export($cb, 1), 1);
            }

            if (!empty($observer['module_name'])) {
                $this->BModuleRegistry->popModule();
            }
        }
        BDebug::profile($profileStart);
        return $result;
    }

    public function fireRegexp($eventRegexp, $args)
    {
        $results = [];
        foreach ($this->_events as $eventName => $event) {
            if (preg_match($eventRegexp, $eventName)) {
                $results += (array)$this->fire($eventName, $args);
            }
        }
        return $results;
    }

    public function debug()
    {
        echo "<pre>"; print_r($this->_events); echo "</pre>";
    }
}

/**
* Facility to handle session state
*/
class BSession extends BClass
{
    /**
    * Session data, specific to the application namespace
    *
    * @var array
    */
    public $data = null;

    /**
    * Current sesison ID
    *
    * @var string
    */
    protected $_sessionId;

    /**
    * Whether PHP session is currently open
    *
    * @var bool
    */
    protected $_phpSessionOpen = false;

    /**
    * Whether any session variable was changed since last session save
    *
    * @var bool
    */
    protected $_dirty = false;

    protected $_availableHandlers = [
        '' => 'Default',
    ];

    protected $_defaultSessionCookieName = 'fulleron';
    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return BSession
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    public function addHandler($name, $class)
    {
        $this->_availableHandlers[$name] = $class;
    }

    public function getHandlers()
    {
        $handlers = array_keys($this->_availableHandlers);
        return $handlers ? array_combine($handlers, $handlers) : [];
    }

    public function getCookieDomain()
    {
        $confDomain = $this->BConfig->get('cookie/domain');
        $httpHost = $this->BRequest->httpHost(false);
        if (!empty($confDomain)) {
            $allowedDomains = explode('|', $confDomain);
            if (in_array($httpHost, $allowedDomains)) {
                $domain = $httpHost;
            } else {
                $domain = $allowedDomains[0];
            }
        } else {
            $domain = $httpHost;
        }
        return $domain;
    }

    public function getCookiePath()
    {
        $confPath = $this->BConfig->get('cookie/path');
        $path = $confPath ? $confPath : $this->BConfig->get('web/base_store');
        if (empty($path)) {
            $path = $this->BRequest->webRoot();
        }
        return $path;
    }

    /**
     * Open session
     *
     * @todo work around multiple cookies in header bug: https://bugs.php.net/bug.php?id=38104
     * @param string|null $id Optional session ID
     * @param bool        $autoClose
     * @return $this
     */
    public function open($id = null, $autoClose = false)
    {
        if (null !== $this->data) {
            return $this;
        }
        $config = $this->BConfig->get('cookie');
        if (!empty($config['session_disable'])) {
            return $this;
        }

        $rememberMeTtl = 86400 * (!empty($config['remember_days']) ? $config['remember_days'] : 30);
        if ($this->BRequest->cookie('remember_me')) {
            $ttl = $rememberMeTtl;
        } else {
            $ttl = !empty($config['timeout']) ? $config['timeout'] : 3600;
        }

        $domain = $this->getCookieDomain();
        $path = $this->getCookiePath();

        if (!empty($config['session_handler']) && !empty($this->_availableHandlers[$config['session_handler']])) {
            $class = $this->_availableHandlers[$config['session_handler']];
            $class::i()->register($ttl);
        }
        //session_set_cookie_params($ttl, $path, $domain);
        session_name(!empty($config['name']) ? $config['name'] : $this->_defaultSessionCookieName);
        if (($dir = $this->BApp->storageRandomDir())) {
            $dir .= '/session';
            $this->BUtil->ensureDir($dir);
            session_save_path($dir);
        }
        #ini_set('session.gc_maxlifetime', $rememberMeTtl); // moved to .haccess
        if (!$id) {
            $id = $this->BRequest->get('SID');
            if (!$id && !empty($_COOKIE[session_name()])) {
                $id = $_COOKIE[session_name()];
            }
        }
        if (preg_match('#^[A-Za-z0-9]{26,60}$#', $id)) {
            session_id($id);
        } else {
            $this->regenerateId();
        }
        if (headers_sent()) {
            BDebug::warning("Headers already sent, can't start session");
        } else {
            $https = $this->BRequest->https();
            session_set_cookie_params($ttl, $path, $domain, $https, true);
            session_start();
            // update session cookie expiration to reflect current visit
            // @see http://www.php.net/manual/en/function.session-set-cookie-params.php#100657
            setcookie(session_name(), session_id(), time() + $ttl, $path, $domain, $https, true);
            $this->_phpSessionOpen = true;
        }
        $this->_sessionId = session_id();

        if (!empty($config['session_check_ip'])) {
            $ip = $this->BRequest->ip();
            if (empty($_SESSION['_ip'])) {
                $_SESSION['_ip'] = $ip;
            } elseif ($_SESSION['_ip'] !== $ip) {
                $_SESSION = [];
                session_destroy();
                session_start();
                //$this->BResponse->status(403, "Remote IP doesn't match session", "Remote IP doesn't match session");
            }
        }

        $namespace = !empty($config['session_namespace']) ? $config['session_namespace'] : 'default';
        if (empty($_SESSION[$namespace])) {
            $_SESSION[$namespace] = [];
        }
        if ($autoClose) {
            $this->data = $_SESSION[$namespace];
        } else {
            $this->data =& $_SESSION[$namespace];
        }

        if (empty($this->data['_language'])) {
            $lang = $this->BRequest->language();
            if (!empty($lang)) {
                $this->data['_language'] = $lang;
            }
        }

        #$this->data['_locale'] = $this->BConfig->get('locale');
        /*
        if (!empty($this->data['_locale'])) {
            if (is_array($this->data['_locale'])) {
                foreach ($this->data['_locale'] as $c => $l) {
                    setlocale($c, $l);
                }
            } elseif (is_string($this->data['_locale'])) {
                setlocale(LC_ALL, $this->data['_locale']);
            }
        } else {
            setLocale(LC_ALL, 'en_US.UTF-8');
        }
        */
        setLocale(LC_ALL, 'en_US.UTF-8');

        if (!empty($this->data['_timezone'])) {
            date_default_timezone_set($this->data['_timezone']);
        }

        if ($autoClose) {
            session_write_close();
            $this->_phpSessionOpen = false;
        }
BDebug::debug(__METHOD__ . ': ' . spl_object_hash($this));
        return $this;
    }

    /**
    * Set or retrieve dirty session flag
    *
    * @deprecated
    * @param bool $flag
    * @return bool
    */
    public function dirty($flag = null)
    {
        if (null === $flag) {
            return $this->_dirty;
        }
        BDebug::debug('SESSION.DIRTY ' . ($flag ? 'TRUE' : 'FALSE'), 2);
        $this->_dirty = $flag;
        return $this;
    }

    public function isDirty()
    {
        return $this->_dirty;
    }

    public function setDirty($flag = true)
    {
        BDebug::debug('SESSION.DIRTY ' . ($flag ? 'TRUE' : 'FALSE'), 2);
        $this->_dirty = $flag;
        return $this;
    }

    public function get($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
            return $this;
        }
        if (!isset($this->data[$key]) || $this->data[$key] !== $value) {
            $this->setDirty();
        }
        $this->data[$key] = $value;
        return $this;
    }

    public function pop($key)
    {
        $data = $this->get($key);
        $this->set($key, null);
        return $data;
    }

    /**
    * Get reference to session data and set dirty flag true
    *
    * @return array
    */
    public function &dataToUpdate()
    {
        $this->setDirty();
        return $this->data;
    }

    /**
    * Write session variable changes and close PHP session
    *
    * @return BSession
    */
    public function close()
    {
        if (!$this->_dirty/* || !empty( $_SESSION )*/) {
#echo "<pre>"; var_dump($this->_dirty, $_SESSION); echo "</pre>";
            return;
        }
#BDebug::debug( __METHOD__ . ': ' . spl_object_hash( $this ) );
#ob_start(); debug_print_backtrace(); BDebug::debug(nl2br(ob_get_clean()));
        if (!$this->_phpSessionOpen) {
            if (headers_sent()) {
                BDebug::info("Headers already sent, can't start session");
            } else {
                session_start();
            }
            $namespace = $this->BConfig->get('cookie/session_namespace');
            if (!$namespace) $namespace = 'default';
            $_SESSION[$namespace] = $this->data;
        }
        // TODO: i think having problem with https://bugs.php.net/bug.php?id=38104

        BDebug::debug(__METHOD__, 1);
        session_write_close();
        $this->_phpSessionOpen = false;

        if ($this->get('_regenerate_id')) {
            #session_regenerate_id(true);
            session_id($this->BUtil->randomString(26, '0123456789abcdefghijklmnopqrstuvwxyz'));
            $this->set('_regenerate_id', 0);
        }

        /*
echo "<pre style='margin-left:300px'>"; var_dump(headers_list()); echo "</pre>";
        $sessionCookie = null;
        $otherCookies = [];
        foreach (headers_list() as $header) {
            if (preg_match('/^set-cookie: (' . preg_quote(session_name()) . '=)?(.*)$/i', $header, $m)) {
                if ($m[1]) { // not session cookie
                    $sessionCookie = $m[0];
                } else {
                    $otherCookies[] = $m[0];
                }
            }
        }
        header($sessionCookie, true);
        foreach ($otherCookies as $cookie) {
            header($cookie, false);
        }
        */
        //$this->setDirty();
        return $this;
    }

    public function destroy()
    {
        $path = $this->getCookiePath();
        $domain = $this->getCookieDomain();
        $https = $this->BRequest->https();
        if (!isset($_SESSION) && !headers_sent()) {
            session_set_cookie_params(0, $path, $domain, $https, true);
            session_start();
        }
        session_destroy();

        setcookie(session_name(), '', time() - 3600, $this->getCookiePath(), $this->getCookieDomain(), $https, true);
#echo "<pre>"; var_dump($_SESSION, $_COOKIE, session_name(), $this->getCookiePath(), $this->getCookieDomain()); exit;
        return $this;
    }

    public function regenerateId()
    {
        $oldSessionId = session_id();
        @session_regenerate_id(true);
        $this->BEvents->fire(__METHOD__, ['old_session_id' => $oldSessionId, 'session_id' => session_id()]);
        //$this->BSession->set('_regenerate_id', 1);
        //session_id($this->BUtil->randomString(26, '0123456789abcdefghijklmnopqrstuvwxyz'));
        return $this;
    }

    /**
    * Get session ID
    *
    * @return string
    */
    public function sessionId()
    {
        return $this->_sessionId;
    }

    /**
    * Add session message
    *
    * @todo come up with sensible data structure
    * @param string $msg
    * @param string $type
    * @param string $tag
    * @param array $options
    * @return BSession
    */
    public function addMessage($msg, $type = 'info', $tag = '_', $options = [])
    {
        $this->setDirty();
        $message = ['type' => $type];
        if (is_array($msg) && !empty($msg[0])) {
            $message['msgs'] = $msg;
        } else {
            $message['msg'] = $msg;
        }
        if (isset($options['title'])) {
            $message['title'] = $options['title'];
        }
        if (isset($options['icon'])) {
            $message['icon'] = $options['icon'];
        }
        $this->data['_messages'][$tag][] = $message;
        return $this;
    }

    /**
    * Return any buffered messages for a tag and clear them from session
    *
    * @param string $tags comma separated
    * @return array
    */
    public function messages($tags = '_')
    {
        if (empty($this->data['_messages'])) {
            return [];
        }
        $tags = explode(',', $tags);
        $msgs = [];
        foreach ($tags as $tag) {
            if (empty($this->data['_messages'][$tag])) {
                continue;
            }
            foreach ($this->data['_messages'][$tag] as $i => $m) {
                $msgs[] = $m;
                unset($this->data['_messages'][$tag][$i]);
                $this->setDirty();
            }
        }
        return $msgs;
    }

    public function csrfToken($validating = false, $hashReferrer = null)
    {
        $data =& static::dataToUpdate();
        if (empty($data['_csrf_token'])) {
            $data['_csrf_token'] = $this->BUtil->randomString(32);
        }
        if (null === $hashReferrer) {
            $hashReferrer = $this->BConfig->get('web/csrf_check_method') === 'token+referrer';
        }

        if ($hashReferrer) {
            if ($validating) {
                $url = $this->BRequest->referrer();
            } else {
                $url = $this->BRequest->currentUrl();
            }
            $url = rtrim(str_replace('/index.php', '', $url), '/?&#');
            return sha1($data['_csrf_token'] . $url);
        }
        return $data['_csrf_token'];
    }

    public function validateCsrfToken($token)
    {
        return $token === $this->csrfToken(true);
    }

    public function __destruct()
    {
        //$this->close();
    }
}

class BSession_APC extends BClass
{
    protected $_prefix;
    protected $_ttl;
    protected $_lockTimeout = 10; // if empty, no session locking, otherwise seconds to lock timeout

    public function __construct($params = array())
    {
        if (function_exists('apc_store')) {
            $this->BSession->addHandler('apc', __CLASS__);
        }
        $def = session_get_cookie_params();
        $this->_ttl = $def['lifetime'];
        if (isset($params['ttl'])) {
            $this->_ttl = $params['ttl'];
        }
        if (isset($params['lock_timeout'])) {
            $this->_lockTimeout = $params['lock_timeout'];
        }
    }

    public function register($ttl = null)
    {
        if (null !== $ttl) {
            $this->_ttl = $ttl;
        }
        session_set_save_handler(
            [$this, 'open'], [$this, 'close'],
            [$this, 'read'], [$this, 'write'],
            [$this, 'destroy'], [$this, 'gc']
        );
    }

    public function open($savePath, $sessionName)
    {
        $this->_prefix = 'BSession/' . $sessionName;
        if (!apc_exists($this->_prefix . '/TS')) {
            // creating non-empty array @see http://us.php.net/manual/en/function.apc-store.php#107359
            apc_store($this->_prefix . '/TS', ['']);
            apc_store($this->_prefix . '/LOCK', ['']);
        }
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        $key = $this->_prefix . '/' . $id;
        if (!apc_exists($key)) {
            return ''; // no session
        }

        // redundant check for ttl before read
        if ($this->_ttl) {
            $ts = apc_fetch($this->_prefix . '/TS');
            if (empty($ts[$id])) {
                return ''; // no session
            } elseif (!empty($ts[$id]) && $ts[$id] + $this->_ttl < time()) {
                unset($ts[$id]);
                apc_delete($key);
                apc_store($this->_prefix . '/TS', $ts);
                return ''; // session expired
            }
        }

        if ($this->_lockTimeout) {
            $locks = apc_fetch($this->_prefix . '/LOCK');
            if (!empty($locks[$id])) {
                while (!empty($locks[$id]) && $locks[$id] + $this->_lockTimeout >= time()) {
                    usleep(10000); // sleep 10ms
                    $locks = apc_fetch($this->_prefix . '/LOCK');
                }
            }
            /*
            // by default will overwrite session after lock expired to allow smooth site function
            // alternative handling is to abort current process
            if (!empty($locks[$id])) {
                return false; // abort read of waiting for lock timed out
            }
            */
            $locks[$id] = time(); // set session lock
            apc_store($this->_prefix . '/LOCK', $locks);
        }

        return apc_fetch($key); // if no data returns empty string per doc
    }

    public function write($id, $data)
    {
        $ts = apc_fetch($this->_prefix . '/TS');
        $ts[$id] = time();
        apc_store($this->_prefix . '/TS', $ts);

        $locks = apc_fetch($this->_prefix . '/LOCK');
        unset($locks[$id]);
        apc_store($this->_prefix . '/LOCK', $locks);

        return apc_store($this->_prefix . '/' . $id, $data, $this->_ttl);
    }

    public function destroy($id)
    {
        $ts = apc_fetch($this->_prefix . '/TS');
        unset($ts[$id]);
        apc_store($this->_prefix . '/TS', $ts);

        $locks = apc_fetch($this->_prefix . '/LOCK');
        unset($locks[$id]);
        apc_store($this->_prefix . '/LOCK', $locks);

        return apc_delete($this->_prefix . '/' . $id);
    }

    public function gc($lifetime)
    {
        if ($this->_ttl) {
            $lifetime = min($lifetime, $this->_ttl);
        }
        $ts = apc_fetch($this->_prefix . '/TS');
        foreach ($ts as $id => $time) {
            if ($time + $lifetime < time()) {
                apc_delete($this->_prefix . '/' . $id);
                unset($ts[$id]);
            }
        }
        return apc_store($this->_prefix . '/TS', $ts);
    }
}
BSession_APC::i();
