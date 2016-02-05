<?php

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
 * Layout facility to register views and render output from views
 */
class BLayout extends BClass
{
    /**
     * Installed themes registry
     *
     * @var array
     */
    protected $_themes = [];

    /**
     * Default theme name (current area / main module)
     *
     * @var string|array
     */
    protected $_defaultTheme;

    /**
     * Load these layout files after theme
     *
     * @var array
     */
    protected $_loadLayoutFiles = [];

    /**
     * Add view templates from these dirs after theme
     *
     * @var array
     */
    protected $_addViewsDirs = [];

    /**
     * Add view templates from these files after theme
     *
     * @var array
     */
    protected $_addViewsFiles = [];

    /**
     * Flags whether views were collected automatically for each area
     *
     * @var array
     */
    protected $_areViewsCollectedInArea = [];

    /**
     * Area for the current layout object
     *
     * @var string
     */
    protected $_area;

    /**
     * Layouts declarations registry
     *
     * @var array
     */
    protected $_layouts = [];

    /**
     * View objects registry
     *
     * @var array
     */
    protected $_views = [];

    /**
     * Main (root) view to be rendered first
     *
     * @var BView
     */
    protected $_rootViewName = 'root';

    /**
     * Main root dir for view files if operating outside of a module
     *
     * @var mixed
     */
    protected $_viewRootDir;

    /**
     * Default class name for newly created views
     *
     * @var string
     */
    protected $_defaultViewClass;

    /**
     * @var array
     */
    protected static $_metaDirectives = [
        'remove'   => 'BLayout.metaDirectiveRemoveCallback',
        'callback' => 'BLayout.metaDirectiveCallback',
        'include'  => 'BLayout.metaDirectiveIncludeCallback',
        'root'     => 'BLayout.metaDirectiveRootCallback',
        'hook'     => 'BLayout.metaDirectiveHookCallback',
        'view'     => 'BLayout.metaDirectiveViewCallback',
    ];

    protected static $_renderers = [];

    /**
     * @var array
     */
    protected static $_extRenderers = [
        '.php' => ['callback' => null],
    ];

    /**
     * @var string
     */
    protected static $_extRegex = '\.php';

    protected $_rememberOverrides = false;

    protected $_currentViewStack = array();

    /**
     * Shortcut to help with IDE autocompletion
     *
     * @param bool  $new
     * @param array $args
     * @return BLayout
     */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    /**
     * Get view root dir
     * If there is a module in registry as current module, its view root dir will be used, else default one.
     *
     * @return string
     */
    public function getViewRootDir()
    {
        $module = $this->BModuleRegistry->currentModule();

        return $module ? $module->view_root_dir : $this->_viewRootDir;
    }

    /**
     * Set view root dir
     * If there is current module in registry, set it to it, else set it to layout.
     *
     * @param $rootDir
     * @return $this
     */
    public function setViewRootDir($rootDir, $module = null)
    {
        if (null === $module) {
            $module = $this->BModuleRegistry->currentModule();
        }
        $isAbsPath = strpos($rootDir, '/') === 0 || strpos($rootDir, ':') === 1;
        if ($module) {
            $module->view_root_dir = $isAbsPath ? $rootDir : $module->root_dir . '/' . $rootDir;
        } else {
            $this->_viewRootDir = $rootDir;
        }

        return $this;
    }

    public function addMetaDirective($name, $callback)
    {
        static::$_metaDirectives[$name] = $callback;
        return $this;
    }

    /**
     * Add extension renderer
     *
     * Set renderer for particular file extension. E.g. '.php'
     * For renderer to work, params should either be array with 'renderer' field
     * or a string representing renderer class.
     *
     * @param       $name
     * @param array $params
     * @return $this
     */
    public function addRenderer($name, $params)
    {
        if (is_string($name) && is_string($params)) {
            $params = ['file_ext' => [$name], 'callback' => $params];
        }
        if (is_string($params['file_ext'])) {
            $params['file_ext'] = explode(';', $params['file_ext']);
        }
        if (empty($params['editor'])) {
            $params['editor'] = 'text';
        }

        static::$_renderers[$name] = $params;

        foreach ($params['file_ext'] as $ext) {
            static::$_extRenderers[$ext] = $params;
        }
        static::$_extRegex = join('|', array_map('preg_quote', array_keys(static::$_extRenderers)));
        BDebug::debug('ADD RENDERER: ' . join('; ', $params['file_ext']));
        return $this;
    }

    public function getAllRenderers($asOptions = false)
    {
        if ($asOptions) {
            $options = [];
            foreach (static::$_renderers as $k => $r) {
                $options[$k] = !empty($r['description']) ? $r['description'] : $k;
            }
            asort($options);
            return $options;
        }
        return static::$_renderers;
    }

    public function getRenderer($name)
    {
        return !empty(static::$_renderers[$name]) ? static::$_renderers[$name] : null;
    }

    public function addAllViewsDir($rootDir = null, $prefix = '', $curModule = null)
    {
        if (null === $curModule) {
            $curModule = $this->BModuleRegistry->currentModule();
        }
        $this->_addViewsDirs[] = [$rootDir, $prefix, $curModule];
        $this->BEvents->fire(__METHOD__, ['root_dir' => $rootDir, 'prefix' => $prefix, 'module' => $curModule]);
        return $this;
    }

    /**
     * @param string $rootDir
     * @param BModule|null $curModule
     * @return bool|mixed
     */
    public function processRootDir($rootDir, $curModule = null)
    {
        if ($curModule && !$this->BUtil->isPathAbsolute($rootDir)) {
            $rootDir = $curModule->root_dir . '/' . $rootDir;
        }
        if (!is_dir($rootDir)) {
            BDebug::warning('Not a valid directory: ' . $rootDir);

            return false;
        }
        $rootDir1 = str_replace('\\', '/', realpath($rootDir));

        if (!$rootDir1) {
            BDebug::warning('Invalid root view dir: ' . $rootDir);
        }
        return $rootDir1;
    }

    public function rememberOverrides($flag)
    {
        $this->_rememberOverrides = $flag;
        return $this;
    }

    public function collectAllViewsFiles($area = null, $force = false)
    {
        if (!$this->_area) {
            if (null === $area) {
                $area = $this->BRequest->area();
            }
            $this->_area = $area;
        }
        if (false === $force && !empty($this->_areViewsCollectedInArea[$area])
            || !$this->BModuleRegistry->isBootstrapFinished()
        ) {
            return $this;
        }
        $t = BDebug::debug(__METHOD__);
        $cacheKey = 'ALL_VIEWS-' . $area;
        $cacheConfig = $this->BConfig->get('core/cache/view_files');
        $useCache = !$cacheConfig && $this->BDebug->is(['STAGING', 'PRODUCTION']) || $cacheConfig === 'enable';
        if ($useCache) {
            $data = $this->BCache->load($cacheKey);
        }
        if (!empty($data)) {
            $this->_addViewsFiles = $data;
        } else {
            foreach ($this->_addViewsDirs as $dirData) {
                $rootDir = $this->processRootDir($dirData[0], $dirData[2]); // rootDir
                if (!$rootDir) {
                    continue;
                }
                $files = $this->BUtil->globRecursive($rootDir);
                if (!$files) {
                    continue;
                }
                $prefix = $dirData[1]; // prefix
                if ($prefix) {
                    $prefix = rtrim($prefix, '/') . '/';
                }
                $re = '#^(' . preg_quote($rootDir . '/', '#') . ')(.*)(' . static::$_extRegex . ')$#';
                foreach ($files as $file) {
                    // highly unlikely that the folder will match template regex, but saves I/O
                    /*if (!is_file($file)) {
                        continue;
                    }*/
                    if (preg_match($re, $file, $m)) {
                        $viewName = $prefix . $m[2];
                        $viewParams = [
                            'template' => $file,
                            'file_ext' => $m[3],
                            'module_name' => $dirData[2]->name, // module
                            'renderer' => static::$_extRenderers[$m[3]]['callback'],
                            'overrides' => [],
                        ];
                        if ($this->_rememberOverrides && !empty($this->_addViewsFiles[$viewName])) {
                            $oldViewParams = $this->_addViewsFiles[$viewName];
                            $viewParams['overrides'] = $oldViewParams['overrides'];
                            unset($oldViewParams['overrides']);
                            $viewParams['overrides'][] = $oldViewParams;
                        }
                        $this->_addViewsFiles[$viewName] = $viewParams;
                    }
                }
            }
            $this->_addViewsDirs = [];
            if ($useCache) {
                $this->BCache->save($cacheKey, $this->_addViewsFiles);
            } else {
                $this->BCache->delete($cacheKey);
            }
        }
        BDebug::profile($t);
        foreach ($this->_addViewsFiles as $viewName => $viewParams) {
            $this->addView($viewName, $viewParams);
        }
        $this->_addViewsFiles = [];
        $this->_areViewsCollectedInArea[$area] = true;
        return $this;
    }

