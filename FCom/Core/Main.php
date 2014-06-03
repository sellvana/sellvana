<?php

if (!defined('FULLERON_ROOT_DIR')) {
    define('FULLERON_ROOT_DIR', str_replace('\\', '/', dirname(dirname(__DIR__))));
}

require_once __DIR__ . '/buckyball/buckyball.php';

class FCom_Core_Main extends BClass
{
    protected $_modulesDirs = [];

    protected $_env;

    public function __construct(BEnv $env)
    {
        $this->_env = $env;
        $env->debug->mode('DEBUG');
    }

    public function init($area)
    {
        try {
            // initialize start time and register error/exception handlers

            $this->_env->debug->registerErrorHandlers();

            $this->initConfig($area);
            $this->initDebug();
            $this->initModules();

            if (!$this->_env->request->validateHttpHost()) {
                $this->_env->response->status(404, 'Unapproved HTTP Host header', 'Host not found');
                die();
            }

            return $this->_env->app;

        } catch (Exception $e) {
            $this->_env->debug->dumpLog();
            $this->_env->debug->exceptionHandler($e);
        }
    }

    public function run($area)
    {
        $this->init($area);
        try {
            $this->_env->app->run();
        } catch (Exception $e) {
            $this->_env->debug->dumpLog();
            $this->_env->debug->exceptionHandler($e);
        }
    }

