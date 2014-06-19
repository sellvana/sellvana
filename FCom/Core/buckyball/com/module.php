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
* Registry of modules, their manifests and dependencies
*/
class BModuleRegistry extends BClass
{
    /**
     * Local static singleton instance for performance optimization
     */
    protected static $_singleton;

    /**
    * Module information collected from manifests
    *
    * @var array
    */
    protected $_modules = [];

    /**
    * Current module name, not null when:
    * - In module bootstrap
    * - In observer
    * - In view
    *
    * @var string
    */
    protected $_currentModuleName = null;

    /**
    * Current module stack trace
    *
    * @var array
    */
    protected $_currentModuleStack = [];

    public function __construct()
    {
        //$this->BEvents->on('BFrontController::dispatch:before', array($this, 'onBeforeDispatch'));
    }

    /**
     * Shortcut to help with IDE autocompletion
     *
     * Singleton performance optimization
     *
     * @param bool  $new
     * @param array $args
     * @return BModuleRegistry
     */
    static public function i($new = false, array $args = [])
    {
        if (!$new) {
            if (!static::$_singleton) {
                static::$_singleton = BClassRegistry::instance(__CLASS__, $args, !$new);
            }
            return static::$_singleton;
        }
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    public function getAllModules()
    {
        return $this->_modules;
    }

    public function isLoaded($modName)
    {
        return !empty($this->_modules[$modName]) && $this->_modules[$modName]->run_status === BModule::LOADED;
    }

    /**
    * Register or return module object
    *
    * @todo remove adding module from here
    * @param string $modName
    * @param mixed $params if not supplied, return module by name
    * @return BModule
    */
    public function module($modName, $params = null)
    {
        if (null === $params) {
            return isset($this->_modules[$modName]) ? $this->_modules[$modName] : null;
        }
        return $this->addModule($modName, $params);
    }

    public function addModule($modName, $params)
    {
        if (is_callable($params)) {
            $params = ['bootstrap' => ['callback' => $params]];
        } else {
            $params = (array)$params;
        }

        if (!empty($this->_modules[$modName])) {
            BDebug::debug('MODULE UPDATE: ' . $this->_modules[$modName]->name);
            $this->_modules[$modName]->update($params);
        } else {
            $params['name'] = $modName;
            $this->_modules[$modName] = BModule::i(true, [$params]);
        }
        return $this;
    }

    /**
    * Set or return current module context
    *
    * If $name is specified, set current module, otherwise retrieve one
    *
    * Used in context of bootstrap, event observer, view
    *
    * @todo remove setting module func
    *
    * @param string|empty $name
    * @return BModule|BModuleRegistry
    */
    public function currentModule($name = null)
    {
        if (null === $name) {
#echo '<hr><pre>'; debug_print_backtrace(); echo static::$_currentModuleName.' * '; print_r($this->module(static::$_currentModuleName)); #echo '</pre>';
            $name = $this->currentModuleName();
            return $name ? $this->module($name) : false;
        }
        $this->_currentModuleName = $name;
        return $this;
    }

    public function setCurrentModule($name)
    {
        $this->_currentModuleName = $name;
        return $this;
    }

    public function pushModule($name)
    {
        array_push($this->_currentModuleStack, $name);
        return $this;
    }

    public function popModule()
    {
        array_pop($this->_currentModuleStack);
        return $this;
    }

    public function currentModuleName()
    {
        if (!empty($this->_currentModuleStack)) {
            return $this->_currentModuleStack[sizeof($this->_currentModuleStack)-1];
        }
        return $this->_currentModuleName;
    }
/*
    public function onBeforeDispatch()
    {
        $routing = $this->BRouting;
        foreach ($this->_modules as $module) {
            if ($module->run_status===BModule::LOADED && ($prefix = $module->url_prefix)) {
                $routing->redirect('GET /'.$prefix, $prefix.'/');
            }
        }
    }
*/
    protected function _getManifestCacheFilename()
    {
        $area = $this->BRequest->area();
        $fileName = $this->BConfig->get('fs/cache_dir') . '/manifests' . ($area ? '_' . $area : '') . '.data';#.'.php';
        $this->BUtil->ensureDir(dirname($fileName));
        return $fileName;
    }

    public function saveManifestCache()
    {
        $t = BDebug::debug('SAVE MANIFESTS');
        $cacheFile = $this->_getManifestCacheFilename();
        # file_put_contents($cacheFile, serialize($this->_modules)); return;

        $data = [];
        foreach ($this->_modules as $modName => $mod) {
            $data[$modName] = $mod->asArray();
            $data[$modName]['is_cached'] = true;
            unset($data['run_level']);
        }
        #file_put_contents($cacheFile, '<'.'?php return '.var_export($data, 1).';');
        file_put_contents($cacheFile, serialize($data));
        BDebug::profile($t);
        return true;
    }

    public function loadManifestCache()
    {
        $cacheFile = $this->_getManifestCacheFilename();
        if (is_readable($cacheFile)) {
            # $this->_modules = unserialize(file_get_contents($cacheFile)); return;

            #$data = include($cacheFile);
            $data = unserialize(file_get_contents($cacheFile));
            foreach ($data as $modName => $params) {
                $this->addModule($modName, $params);
            }
            return true;
        } else {
            return false;
        }
    }

    public function deleteManifestCache()
    {
        $cacheFile = $this->_getManifestCacheFilename();
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            return true;
        }
        return false;
    }

    /**
     * Scan for module manifests in a folder
     *
     * Scan can be performed multiple times on different locations, order doesn't matter for dependencies
     * Wildcards are accepted.
     *
     * @see $this->BApp->load() for examples
     *
     * @param string $source
     * @param bool   $validateManifests
     * @throws BException
     * @return BModuleRegistry
     */
    public function scan($source, $validateManifests = false)
    {
        // if $source does not end with .json, assume it is a folder
        if (!preg_match('/\.(json|yml|php)$/', $source)) {
            $source .= '/manifest.{json,yml,php}';
        }
        $source = str_replace('\\', '/', $source);
        $manifests = glob($source, GLOB_BRACE);
        BDebug::debug('MODULE.SCAN ' . $source . ': ' . print_r($manifests, 1));
        if (!$manifests) {
            return $this;
        }
        foreach ($manifests as $file) {
            $info = pathinfo($file);
            switch ($info['extension']) {
                case 'php':
                    if ($validateManifests) {
                        if ($this->BConfig->isInvalidManifestPHP(file_get_contents($file))) {
                            throw new BException('Invalid PHP Manifest File');
                        }
                    }
                    $manifest = include($file);
                    break;
                case 'yml':
                    // already should be taken care of with filemtime()
                    $useCache = true;#!$this->BDebug->is('DEBUG,DEVELOPMENT,INSTALLATION');
                    $manifest = $this->BYAML->load($file, $useCache);
                    break;
                case 'json':
                    $json = file_get_contents($file);
                    $manifest = $this->BUtil->fromJson($json);

                    break;
                default:
                    throw new BException($this->BLocale->_("Unknown manifest file format: %s", $file));
            }
            if (empty($manifest['modules']) && empty($manifest['include'])) {
                throw new BException($this->BLocale->_("Invalid or empty manifest file: %s", $file));
            }
            if (!empty($manifest['modules'])) {
                foreach ($manifest['modules'] as $modName => $params) {
                    if (!is_array($params)) {
                        BDebug::debug('Invalid module declaration: ' . print_r($manifest['modules'], 1));
                        continue;
                    }
                    $params['manifest_file'] = $file;
                    $this->addModule($modName, $params);
                }
            }
            if (!empty($manifest['include'])) {
                $dir = dirname($file);
                foreach ($manifest['include'] as $include) {
                    $this->scan($dir . '/' . $include);
                }
            }
        }
        return $this;
    }