    /**
     * Find and register all templates within a folder as view objects
     *
     * View objects will be named by template file paths, stripped of extension (.php)
     *
     * @param string $rootDir Folder with view templates, relative to current module root
     *                        Can end with slash or not - make sure to specify
     * @param string $prefix Optional: add prefix to view names
     * @param BModule $curModule
     * @return BLayout
     */
    public function addAllViews($rootDir, $prefix = '', $curModule = null)
    {
        #$this->BDebug->debug(__METHOD__ . ': ' . $rootDir . ($curModule ? ' (' . $curModule->name . ')' : ''));

        if (null === $curModule) {
            $curModule = $this->BModuleRegistry->currentModule();
        }
        $rootDir = $this->processRootDir($rootDir, $curModule);
        if (!$rootDir) {
            return $this;
        }
        $this->setViewRootDir($rootDir);
        $files = $this->BUtil->globRecursive($rootDir);
        if (!$files) {
            return $this;
        }

        if ($prefix) {
            $prefix = rtrim($prefix, '/') . '/';
        }
        $re = '#^(' . preg_quote($rootDir . '/', '#') . ')(.*)(' . static::$_extRegex . ')$#';
        foreach ($files as $file) {
            // highly unlikely that the folder will match template regex, but saves I/O
            /*if (!is_file($file)) {
                continue;
            }*/
            if (preg_match($re, $file, $m)) {
                //$this->view($prefix.$m[2], array('template'=>$m[2].$m[3]));
                $viewParams = [
                    'template' => $file,
                    'file_ext' => $m[3],
                    'module_name' => $curModule ? $curModule->name : null,
                    'renderer' => static::$_extRenderers[$m[3]]['callback'],
                ];
                $this->addView($prefix . $m[2], $viewParams);
            }
        }

        $this->BEvents->fire(__METHOD__, ['root_dir' => $rootDir, 'prefix' => $prefix, 'module' => $curModule]);

        return $this;
    }

    /**
     * Get all views in this layout or filtered by pattern
     *
     * @return BView[]
     */
    public function getAllViews()
    {
        if (!$this->_area || empty($this->_areViewsCollectedInArea[$this->_area])) {
            $this->collectAllViewsFiles($this->_area);
        }
        return $this->_views;
    }

    /**
     * Set default view class
     *
     * @param mixed $className
     * @return BLayout
     */
    public function setDefaultViewClass($className)
    {
        $this->_defaultViewClass = $className;
        return $this;
    }

    /**
     * Alias for getView()
     *
     * Not sure whether to leave view() for convenience
     *
     * @param string  $viewName
     * @return BView|BLayout
     */
    public function view($viewName)
    {
        return $this->getView($viewName);
    }

    /**
     * Return registered view
     *
     * Returns BViewEmpty to avoid errors in templates or controllers, when methods are chained
     *
     * @param mixed $viewName
     * @return BView
     */
    public function getView($viewName)
    {
        if (!$this->_area || empty($this->_areViewsCollectedInArea[$this->_area])) {
            $views = $this->getAllViews();
        } else {
            $views = $this->_views;
        }
        return isset($views[$viewName]) ? $views[$viewName]
            : $this->BViewEmpty->i(true)->setParam('view_name', $viewName);
    }

    /**
     * Add or update view to layout
     * Adds or updates a view to layout.
     * If view already exists, will replace its params with provided ones.
     *
     * @param string|array $viewName
     * @param string|array $params if string - view class name
     *   - template: optional, for templated views
     *   - view_class: optional, for custom views
     *   - module_name: optional, to use template from a specific module
     * @param bool $reset
     * @return $this
     * @throws BException
     */
    public function addView($viewName, $params = [], $reset = false)
    {
        if (is_array($viewName)) {
            foreach ($viewName as $i => $view) {
                if (!is_numeric($i)) {
                    throw new BException($this->BLocale->_('Invalid argument: %s', print_r($viewName, 1)));
                }
                $this->addView($view[0], $view[1], $reset); // if self::view is possible to disappear better not use it.
            }

            return $this;
        }
        if (is_string($params)) {
            $params = ['view_class' => $params];
        }
        if (empty($params['module_name']) && ($moduleName = $this->BModuleRegistry->currentModuleName())) {
            $params['module_name'] = $moduleName;
        }
        $viewAlias = !empty($params['view_alias']) ? $params['view_alias'] : $viewName;
        $viewFile = !empty($params['view_file']) ? $params['view_file'] : $viewName;
        if (!isset($this->_views[$viewAlias]) || !empty($params['view_class'])) {
            if (empty($params['view_class'])) {
                /*
                if (!empty($params['module_name'])) {
                    $viewClass = $this->BModuleRegistry->module($params['module_name'])->default_view_class;
                    if ($viewClass) {
                        $params['view_class'] = $viewClass;
                    }
                } else
                */
                if (!empty($this->_defaultViewClass)) {
                    $params['view_class'] = $this->_defaultViewClass;
                }
            }

            $this->_views[$viewAlias] = $this->BView->factory($viewFile, $params);
            /*
            $this->BEvents->fire('BLayout::view:add:' . $viewAlias, array(
                'view' => $this->_views[$viewAlias],
            ));
            */
        } else {
            $this->_views[$viewAlias]->setParam($params);
            /*
            $this->BEvents->fire('BLayout::view:update:' . $viewAlias, array(
                'view' => $this->_views[$viewAlias],
            ));
            */
        }

        return $this;
    }

    /**
     * Find a view by matching its name to a regular expression
     *
     * @param string $re
     * @return BView[]
     */
    public function findViewsRegex($re)
    {
        $views = [];
        foreach ($this->getAllViews() as $viewName => $view) {
            if (preg_match($re, $viewName)) {
                $views[$viewName] = $view;
            }
        }

        return $views;
    }

    /**
     * Set root view name
     * @param string $viewName
     * @return $this
     */
    public function setRootView($viewName)
    {
        $this->_rootViewName = $viewName;

        return $this;
    }

    /**
     * @return BLayout|BView|null
     */
    public function getRootView()
    {
        return $this->_rootViewName ? $this->getView($this->_rootViewName) : null;
    }

    /**
     * @return string
     */
    public function getRootViewName()
    {
        return $this->_rootViewName;
    }

    /**
     * Clone view object to another name
     *
     * @param string $from
     * @param string $to
     * @return BView
     */
    public function cloneView($from, $to = null)
    {
        $views = $this->getAllViews();
        if (null === $to) {
            $to = $from . '-copy';
            for ($i = 2; !empty($views[$to]); $i++) {
                $to = $from . '-copy' . $i;
            }
        }
        $this->_views[$to] = clone $views[$from];
        $this->_views[$to]->setParam('view_name', $to);

        return $this->_views[$to];
    }

    /**
     * Register a call back to a hook
     *
     * @param string $hookName
     * @param mixed  $callback
     * @param array  $args
     * @param array  $params
     * @return $this
     */
    public function hook($hookName, $callback, $args = [], $params = [])
    {
        $this->BEvents->on('BLayout::hook:' . $hookName, $callback, $args, $params);

        return $this;
    }

    /**
     * Register a view as call back to a hook
     * $viewName should either be a string with a name of view,
     * or an array in which first field is view name and the rest are view parameters.
     *
     * @param string $hookName
     * @param string|array $viewName
     * @param array $args
     * @param array  $params
     * @return $this
     */
    public function hookView($hookName, $viewName, $args = [], $params = [])
    {
        if (is_array($viewName)) {
            $viewParams = $viewName;
            $viewName = array_shift($viewParams);
            static::addView($viewName, $viewParams);
        }
        $view = static::getView($viewName);
        if (!$view) {
            BDebug::warning('Invalid view name: ' . $viewName, 1);

            return $this;
        }
        //$view->set($args);
        if (empty($params['alias'])) {
            $params['alias'] = $viewName;
        }
        return $this->hook($hookName, $view, $args, $params);
    }

    public function hookViewsRegex($hookName, $viewsRegex, $args = [], $params = [])
    {
        $views = $this->findViewsRegex($viewsRegex);
        foreach ($views as $viewName => $view) {
            $this->hookView($hookName, $viewName, $args, $params);
        }
        return $this;
    }

    public function hookText($hookName, $text)
    {
        return $this->hook($hookName, function() use ($text) { return $text; });
    }

    public function hookClear($hookName, $viewNames)
    {
        $eventHlp = $this->BEvents;
        $eventName = 'BLayout::hook:' . $hookName;
        if (true === $viewNames || 'ALL' === $viewNames) {
            $eventHlp->off($eventName, true);
        } else {
            foreach ((array)$viewNames as $clearViewName) {
                $eventHlp->off($eventName, $clearViewName);
            }
        }
        return $this;
    }

    /**
     *
     * @deprecated
     * @param mixed $layoutName
     * @param mixed $layout
     * @return BLayout
     */
    public function layout($layoutName, $layout = null)
    {
        if (is_array($layoutName) || null !== $layout) {
            $this->addLayout($layoutName, $layout);
        } else {
            $this->applyLayout($layoutName);
        }

        return $this;
    }

