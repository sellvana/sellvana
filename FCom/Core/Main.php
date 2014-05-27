<?php

if (!defined('FULLERON_ROOT_DIR')) {
    define('FULLERON_ROOT_DIR', str_replace('\\', '/', dirname(dirname(__DIR__))));
}

require_once __DIR__ . '/buckyball/buckyball.php';

class FCom_Core_Main extends BClass
{
    protected $_modulesDirs = [];

    public function init($area)
    {
        try {
            // initialize start time and register error/exception handlers
            BDebug::i()->registerErrorHandlers();

            $this->initConfig($area);
            $this->initDebug();
            $this->initModules();

            return BApp::i();

        } catch (Exception $e) {
            BDebug::dumpLog();
            BDebug::exceptionHandler($e);
        }
    }

    public function run($area)
    {
        $this->init($area);
        try {
            BApp::i()->run();
        } catch (Exception $e) {
            BDebug::dumpLog();
            BDebug::exceptionHandler($e);
        }
    }

    public function initConfig($area)
    {
        $req = BRequest::i();

        // Chrome has a bug of not storing cookies for localhost domain
        if ($req->httpHost(false) === 'localhost' && $req->userAgent('/chrome/i')) {
            $url = str_replace('//localhost', '//127.0.0.1', $req->currentUrl());
            BResponse::i()->redirect($url);
            exit;
        }

        date_default_timezone_set('UTC');

        $config = BConfig::i();

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

        BDebug::debug('ROOTDIR=' . $rootDir);

        $docRoot = $req->docRoot();
        $webRoot = $req->webRoot();
        $webRootTrimmed = rtrim($webRoot, '/');
        $baseHref = $config->get('web/base_href');
        $baseSrc = $config->get('web/base_src');
        $baseStore = $config->get('web/base_store');

        if (!$baseHref) {
            $baseHref = $webRoot;
        } elseif (!BUtil::isPathAbsolute($baseHref)) {
            $baseHref = $webRootTrimmed . '/' . $baseHref;
        }
        if (!$baseSrc) {
            $baseSrc = $baseHref;
        } elseif (!BUtil::isPathAbsolute($baseSrc)) {
            $baseSrc = $webRootTrimmed . '/' . $baseSrc;
        }
        if (!$baseStore) {
            $baseStore = $baseHref;
        } elseif (!BUtil::isPathAbsolute($baseStore)) {
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
            BLayout::i()
                ->addView('core/errors', ['template' => __DIR__ . '/views/core/errors.php'])
                ->setRootView('core/errors');
            BLayout::i()->view('core/errors')->set('errors', $errors);
            BResponse::i()->output();
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
                $randomDirName = 'random-' . BUtil::randomString(16);
                BUtil::ensureDir($storageDir . '/' . $randomDirName);
            }
            $config->set('core/storage_random_dir', $randomDirName, false, true);
            $this->writeConfigFiles('core');
        }
        $randomDir = $storageDir . '/' . $randomDirName;
        BUtil::ensureDir($randomDir);

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

        BApp::i()->set('area', $area, true);