    /**
    * Check module requirements
    *
    * @return BModuleRegistry
    */
    public function checkRequires()
    {
        // validate required modules
        $requestRunLevels = (array)$this->BConfig->get('module_run_levels/request');
        foreach ($requestRunLevels as $modName => $runLevel) {
            if (!empty($this->_modules[$modName])) {
                $this->_modules[$modName]->run_level = $runLevel;
            } elseif ($runLevel === BModule::REQUIRED) {
                BDebug::warning('Module is required but not found: ' . $modName);
            }
        }
        // scan for require

        foreach ($this->_modules as $modName => $mod) {
            // is currently iterated module required?
            if ($mod->run_level === BModule::REQUIRED) {
                $mod->run_status = BModule::PENDING; // only 2 options: PENDING or ERROR
            }
            // iterate over require for modules
            if (!empty($mod->require['module'])) {
                foreach ($mod->require['module'] as &$req) {
                    $reqMod = !empty($this->_modules[$req['name']]) ? $this->_modules[$req['name']] : false;
                    // is the module missing
                    if (!$reqMod) {
                        $mod->errors[] = ['type' => 'missing', 'mod' => $req['name']];
                        continue;
                    // is the module disabled
                    } elseif ($reqMod->run_level === BModule::DISABLED) {
                        $mod->errors[] = ['type' => 'disabled', 'mod' => $req['name']];
                        continue;
                    // is the module version not valid
                    } elseif (!empty($req['version'])) {
                        $reqVer = $req['version'];
                        if (!empty($reqVer['from']) && version_compare($reqMod->version, $reqVer['from'], '<')
                            || !empty($reqVer['to']) && version_compare($reqMod->version, $reqVer['to'], '>')
                            || !empty($reqVer['exclude']) && in_array($reqVer->version, (array)$reqVer['exclude'])
                        ) {
                            $mod->errors[] = ['type' => 'version', 'mod' => $req['name']];
                            continue;
                        }
                    }
                    if (!in_array($req['name'], $mod->parents)) {
                        $mod->parents[] = $req['name'];
                    }
                    if (!in_array($modName, $reqMod->children)) {
                        $reqMod->children[] = $modName;
                    }
                    if ($mod->run_status === BModule::PENDING) {
                        $reqMod->run_status = BModule::PENDING;
                    }
                }
                unset($req);
            }

            if (!$mod->errors && $mod->run_level === BModule::REQUESTED) {
                $mod->run_status = BModule::PENDING;
            }
        }

        foreach ($this->_modules as $modName => $mod) {
            if (!is_object($mod)) {
                var_dump($mod); exit;
            }
            if ($mod->errors && !$mod->errors_propagated) {
                // propagate dependency errors into subdependent modules
                $this->propagateRequireErrors($mod);
            } elseif ($mod->run_status === BModule::PENDING) {
                // propagate pending status into deep dependent modules
                $this->propagateRequires($mod);
            }
        }
        #var_dump($this->_modules);exit;
        return $this;
    }

    /**
    * Propagate dependency errors into children modules recursively
    *
    * @param BModule $mod
    * @return BModuleRegistry
    */
    public function propagateRequireErrors($mod)
    {
        //$mod->action = !empty($dep['action']) ? $dep['action'] : 'error';
        BDebug::debug('Module dependency unmet for ' . $mod->name . ': ' . print_r($mod->errors, 1));
        $mod->run_status = BModule::ERROR;
        $mod->errors_propagated = true;
        foreach ($mod->children as $childName) {
            if (empty($this->_modules[$childName])) {
                continue;
            }
            $child = $this->_modules[$childName];
            if ($child->run_level === BModule::REQUIRED && $child->run_status !== BModule::ERROR) {
                $this->propagateRequireErrors($child);
            }
        }
        return $this;
    }

    /**
    * Propagate dependencies into parent modules recursively
    *
    * @param BModule $mod
    * @return BModuleRegistry
    */
    public function propagateRequires($mod)
    {
        foreach ($mod->parents as $parentName) {
            if (empty($this->_modules[$parentName])) {
                continue;
            }
            $parent = $this->_modules[$parentName];
            if ($parent->run_status === BModule::PENDING) {
                continue;
            }
            $parent->run_status = BModule::PENDING;
            $this->propagateRequires($parent);
        }
        return $this;
    }

    /**
     * Detect circular module dependencies references
     */
    public function detectCircularReferences($mod, $depPathArr = [])
    {
        $circ = [];
        if ($mod->parents) {
            foreach ($mod->parents as $p) {
                if (isset($depPathArr[$p])) {
                    $found = false;
                    $circPath = [];
                    foreach ($depPathArr as $k => $_) {
                        if ($p === $k) {
                            $found = true;
                        }
                        if ($found) {
                            $circPath[] = $k;
                        }
                    }
                    $circPath[] = $p;
                    $circ[] = $circPath;
                } else {
                    $depPathArr1 = $depPathArr;
                    $depPathArr1[$p] = 1;
                    $circ += $this->detectCircularReferences($this->_modules[$p], $depPathArr1);
                }
            }
        }
        return $circ;
    }