    /**
     * Load layout update from file
     *
     * @param string $layoutFilename
     * @throws BException
     * @return BLayout
     */
    public function loadLayout($layoutFilename)
    {
#echo "<pre>"; debug_print_backtrace(); echo "</pre>";
        $ext = strtolower(pathinfo($layoutFilename, PATHINFO_EXTENSION));
        if (!$this->BUtil->isPathAbsolute($layoutFilename)) {
            $mod = $this->BModuleRegistry->currentModule();
            if ($mod) {
                $layoutFilename = $mod->root_dir . '/' . $layoutFilename;
            }
        }
        BDebug::debug('LAYOUT.LOAD: ' . $layoutFilename);
        switch ($ext) {
            case 'yml':
            case 'yaml':
                $layoutData = $this->BYAML->load($layoutFilename);
                break;
            case 'json':
                $layoutData = $this->BUtil->fromJson(file_get_contents($layoutFilename));
                break;
            case 'php':
                if ($this->BDebug->is(['DEBUG', 'DEVELOPMENT']) && function_exists('opcache_invalidate')) {
                    opcache_invalidate($layoutFilename);
                }
                $layoutData = include($layoutFilename);
                break;
            default:
                throw new BException('Unknown layout file type: ' . $layoutFilename);
        }
        //$this->_layoutDataCache[$layoutFilename] = $layoutData;
        $this->addLayout($layoutData);
        return $this;
    }

    /**
    * Load layout update after theme has been initialized
    *
    * @param string $layoutFilename
    * @param boolean $first
    * @return BLayout
    */
    public function loadLayoutAfterTheme($layoutFilename, $first = false)
    {
        if (!$this->BUtil->isPathAbsolute($layoutFilename)) {
            $mod = $this->BModuleRegistry->currentModule();
            if ($mod) {
                $layoutFilename = $mod->root_dir . '/' . $layoutFilename;
            }
        }
        if ($first) {
            array_unshift($this->_loadLayoutFiles, $layoutFilename);
        } else {
            $this->_loadLayoutFiles[] = $layoutFilename;
        }
        /*
        $layout = $this;
        $this->onAfterTheme(function() use($layout, $layoutFilename) {
            $layout->loadLayout($layoutFilename);
        });
        */
        return $this;
    }

    public function loadLayoutFilesFromAllModules()
    {
        $t = BDebug::debug(__METHOD__);
        $cacheKey = 'LAYOUTS-' . $this->BRequest->area(); //TODO: more flexible key
        $cacheConfig = $this->BConfig->get('core/cache/layout_files');
        $useCache = !$cacheConfig && $this->BDebug->is(['STAGING', 'PRODUCTION']) || $cacheConfig === 'enable';
        if ($useCache) {
            $data = $this->BCache->load($cacheKey);
        }
        if (!empty($data)) {
            $this->_layouts = $data;
        } else {
            foreach ($this->_loadLayoutFiles as $layoutFile) {
                $this->loadLayout($layoutFile);
            }
            $this->_loadLayoutFiles = [];
            if ($useCache) {
                $this->BCache->save($cacheKey, $this->_layouts);
            } else {
                $this->BCache->delete($cacheKey);
            }
        }
        BDebug::profile($t);
        return $this;
    }

    /**
     * @param      $layoutName
     * @param null $layout
     * @return $this
     */
    public function addLayout($layoutName, $layout = null)
    {
        if (is_array($layoutName)) {
            foreach ($layoutName as $l => $def) {
                $this->addLayout($l, $def);
            }

            return $this;
        }
        if (!is_array($layout)) {
            BDebug::debug('LAYOUT.ADD ' . $layoutName . ': Invalid or empty layout');
        } else {
            if (!isset($this->_layouts[$layoutName])) {
                BDebug::debug('LAYOUT.ADD ' . $layoutName);
                $this->_layouts[$layoutName] = $layout;
            } else {
                BDebug::debug('LAYOUT.UPDATE ' . $layoutName);
                $this->_layouts[$layoutName] = array_merge_recursive($this->_layouts[$layoutName], $layout);
            }
        }

        return $this;
    }

    /**
     * Get raw layout instructions
     *
     * @param string $layoutName
     * @return array
     */
    public function getLayout($layoutName)
    {
        return !empty($this->_layouts[$layoutName]) ? $this->_layouts[$layoutName] : [];
    }

    /**
     * @param $layoutName
     * @return $this
     */
    public function applyLayout($layoutName)
    {
        if (empty($this->_layouts[$layoutName])) {
            BDebug::debug('LAYOUT.EMPTY ' . $layoutName);

            return $this;
        }
        BDebug::debug('LAYOUT.APPLY ' . $layoutName);

        $this->BLayout->collectAllViewsFiles();

        // collect callbacks
        $callbacks = [];
        foreach ($this->_layouts[$layoutName] as $d) {
            $d['layout_name'] = $layoutName;
            if (!empty($d['if'])) {
                if (!$this->BUtil->call($d['if'], $d)) {
                    continue;
                }
            }
            if (empty($d['type'])) {
                if (!empty($d[0])) {
                    $d['type'] = $d[0];
                } else {
                    reset($d);
                    $d['type'] = key($d);
                    $d['name'] = current($d);
                    if (empty(static::$_metaDirectives[$d['type']])) {
                        BDebug::error('Unknown directive: ' . print_r($d, 1));
                        continue;
                    }
                }
                if (empty($d['type'])) {
                    BDebug::error('Unknown directive: ' . print_r($d, 1));
                    continue;
                }
            }
            $d['type'] = trim($d['type']);
            if (empty($d['type']) || empty(static::$_metaDirectives[$d['type']])) {
                BDebug::error('Unknown directive: ' . print_r($d, 1));
                continue;
            }
            if (empty($d['name']) && !empty($d[1])) {
                $d['name'] = $d[1];
            }
            $d['name'] = trim($d['name']);
            $d['layout_name'] = $layoutName;
            $callback = static::$_metaDirectives[$d['type']];

            if ($d['type'] === 'remove') {
                if ($d['name'] === 'ALL') { //TODO: allow removing specific instructions
                    BDebug::debug('LAYOUT.REMOVE ALL');
                    $callbacks = [];
                }
            } else {
                $callbacks[] = [$callback, $d];
            }
        }

        // perform all callbacks
        foreach ($callbacks as $cb) {
            $this->BUtil->call($cb[0], $cb[1]);
        }

        return $this;
    }

    /**
     * @param $d
     */
    public function metaDirectiveCallback($d)
    {
        $this->BUtil->call($d['name'], !empty($d['args']) ? $d['args'] : [], true);
    }

    /**
     * @param $d
     */
    public function metaDirectiveRemoveCallback($d)
    {
        //TODO: implement
    }

    /**
     * @param $d
     */
    public function metaDirectiveIncludeCallback($d)
    {
        if ($d['name'] == $d['layout_name']) { // simple 1 level recursion stop
            BDebug::error('Layout recursion detected: ' . $d['name']);

            return;
        }
        static $layoutsApplied = [];
        if (!empty($layoutsApplied[$d['name']]) && empty($d['repeat'])) {
            return;
        }
        $layoutsApplied[$d['name']] = 1;
        $this->applyLayout($d['name']);
    }

    /**
     * @param array $d
     */
    public function metaDirectiveRootCallback($d)
    {
        $this->setRootView($d['name']);
        BDebug::debug('SET ROOT VIEW: ' . $d['name']);
    }

    /**
     * @param array $d
     */
    public function metaDirectiveHookCallback($d)
    {
        $args = !empty($d['args']) ? $d['args'] : [];
        if (!empty($d['position'])) {
            $args['position'] = $d['position'];
        }
        $params = !empty($d['params']) ? $d['params'] : [];

        if (!empty($d['callbacks'])) {
            foreach ($d['callbacks'] as $cb) {
                $this->hook($d['name'], $cb, $args, $params);
            }
        }
        if (!empty($d['clear'])) {
            $this->hookClear($d['name'], $d['clear']);
        }
        if (!empty($d['views'])) {
            foreach ((array)$d['views'] as $v) {
                if ($v[0] === '^') {
                    $this->hookViewsRegex($d['name'], '#' . $v . '#', $args, $params);
                } else {
                    $this->hookView($d['name'], $v, $args, $params);
                }
            }
            if (!empty($d['use_meta'])) {
                $this->view($v)->useMetaData();
            }
        }
        if (!empty($d['text'])) {
            foreach ((array)$d['text'] as $text) {
                $this->hook($d['name'], function() use ($text) { return $text; });
            }
        }
    }

    /**
     * @param $d
     */
    public function metaDirectiveViewCallback($d)
    {
        if (!empty($d['view_class']) || !empty($d['view_file']) || !empty($d['view_alias'])) {
            $this->addView($d['name'], [
                'view_class' => !empty($d['view_class']) ? $d['view_class'] : null,
                'view_file'  => !empty($d['view_file'])  ? $d['view_file']  : null,
                'view_alias' => !empty($d['view_alias']) ? $d['view_alias'] : null,
            ]);
        }

        $view = $this->getView($d['name']);
        if (!empty($d['set'])) {
            foreach ($d['set'] as $k => $v) {
                $view->set($k, $v);
            }
        }
        if (!empty($d['param'])) {
            foreach ($d['param'] as $k => $v) {
                $view->setParam($k, $v);
            }
        }
        if (!empty($d['do'])) {
            foreach ($d['do'] as $args) {
                $method = array_shift($args);
                BDebug::debug('LAYOUT.view.do ' . $method .
                    (!empty($args[0]) ? (' (' . print_r($args[0], 1) . (
                        !empty($args[1]) ? (', ' . json_encode($args[1])) : '') . ')') : ''
                    ));
                call_user_func_array([$view, $method], $args);
            }
        }
    }