    public function initConfig($area)
    {
        $req = $this->_env->request;

        // Chrome has a bug of not storing cookies for localhost domain
        if ($req->httpHost(false) === 'localhost' && $req->userAgent('/chrome/i')) {
            $url = str_replace('//localhost', '//127.0.0.1', $req->currentUrl());
            $this->_env->response->redirect($url);
            exit;
        }

        date_default_timezone_set('UTC');

        $config = $this->_env->config;

        // $localConfig used to override saved config with settings from entry point
        $localConfig = [];
        $localConfig['fs']['fcom_root_dir'] = FULLERON_ROOT_DIR;

        // $rootDir is used and not FULLERON_ROOT_DIR, to allow symlinks and other configurations
        $rootDir = $config->get('fs/root_dir');
        if ($rootDir) {
            $rootDir = realpath($rootDir);
        }
        if (!$rootDir) {
            // not FULLERON_ROOT_DIR, but actual called entry point dir
            $rootDir = $req->scriptDir();
        }
        $localConfig['fs']['root_dir'] = $rootDir = str_replace('\\', '/', $rootDir);

        $this->_env->debug->debug('ROOTDIR=' . $rootDir);

        $docRoot = $req->docRoot();
        $webRoot = $req->webRoot();
        $webRootTrimmed = rtrim($webRoot, '/');
        $baseHref = $config->get('web/base_href');
        $baseSrc = $config->get('web/base_src');
        $baseStore = $config->get('web/base_store');

        if (!$baseHref) {
            $baseHref = $webRoot;
        } elseif (!$this->_env->util->isPathAbsolute($baseHref)) {
            $baseHref = $webRootTrimmed . '/' . $baseHref;
        }
        if (!$baseSrc) {
            $baseSrc = $baseHref;
        } elseif (!$this->_env->util->isPathAbsolute($baseSrc)) {
            $baseSrc = $webRootTrimmed . '/' . $baseSrc;
        }
        if (!$baseStore) {
            $baseStore = $baseHref;
        } elseif (!$this->_env->util->isPathAbsolute($baseStore)) {
            $baseStore = $webRootTrimmed . '/' . $baseStore;
        }
        $localConfig['web']['base_href'] = $baseHref;
        $localConfig['web']['base_src'] = $baseSrc;
        $localConfig['web']['base_store'] = $baseStore;

        $errors = [];

        $mediaDir = $config->get('fs/media_dir');
        if (!$mediaDir) {
            $mediaDir = $rootDir . '/media';
            if (!is_writable($mediaDir)) {
                $errors['permissions'][] = $mediaDir;
            }
            $config->set('fs/media_dir', $mediaDir);
        }

        if (!$config->get('web/media_dir')) {
            $mediaUrl = str_replace($docRoot, '', $mediaDir);
            $config->set('web/media_dir', $mediaUrl);
        }

        $imageFolder = $config->get('fs/image_folder');
        if (!$imageFolder) {
            $config->set('fs/image_folder', 'media/product/image');
        }

        $dlcDir = $config->get('fs/dlc_dir');
        if (!$dlcDir) {
            $dlcDir = $rootDir . '/dlc';
            if (!is_writable($dlcDir)) {
                $errors['permissions'][] = $dlcDir;
            }
            $config->set('fs/dlc_dir', $dlcDir);
        }

        $localDir = $config->get('fs/local_dir');
        if (!$localDir) {
            $localDir = $rootDir . '/local';
            $config->set('fs/local_dir', $localDir);
        }

        $storageDir = $config->get('fs/storage_dir');
        if (!$storageDir) {
            $storageDir = $rootDir . '/storage';
            if (!is_writable($storageDir)) {
                $errors['permissions'][] = $storageDir;
            }
            $config->set('fs/storage_dir', $storageDir);
        }

        // local configuration (db, enabled modules)
        $configDir = $config->get('fs/config_dir');
        if (!$configDir) {
            $configDir = $storageDir . '/config';
            $config->set('fs/config_dir', $configDir);
        }

        // for the rest of var dirs use writable tmp if storage is not writable
        // MD5 used to keep separate storage for each fulleron instance
        #if (!is_writable($storageDir)) {
        #    $storageDir = sys_get_temp_dir().'/fulleron/'.md5(__DIR__);
        #    $config->set('fs/storage_dir', $storageDir);
        #}

        $config->add($localConfig);

        $extLoaded = array_flip(get_loaded_extensions());
        foreach ([
            'bcmath', 'date', 'hash', 'iconv', 'json', 'SPL', 'pcre', 'session',
            'zip', 'pdo_mysql', 'curl', 'gd', 'exif'
        ] as $ext) {
            if (empty($extLoaded[$ext])) {
                $errors['phpext'][] = $ext;
            }
        }

        if ($errors) {
            $this->_env->layout
                ->addView('core/errors', ['template' => __DIR__ . '/views/core/errors.php'])
                ->setRootView('core/errors');
            $this->_env->layout->view('core/errors')->set('errors', $errors);
            $this->_env->response->output();
            exit;
        }

        $configDir = $config->get('fs/config_dir');
        if (file_exists($configDir . '/core.php')) {
            $config->addFile('core.php', true);
        }

        $randomDirName = $config->get('core/storage_random_dir');
        if (!$randomDirName || strpos($randomDirName, 'storage/') !== false) {
            $randomDirGlob = glob($storageDir . '/random-*');
            if ($randomDirGlob) {
                $randomDirName = basename($randomDirGlob[0]);
            } else {
                $randomDirName = 'random-' . $this->_env->util->randomString(16);
                $this->_env->util->ensureDir($storageDir . '/' . $randomDirName);
            }
            $config->set('core/storage_random_dir', $randomDirName, false, true);
            $this->writeConfigFiles('core');
        }
        $randomDir = $storageDir . '/' . $randomDirName;
        $this->_env->util->ensureDir($randomDir);

        // cache files
        $cacheDir = $config->get('fs/cache_dir');
        if (!$cacheDir) {
            $cacheDir = $randomDir . '/cache';
            $config->set('fs/cache_dir', $cacheDir);
        }

        // log files
        $logDir = $config->get('fs/log_dir');
        if (!$logDir) {
            $logDir = $randomDir . '/log';
            $config->set('fs/log_dir', $logDir);
        }

        // session files
        $logDir = $config->get('fs/session_dir');
        if (!$logDir) {
            $logDir = $randomDir . '/session';
            $config->set('fs/session_dir', $logDir);
        }

        $this->_env->request->setArea($area);

        return $this;
    }