    /**
    * Perform topological sorting for module dependencies
    *
    * @return BModuleRegistry
    */
    public function sortRequires()
    {
        $modules = $this->_modules;

        $circRefsArr = [];
        foreach ($modules as $modName => $mod) {
            $circRefs = $this->detectCircularReferences($mod);
            if ($circRefs) {
                foreach ($circRefs as $circ) {
                    $circRefsArr[join(' -> ', $circ)] = 1;

                    $s = sizeof($circ);
                    $mod1name = $circ[$s-1];
                    $mod2name = $circ[$s-2];
                    $mod1 = $modules[$mod1name];
                    $mod2 = $modules[$mod2name];
                    foreach ($mod1->parents as $i => $p) {
                        if ($p === $mod2name) {
                            unset($mod1->parents[$i]);
                        }
                    }
                    foreach ($mod2->children as $i => $c) {
                        if ($c === $mod1name) {
                            unset($mod2->children[$i]);
                        }
                    }
                }
            }
        }
        foreach ($circRefsArr as $circRef => $_) {
            BDebug::warning('Circular reference detected: ' . $circRef);
        }

        // take care of 'load_after' option
        foreach ($modules as $modName => $mod) {
            $mod->children_copy = $mod->children;
            if ($mod->load_after && is_array($mod->load_after)) {
                foreach ($mod->load_after as $n) {
                    if (empty($modules[$n])) {
                        BDebug::debug('Invalid module name specified in load_after: ' . $n);
                        continue;
                    }
                    $mod->parents[] = $n;
                    $modules[$n]->children[] = $mod->name;
                }
            }
        }
        // get modules without dependencies
        $rootModules = [];
        foreach ($modules as $modName => $mod) {
            if (empty($mod->parents)) {
                $rootModules[] = $mod;
            }
        }
#echo "<pre>"; print_r($this->_modules); echo "</pre>";
#echo "<pre>"; print_r($rootModules); echo "</pre>";
        // begin algorithm
        $sorted = [];
        while ($modules) {
            // check for circular reference
            if (!$rootModules) {
                BDebug::warning('Circular reference detected, aborting module sorting');
                return false;
            }
            // remove this node from root modules and add it to the output
            $n = array_pop($rootModules);
            $sorted[$n->name] = $n;
            // for each of its children: queue the new node, finally remove the original
            for ($i = count($n->children)-1; $i >= 0; $i--) {
                // get child module
                $childModule = $modules[$n->children[$i]];
                // remove child modules from parent
                unset($n->children[$i]);
                // remove parent from child module
                unset($childModule->parents[array_search($n->name, $childModule->parents)]);
                // check if this child has other parents. if not, add it to the root modules list
                if (!$childModule->parents) array_push($rootModules, $childModule);
            }
            // remove processed module from list
            unset($modules[$n->name]);
        }
        // move modules that have load_after=='ALL' to the end of list
        foreach ($sorted as $modName => $mod) {
            if ($mod->load_after === 'ALL') {
                unset($sorted[$modName]);
                $sorted[$modName] = $mod;
            }
        }
        $this->_modules = $sorted;
        return $this;
    }

    public function processRequires()
    {
        $this->checkRequires();
        $this->sortRequires();
        return $this;
    }

    public function processDefaultConfig()
    {
        //$this->BUtil->arrayWalk($this->_modules, 'processDefaultConfig');
        foreach ($this->_modules as $mod) {
            $mod->processDefaultConfig();
        }
        return $this;
    }

    /**
    * Run modules bootstrap callbacks
    *
    * @todo enable loading in runtime
    * @return BModuleRegistry
    */
    public function bootstrap()
    {
        foreach ($this->_modules as $mod) {
            $this->pushModule($mod->name);
            $mod->beforeBootstrap();
            $this->popModule();
        }
        foreach ($this->_modules as $mod) {
            $this->pushModule($mod->name);
            $mod->bootstrap();
            $this->popModule();
        }
        $this->BLayout->collectAllViewsFiles(); // TODO: refactor, decide on a better place
        $this->BEvents->fire('BModuleRegistry::bootstrap:after');
        return $this;
    }
}

/**
* Module object to store module manifest and other properties
*/
class BModule extends BClass
{
    /**
    * Relevant environment variables cache
    *
    * @var array
    */
    static protected $_envVars = [];

    /**
    * Default module run_level
    *
    * @var string
    */
    static protected $_defaultRunLevel = 'ONDEMAND';

    /**
    * Manifest files cache
    *
    * @var array
    */
    static protected $_manifestCache = [];

    public $manifest = [];

    public $name;
    public $run_level;
    public $run_status;
    public $before_bootstrap;
    public $bootstrap;
    public $version;
    public $channel;
    public $category;
    public $db_connection_name;
    public $root_dir;
    public $view_root_dir;
    public $url_prefix;
    public $base_src;
    public $base_href;
    public $manifest_file;
    public $require = [];
    public $parents = [];
    public $children = [];
    public $children_copy = [];
    public $update;
    public $errors = [];
    public $errors_propagated;
    public $title;
    public $author;
    public $description;
    public $migrate;
    public $load_after;
    public $auto_use;
    public $views;
    public $layout;
    public $routing;
    public $observe;
    public $provides;
    public $areas;
    public $area;
    public $override;
    public $default_config;
    public $autoload;
    public $crontab;
    public $security;
    public $custom;
    public $license;
    public $uploads;

    public $is_cached;
    /**
     * @var array
     */
    public $translations;

    const
        // run_level
        DISABLED  = 'DISABLED', // Do not allow the module to be loaded
        ONDEMAND  = 'ONDEMAND', // Load this module only when required by another module
        REQUESTED = 'REQUESTED', // Attempt to load the module, and silently ignore, if dependencies are not met.
        REQUIRED  = 'REQUIRED', // Attempt to load the module, and fail, if dependencies are not met.

        // run_status
        IDLE    = 'IDLE', // The module was found, but not loaded
        PENDING = 'PENDING', // The module is marked to be loaded, but not loaded yet. This status is currently used during internal bootstrap only.
        LOADED  = 'LOADED', // The module has been loaded successfully
        ERROR   = 'ERROR' // There was an error loading the module due to unmet dependencies
    ;

    protected static $_fieldOptions = [
        'run_level' => [
            self::DISABLED  => 'DISABLED',
            self::ONDEMAND  => 'ONDEMAND',
            self::REQUESTED => 'REQUESTED',
            self::REQUIRED  => 'REQUIRED',
        ],
        'run_status' => [
            self::IDLE    => 'IDLE',
            self::PENDING => 'PENDING',
            self::LOADED  => 'LOADED',
            self::ERROR   => 'ERROR'
        ],
    ];

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return BModule
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    /**
    * Set default run_level which new modules should initialize with
    *
    * @param string $runLevel
    */
    public function defaultRunLevel($runLevel)
    {
        static::$_defaultRunLevel = $runLevel;
    }