    /**
     * @deprecated
     *
     * @param mixed $themeName
     * @return BLayout
     */
    public function defaultTheme($themeName = null)
    {
        if (null === $themeName) {
            return $this->_defaultTheme;
        }
        $this->_defaultTheme = $themeName;
        BDebug::debug('THEME.DEFAULT: ' . $themeName);

        return $this;
    }

    /**
     * @param $themeName
     * @return $this
     */
    public function setDefaultTheme($themeName)
    {
        $this->_defaultTheme = $themeName;
        BDebug::debug('THEME.DEFAULT: ' . $themeName);

        return $this;
    }

    /**
     * @return array|string
     */
    public function getDefaultTheme()
    {
        return $this->_defaultTheme;
    }

    /**
     * @param $themeName
     * @param $params
     * @param $curModName
     * @return $this
     */
    public function addTheme($themeName, $params, $curModName = null)
    {
        BDebug::debug('THEME.ADD ' . $themeName);

        if (!$curModName) {
            $curModName = $this->BModuleRegistry->currentModuleName();
        }
        foreach (['area', 'parent', 'layout_before', 'layout_after', 'views_before', 'views_after'] as $k) {
            if (empty($params[$k])) {
                continue;
            }
            $params[$k] = (array)$params[$k];
            if ($k === 'area' || $k === 'parent') {
                continue;
            }
            foreach ($params[$k] as $i => $v) {
                if ($v[0] !== '@') {
                    $params[$k][$i] = '@' . $curModName . '/' . $v;
                }
            }
        }
        if (empty($this->_themes[$themeName])) {
            $this->_themes[$themeName] = $params;
        } else {
            $this->_themes[$themeName] = $this->BUtil->arrayMerge($this->_themes[$themeName], $params);
        }

        return $this;
    }

    public function updateTheme($themeName, $params, $curModName = null)
    {
        BDebug::debug('THEME.UPDATE ' . $themeName);
        //TODO: figure out if needed a separate method for update
        return $this->addTheme($themeName, $params, $curModName);
    }

    /**
     * @param null $area
     * @param bool $asOptions
     * @return array
     */
    public function getThemes($area = null, $asOptions = false)
    {
        if (null === $area) {
            return $this->_themes;
        }
        $themes = [];
        foreach ($this->_themes as $name => $theme) {
            if (!empty($theme['area']) && in_array($area, $theme['area'])) {
                if ($asOptions) {
                    $themes[$name] = !empty($theme['description']) ? $theme['description'] : $name;
                } else {
                    $themes[$name] = $theme;
                }
            }
        }
        return $themes;
    }

    public function loadTheme($themeName)
    {
        if (empty($this->_themes[$themeName])) {
            BDebug::warning('Invalid theme name: ' . $themeName);
            return false;
        }

        $theme = $this->_themes[$themeName];

        $area = $this->BRequest->area();
        if (!empty($theme['area']) && !in_array($area, (array)$theme['area'])) {
            BDebug::debug('Theme ' . $themeName . ' can not be used in ' . $area);
            return false;
        }

        if (!empty($theme['parent'])) {
            foreach ((array)$theme['parent'] as $parentThemeName) {
                if ($this->loadTheme($parentThemeName)) {
                    break; // load the first available parent theme
                }
            }
        }

        $this->BEvents->fire('BLayout::loadTheme:before', ['theme_name' => $themeName, 'theme' => $theme]);

        $modReg = $this->BModuleRegistry;

        if (!empty($theme['views_before'])) {
            foreach ($theme['views_before'] as $viewsBefore) {
                $a = explode('/', $viewsBefore, 2);
                $viewsMod = $modReg->module(substr($a[0], 1));
                $viewsDir = $viewsMod->root_dir . '/' . $a[1];
                $this->addAllViews($viewsDir, '', $viewsMod);
            }
        }
        if (!empty($theme['layout_before'])) {
            foreach ($theme['layout_before'] as $layoutBefore) {
                $this->loadLayout($modReg->expandPath($layoutBefore));
            }
        }
        if (!empty($theme['callback'])) {
            $this->BUtil->call($theme['callback']);
        }

        $this->BEvents->fire('BLayout::loadTheme:after', ['theme_name' => $themeName, 'theme' => $theme]);

        return true;
    }

    /**
     * @param null $themeName
     * @return $this
     */
    public function applyTheme($themeName = null)
    {
        if (null === $themeName) {
            if (!$this->_defaultTheme) {
                BDebug::error('Empty theme supplied and no default theme is set');
            }
            $themeName = $this->_defaultTheme;
        }
        if (is_array($themeName)) {
            foreach ($themeName as $n) {
                $this->applyTheme($n);
            }
            return $this;
        }
        if (empty($this->_themes[$themeName])) {
            BDebug::error('Invalid theme supplied: ' . $themeName);
            return $this;
        }
        BDebug::debug('THEME.APPLY ' . $themeName);
        $this->BEvents->fire('BLayout::applyTheme:before', ['theme_name' => $themeName]);

        $this->loadTheme($themeName);
        $this->loadLayoutFilesFromAllModules();

        $theme = $this->_themes[$themeName];
        $modReg = $this->BModuleRegistry;

        if (!empty($theme['views_after'])) {
             foreach ($theme['views_after'] as $viewsAfter) {
                $a = explode('/', $viewsAfter, 2);
                $viewsMod = $modReg->module(substr($a[0], 1));
                $viewsDir = $viewsMod->root_dir . '/' . $a[1];
                $this->addAllViews($viewsDir, '', $viewsMod);
            }
        }
        if (!empty($theme['layout_after'])) {
            foreach ($theme['layout_after'] as $layoutAfter) {
                $this->loadLayout($modReg->expandPath($layoutAfter));
            }
        }

        $this->BEvents->fire('BLayout::applyTheme:after', ['theme_name' => $themeName]);

        return $this;
    }

    /**
     * @param $themeName
     * @return $this
     */
    public function loadThemeViews($themeName)
    {
        if (empty($this->_themes[$themeName])) {
            BDebug::warning('Invalid theme name: ' . $themeName);
            return false;
        }

        $theme = $this->_themes[$themeName];
        $area = $this->BRequest->area();
        if (!empty($theme['area']) && !in_array($area, (array)$theme['area'])) {
            BDebug::debug('Theme ' . $themeName . ' can not be used in ' . $area);
            return false;
        }

        if (!empty($theme['parent'])) {
            foreach ((array)$theme['parent'] as $parentThemeName) {
                if ($this->loadThemeViews($parentThemeName)) {
                    break; // load the first available parent theme
                }
            }
        }
        $modReg = $this->BModuleRegistry;
        if (!empty($theme['views_before'])) {
            foreach ($theme['views_before'] as $viewsBefore) {
                $a = explode('/', $viewsBefore, 2);
                $viewsMod = $modReg->module(substr($a[0], 1));
                $viewsDir = $viewsMod->root_dir . '/' . $a[1];
                $this->addAllViews($viewsDir, '', $viewsMod);
            }
        }
        if (!empty($theme['views_after'])) {
             foreach ($theme['views_after'] as $viewsAfter) {
                $a = explode('/', $viewsAfter, 2);
                $viewsMod = $modReg->module(substr($a[0], 1));
                $viewsDir = $viewsMod->root_dir . '/' . $a[1];
                $this->addAllViews($viewsDir, '', $viewsMod);
            }
        }
        return $this;
    }

    /**
     * Shortcut for event registration
     * @param $callback
     * @return $this
     */
    public function onAfterTheme($callback)
    {
        $this->BEvents->on('BLayout::applyTheme:after', $callback);

        return $this;
    }

    /**
     * Dispatch layout event, for both general observers and route specific observers
     *
     * Observers should watch for these events:
     * - BLayout::{event}
     * - BLayout::{event}: GET {route}
     *
     * @param mixed $eventName
     * @param mixed $routeName
     * @param mixed $args
     * @return array
     */
    public function dispatch($eventName, $routeName = null, $args = [])
    {
        if (null === $routeName && ($route = $this->BRouting->currentRoute())) {
            $args['route_name'] = $routeName = $route->route_name;
        }
        $result = $this->BEvents->fire("BLayout::{$eventName}", $args);

        $routes = is_string($routeName) ? explode(',', $routeName) : (array)$routeName;
        foreach ($routes as $route) {
            $args['route_name'] = $route;
            $r2                 = $this->BEvents->fire("BLayout::{$eventName}: {$route}", $args);
            $result             = $this->BUtil->arrayMerge($result, $r2);
        }

        return $result;
    }

    /**
     * Render layout starting with main (root) view
     *
     * @param string $routeName Optional: render a specific route, default current route
     * @param array  $args Render arguments
     * @return mixed
     */
    public function render($routeName = null, $args = [])
    {
        $this->dispatch('render:before', $routeName, $args);

        $rootView = $this->getRootView();
        BDebug::debug('LAYOUT.RENDER ' . var_export($rootView, 1));
        if (!$rootView) {
            BDebug::error($this->BLocale->_('Main view not found: %s', $this->_rootViewName));
        }
        $result = $rootView->render($args);

        $args['output'] =& $result;
        $this->dispatch('render:after', $routeName, $args);

        //$this->BSession->dirty(false); // disallow session change during layout render

        return $result;
    }

