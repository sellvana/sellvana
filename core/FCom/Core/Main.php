<?php

if (!defined('FULLERON_ROOT_DIR')) {
    define('FULLERON_ROOT_DIR', str_replace('\\', '/', dirname(dirname(dirname(__DIR__)))));
}

require_once __DIR__ . '/buckyball/buckyball.php';

class FCom_Core_Main extends BClass
{
    protected $_modulesDirs = [];

    protected $_env;

    public function __construct()
    {
        $this->BDebug->mode('DEBUG');
    }

    public function init($area)
    {
        try {
            // initialize start time and register error/exception handlers

            $this->BDebug->registerErrorHandlers();

            $this->initConfig($area);
            $this->initDebug();
            $this->initModules();

        } catch (Exception $e) {
            $this->BDebug->dumpLog();
            $this->BDebug->exceptionHandler($e);
        }

        return $this->BApp;
    }

    public function run($area)
    {
        $this->init($area);
        try {
            $this->BApp->run();
        } catch (Exception $e) {
            $this->BDebug->dumpLog();
            $this->BDebug->exceptionHandler($e);
        }
    }

    public function initConfig($area)
    {
        $req = $this->BRequest;

        // Chrome has a bug of not storing cookies for localhost domain
        if ($req->httpHost(false) === 'localhost' && $req->userAgent('/chrome/i')) {
            $url = str_replace('//localhost', '//127.0.0.1', $req->currentUrl());
            $this->BResponse->redirect($url);
            exit;
        }

        date_default_timezone_set('UTC');

        $config = $this->BConfig;

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
            if (PHP_SAPI === 'cli') {
                $rootDir = str_replace(['\\', '/core/FCom/Core'], ['/', ''], __DIR__);
            } else {
                $rootDir = $req->scriptDir();
            }
        }
        $localConfig['fs']['root_dir'] = $rootDir = str_replace('\\', '/', $rootDir);

        $this->BDebug->debug('ROOTDIR=' . $rootDir);
        $this->BDebug->debug('REMOTE=' . $this->BRequest->ip() . ', LOCAL=' . $this->BRequest->serverIp());

        $docRoot = $req->docRoot();
        $webRoot = $req->webRoot();
        $webRootTrimmed = rtrim($webRoot, '/');
        $baseHref = $config->get('web/base_href');
        $baseSrc = $config->get('web/base_src');
        $baseStore = $config->get('web/base_store');

        if (!$baseHref) {
            $baseHref = $webRoot;
        } elseif (!$this->BUtil->isPathAbsolute($baseHref)) {
            $baseHref = $webRootTrimmed . '/' . $baseHref;
        }
        if (!$baseSrc) {
            $baseSrc = $baseHref;
        } elseif (!$this->BUtil->isPathAbsolute($baseSrc)) {
            $baseSrc = $webRootTrimmed . '/' . $baseSrc;
        }
        if (!$baseStore) {
            $baseStore = $baseHref;
        } elseif (!$this->BUtil->isPathAbsolute($baseStore)) {
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
            if (strpos($mediaDir, FULLERON_ROOT_DIR) === 0) {
                $mediaUrl = preg_replace('#^' . preg_quote(FULLERON_ROOT_DIR, '#') . '#', '', $mediaDir);
            } elseif ($rootDir && $rootDir !== '/' && strpos($mediaDir, $rootDir) === 0) {
                $mediaUrl = preg_replace('#^' . preg_quote($rootDir, '#') . '#', '', $mediaDir);
            } elseif ($baseSrc && $baseSrc !== '/' && strpos($mediaDir, $baseSrc) === 0) {
                $mediaUrl = preg_replace('#^' . preg_quote($baseSrc, '#') . '#', '', $mediaDir);
            #} elseif (strpos($mediaDir, $docRoot) === 0) {
            #    $mediaUrl = str_replace($docRoot, '', $mediaDir);
            } else {
                $mediaUrl = 'media';
            }
            $config->set('web/media_dir', $mediaUrl);
        }

        $imageFolder = $config->get('fs/image_folder');
        if (!$imageFolder) {
            $config->set('fs/image_folder', 'media/product/image');
        }