    /**
    * Assign arguments as module parameters
    *
    * @param array $args
    * @return BModule
    */
    public function __construct(array $args)
    {
        #if (empty($args['area'])) {
            $args['area'] = $this->BRequest->area();
        #}
/*
if ($args['name']==="FCom_Referrals") {
    echo "<pre>";
    var_dump($args);
    debug_print_backtrace();
    exit;
}
*/
        $this->set($args);

        $m = $this->_getManifestData();

        if (!$this->is_cached) {
            $this->_processAreas($args);

            if (!empty($this->bootstrap) && empty($this->bootstrap['file'])) {
                $this->bootstrap['file'] = null;
            }
            if (empty($this->root_dir)) {
                $this->root_dir = $m['root_dir'];
            }
            //TODO: optimize path calculations
            if (!$this->BUtil->isPathAbsolute($this->root_dir)) {
    //echo "{$m['root_dir']}, {$args['root_dir']}\n";
                if ($m['root_dir'] != $this->root_dir)
                    $this->root_dir = $this->BUtil->normalizePath($m['root_dir'] . '/' . $this->root_dir);
                else  {
                    $this->root_dir = $this->BUtil->normalizePath($this->root_dir);
                }

                //$this->root_dir = $this->BUtil->normalizePath($this->root_dir);
                //echo $this->root_dir."\n";
            }

            $this->_normalizeManifestRequireFormat();
        }

        $this->run_level = static::$_defaultRunLevel; // disallow declaring run_level in manifest
        /*
        if (!isset($this->run_level)) {
            $runLevel = $this->BConfig->get('module_run_levels/request/'.$this->name);
            $this->run_level = $runLevel ? $runLevel : BModule::ONDEMAND;
        }
        */
        if (!isset($this->run_status)) {
            $this->run_status = BModule::IDLE;
        }

        if (!isset($this->channel)) {
            $this->channel = 'alpha';
        }
    }

    protected function _normalizeManifestRequireFormat()
    {
        // normalize require format
        foreach ($this->require as $reqType => $req) {
            if (is_string($req)) {
                if (is_numeric($reqType)) {
                    $this->require['module'] = [['name' => $req]];
                    unset($this->require[$reqType]);
                } else {
                    $this->require[$reqType] = [['name' => $req]];
                }
            } else if (is_array($req)) {
                foreach ($this->require[$reqType] as $reqMod => &$reqVer) {
                    if (is_numeric($reqMod)) {
                        $reqVer = ['name' => $reqVer];
                    } elseif (is_string($reqVer) || is_float($reqVer)) {
                        $from = '';
                        $to = '';
                        $reqVerAr = explode(";", (string)$reqVer);
                        if (!empty($reqVerAr[0])) {
                            $from = $reqVerAr[0];
                        }
                        if (!empty($reqVerAr[1])) {
                            $to = $reqVerAr[1];
                        }
                        if (!empty($from)) {
                            $reqVer = ['name' => $reqMod, 'version' => ['from' => $from, 'to' => $to]];
                        } else {
                            $reqVer = ['name' => $reqMod];
                        }
                    }
                }
            }
        }
    }

    protected function _processAreas()
    {
        if ($this->area && !empty($this->areas[$this->area])) {
            $areaParams = $this->areas[$this->area];
            $areaParams['update'] = true;
            $this->update($areaParams);
        }
        return;
    }

    public function update(array $params)
    {
        //$params = $this->_processAreas($params);
        if (empty($params['update'])) {
            $rootDir = $this->root_dir;
            $file = $this->bootstrap['file'];
            BDebug::debug($this->BLocale->_('Module is already registered: %s (%s)', [$this->name, $rootDir . '/' . $file]));
            return $this;
        }
        unset($params['update']);
        foreach ($params as $k => $v) {
            if (is_array($this->$k)) {
                $this->$k = array_merge_recursive((array)$this->$k, (array)$v);
            } else {
                $this->$k = $v;
                //TODO: make more flexible without sacrificing performance
                switch ($k) {
                case 'url_prefix':
                    $this->base_href = $this->BApp->baseUrl() . ($v ? '/' . $v : '');
                    break;
                }
            }
        }
        return $this;
    }

    protected function _getManifestData()
    {
        if (empty($this->manifest_file)) {
            $bt = debug_backtrace();
            foreach ($bt as $i => $t) {
                if (!empty($t['function']) && ($t['function'] === 'module' || $t['function'] === 'addModule')) {
                    $t1 = $t;
                    break;
                }
            }
            if (!empty($t1)) {
                $this->manifest_file = $t1['file'];
            }
        }
        //TODO: eliminate need for manifest file
        $file = $this->manifest_file;
        if (empty(static::$_manifestCache[$file])) {
            static::$_manifestCache[$file] = ['root_dir' => str_replace('\\', '/', dirname($file))];
        }
        return static::$_manifestCache[$file];
    }

    /**
    * put your comment there...
    *
    * @todo optional omit http(s):
    */
    protected function _initEnvData()
    {
        if (!empty(static::$_envVars)) {
            return;
        }
        $r = $this->BRequest;
        $c = $this->BConfig;
        static::$_envVars['doc_root'] = $r->docRoot();
        static::$_envVars['web_root'] = $r->webRoot();
        //static::$_envVars['http_host'] = $r->httpHost();
        if (($rootDir = $c->get('fs/root_dir'))) {
            static::$_envVars['root_dir'] = str_replace('\\', '/', $rootDir);
        } else {
            static::$_envVars['root_dir'] = str_replace('\\', '/', $r->scriptDir());
        }
        if (($baseSrc = $c->get('web/base_src'))) {
            static::$_envVars['base_src'] = $baseSrc;//$r->scheme().'://'.static::$_envVars['http_host'].$baseSrc;
        } else {
            static::$_envVars['base_src'] = static::$_envVars['web_root'];
        }
        if (($baseHref = $c->get('web/base_href'))) {
            static::$_envVars['base_href'] = $baseHref;//$r->scheme().'://'.static::$_envVars['http_host'].$c->get('web/base_href');
        } else {
            static::$_envVars['base_href'] = static::$_envVars['web_root'];
        }
#echo "<pre>"; var_dump(static::$_envVars, $_SERVER); echo "</pre>"; exit;
        foreach (static::$_manifestCache as &$m) {
            //    $m['base_src'] = static::$_envVars['base_src'].str_replace(static::$_envVars['root_dir'], '', $m['root_dir']);
            $m['base_src'] = rtrim(static::$_envVars['base_src'], '/') . str_replace(static::$_envVars['root_dir'], '', $m['root_dir']);
        }
        unset($m);
    }

    protected function _prepareModuleEnvData()
    {
        static::_initEnvData();
        $m = static::$_manifestCache[$this->manifest_file];

        if (empty($this->url_prefix)) {
            $this->url_prefix = '';
        }
        if (empty($this->view_root_dir)) {
            $this->view_root_dir = $this->root_dir;
        }
        if (empty($this->base_src)) {
            $url = $m['base_src'];
            $url .= str_replace($m['root_dir'], '', $this->root_dir);
            $this->base_src = $this->BUtil->normalizePath(rtrim($url, '/'));
        }
        if (empty($this->base_href)) {
            $this->base_href = static::$_envVars['base_href'];
            if (!empty($this->url_prefix)) {
                $this->base_href .= '/' . $this->url_prefix;
            }
        }
    }