        return $this;
    }

    public function initDebug()
    {
        #BDebug::mode('production');
        #BDebug::mode('development');
        #BDebug::mode('debug');

        $config = BConfig::i();
        // Initialize debugging mode and levels
        BDebug::logDir($config->get('fs/log_dir'));

        BDebug::adminEmail($config->get('admin_email'));

        $area = BApp::i()->get('area');

        if ($area === 'FCom_Admin' && BRequest::i()->get('RECOVERY') === '') {
            BDebug::mode('RECOVERY');
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
            $ip = BRequest::i()->ip();
            if (PHP_SAPI === 'cli' && !empty($ipModes['$'])) {
                BDebug::mode($ipModes['$']);
                return $this;
            }
            if (!empty($ipModes[$ip])) {
                BDebug::mode($ipModes[$ip]);
                return $this;
            }
            if (!empty($ipPatterns)) {
                foreach ($ipPatterns as $pat => $mode) {
                    $pat = str_replace('*', '.*', str_replace('.', '\\.', $pat));
                    if (preg_match('#^' . $pat . '$#', $ip)) {
                        BDebug::mode($mode);
                        return $this;
                    }
                }
            }
            if (!empty($ipModes['*'])) {
                BDebug::mode($ipModes['*']);
            }
        }
#print_r(BDebug::mode());
        return $this;
    }

    public function initModules()
    {
        $config = BConfig::i();
        $area = BApp::i()->get('area');
        $mode = BDebug::mode();
        $configDir = $config->get('fs/config_dir');

        if ('DISABLED' === $mode) {
            BResponse::i()->status('404', 'Page not found', 'Page not found');
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
            BApp::i()->set('area', $area, true);
        }
        BDebug::debug('AREA: ' . $area . ', MODE: ' . $mode);
        if ('RECOVERY' === $mode) { // load manifests for RECOVERY mode
            $recoveryModules = BConfig::i()->get('recovery_modules/' . $area);
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

        //FCom::i()->registerBundledModules();
#$d = BDebug::debug('SCANNING MANIFESTS');

        $dirConf = $config->get('fs');
        $modReg = BModuleRegistry::i();

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
#BDebug::profile($d);

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

        BClassAutoload::i(true, ['root_dir' => $dirConf['local_dir']]);
        BClassAutoload::i(true, ['root_dir' => $dirConf['dlc_dir']]);
        BClassAutoload::i(true, ['root_dir' => $dirConf['root_dir']]);

        return $this;
    }

    public function addModulesDir($dir)
    {
        $this->_modulesDirs[] = $dir;
        return $this;
    }

    static public function beforeBootstrap()
    {
        BLayout::i()->setDefaultViewClass('FCom_Core_View_Base');
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

        $config = BConfig::i();
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
            $local = BUtil::arrayMask($c, 'db,install_status,module_run_levels,recovery_modules,mode_by_ip,cache,core', true);
            $config->writeFile('local.php', $local);
        }
        return $this;
    }

    public function getConfigVersionHash()
    {
        $dir = BConfig::i()->get('fs/config_dir');
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
                $dir = BApp::baseUrl(true);
            } else {
                $dir = rtrim(BConfig::i()->get('web/base_src'), '/');
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
        $url = BApp::src($module, $path);
        $path = str_replace(BApp::baseUrl(true), '', $url);
        return $this->resizeUrl($path, ['s' => $size]);
    }

    public function dir($path, $autocreate = true, $mode = 0777)
    {
        $dir = BConfig::i()->get('fs/root_dir') . '/' . $path;
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
    static public function getUrlKey($str)
    {
        return BLocale::transliterate($str);
    }

    static public function url($type, $args)
    {
        if (is_string($args)) {
            return BApp::href('' . $type . '/' . $args);
        }
        return false;
    }

    public static function frontendHref($url = '')
    {
        $r = BRequest::i();

        $href = $r->scheme() . '://' . $r->httpHost() . BConfig::i()->get('web/base_store');
        return trim(rtrim($href, '/') . '/' . ltrim($url, '/'), '/');
    }


    static public function lastNav($save = false)
    {
        $s = BSession::i();
        $r = BRequest::i();
        if ($save) {
            $s->set('lastNav', [$r->rawPath(), $r->get()]);
        } else {
            $d = $s->get('lastNav');
            return BApp::href() . ($d ? $d[0] . '?' . http_build_query((array)$d[1]) : '');
        }
    }

    public static function defaultThemeCustomLayout()
    {
        $cookieConfig = BConfig::i()->get('cookie');
        $head = BLayout::i()->view('head');

        $head->csrf_token();
        $head->js_raw('js_init', ['content' => "
FCom = {};
FCom.cookie_options = " . BUtil::toJson([
    'domain' => !empty($cookieConfig['domain']) ? $cookieConfig['domain'] : null,
    'path' => !empty($cookieConfig['path']) ? $cookieConfig['path'] : null,
]) . ";
FCom.base_href = '" . BApp::i()->baseUrl() . "';
FCom.base_src = '" . BConfig::i()->get('web/base_src') . "';
        "]);
    }

    public static function onTwigInit($args)
    {
        $fa = $args['file_adapter'];
        $fa->addFunction(new Twig_SimpleFunction('fcom_htmlgrid', function($config) {
            return BLayout::i()->view('core/htmlgrid-wrapper')->set('config', $config);
        }));
    }

    public function runConfigMigration()
    {
        $ver = BConfig::i()->get('core/patch_version');

    }
}