    /**
     * @param $view
     * @return $this
     */
    public function viewStackOn($view)
    {
        array_push($this->_currentViewStack, $view);
        return $this;
    }

    /**
     * @param $view
     * @return $this
     */
    public function viewStackOff($view)
    {
        $lastView = array_pop($this->_currentViewStack);
        $lastViewName = $lastView->getParam('view_name');
        $viewName = $view->getParam('view_name');
        if ($lastViewName !== $viewName) {
            BDebug::debug('Wrong view stack off: ' . $viewName . ', expected: ' . $lastViewName);
        }
        return $this;
    }

    /**
     * @param bool $formatted
     * @return array|string
     */
    public function getViewStack($formatted = false)
    {
        if (!$formatted) {
            return $this->_currentViewStack;
        }
        $output = array();
        foreach ($this->_currentViewStack as $i => $view) {
            $output[] = "#{$i}: @{$view->getParam('module_name')}/{$view->getParam('view_name')}";
        }
        return join("\n", $output);
    }

    /**
     * @return void
     */
    public function debugPrintViews()
    {
        foreach ($this->getAllViews() as $viewName => $view) {
            echo $viewName . ':<pre>';
            print_r($view);
            echo '</pre><hr>';
        }
    }

    /**
     *
     */
    public function debugPrintLayouts()
    {
        echo "<pre>";
        print_r($this->_layouts);
        echo "</pre>";
    }

    /**
     * Add all view dirs and layouts declared in module manifest
     *
     * @param BModule $module
     * @param string $area
     * @return $this
     */
    public function addModuleViewsDirsAndLayouts($module, $area)
    {
        $auto = array_flip((array)$module->auto_use);
        $areaDir = str_replace('FCom_', '', $area);
        $moduleRootDir = $module->root_dir;
        if (isset($auto['all']) || isset($auto['views'])) {
            if (is_dir($moduleRootDir . '/views')) {
                $this->addAllViewsDir($moduleRootDir . '/views');
            }
            if (is_dir($moduleRootDir . '/' . $areaDir . '/views')) {
                $this->addAllViewsDir($moduleRootDir . '/' . $areaDir . '/views');
            }
        }
        if (isset($auto['all']) || isset($auto['layout'])) {
            if (file_exists($moduleRootDir . '/layout.yml')) {
                $this->loadLayoutAfterTheme($moduleRootDir . '/layout.yml');
            }
            if (file_exists($moduleRootDir . '/' . $areaDir . '/layout.yml')) {
                $this->loadLayoutAfterTheme($moduleRootDir . '/' . $areaDir . '/layout.yml');
            }
        }
        return $this;
    }

    public function addAllModuleLayouts($module, $area)
    {

        $areaDir = str_replace('FCom_', '', $area);
    }
}

/**
 * First parent view class
 * @method static BView i()
 */
class BView extends BClass
{
    /**
     * @var
     */
    protected static $_renderer;

    /**
     * @var string
     */
    protected static $_metaDataRegex = '#<!--\s*\{\s*([^:]+):\s*(.*?)\s*\}\s*-->#';

    /**
     * View parameters
     * - view_class
     * - template
     * - module_name
     * - args
     *
     * @var array
     */
    protected $_params;

    /**
     * Factory to generate view instances
     *
     * @param string $viewName
     * @param array  $params
     * @return BView
     */
    public function factory($viewName, array $params = [])
    {
        $params['view_name'] = $viewName;
        $className = !empty($params['view_class']) ? $params['view_class'] : get_called_class();
        $view = BClassRegistry::instance($className, [$params]);

        return $view;
    }

    /**
     * Constructor, set initial view parameters
     *
     * @param array $params
     * @return BView
     */
    public function __construct(array $params = [])
    {
        $this->_params = $params;
    }

    /**
     * Retrieve view parameters
     *
     * @param string $key
     * @return mixed|BView
     */
    public function param($key = null)
    {
        if (null === $key) {
            return $this->_params;
        }

        return isset($this->_params[$key]) ? $this->_params[$key] : null;
    }

    /**
     * @param      $key
     * @param null $value
     * @return $this
     */
    public function setParam($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setParam($k, $v);
            }