    protected function _processAutoUse()
    {
        if (empty($this->auto_use)) {
            return;
        }
        $auto = array_flip((array)$this->auto_use);
        $area = $this->BRequest->area();
        $areaDir = str_replace('FCom_', '', $area);
        if (isset($auto['all']) || isset($auto['bootstrap'])) { // TODO: check for is_callable() ?
            if (method_exists($this->name . '_' . $areaDir, 'bootstrap')) {
                $this->bootstrap = ['callback' => $this->name . '_' . $areaDir . '::bootstrap'];
            } elseif (method_exists($this->name . '_Main', 'bootstrap')) {
                $this->bootstrap = ['callback' => $this->name . '_Main::bootstrap'];
            } elseif (method_exists($this->name, 'bootstrap')) {
                $this->bootstrap = ['callback' => $this->name . '::bootstrap'];
            }
        }
        $layout = $this->BLayout;
        if (isset($auto['all']) || isset($auto['views'])) {
            if (is_dir($this->root_dir . '/views')) {
                $layout->addAllViewsDir($this->root_dir . '/views');
            }
            if (is_dir($this->root_dir . '/' . $areaDir . '/views')) {
                $layout->addAllViewsDir($this->root_dir . '/' . $areaDir . '/views');
            }
        }
        if (isset($auto['all']) || isset($auto['layout'])) {
            if (file_exists($this->root_dir . '/layout.yml')) {
                $layout->loadLayoutAfterTheme($this->root_dir . '/layout.yml');
            }
            if (file_exists($this->root_dir . '/' . $areaDir . '/layout.yml')) {
                $layout->loadLayoutAfterTheme($this->root_dir . '/' . $areaDir . '/layout.yml');
            }
        }
    }

    protected function _processAutoload()
    {
        if (!empty($this->autoload)) {
            foreach ((array)$this->autoload as $al) {
                if (is_string($al)) {
                    $al = ['root_dir' => $al];
                }
                $this->autoload($al['root_dir'], !empty($al['callback']) ? $al['callback'] : null);
            }
        }
    }

    protected function _processProvides()
    {
        //TODO: automatically enable theme module when it is used
        if ($this->run_status === BModule::PENDING && !empty($this->provides['themes'])) {
            foreach ($this->provides['themes'] as $name => $params) {
                $params['module_name'] = $this->name;
                $this->BLayout->addTheme($name, $params);
            }
        }
    }

    protected function _processRouting()
    {
        if (empty($this->routing)) {
            return;
        }
        $hlp = $this->BRouting;
        foreach ($this->routing as $r) {
            if ($r[0][0] === '/' || $r[0][0] === '^') {
                $method = 'route';
                $route = $r[0];
                $callback = $r[1];
                $args = isset($r[2]) ? $r[2] : [];
                $name = isset($r[3]) ? $r[3] : null;
                $multiple = isset($r[4]) ? $r[4] : true;
            } else {
                $method = strtolower($r[0]);
                if (!isset($r[1])) {
                    BDebug::error('Invalid routing directive: ' . print_r($r));
                    continue;
                }
                $route = $r[1];
                $callback = isset($r[2]) ? $r[2] : null;
                $args = isset($r[3]) ? $r[3] : [];
                $name = isset($r[4]) ? $r[4] : null;
                $multiple = isset($r[5]) ? $r[5] : true;
            }
            $hlp->$method($route, $callback, $args, $name, $multiple);
        }
    }

    protected function _processViews()
    {
        if (empty($this->views)) {
            return;
        }
        $hlp = $this->BLayout;
        foreach ($this->views as $v) {
            $viewName = strtolower($v[0]);
            $params = $v[1];
            $hlp->addView($viewName, $params);
        }
    }

    protected function _processObserve()
    {
        if (empty($this->observe)) {
            return;
        }
        $hlp = $this->BEvents;
        foreach ($this->observe as $o) {
            $event = $o[0];
            $callback = $o[1];
            $args = !empty($o[2]) ? $o[2] : [];
            $hlp->on($event, $callback, $args);
        }
    }

    protected function _processOverrides()
    {
        if (!empty($this->override['class'])) {
            foreach ($this->override['class'] as $o) {
                if (!$o) {
                    continue;
                }
if (!isset($o[0]) || !isset($o[1])) {
    BDebug::notice('Invalid override in module ' . $this->name . '(' . print_r($o, 1) . ')');
    continue;
}
                $this->BClassRegistry->overrideClass($o[0], $o[1]);
            }
        }
    }

    protected function _processTranslations()
    {
        //load translations
        $language = $this->BSession->get('_language');
        if (!empty($language) && !empty($this->translations[$language])) {
            /*
            if (!is_array($this->translations[$language])) {
                $this->translations[$language] = array($this->translations[$language]);
            }
            */
            $file = $this->root_dir . '/i18n/' . $this->translations[$language];
            $this->BLocale->addTranslationsFile($file);
        }
    }

    protected function _processSecurity()
    {
        if (!empty($this->security['request_fields_whitelist'])) {
            $this->BRequest->addRequestFieldsWhitelist($this->security['request_fields_whitelist']);
        }
    }

    /**
     * Register module specific autoload callback
     *
     * @param mixed $rootDir
     * @param mixed $callback
     * @return $this
     */
    public function autoload($rootDir = '', $callback = null)
    {
        if (!$rootDir) {
            $rootDir = $this->root_dir;
        } elseif (!$this->BUtil->isPathAbsolute($rootDir)) {
            $rootDir = $this->root_dir . '/' . $rootDir;
        }
        $this->BClassAutoload->addPath(rtrim($rootDir, '/'), $this->name, $callback);
        return $this;
    }

    /**
    * Module specific base URL
    *
    * @return string
    */
    public function baseSrc($full = true)
    {
        $src = $this->base_src;
        if ($full) {
            $r = $this->BRequest;
            $scheme = $r->scheme();
            if ($scheme == 'http') {
                $scheme = ''; // don't force http
            } else {
                $scheme .= ':';
            }
            $src = $scheme . '//' . $r->httpHost() . $src;
        }
        return $src;
    }

    public function baseHref($full = true)
    {
        $href = $this->base_href;
        if ($full) {
            $r = $this->BRequest;
            $scheme = $r->scheme();
            if ($scheme == 'http') {
                $scheme = ''; // don't force http
            } else {
                $scheme .= ':';
            }
            $href = $scheme . '://' . $r->httpHost() . $href;
        }
        return $href;
    }

    public function baseDir()
    {
        $dir = $this->root_dir;

        return $dir;
    }

    public function runLevel($level = null, $updateConfig = false)
    {
        if (null === $level) {
            return $this->run_level;
        }
        return $this->setRunLevel($level, $updateConfig);
    }