        $coreDir = $config->get('fs/core_dir');
        if (!$coreDir) {
            $coreDir = $rootDir . '/core';
            $config->set('fs/core_dir', $coreDir);
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

        $devDir = $config->get('fs/dev_dir');
        if (!$devDir) {
            $devDir = $rootDir . '/dev';
            $config->set('fs/dev_dir', $devDir);
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
            /*'bcmath', */
            'date', 'hash', 'iconv', 'json', 'SPL', 'pcre', 'session',
            'zip', 'pdo_mysql', 'curl', 'gd', 'intl', 'mbstring', 'soap', 'xml',
        ] as $ext) {
            if (empty($extLoaded[$ext])) {
                $errors['phpext'][] = $ext;
            }
        }

        if ($errors) {
            $this->BLayout
                ->addView('core/errors', ['template' => __DIR__ . '/views/core/errors.php'])
                ->setRootView('core/errors');
            $this->BLayout->getView('core/errors')->set('errors', $errors);
            $this->BResponse->output();
            exit;
        }

        $configDir = $config->get('fs/config_dir');
        $coreConfigFile = $config->get('fs/config_file_core', $configDir . '/' . 'core.php');
        if (file_exists($coreConfigFile)) {
            $config->addFile($coreConfigFile, true);
        }

        $randomDirName = $config->get('core/storage_random_dir');
        if (!$randomDirName || strpos($randomDirName, 'storage/') !== false) {
            $randomDirGlob = glob($storageDir . '/random-*');
            if ($randomDirGlob) {
                $randomDirName = basename($randomDirGlob[0]);
            } else {
                $randomDirName = 'random-' . $this->BUtil->randomString(16);
                $this->BUtil->ensureDir($storageDir . '/' . $randomDirName);
            }
            $config->set('core/storage_random_dir', $randomDirName, false, true);
            $config->writeConfigFiles('core');
        }
        $randomDir = $storageDir . '/' . $randomDirName;
        $this->BUtil->ensureDir($randomDir);

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

        $this->BRequest->setArea($area);

        return $this;
    }

    public function initDebug()
    {
        #$this->BDebug->mode('PRODUCTION');
        #$this->BDebug->mode('DEVELOPMENT');
        #$this->BDebug->mode('DEBUG');

        $config = $this->BConfig;
        // Initialize debugging mode and levels
        $this->BDebug->logDir($config->get('fs/log_dir'));

        $this->BDebug->adminEmail($config->get('admin_email'));

        $area = $this->BRequest->area();

        if ($area === 'FCom_Admin' && $this->BRequest->get('RECOVERY') === '') {
            $this->BDebug->mode('RECOVERY');
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
            $ip = $this->BRequest->ip();
            if (PHP_SAPI === 'cli' && !empty($ipModes['$'])) {
                $this->BDebug->mode($ipModes['$']);
                return $this;
            }
            if (!empty($ipModes[$ip])) {
                $this->BDebug->mode($ipModes[$ip]);
                return $this;
            }
            if (!empty($ipPatterns)) {
                foreach ($ipPatterns as $pat => $mode) {
                    $pat = str_replace('*', '.*', str_replace('.', '\\.', $pat));
                    if (preg_match('#^' . $pat . '$#', $ip)) {
                        $this->BDebug->mode($mode);
                        return $this;
                    }
                }
            }
            if (!empty($ipModes['*'])) {
                $this->BDebug->mode($ipModes['*']);
            }
        }
        if ($this->BDebug->is('DEBUG')) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL | E_STRICT);
        } else {
            ini_set('display_errors', 0);
            error_reporting(0);
        }