            return $this;
        }
        $this->_params[$key] = $value;

        return $this;
    }

    /**
     * @param $key
     * @return null
     */
    public function getParam($key)
    {
        return isset($this->_params[$key]) ? $this->_params[$key] : null;
    }

    /**
     * @param      $name
     * @param null $value
     * @return $this
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->_params['args'][$k] = $v;
            }

            return $this;
        }
        $this->_params['args'][$name] = $value;

        return $this;
    }

    /**
     * @param $name
     * @return null
     */
    public function get($name)
    {
        return isset($this->_params['args'][$name]) ? $this->_params['args'][$name] : null;
    }

    /**
     * @return array
     */
    public function getAllArgs()
    {
        return !empty($this->_params['args']) ? $this->_params['args'] : [];
    }

    /**
     * Magic method to retrieve argument, accessible from view/template as $this->var
     *
     * @param string $name
     * @return mixed
     */
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
        return $this->get($name);
    }

    /**
     * Magic method to set argument, stored in params['args']
     *
     * @param string $name
     * @param mixed  $value
     * @return $this
     */
    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    /**
     * Magic method to check if argument is set
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->_params['args'][$name]);
    }

    /**
     * Magic method to unset argument
     *
     * @param string $name
     */
    public function __unset($name)
    {
        unset($this->_params['args'][$name]);
    }

    /**
     * Retrieve view object
     *
     * @todo detect multi-level circular references
     * @param string $viewName
     * @param array  $params
     * @throws BException
     * @return BView|null
     */
    public function view($viewName, $params = null)
    {
        if ($viewName === $this->param('view_name')) {
            throw new BException($this->BLocale->_('Circular reference detected: %s', $viewName));
        }

        $view = $this->BLayout->getView($viewName);

        if ($view && $params) {
            $view->set($params);
        }

        return $view;
    }

    /**
     * Collect output from subscribers of a layout event
     *
     * @param string $hookName
     * @param array  $args
     * @return string
     */
    public function hook($hookName, $args = [])
    {
        $args['_viewname'] = $this->param('view_name');
        $result = '';

        $debug = $this->BDebug->is('DEBUG');
        if ($debug) {
            $result .= "<!-- START HOOK: {$hookName} -->\n";
        }

        $result .= join('', $this->BEvents->fire('BView::hook:before', ['view' => $this, 'name' => $hookName]));

        $result .= join('', $this->BEvents->fire('BLayout::hook:' . $hookName, $args));

        $result .= join('', $this->BEvents->fire('BView::hook:after', ['view' => $this, 'name' => $hookName]));

        if ($debug) {
            $result .= "<!-- END HOOK: {$hookName} -->\n";
        }

        return $result;
    }

    /**
     * @param null $fileExt
     * @param bool $quiet
     * @return BView|mixed|string
     */
    public function getTemplateFileName($fileExt = null, $quiet = false)
    {
        if (null === $fileExt) {
            $fileExt = $this->getParam('file_ext');
        }
        $template = $this->param('template');
        if (!$template && ($viewName = $this->param('view_name'))) {
            $template = $viewName . $fileExt;
        }
        if ($template) {
            if (!$this->BUtil->isPathAbsolute($template)) {
                $template = $this->BLayout->getViewRootDir() . '/' . $template;
            }
            if (!is_readable($template) && !$quiet) {
                BDebug::notice('TEMPLATE NOT FOUND: ' . $template);
            } else {
                BDebug::debug('TEMPLATE ' . $template);
            }
        }

        return $template;
    }

    /**
     * Used by external renderers to include compiled PHP file within $this context
     *
     * @param mixed $file
     * @return string
     */
    public function renderFile($file)
    {
        ob_start();
        include $file;
        return ob_get_clean();
    }
    /*
    public function renderEval($source)
    {
        ob_start();
        eval($source);
        return ob_get_clean();
    }
    */
    /**
     * View class specific rendering
     *
     * @return string
     */
    protected function _render()
    {
        $renderer = $this->getParam('renderer');
        if ($renderer) {
            BDebug::debug('VIEW.RENDER "' . $this->param('view_name') . '" USING ' . print_r($renderer, 1));
            return $this->BUtil->call($renderer, $this);
        }

        if ($this->getParam('source')) {
            BDebug::debug('VIEW.RENDER "' . $this->param('view_name') . '" RAW SOURCE');
            return $this->getParam('source');
        }

        BDebug::debug('VIEW.RENDER "' . $this->param('view_name') . '" USING PHP');
        ob_start();
        include $this->getTemplateFileName();
        return ob_get_clean();
    }

    /**
     * General render public method
     *
     * @param array $args
     * @param bool  $retrieveMetaData
     * @return string
     */
    public function render(array $args = [], $retrieveMetaData = false)
    {
        $debug = $this->BDebug->is('DEBUG') && !$this->get('no_debug');
        $viewName = $this->param('view_name');
        $modName = $this->param('module_name');

        $timer = BDebug::debug('RENDER.VIEW @' . $modName . '/' . $viewName);
        if ($this->param('raw_text') !== null) {
            return $this->param('raw_text');
        }
        foreach ($args as $k => $v) {
            $this->_params['args'][$k] = $v;
        }
        if ($modName) {
            //$this->BModuleRegistry->pushModule($modName);
        }
        $result = '';
        if (!$this->_beforeRender()) {
            BDebug::debug('BEFORE.RENDER failed');
            if ($debug) {
                $result .= "<!-- FAILED VIEW: {$viewName} -->\n";
            }
            if ($modName) {
                //$this->BModuleRegistry->popModule();
            }
            return $result;
        }

        $showDebugTags = $debug && $modName && $viewName && $this->BLayout->getRootViewName() !== $viewName;

        if ($showDebugTags) {
            $result .= "<!-- START VIEW: @{$modName}/{$viewName} -->\n";
        }

        // TODO: link views with layouts
        $this->BLayout->viewStackOn($this);

        $result .= join('', $this->BEvents->fire('BView::render:before', ['view' => $this]));

        $viewContent = $this->_render();

        if ($retrieveMetaData) {
            // collect meta data and remove meta tags from source
            $viewContent = $this->collectMetaData($viewContent);
        }

        $result .= $viewContent;
        $result .= join('', $this->BEvents->fire('BView::render:after', ['view' => $this]));

        $this->BLayout->viewStackOff($this);

        if ($showDebugTags) {
            $result .= "<!-- END VIEW: @{$modName}/{$viewName} -->\n";
        }
        BDebug::profile($timer);

        $this->_afterRender();
        if ($modName) {
            //$this->BModuleRegistry->popModule();
        }

        return $result;
    }

    /**
     * @param null $viewContent
     * @return mixed|null|string
     */
    public function collectMetaData($viewContent = null)
    {
        $t = BDebug::debug('COLLECT META DATA: ' . $this->getParam('view_name'));

        if (null === $viewContent) {
            $prerendered = false;
            $viewContent = $this->getParam('source');
            if (!$viewContent) {
                // get template file name for the view
                $viewFile = $this->getTemplateFileName();
                if (!$viewFile) {
                    BDebug::profile($t);
                    return $viewContent;
                }
                $viewContent = file_get_contents($viewFile);
            }
        } else {
            $prerendered = true;
        }

        // collect template source for meta tags for further interpolation processing
        //TODO: revisit isolating meta tags in case of dynamic generation
        if (!preg_match_all(static::$_metaDataRegex, $viewContent, $matches, PREG_PATTERN_ORDER)) {
            BDebug::profile($t);
            return $viewContent;
        }
        $metaContent = join("\n", $matches[0]);
        if ($prerendered) {
            $metaOutput = $metaContent;
        } else {
            // create a view with only meta tags
            $metaView = $this->BView->factory($this->getParam('view_name') . '__meta', [
                'renderer' => $this->getParam('renderer'),
                'source' => $metaContent,
            ]);
            // render the meta view for variables interpolation
            $metaOutput = $metaView->_render();
        }
        // collect meta data
        $metaData = [];
        if (preg_match_all(static::$_metaDataRegex, $metaOutput, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $metaData[$m[1]] = $m[2];
                $viewContent     = str_replace($m[0], '', $viewContent);
            }
        }
        $this->setParam('meta_data', $metaData);
        BDebug::profile($t);
        return $viewContent;
    }

    /**
     * Use meta data declared in the view template to set head meta tags
     */
    public function useMetaData()
    {
        if (!$this->getParam('meta_data')) {
            $this->collectMetaData();
        }

        $metaData = $this->getParam('meta_data');
        if ($metaData) {
            if (!empty($metaData['layout.include'])) {
                $this->BLayout->applyLayout($metaData['layout.include']);
            }
            if (!empty($metaData['layout.yml'])) {
                $layoutData = $this->BYAML->parse(trim($metaData['layout.yml']));
                $this->BLayout->addLayout('viewproxy-metadata', $layoutData)->applyLayout('viewproxy-metadata');
            }
            if (($head = $this->view('head'))) {
                foreach ($metaData as $k => $v) {
                    $k = strtolower($k);
                    switch ($k) {
                    case 'title':
                        $head->addTitle($v); break;
                    case 'meta_title': case 'meta_description': case 'meta_keywords':
                        $head->meta(str_replace('meta_', '', $k), $v); break;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return bool
     */
    protected function _beforeRender()
    {
        return true;
    }

    /**
     *
     */
    protected function _afterRender()
    {

    }

    /**
     * Clear parameters to avoid circular reference memory leaks
     *
     */
    public function clear()
    {
        $this->_params = null;
    }

    /**
     * Clear params on destruct
     *
     */
    public function __destruct()
    {
        $this->clear();
    }

    /**
     * Render as string
     *
     * If there's exception during render, output as string as well
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $result = $this->render();
        } catch (PDOException $e) {
            $result = '<hr>' . get_class($e) . ': ' . $e->getMessage() . '<hr>' . ORM::get_last_query() . '<hr>';
        } catch (Exception $e) {
            $result = '<hr>' . get_class($e) . ': ' . $e->getMessage() . '<hr>';
        }

        return $result;
    }

    /**
     * Escape HTML
     *
     * @param string $str
     * @param array  $args
     * @return string
     */
    public function q($str, $args = [])
    {
        if (null === $str) {
            return '';
        }
        if (!is_scalar($str)) {
            var_dump($str);

            return ' ** ERROR ** ';
        }

        return htmlspecialchars($args ? $this->BUtil->sprintfn($str, $args) : $str);
    }

    /**
     * @param      $str
     * @param null $tags
     * @return string
     */
    public function s($str, $tags = null)
    {
        return strip_tags($str, $tags);
    }

    /**
     * @deprecated by $this->BUtil->optionsHtml()
     * @param        $options
     * @param string $default
     * @return string
     */
    public function optionsHtml($options, $default = '')
    {
        return $this->BUtil->optionsHtml($options, $default);
    }


    /**
     * Send email using the content of the view as body using standard PHP mail()
     *
     * Templates can include the following syntax for default headers:
     * - <!--{ From: Support <support@example.com> }-->
     * - <!--{ Subject: New order notification #<?php echo $this->order_id?> }-->
     *
     * $p accepts following parameters:
     * - to: email OR "name" <email>
     * - from: email OR "name" <email>
     * - subject: email subject
     * - cc: email OR "name" <email> OR array of these
     * - bcc: same as cc
     * - reply-to
     * - return-path
     *
     * All parameters are also available in the template as $this->{param}
     *
     * @param array|string $p if string, used as "To:" header
     * @return bool true if successful
     */
    public function email($p = [])
    {
        if (is_string($p)) {
            $p = ['to' => $p];
        }

        $body = $this->render($p, true);

        $metaData = $this->param('meta_data') ? array_change_key_case($this->param('meta_data'), CASE_LOWER) : [];
        $data = array_merge($metaData, array_change_key_case($p, CASE_LOWER));
        $data['body'] = $body;
        $data['view_name'] = $this->param('view_name');
        $data['template'] = $this->param('template');

        return $this->BEmail->send($data);
    }

    /**
     * Translate string within view class method or template
     *
     * @param string $string
     * @param array  $params
     * @param string $module if null, try to get current view module
     * @return \false|string
     */
    public function _($string, $params = [], $module = null)
    {
        if (empty($module) && !empty($this->_params['module_name'])) {
            $module = $this->_params['module_name'];
        }

        return $this->BLocale->_($string, $params, $module);
    }

    protected $_validators = [];

    public function validator($formName, $data = null)
    {
        if (empty($this->_validators[$formName])) {
            $this->_validators[$formName] = BValidateViewHelper::i(true, [[
                'form' => $formName,
                'data' => $data,
            ]]);
        }
        return $this->_validators[$formName];
    }

    /**
     * @return string
     */
    public function twigName()
    {
        return "@{$this->_params['module_name']}/{$this->_params['view_name']}{$this->_params['file_ext']}";
    }
}

/**
 * Helper view to avoid errors of using views from disabled modules
 */
class BViewEmpty extends BView
{
    public function render(array $args = [], $retrieveMetaData = true)
    {
        return '';
    }
}

/**
 * View dedicated for rendering HTML HEAD tags
 *
 * default tag templates method
 * @method js()
 * @method js_raw()
 * @method css()
 * @method css_raw()
 * @method less()
 * @method icon()
 */
class BViewHead extends BView
{
    /**
     * @var array
     */
    protected $_title = [];

    /**
     * @var string
     */
    protected $_titleSeparator = ' :: ';

    /**
     * @var bool
     */
    protected $_titleReverse = true;

    /**
     * Substitution variables
     *
     * @var array
     */
    protected $_subst = [];

    /**
     * Meta tags
     *
     * @var array
     */
    protected $_meta = [];

    /**
     * External resources (JS and CSS)
     *
     * @var array
     */
    protected $_elements = [];

    /**
     * Support for head.js
     *
     * @see http://headjs.com/
     * @var array
     */
    protected $_headJs = ['enabled' => false, 'loaded' => false, 'jquery' => null, 'scripts' => []];

    /**
     * Support for require.js
     *
     * @see http://requirejs.org/
     * @var array
     */
    protected $_requireJs = ['config' => [], 'run' => []];

    /**
     * Default tag templates for JS and CSS resources
     *
     * @var array
     */
    protected $_defaultTag = [
        'js'      => '<script type="text/javascript" src="%s" %a></script>',
        'js_raw'  => '<script type="text/javascript" %a>%c</script>',
        'css'     => '<link rel="stylesheet" type="text/css" href="%s" %a/>',
        'css_raw' => '<style type="text/css" %a>%c</style>',
        //'less' => '<link rel="stylesheet" type="text/less" href="%s" %a/>',
        'less'    => '<link rel="stylesheet/less" type="text/css" href="%s" %a/>',
        'icon'    => '<link rel="icon" href="%s" type="image/x-icon" %a/><link rel="shortcut icon" href="%s" type="image/x-icon" %a/>',
    ];

    /**
     * Current IE <!--[if]--> context
     *
     * @var string
     */
    protected $_currentIfContext = null;

    /**
     * @param      $from
     * @param null $to
     * @return $this|string
     */
    public function subst($from, $to = null)
    {
        if (null === $to) {
            return str_replace(array_keys($this->_subst), array_values($this->_subst), $from);
        }
        $this->_subst['{' . $from . '}'] = $to;

        return $this;
    }

    /**
     * Enable/disable head js
     *
     * @param bool $enable
     * @return $this
     */
    public function headJs($enable = true)
    {
        $this->_headJs['enabled'] = $enable;

        return $this;
    }

    /**
     * Alias for addTitle($title)
     *
     * @deprecated
     * @param mixed $title
     * @param bool  $start
     * @return BViewHead
     */
    public function title($title, $start = false, $length = 1)
    {
        $this->addTitle($title, $start, $length);
    }

    /**
     * Add meta tag, or return meta tag(s)
     *
     * @deprecated
     *
     * @param string $name If not specified, will return all meta tags as string
     * @param string $content If not specified, will return meta tag by name
     * @param bool   $httpEquiv Whether the tag is http-equiv
     * @return BViewHead
     */
    public function meta($name = null, $content = null, $httpEquiv = false)
    {
        if (null === $content) {
            return $this->getMeta($name);
        }
        $this->addMeta($name, $content, $httpEquiv);

        return $this;
    }

    /**
     * @return $this
     */
    public function csrf_token()
    {
        $this->addMeta('csrf-token', $this->BSession->csrfToken());
        return $this;
    }

    public function link($rel, $href)
    {
        $this->addElement('link', $rel, ['tag' => '<link rel="'. $rel . '" href="' . $href . '"/>']);
        return $this;
    }

    /**
     * Add canonical link
     * @deprecated by link()
     * @param $href
     * @return $this
     */
    public function canonical($href)
    {
        return $this->link('canonical', $href);
    }

    /**
     * Add rss link
     *
     * @param $href
     */
    public function rss($href)
    {
        $this->addElement('link', 'rss', ['tag' => '<link rel="alternate" type="application/rss+xml" title="RSS" href="' . $href . '">']);
    }

    /**
     * Enable direct call of different item types as methods (js, css, icon, less)
     *
     * @param string $name
     * @param array  $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        if (!empty($this->_defaultTag[$name])) {
            array_unshift($args, $name);
            return call_user_func_array([$this, 'addElement'], $args);
        } else {
            BDebug::error('Invalid method: ' . $name);
        }
    }

    /**
     * @return $this
     */
    public function removeAll()
    {
        $this->_elements = [];
        $this->_headJs = [];
        return $this;
    }

    /**
     * Remove JS/CSS elements by type and pattern (strpos)
     *
     * @param string $type
     * @param string $pattern
     * @return BViewHead
     */
    public function remove($type, $pattern)
    {
        if ($type === 'js' && $this->_headJs['loaded']) {
            foreach ($this->_headJs['scripts'] as $i => $file) {
                if (true === $pattern || strpos($file, $pattern) !== false) {
                    unset($this->_headJs['scripts'][$i]);
                }
            }
        }
        foreach ($this->_elements as $k => $args) {
            if (strpos($k, $type) === 0 && (true === $pattern || strpos($k, $pattern) !== false)) {
                unset($this->_elements[$k]);
            }
        }

        return $this;
    }

    /**
     * Set title
     * This will replace any current title
     *
     * @param $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->_title = [$title];
        return $this;
    }

    /**
     * Add title
     * Add title to be appended to or replace current titles
     *
     * @param      $title
     * @param bool $start
     * @return $this
     */
    public function addTitle($title, $start = false, $length = 1)
    {
        if ($start !== false) {
            array_splice($this->_title, $start, $length, (array)$title);
        } else {
            $this->_title[] = $title;
        }

        return $this;
    }

    /**
     * Set title separator
     * Set character or string to be used to separate title values.
     *
     * @param $sep
     * @return $this
     */
    public function setTitleSeparator($sep)
    {
        $this->_titleSeparator = $sep;
        return $this;
    }

    /**
     * Should title be composed in reverse order
     *
     * @param $reverse
     * @return $this
     */
    public function setTitleReverse($reverse)
    {
        $this->_titleReverse = $reverse;
        return $this;
    }

    /**
     * Compose and return title
     * Title is composed by all elements in $_title object field separated by _titleSeparator
     *
     * @return string
     */
    public function getTitle()
    {
        if (!$this->_title) {
            return '';
        }
        if ($this->_titleReverse) {
            $this->_title = array_reverse($this->_title);
        }

        return '<title>' . $this->q(join($this->_titleSeparator, $this->_title)) . '</title>';
    }

    public function removeTitle($pattern)
    {
if ($this->BDebug->is('DEBUG')) {
    #var_dump($this->_title); exit;
}
        $this->_title = array_filter($this->_title, function ($val) use ($pattern) {
            return !preg_match('#' . $pattern . '#', $val);
        });
        return $this;
    }

    /**
     * Get meta tags
     * If name is null, returns all meta tags joined
     * else returns named meta tag or null if name is not in _meta array
     *
     * @param null $name
     * @return null|string
     */
    public function getMeta($name = null)
    {
        if (null === $name) {
            return join("\n", $this->_meta);
        }

        return !empty($this->_meta[$name]) ? $this->_meta[$name] : null;
    }

    /**
     * Add meta tag
     *
     * @param      $name
     * @param      $content
     * @param bool $httpEquiv
     * @return $this
     */
    public function addMeta($name, $content, $httpEquiv = false)
    {
        if ($httpEquiv) {
            $this->_meta[$name] = '<meta http-equiv="' . $name . '" content="' . htmlspecialchars($content) . '" />';
        } else {
            $this->_meta[$name] = '<meta name="' . $name . '" content="' . htmlspecialchars($content) . '" />';
        }

        return $this;
    }

    public function addDefaultTag($type, $params)
    {
        $this->_defaultTag[$type] = $params;
        return $this;
    }

    /**
     * Add element
     * @param       $type
     * @param       $name
     * @param array $args
     * @return $this
     */
    public function addElement($type, $name, $args = [])
    {
//echo "<pre>"; debug_print_backtrace(); echo "</pre>";
//var_dump($type, $name, $args);
        if (is_string($args)) {
            $args = ['content' => $args];
        }
        if (!empty($args['alias'])) {
            $args['file'] = trim($name);
            $name         = trim($args['alias']);
        }
        if (!isset($args['module_name']) && ($moduleName = $this->BModuleRegistry->currentModuleName())) {
            $args['module_name'] = $moduleName;
        }
        if (!isset($args['if']) && $this->_currentIfContext) {
            $args['if'] = $this->_currentIfContext;
        }
        $args['type'] = $type;
        if (empty($args['position'])) {
            $this->_elements[$type . ':' . $name] = (array)$args;
        } else {
            $this->_elements = $this->BUtil->arrayInsert(
                $this->_elements,
                [$type . ':' . $name => (array)$args],
                $args['position']
            );
#echo "<pre>"; print_r($this->_elements); echo "</pre>";
        }

        if (!empty($this->_headJs['enabled'])) {
            $basename = basename($name);
            if ($basename === 'head.js' || $basename === 'head.min.js' || $basename === 'head.load.min.js') {
                $this->_headJs['loaded'] = $name;
            }
        }

#BDebug::debug('EXT.RESOURCE '.$name.': '.print_r($this->_elements[$type.':'.$name], 1));
        return $this;
    }

    public function src($file, $ts = false)
    {
        if (is_array($file)) {
            $files = [];
            foreach ($file as $k => $f) {
                $files[$k] = $this->src($f, $ts);
            }
            return $files;
        }
        if ($file[0] === '@') { // @Mod_Name/file.ext
            preg_match('#^@([^/]+)(.*)$#', $file, $m);
            $mod = $this->BModuleRegistry->module($m[1]);
            if (!$mod) {
                BDebug::notice('Module not found: ' . $file);
                return '';
            }
            $fsFile = $this->BModuleRegistry->module($m[1])->root_dir . $m[2];
            $file   = $this->BModuleRegistry->module($m[1])->baseSrc() . $m[2];
            if ($ts && file_exists($fsFile)) {
                $file .= '?' . substr(md5(filemtime($fsFile)), 0, 10);
            }
        } elseif (preg_match('#\{([A-Za-z0-9_]+)\}#', $file, $m)) { // {Mod_Name}/file.ext (deprecated)
            $mod = $this->BModuleRegistry->module($m[1]);
            if (!$mod) {
                BDebug::notice('Module not found: ' . $file);
                return '';
            }
            $fsFile = str_replace('{' . $m[1] . '}', $this->BModuleRegistry->module($m[1])->root_dir, $file);
            $file   = str_replace('{' . $m[1] . '}', $this->BModuleRegistry->module($m[1])->baseSrc(), $file);
            if ($ts && file_exists($fsFile)) {
                $file .= '?' . substr(md5(filemtime($fsFile)), 0, 10);
            }
        } elseif (!preg_match('#^(http|/)#', $file)) {
            //echo ' ***** '; var_dump($file);
        }
        return $file;
    }

    /**
     * @param $type
     * @param $name
     * @return mixed|null|string
     */
    public function getElement($type, $name)
    {
        $typeName = $type . ':' . $name;
        if (!isset($this->_elements[$typeName])) {
            return null;
        }
        $args = $this->_elements[$typeName];

        $file = !empty($args['file']) ? $args['file'] : $name;
        $webFile = $this->src($file, true);
        if (strpos($webFile, 'http:') === false && strpos($webFile, 'https:') === false && $webFile[0] !== '/') {
            $module  = !empty($args['module_name']) ? $this->BModuleRegistry->module($args['module_name']) : null;
            $baseUrl = $module ? $module->baseSrc() : $this->BApp->baseUrl();
            $webFile = rtrim($baseUrl, '/') . '/' . $webFile;
        }

        if ($type === 'js' && !empty($this->_headJs['loaded']) && $this->_headJs['loaded'] !== $name
            && empty($args['separate']) && empty($args['tag']) && empty($args['params']) && empty($args['if'])
        ) {
            if (empty($this->_headJs['jquery']) && strpos($name, 'jquery') !== false) {
                $this->_headJs['jquery'] = $webFile;
            } else {
                $this->_headJs['scripts'][] = $webFile;
            }

            return '';
        }

        if (!empty($this->_defaultTag[$type])) {
            if (is_string($this->_defaultTag[$type])) {
                $defaultTag = $this->_defaultTag[$type];
            } elseif (is_callable($this->_defaultTag[$type])) {
                $args['file'] = $file;
                $defaultTag   = $this->BUtil->call($this->_defaultTag[$type], $args);
            } else {
                throw new BException('Invalid tag declaration: ' . $type);
            }
        }

        $tag = !empty($args['tag']) ? $args['tag'] : $defaultTag;
        $tag = str_replace('%s', htmlspecialchars($webFile), $tag);
        $tag = str_replace('%c', !empty($args['content']) ? $args['content'] : '', $tag);
        $tag = str_replace('%a', !empty($args['params']) ? $args['params'] : '', $tag);
        if (!empty($args['if'])) {
            $tag = '<!--[if ' . $args['if'] . ']>' . $tag . '<![endif]-->';
        }

        return $tag;
    }

    /**
     * @return mixed
     */
    public function getAllElements()
    {
        $result = [];
        $res1   = [];
        foreach ($this->_elements as $typeName => $els) {
            list($type, $name) = explode(':', $typeName, 2);
            $result[] = $this->getElement($type, $name);

            //$res1[$type == 'css' ? 0 : 1][] = $this->getElement($type, $name);
        }
//        for ($i = 0; $i <= 1; $i++) {
//            if (!empty($res1[$i])) $result[] = join("\n", $res1[$i]);
//        }

        return preg_replace('#\n{2,}#', "\n", join("\n", $result));
    }

    /**
     * Start/Stop IE if context
     *
     * @param mixed $context
     * @return $this
     */
    public function ifContext($context = null)
    {
        $this->_currentIfContext = $context;

        return $this;
    }

    /**
     * @param null $name
     * @param null $path
     * @return $this
     */
    public function requireModulePath($name = null, $path = null)
    {
        if (null === $name) {
            $m = $this->BModuleRegistry->currentModule();
            $name = $m->name;
        } else {
            $m = $this->BModuleRegistry->module($name);
        }
        if (null === $path) {
            $path = trim($m->base_src, '/') . '/js';
        }
        BDebug::debug(__METHOD__ . ':' . $name . ':' . $path);
        $this->_requireJs['config']['paths'][$name] = $path;
        return $this;
    }

    /**
     * @param $name
     * @param $path
     * @param null $shim
     * @return $this
     */
    public function requireJs($name, $path, $shim = null)
    {
        $this->_requireJs['config']['paths'][$name] = $path;
        if (null !== $shim) {
            $this->_requireJs['config']['shim'][$name] = $shim;
        }
        return $this;
    }

    /**
     * @param $config
     * @return $this
     */
    public function requireConfig($config)
    {
        $this->_requireJs['config'] = $this->BUtil->arrayMerge($this->_requireJs['config'], $config);
        return $this;
    }

    /**
     * @param $names
     * @return $this
     */
    public function requireRun($names)
    {
        $this->_requireJs['run'] = array_merge($this->_requireJs['run'], (array)$names);
        return $this;
    }

    /**
     * @return string
     */
    public function renderRequireJs()
    {
        $jsArr = [];
        if (!empty($this->_requireJs['config'])) {
            $config = $this->_requireJs['config'];
            if (empty($config['baseUrl'])) {
                $config['baseUrl'] = $this->BConfig->get('web/base_src');
            }
            if (!empty($config['paths'])) {
                foreach ($config['paths'] as $name => $file) {
                    $config['paths'][$name] = $this->src($file);
                }
            }
            // if ($this->BDebug->is('DEBUG')) {
            //     $config['urlArgs'] = 'bust='.time();
            // }
            $jsArr[] = "require.config(" . $this->BUtil->toJavaScript($config) . "); ";
        }
        if (!empty($this->_requireJs['run'])) {
            $jsArr[] = "require(['" . join("', '", $this->_requireJs['run']) . "']);";
        }
        return join("\n", $jsArr);
    }

    /**
     * Render the view
     *
     * If param['template'] is not specified, return meta+css+js tags
     *
     * @param array $args
     * @param bool  $retrieveMetaData
     * @return string
     */
    public function render(array $args = [], $retrieveMetaData = true)
    {
        if (!$this->param('template')) {
            $html = $this->getTitle() . "\n" . $this->getMeta() . "\n" . $this->getAllElements();

            $scriptsArr = [];

            $requireJs = $this->renderRequireJs();
            if ($requireJs) {
                $scriptsArr[] = $requireJs;
            }

            if ($scriptsArr) {
                $html .= "<script>" . join("\n", $scriptsArr) . "</script>";
            }

            return $html;
        }

        return parent::render($args);
    }
}

/**
 * View subclass to store and render lists of views
 *
 * @deprecated by $this->BLayout->hook()
 */
class BViewList extends BView
{
    /**
     * Child blocks
     *
     * @var array
     */
    protected $_children = [];

    /**
     * Last registered position to sort children
     *
     * @var int
     */
    protected $_lastPosition = 0;

    /**
     * Append block to the list
     *
     * @param string|array $viewname array or comma separated list of view names
     * @param array        $params
     * @return BViewList
     */
    public function append($viewname, array $params = [])
    {
        if (is_string($viewname)) {
            $viewname = explode(',', $viewname);
        }
        if (isset($params['position'])) {
            $this->_lastPosition = $params['position'];
        }
        foreach ($viewname as $v) {
            $params['name']     = $v;
            $params['position'] = $this->_lastPosition++;
            $this->_children[]  = $params;
        }

        return $this;
    }

    /**
     * Append plain text to the list
     *
     * A new view object will be created for each text entry with random name
     *
     * @param string $text
     * @return BViewList
     */
    public function appendText($text)
    {
        $layout = $this->BLayout;
        for ($viewname = md5(mt_rand()); $layout->getView($viewname);) ;
        $layout->addView($viewname, ['raw_text' => (string)$text]);
        $this->append($viewname);

        return $this;
    }

    /**
     * Find child view by its content
     *
     * May be slow, use sparringly
     *
     * @param string $content
     * @return BView|null
     */
    public function find($content)
    {
        foreach ($this->_children as $i => $child) {
            $view = $this->view($child['name']);
            if (strpos($view->render(), $content) !== false) {
                return $view;
            }
        }

        return null;
    }

    /**
     * Remove child view from the list
     *
     * @param string $viewname
     * @return BViewList
     */
    public function remove($viewname)
    {
        if (true === $viewname) {
            $this->_children = [];

            return $this;
        }
        foreach ($this->_children as $i => $child) {
            if ($child['name'] == $viewname) {
                unset($this->_children[$i]);
                break;
            }
        }

        return $this;
    }

    /**
     * Render the children views
     *
     * @param array $args
     * @param bool  $retrieveMetaData
     * @throws BException
     * @return string
     */
    public function render(array $args = [], $retrieveMetaData = true)
    {
        $output = [];
        uasort($this->_children, [$this, 'sortChildren']);
        $layout = $this->BLayout;
        foreach ($this->_children as $child) {
            $childView = $layout->getView($child['name']);
            if (!$childView) {
                throw new BException($this->BLocale->_('Invalid view name: %s', $child['name']));
            }
            $output[] = $childView->render($args);
        }

        return join('', $output);
    }

    /**
     * Sort child views by their position
     *
     * @param mixed $a
     * @param mixed $b
     * @return int
     */
    public function sortChildren($a, $b)
    {
        return $a['position'] < $b['position'] ? -1 : ($a['position'] > $b['position'] ? 1 : 0);
    }
}