    public function setRunLevel($level, $updateConfig = false)
    {
        $this->run_level = $level;
        if ($updateConfig) {
            $this->BConfig->set('module_run_levels/request/' . $this->name, $level);
        }
        return $this;
    }

    /**
    * @todo remove set func
    *
    * @param mixed $status
    * @return BModule
    */
    public function runStatus($status = null)
    {
        if (null === $status) {
            return $this->run_status;
        }
        $this->run_status = $status;
        return $this;
    }

    public function setRunStatus($status)
    {
        $this->run_status = $status;
        return $this;
    }

    public function _($string, $args = [])
    {
        $tr = dgettext($this->name, $string);
        if ($args) {
            $tr = $this->BUtil->sprintfn($tr, $args);
        }
        return $tr;
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                if ($k[0] === "\0") { // protected properties, for cache saved on previous commit
                    continue;
                }
                $this->$k = $v;
            }
            return $this;
        }
        $this->$key = $value;
        return $this;
    }

    public function processDefaultConfig()
    {
        if (!empty($this->default_config)) {
            $cfgHlp = $this->BConfig;
            $config = $this->default_config;
            foreach ($config as $path => $value) {
                if (strpos($path, '/') !== false) {
                    $cfgHlp->set($path, $value);
                    unset($config[$path]);
                }
            }
            $cfgHlp->add($config);
        }
        $this->_processProvides();
        return $this;
    }

    public function beforeBootstrap()
    {
        if ($this->run_status !== BModule::PENDING) {
            return $this;
        }
        $this->_prepareModuleEnvData();
        $this->_processOverrides();

        if (empty($this->before_bootstrap)) {
            return $this;
        }

        $bb = $this->before_bootstrap;
        if (!is_array($bb)) {
            $bb = ['callback' => $bb];
        }
        if (!empty($bb['file'])) {
            $includeFile = $this->BUtil->normalizePath($this->root_dir . '/' . $bb['file']);
            BDebug::debug('MODULE.BEFORE.BOOTSTRAP ' . $includeFile);
            require_once ($includeFile);
        }
        if (!empty($bb['callback'])) {
            $start = BDebug::debug($this->BLocale->_('Start BEFORE bootstrap for %s', [$this->name]));
            $this->BUtil->call($bb['callback']);
            #$mod->run_status = BModule::LOADED;
            BDebug::profile($start);
            BDebug::debug($this->BLocale->_('End BEFORE bootstrap for %s', [$this->name]));
        }

        return $this;
    }

    public function bootstrap($force = false)
    {
        if ($this->run_status !== BModule::PENDING) {
            if ($force) {
                $this->_prepareModuleEnvData(); // prepare data missed in beforeBootstrap
            } else {
                return $this; // skip module bootstrap
            }
        }

        $this->_processAutoload();
        $this->_processTranslations();
        $this->_processViews(); // before auto_use to initialize custom view classes
        $this->_processAutoUse();
        $this->_processRouting();
        $this->_processObserve();
        $this->_processSecurity();

        $this->BEvents->fire('BModule::bootstrap:before', ['module' => $this]);

        if (!empty($this->bootstrap)) {
            if (!empty($this->bootstrap['file'])) {
                $includeFile = $this->BUtil->normalizePath($this->root_dir . '/' . $this->bootstrap['file']);
                BDebug::debug('MODULE.BOOTSTRAP ' . $includeFile);
                require_once ($includeFile);
            }
            if (!empty($this->bootstrap['callback'])) {
                $start = BDebug::debug($this->BLocale->_('Start bootstrap for %s', [$this->name]));
                $this->BUtil->call($this->bootstrap['callback']);
                #$mod->run_status = BModule::LOADED;
                BDebug::profile($start);
                BDebug::debug($this->BLocale->_('End bootstrap for %s', [$this->name]));
            }
        }

        $this->run_status = BModule::LOADED;
        return $this;
    }

    public function asArray()
    {
        $a = (array)$this;
        foreach ($a as $k => $v) {
            if ($k[0] === "\0") {
                unset($a[$k]);
            }
        }
        return $a;
    }
}

class BMigrate extends BClass
{
    /**
    * Information about current module being migrated
    *
    * @var array
    */
    protected static $_migratingModule;

    /**
    * Last ran query during installation routine
    */
    protected static $_lastQuery;

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return BMigrate
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    /**
    * Collect migration data from all modules
    *
    * @return array
    */
    public function getMigrationData()
    {
        $migration = [];
        $allModules = $this->BModuleRegistry->getAllModules();
        foreach ($allModules as $modName => $mod) {
            if (empty($mod->migrate) && class_exists($mod->name . '_Migrate')) {
                $mod->migrate = $mod->name . '_Migrate';
            }
            if ($mod->version && $mod->migrate) {
                $connName = $mod->db_connection_name ? $mod->db_connection_name : 'DEFAULT';
                $migration[$connName][$modName] = [
                    'code_version' => $mod->version,
                    'script' => $mod->migrate,
                    'run_status' => $mod->run_status,
                    'module_name' => $modName,
                    'connection_name' => $connName,
                ];
            }
        }
        return $migration;
    }

    /**
    * Declare DB Migration script for a module
    *
    * @param string $script callback, script file name, script class name or directory
    * @param string|null $moduleName if null, use current module
    */
    /*
    public function migrate($script='migrate.php', $moduleName=null)
    {
        if (null === $moduleName) {
            $moduleName = $this->BModuleRegistry->currentModuleName();
        }
        $module = $this->BModuleRegistry->module($moduleName);
        $connectionName = $module->db_connection_name ? $module->db_connection_name : 'DEFAULT';
        static::$_migration[$connectionName][$moduleName]['script'] = $script;
    }
    */

    /**
    * Declare DB uninstallation script for a module
    *
    * @param mixed $script
    * @param empty $moduleName
    */
    /*
    public function uninstall($script, $moduleName=null)
    {
        if (null === $moduleName) {
            $moduleName = $this->BModuleRegistry->currentModuleName();
        }
        static::$_uninstall[$moduleName]['script'] = $script;
    }
    */