#print_r($this->BDebug->mode());
        return $this;
    }

    public function initModules()
    {
        $config = $this->BConfig;
        $area = $this->BRequest->area();
        $mode = $this->BDebug->mode();
        $configDir = $config->get('fs/config_dir');

        if ('DISABLED' === $mode) {
            $this->BResponse->header('X-Remote-IP: ' . $this->BRequest->ip());
            $this->BResponse->status('404', 'Page not found', 'Page not found');
            die;
        }

        if ($config->get('install_status') === 'installed') {
            $runLevels = [$area => 'REQUIRED'];
        } else {
            $config->set('module_run_levels', []);
            $runLevels = [
                'FCom_Install' => 'REQUIRED',
                'FCom_Shell' => 'REQUESTED',
                'FCom_LibTwig' => 'REQUESTED',
                'Sellvana_MarketClient' => 'REQUESTED', //TODO: move out to MarketClient module
            ];
            $area = 'FCom_Install';
            $this->BRequest->setArea($area);
        }
        $this->BDebug->debug('AREA: ' . $area . ', MODE: ' . $mode);
        if ('RECOVERY' === $mode) { // load manifests for RECOVERY mode
            $recoveryRedirect = $this->BConfig->get("recovery/{$area}/redirect");
            if ($recoveryRedirect) {
                $this->BResponse->redirect($recoveryRedirect);
            }
            $recoveryModules = $this->BConfig->get("recovery/{$area}/modules");
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

#$d = $this->BDebug->debug('SCANNING MANIFESTS');

        $dirConf = $config->get('fs');
        $modReg = $this->BModuleRegistry;

        //TODO: Figure out how to load db config only once
        $dbConfigFile = $config->get('fs/config_file_db', $configDir . '/' . 'db.php');
        if (file_exists($dbConfigFile)) {
            $config->addFile($dbConfigFile, true);
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
        $loadCoreDev = $this->BConfig->get('core/dev/load');
        if (!$manifestsLoaded) {
            // if (defined('BUCKYBALL_ROOT_DIR')) {
                // $this->_modulesDirs[] = BUCKYBALL_ROOT_DIR.'/plugins';
                // if minified version used, need to load plugins manually
            // }
            $this->_modulesDirs[] = $config->get('core/storage_random_dir') . '/custom'; // Custom module
            $this->_modulesDirs[] = $dirConf['local_dir'] . '/*/*'; // Local modules
            $this->_modulesDirs[] = $dirConf['dlc_dir'] . '/*/*'; // Downloaded modules
            $this->_modulesDirs[] = $dirConf['core_dir'] . '/*/*'; // Core modules
            if ($loadCoreDev) {
                $this->_modulesDirs[] = $dirConf['dev_dir'] . '/*/*'; // Dev modules
            }

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
#$this->BDebug->profile($d);

        if ($useProductionCache && !$manifestsLoaded) {
            $modReg->saveManifestCache(); //TODO: call explicitly
        }

        if (file_exists($dbConfigFile)) {
            $config->addFile($dbConfigFile, true);
        }

        $localConfigFile = $config->get('fs/config_file_local', $configDir . '/' . 'local.php');
        if (file_exists($localConfigFile)) {
            $config->addFile($localConfigFile, true);
        }

        $modReg->processDefaultConfig();

        $this->BClassAutoload->addPath($dirConf['local_dir']);
        $this->BClassAutoload->addPath($dirConf['dlc_dir']);
        $this->BClassAutoload->addPath($dirConf['core_dir']);
        if ($loadCoreDev) {
            $this->BClassAutoload->addPath($dirConf['dev_dir']);
        }

        #$this->BClassAutoload->addPath($modReg->module('FCom_Core')->root_dir . '/lib');

        return $this;
    }

    public function addModulesDir($dir)
    {
        $this->_modulesDirs[] = $dir;
        return $this;
    }

    public function onBeforeBootstrap()
    {
        $this->BLayout->setDefaultViewClass('FCom_Core_View_Base');

        $area = $this->BRequest->area();
        $conf = $this->BConfig;
        foreach (['cookie', 'web'] as $section) {
            $areaConfig = $conf->get("modules/{$area}/{$section}");
            if ($areaConfig) {
                $areaConfig = $this->BUtil->arrayCleanEmpty($areaConfig);
                if ($areaConfig) {
                    $conf->set($section, $areaConfig, true);
                }
            }
        }
    }

    public function getConfigVersionHash()
    {
        $dir = $this->BConfig->get('fs/config_dir');
        $hash = '';
        foreach (['core', 'db', 'local', 'dev'] as $f) {
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
                $dir = $this->BApp->baseUrl(true);
            } else {
                $dir = rtrim($this->BConfig->get('web/base_src'), '/');
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
        $url = $this->BApp->src($module, $path);
        $path = str_replace($this->BApp->baseUrl(true), '', $url);
        return $this->resizeUrl($path, ['s' => $size]);
    }

    public function dir($path, $autocreate = true, $mode = 0777)
    {
        $dir = $this->BConfig->get('fs/root_dir') . '/' . $path;
        if ($autocreate && !file_exists($dir)) {
            mkdir($dir, $mode, true);
        }
        return $dir;
    }

    /**
     * @deprecated
     *
     * @param mixed $str
     * @return mixed|string
     */
    public function getUrlKey($str)
    {
        return $this->BLocale->transliterate($str);
    }

    public function url($type, $args)
    {
        if (is_string($args)) {
            return $this->BApp->href('' . $type . '/' . $args);
        }
        return false;
    }

    public function frontendHref($url = '')
    {
        $r = $this->BRequest;

        $href = $r->scheme() . '://' . $r->httpHost() . $this->BConfig->get('web/base_store');
        return trim(rtrim($href, '/') . '/' . ltrim($url, '/'), '/');
    }


    public function lastNav($save = false)
    {
        $s = $this->BSession;
        $r = $this->BRequest;
        if ($save) {
            $s->set('lastNav', [$r->rawPath(), $r->get()]);
        } else {
            $d = $s->get('lastNav');
            return $this->BApp->href() . ($d ? ltrim($d[0], '/') . '?' . http_build_query((array)$d[1]) : '');
        }
    }

    public function defaultThemeCustomLayout()
    {
        $cookieConfig = $this->BConfig->get('cookie');

        /** @var FCom_Core_View_Head $head */
        $head = $this->BLayout->getView('head');

        /** @var FCom_Core_View_Text $script */
        $script = $this->BLayout->getView('head_script');

        $head->csrf_token();

        $text = "
FCom = {};
FCom.cookie_options = " . $this->BRequest->getCookieConfigJson() . ";
FCom.base_href = '" . $this->BApp->baseUrl() . "';
FCom.base_src = '" . $this->BConfig->get('web/base_src') . "';
        ";

        $head->js_raw('js_init', $text);
        $script->addText('FCom_Core:init', $text);
    }

    public function onTwigInit($args)
    {
        $fa = $args['file_adapter'];
        $fa->addFunction(new Twig_SimpleFunction('fcom_htmlgrid', function($config) {
            return $this->BLayout->getView('core/htmlgrid-wrapper')->set('config', $config);
        }));
    }

    public function runConfigMigration()
    {
        $ver = $this->BConfig->get('core/patch_version');

    }

    public function getAllowedCountries()
    {
        $conf = $this->BConfig->get('modules/FCom_Core');
        $limit = !empty($conf['limit_countries']) ? $conf['allowed_countries'] : null;
        return $this->BLocale->getAvailableCountries('name', $limit);
    }

    public function getDefaultCountry()
    {
        return $this->BConfig->get('modules/FCom_Core/default_country');
    }

    public function getAllowedRegions()
    {
        $conf = $this->BConfig->get('modules/FCom_Core');
        $limit = !empty($conf['limit_countries']) ? $conf['allowed_countries'] : null;
        return $this->BLocale->getAvailableRegions('name', $limit);
    }

    public function onFindOneAfter($args)
    {
        if (!empty($args['result']) && $args['result'] instanceof FCom_Core_Model_Abstract && $args['result']->id()) {
            $args['result']->mapDataFields();
        }
    }

    public function onFindManyAfter($args)
    {
        if (!empty($args['result']) && is_array($args['result'])) {
            /** @var FCom_Core_Model_Abstract $model */
            foreach ($args['result'] as $key => $model) {
                if (!($model instanceof FCom_Core_Model_Abstract) || !$model->id()) {
                    continue;
                }
                $model->mapDataFields();
            }
        }
    }
}