    public function initDebug()
    {
        #$this->_env->debug->mode('PRODUCTION');
        #$this->_env->debug->mode('DEVELOPMENT');
        #$this->_env->debug->mode('DEBUG');

        $config = $this->_env->config;
        // Initialize debugging mode and levels
        $this->_env->debug->logDir($config->get('fs/log_dir'));

        $this->_env->debug->adminEmail($config->get('admin_email'));

        $area = $this->_env->request->area();

        if ($area === 'FCom_Admin' && $this->_env->request->get('RECOVERY') === '') {
            $this->_env->debug->mode('RECOVERY');
            return $this;
        }

        $modeByIp = trim($config->get('mode_by_ip/' . $area));

        if ($modeByIp) {
            $ipModes = [];
            $ipPatterns = [];
            foreach (explode("\n", $modeByIp) as $line) {
                $a = explode(':', $line);
                if (strpos($a[0], '*') > 0) {
                    $ipPatterns[trim($a[0])] = strtoupper(trim($a[1]));
                    continue;
                }
                if (empty($a[1])) {
                    $a = ['*', $a[0]];
                }
                $ipModes[trim($a[0])] = strtoupper(trim($a[1]));
            }
            $ip = $this->_env->request->ip();
            if (PHP_SAPI === 'cli' && !empty($ipModes['$'])) {
                $this->_env->debug->mode($ipModes['$']);
                return $this;
            }
            if (!empty($ipModes[$ip])) {
                $this->_env->debug->mode($ipModes[$ip]);
                return $this;
            }
            if (!empty($ipPatterns)) {
                foreach ($ipPatterns as $pat => $mode) {
                    $pat = str_replace('*', '.*', str_replace('.', '\\.', $pat));
                    if (preg_match('#^' . $pat . '$#', $ip)) {
                        $this->_env->debug->mode($mode);
                        return $this;
                    }
                }
            }
            if (!empty($ipModes['*'])) {
                $this->_env->debug->mode($ipModes['*']);
            }
        }
        if ($this->_env->debug->is('DEBUG')) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL | E_STRICT);
        } else {
            ini_set('display_errors', 0);
            error_reporting(0);
        }