    /**
    * Run declared migration scripts to install or upgrade module DB scheme
    *
    * @param mixed $limitModules
    *   - false: migrate ALL declared modules (including disabled)
    *   - true: migrate only enabled modules in current request
    *   - array or comma separated string: migrate only specified modules
    */
    public function migrateModules($limitModules = false, $force = false, $redirectUrl = null)
    {
        if (!$force) {
            $conf = $this->BConfig;
            $req = $this->BRequest;
            if ($conf->get('install_status') !== 'installed'
                || !$conf->get('db/implicit_migration')
                || $req->xhr() && !$req->get('MIGRATE')
            ) {
                return;
            }
        }

        $modReg = $this->BModuleRegistry;
        $migration = static::getMigrationData();
        if (!$migration) {
            return;
        }

        if (is_string($limitModules)) {
            $limitModules = explode(',', $limitModules);
        }
        // initialize module tables
        // find all installed modules
        $num = 0;
        foreach ($migration as $connectionName => &$modules) {
            if ($limitModules) {
                foreach ($modules as $modName => $mod) {
                    if ((true === $limitModules && $mod['run_status'] === 'LOADED')
                        || (is_array($limitModules) && in_array($modName, $limitModules))
                    ) {
                        continue;
                    }
                    unset($modules[$modName]);
                }
            }
            $this->BDb->connect($connectionName); // switch connection
            $this->BDbModule->init(); // Ensure modules table in current connection
            // collect module db schema versions
            $dbModules = $this->BDbModule->orm()->find_many();
            foreach ($dbModules as $m) {
                if ($m->last_status === 'INSTALLING') { // error during last installation
                    $m->delete();
                    continue;
                }
                $modules[$m->module_name]['schema_version'] = $m->schema_version;
            }
            // run required migration scripts
            foreach ($modules as $modName => $mod) {
                if (empty($mod['code_version'])) {
                    continue; // skip migration of registered module that is not currently active
                }
                if (!empty($mod['schema_version']) && $mod['schema_version'] === $mod['code_version']) {
                    continue; // no migration necessary
                }
                if (empty($mod['script'])) {
                    BDebug::warning('No migration script found: ' . $modName);
                    continue;
                }

                $modules[$modName]['migrate'] = true;
                $num++;
            }
        }
        unset($modules);

        if (!$num) {
            return;
        }

        $this->BConfig->set('db/logging', 1);

        // TODO: move special cases from buckyball to fulleron
        // special case for FCom_Admin because some frontend modules require its tables
        if (empty($migration['DEFAULT']['FCom_Admin']['schema_version'])
            && empty($migration['DEFAULT']['FCom_Admin']['migrate'])
        ) {
            $this->BModuleRegistry->module('FCom_Admin')->run_status = BModule::LOADED;
            static::migrateModules('FCom_Core,FCom_Admin');
            //return;
        }
        /*
        if (!$force && $this->BConfig->get('core/currently_migrating')) {
            return;
        }
        $this->BConfig->set('core/currently_migrating', 1, false, true);
        */
        if (class_exists('FCom_Core_Main')) {
            $this->FCom_Core_Main->writeConfigFiles('core');
        }

        $this->BResponse->startLongResponse();
        $view = $this->BView;
        echo '<html><body><h1>Migrating modules DB structure...</h1><pre>';
        $i = 0;
        $error = false;
        try {
            foreach ($migration as $connectionName => $modules) {
                $this->BDb->connect($connectionName);

                foreach ($modules as $modName => $mod) {
                    if (empty($mod['migrate'])) {
                        continue;
                    }

                    echo '<br>[' . (++$i) . '/' . $num . '] ';
                    if (empty($mod['schema_version'])) {
                        echo 'Installing <strong>' . $view->q($modName . ': ' . $mod['code_version']) . '</strong> ... ';
                    } else {
                        echo 'Upgrading  <strong>' . $view->q($modName . ': ' . $mod['schema_version'] . ' -> ' . $mod['code_version']) . '</strong> ... ';
                    }

                    $modReg->currentModule($modName);
                    $script = $mod['script'];
                    if (is_array($script)) {
                         if (!empty($script['file'])) {
                             $filename = $this->BModuleRegistry->module($modName)->root_dir . '/' . $script['file'];
                             if (!file_exists($filename)) {
                                 BDebug::warning('Migration file not exists: ' . $filename);
                                 continue;
                             }
                             require_once $filename;
                         }
                         $script = $script['callback'];
                    }
                    $module = $modReg->module($modName);
                    static::$_migratingModule =& $mod;
                    /*
                    try {
                        $this->BDb->transaction();
                    */
                        $this->BDb->ddlClearCache(); // clear DDL cache before each migration step
                        BDebug::debug('DB.MIGRATE ' . $view->q($script));
                        if (is_callable($script)) {
                            $result = $this->BUtil->call($script);
                        } elseif (is_file($module->root_dir . '/' . $script)) {
                            $result = include_once($module->root_dir . '/' . $script);
                        } elseif (is_dir($module->root_dir . '/' . $script)) {
                            //TODO: process directory of migration scripts
                        } elseif (class_exists($script, true)) {
                            if (method_exists($script, 'run')) {
                                $script::i()->run();
                            } else {
                                static::_runClassMethods($script);
                            }
                        }
                    /*
                        $this->BDb->commit();
                    } catch (Exception $e) {
                        $this->BDb->rollback();
                        throw $e;
                    }
                    */
                }
            }
            /*
            $this->BConfig->set('core/currently_migrating', 0, false, true);
            if (class_exists('FCom_Core_Main')) {
                $this->FCom_Core_Main->writeConfigFiles('core');
            }
            */
        } catch (Exception $e) {
            /*
            $this->BConfig->set('core/currently_migrating', 0, false, true);
            if (class_exists('FCom_Core_Main')) {
                $this->FCom_Core_Main->writeConfigFiles('core');
            }
            */
            $trace = $e->getTrace();
            foreach ($trace as $traceStep) {
                if (strpos($traceStep['file'], BUCKYBALL_ROOT_DIR) !== 0) {
                    break;
                }
            }
            echo "\n\n" . $e->getMessage();
            if (!static::$_lastQuery) {
                static::$_lastQuery = BORM::get_last_query();
            }
            if (static::$_lastQuery) {
                echo "\n\nQUERY: " . static::$_lastQuery;
            }
            echo "\n\nLOCATION: " . $traceStep['file'] . ':' . $traceStep['line'];
            $error = true;
        }
        $modReg->currentModule(null);
        static::$_migratingModule = null;

        $url = null !== $redirectUrl ? $redirectUrl : $this->BRequest->currentUrl();
        echo '</pre>';
        if (!$error) {
            echo '<script>location.href="' . $url . '";</script>';
            echo '<p>ALL DONE. <a href="' . $url . '">Click here to continue</a></p>';
        } else {
            echo '<p>There was an error, please check the output or log file and try again.</p>';
            echo '<p><a href="' . $url . '">Click here to continue</a></p>';
        }
        echo '</body></html>';
        exit;
    }

    protected function _runClassMethods($class)
    {
        $methods = get_class_methods($class);
        $installs = [];
        $upgrades = [];
        foreach ($methods as $method) {
            if (preg_match('/^install__([0-9_]+)$/', $method, $m)) {
                $installs[] = [
                    'method' => $method,
                    'to' => str_replace('_', '.', $m[1])
                ];
            } elseif (preg_match('/^upgrade__([0-9_]+)__([0-9_]+)$/', $method, $m)) {
                $upgrades[] = [
                    'method' => $method,
                    'from' => str_replace('_', '.', $m[1]),
                    'to' => str_replace('_', '.', $m[2]),
                ];
            }
        }
        usort($installs, function($a, $b) { return version_compare($a['to'], $b['to']); });
        usort($upgrades, function($a, $b) { return version_compare($a['from'], $b['from']); });
        end($installs); $install = current($installs);
        $instance = $class::i();

        if ($install) {
            static::install($install['to'], [$instance, $install['method']]);
        }
        foreach ($upgrades as $upgrade) {
            static::upgrade($upgrade['from'], $upgrade['to'], [$instance, $upgrade['method']]);
        }
    }

    /**
     * Run module DB installation scripts and set module db scheme version
     *
     * @param string $version
     * @param mixed  $callback SQL string, callback or file name
     * @return bool
     * @throws Exception
     * @return bool
     */
    public function install($version, $callback)
    {
        $mod =& static::$_migratingModule;
        // if no code version set, return
        if (empty($mod['code_version'])) {
            return false;
        }
        // if schema version exists, skip
        if (!empty($mod['schema_version'])) {
            return true;
        }

        echo '*' . $version . '; ';

BDebug::debug(__METHOD__ . ': ' . var_export($mod, 1));
        // creating module before running install, so the module configuration values can be created within script
        $module = $this->BDbModule->load($mod['module_name'], 'module_name');
        if (!$module) {
            $module = $this->BDbModule->create([
                'module_name' => $mod['module_name'],
                'schema_version' => $version,
                'last_upgrade' => $this->BDb->now(),
                'last_status' => 'INSTALLING',
            ])->save();
        }
        // call install migration script
        try {
            if (is_callable($callback)) {
                $result = $this->BUtil->call($callback);
            } elseif (is_file($callback)) {
                $result = include $callback;
            } elseif (is_string($callback)) {
                $this->BDb->run($callback);
                $result = null;
            }
            if (false === $result) {
                static::$_lastQuery = BORM::get_last_query();
                $module->delete();
                return false;
            }
            $module->set(['last_status' => 'INSTALLED'])->save();
            $mod['schema_version'] = $version;
        } catch (Exception $e) {
            // delete module schema record if unsuccessful
            static::$_lastQuery = BORM::get_last_query();
            $module->delete();
            throw $e;
        }
        return true;
    }

    /**
     * Run module DB upgrade scripts for specific version difference
     *
     * @param string $fromVersion
     * @param string $toVersion
     * @param mixed  $callback SQL string, callback or file name
     * @return bool
     * @throws BException
     * @throws Exception
     * @return bool
     */
    public function upgrade($fromVersion, $toVersion, $callback)
    {
        $mod =& static::$_migratingModule;

        // if no code version set, return
        if (empty($mod['code_version'])) {
            return false;
        }
        // if schema doesn't exist, throw exception
        if (empty($mod['schema_version'])) {
            throw new BException($this->BLocale->_("Can't upgrade, module schema doesn't exist yet: %s", $this->BModuleRegistry->currentModuleName()));
        }
        $schemaVersion = $mod['schema_version'];

        // if module is not enable skip upgrade
        if (!$this->BModuleRegistry->isLoaded($mod['module_name'])) {
            return true;
        }
        // if code version is older than target scheme version, skip
        if (version_compare($mod['code_version'], $toVersion, '<')) {
            return true;
        }
        // if schema is newer than requested FROM version, skip
        if (version_compare($schemaVersion, $fromVersion, '>')) {
            return true;
        }
        echo '^' . $toVersion . '; ';

        $module = $this->BDbModule->load($mod['module_name'], 'module_name')->set([
            'last_upgrade' => $this->BDb->now(),
            'last_status' => 'UPGRADING',
        ])->save();
        // call upgrade migration script
        try {
            if (is_callable($callback)) {
                $result = $this->BUtil->call($callback);
            } elseif (is_file($callback)) {
                $result = include $callback;
            } elseif (is_string($callback)) {
                $this->BDb->run($callback);
                $result = null;
            }
            if (false === $result) {
                return false;
            }
            // update module schema version to new one
            $mod['schema_version'] = $toVersion;
            $module->set([
                'schema_version' => $toVersion,
                'last_status' => 'UPGRADED',
            ])->save();
        } catch (Exception $e) {
            static::$_lastQuery = BORM::get_last_query();
            $module->set(['last_status' => 'ERROR'])->save();
            throw $e;
        }
        return true;
    }

    /**
    * Run declared uninstallation scripts on module uninstall
    *
    * @param string $modName
    * @return boolean
    */
    public function runUninstallScript($modName = null)
    {
        if (null === $modName) {
            $modName = $this->BModuleRegistry->currentModuleName();
        }
        $mod =& static::$_migratingModule;

        // if no code version set, return
        if (empty($mod['code_version'])) {
            return false;
        }
        // if module schema doesn't exist, skip
        if (empty($mod['schema_version'])) {
            return true;
        }
        $callback = $mod->uninstall_callback; //TODO: implement
        // call uninstall migration script
        if (is_callable($callback)) {
            $this->BUtil->call($callback);
        } elseif (is_file($callback)) {
            include $callback;
        } elseif (is_string($callback)) {
            $this->BDb->run($callback);
        }
        // delete module schema version from db, related configuration entries will be deleted
        $this->BDbModule->load($mod['module_name'], 'module_name')->delete();
        return true;
    }
}


class BDbModule extends BModel
{
    protected static $_table = 'buckyball_module';

    public function init()
    {
        //$this->BDb->connect();
        $table = static::table();
        if (!$this->BDb->ddlTableExists($table)) {
            $this->BDb->ddlTableDef($table, [
                'COLUMNS' => [
                    'id' => 'int unsigned not null auto_increment',
                    'module_name' => 'varchar(100) not null',
                    'schema_version' => 'varchar(20)',
                    'data_version' => 'varchar(20)',
                    'last_upgrade' => 'datetime',
                    'last_status' => 'varchar(20)',
                ],
                'PRIMARY' => '(id)',
                'KEYS' => [
                    'UNQ_module_name' => 'UNIQUE (module_name)',
                ],
            ]);
        }
        //BDbModuleConfig::init();
    }
}
/*
class BDbModuleConfig extends BModel
{
    protected static $_table = 'buckyball_module_config';

    public function init()
    {
        $table = static::table();
        $modTable = $this->BDbModule->table();
        if (!$this->BDb->ddlTableExists($table)) {
            $this->BDb->run("
CREATE TABLE {$table} (
id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
module_id INT UNSIGNED NOT NULL,
`key` VARCHAR(100),
`value` TEXT,
UNIQUE (module_id, `key`),
CONSTRAINT `FK_{$modTable}` FOREIGN KEY (`module_id`) REFERENCES `{$modTable}` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB;
            ");
        }
    }
}
*/