#print_r($this->_env->debug->mode());
        return $this;
    }

    public function initModules()
    {
        $config = $this->_env->config;
        $area = $this->_env->request->area();
        $mode = $this->_env->debug->mode();
        $configDir = $config->get('fs/config_dir');

        if ('DISABLED' === $mode) {
            $this->_env->response->status('404', 'Page not found', 'Page not found');
            die;
        }

        if ($config->get('install_status') === 'installed') {
            $runLevels = [$area => 'REQUIRED'];
        } else {
            $config->set('module_run_levels', []);
            $runLevels = [
                'FCom_Install' => 'REQUIRED',
                'FCom_LibTwig' => 'REQUESTED',
                'FCom_MarketClient' => 'REQUESTED',
            ];
            $area = 'FCom_Install';
            $this->_env->request->setArea($area);
        }
        $this->_env->debug->debug('AREA: ' . $area . ', MODE: ' . $mode);
        if ('RECOVERY' === $mode) { // load manifests for RECOVERY mode
            $recoveryModules = $this->_env->config->get('recovery_modules/' . $area);
            if ($recoveryModules) {
                $moduleNames = preg_split('#\s*(,|\n)\s*#', $recoveryModules);
                foreach ($moduleNames as $modName) {
                    $runLevels[$modName] = 'REQUESTED';
                }
            }
        } else { // load all manifests
            $runLevels += (array)$config->get('module_run_levels/request') +
                (array)$config->get('module_run_levels/' . $area) +
                (array)$config->get('module_run_levels/FCom_Core');
        }
        $config->add(['module_run_levels' => ['request' => $runLevels]]);

#$d = $this->_env->debug->debug('SCANNING MANIFESTS');

        $dirConf = $config->get('fs');
        $modReg = $this->_env->modReg;

        //TODO: Figure out how to load db config only once
        if (file_exists($configDir . '/db.php')) {
            $config->addFile('db.php', true);
        }
        $cacheConfig = $config->get('core/cache/manifest_files');
        $useProductionCache = $cacheConfig === 'enable'
            || !$cacheConfig && ('STAGING' === $mode || 'PRODUCTION' === $mode) && !$config->get('db/implicit_migration');

        if ($useProductionCache) {
            $manifestsLoaded = $modReg->loadManifestCache();
        } else {
            $manifestsLoaded = false;
            $modReg->deleteManifestCache();
        }
        if (!$manifestsLoaded) {
            // if (defined('BUCKYBALL_ROOT_DIR')) {
                // $this->_modulesDirs[] = BUCKYBALL_ROOT_DIR.'/plugins';
                // if minified version used, need to load plugins manually
            // }
            $this->_modulesDirs[] = $config->get('core/storage_random_dir') . '/custom'; // Custom module
            $this->_modulesDirs[] = $dirConf['local_dir'] . '/*/*'; // Local modules
            $this->_modulesDirs[] = $dirConf['dlc_dir'] . '/*/*'; // Downloaded modules
            $this->_modulesDirs[] = $dirConf['root_dir'] . '/FCom/*'; // Core modules

            $addModuleDirs = $config->get('core/module_dirs');
            if ($addModuleDirs && is_array($addModuleDirs)) {
                foreach ($addModuleDirs as $dir) {
                    if ($dir[0] === '@') {
                        $dir = preg_replace_callback('#^@([^/]+)#', function($m) use ($dirConf) {
                            return $dirConf[$m[1] . '_dir'];
                        }, $dir);
                    }
                    $this->_modulesDirs[] = $dir;
                }
            }

            foreach ($this->_modulesDirs as $dir) {
                $modReg->scan($dir);
            }
            $modReg->processRequires();
        }
#$this->_env->debug->profile($d);

        $modReg->processDefaultConfig();

        if ($useProductionCache && !$manifestsLoaded) {
            $modReg->saveManifestCache(); //TODO: call explicitly
        }

        if (file_exists($configDir . '/db.php')) {
            $config->addFile('db.php', true);
        }
        if (file_exists($configDir . '/local.php')) {
            $config->addFile('local.php', true);
        }

        $this->_env->autoload->i(true, [$dirConf['local_dir']]);
        $this->_env->autoload->i(true, [$dirConf['dlc_dir']]);
        $this->_env->autoload->i(true, [$dirConf['root_dir']]);

        return $this;
    }

    public function addModulesDir($dir)
    {
        $this->_modulesDirs[] = $dir;
        return $this;
    }

    public function beforeBootstrap()
    {
        $this->_env->layout->setDefaultViewClass('FCom_Core_View_Base');
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

        $config = $this->_env->config;
        $c = $config->get(null, null, true);

        if (in_array('core', $files)) {
            // configuration necessary for core startup
            unset($c['module_run_levels']['request']);

            $core = [
                'install_status' => !empty($c['install_status']) ? $c['install_status'] : null,
                'core' => !empty($c['core']) ? $c['core'] : null,
                'module_run_levels' => !empty($c['module_run_levels']) ? $c['module_run_levels'] : [],
                'recovery_modules' => !empty($c['recovery_modules']) ? $c['recovery_modules'] : null,
                'mode_by_ip' => !empty($c['mode_by_ip']) ? $c['mode_by_ip'] : [],
                'cache' => !empty($c['cache']) ? $c['cache'] : [],
            ];
            $config->writeFile('core.php', $core);
        }
        if (in_array('db', $files)) {
            // db connections
            $db = !empty($c['db']) ? ['db' => $c['db']] : [];
            $config->writeFile('db.php', $db);
        }
        if (in_array('local', $files)) {
            // the rest of configuration
            $local = $this->_env->util->arrayMask($c, 'db,install_status,module_run_levels,recovery_modules,mode_by_ip,cache,core', true);
            $config->writeFile('local.php', $local);
        }
        return $this;
    }

    public function getConfigVersionHash()
    {
        $dir = $this->_env->config->get('fs/config_dir');
        $hash = '';
        foreach (['core', 'db', 'local'] as $f) {
            $hash += filemtime($dir . '/' . $f);
        }
        return $hash;
    }

    public function resizeUrl($img = null, $params = [])
    {
        static $scriptPath = [], $scriptPrefixRegex;

        $full = !empty($params['full_url']);
        unset($params['full_url']);
        if (empty($scriptPath[$full])) {
            if ($full) {
                $dir = $this->_env->app->baseUrl(true);
            } else {
                $dir = rtrim($this->_env->config->get('web/base_src'), '/');
            }
            $scriptPath[$full] = $dir . '/resize.php';

            if (null === $scriptPrefixRegex) {
                $parsed = parse_url($dir);
                $scriptPrefixRegex = '#^/?' . preg_quote(trim($parsed['path'], '/'), '#') . '/?#';
            }
        }

        if (null === $img) {
            return $scriptPath[$full];
        }

        $params['f'] = preg_replace($scriptPrefixRegex, '', $img);

        return $scriptPath[$full] . '?' . http_build_query($params);
    }

    public function thumbSrc($module, $path, $size)
    {
        $url = $this->_env->app->src($module, $path);
        $path = str_replace($this->_env->app->baseUrl(true), '', $url);
        return $this->resizeUrl($path, ['s' => $size]);
    }

    public function dir($path, $autocreate = true, $mode = 0777)
    {
        $dir = $this->_env->config->get('fs/root_dir') . '/' . $path;
        if ($autocreate && !file_exists($dir)) {
            mkdir($dir, $mode, true);
        }
        return $dir;
    }

    /**
    * @deprecated
    *
    * @param mixed $str
    */
    public function getUrlKey($str)
    {
        return $this->_env->locale->transliterate($str);
    }

    public function url($type, $args)
    {
        if (is_string($args)) {
            return $this->_env->app->href('' . $type . '/' . $args);
        }
        return false;
    }

    public function frontendHref($url = '')
    {
        $r = $this->_env->request;

        $href = $r->scheme() . '://' . $r->httpHost() . $this->_env->config->get('web/base_store');
        return trim(rtrim($href, '/') . '/' . ltrim($url, '/'), '/');
    }


    public function lastNav($save = false)
    {
        $s = $this->_env->session;
        $r = $this->_env->request;
        if ($save) {
            $s->set('lastNav', [$r->rawPath(), $r->get()]);
        } else {
            $d = $s->get('lastNav');
            return $this->_env->app->href() . ($d ? $d[0] . '?' . http_build_query((array)$d[1]) : '');
        }
    }

    public function defaultThemeCustomLayout()
    {
        $cookieConfig = $this->_env->config->get('cookie');
        $head = $this->_env->layout->view('head');

        $head->csrf_token();
        $head->js_raw('js_init', ['content' => "
FCom = {};
FCom.cookie_options = " . $this->_env->util->toJson([
    'domain' => !empty($cookieConfig['domain']) ? $cookieConfig['domain'] : null,
    'path' => !empty($cookieConfig['path']) ? $cookieConfig['path'] : null,
]) . ";
FCom.base_href = '" . $this->_env->app->baseUrl() . "';
FCom.base_src = '" . $this->_env->config->get('web/base_src') . "';
        "]);
    }

    public function onTwigInit($args)
    {
        $fa = $args['file_adapter'];
        $fa->addFunction(new Twig_SimpleFunction('fcom_htmlgrid', function($config) {
            return $this->_env->layout->view('core/htmlgrid-wrapper')->set('config', $config);
        }));
    }

    public function runConfigMigration()
    {
        $ver = $this->_env->config->get('core/patch_version');

    }
}
