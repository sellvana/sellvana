<?php

if (!defined('FULLERON_ROOT_DIR')) {
    define('FULLERON_ROOT_DIR', dirname(dirname(__DIR__)));
}

if (!defined('BUCKYBALL_ROOT_DIR')) {
    require_once FULLERON_ROOT_DIR.'/FCom/buckyball/buckyball.php';
}

class FCom_Core_Main extends BClass
{
    protected $_modulesDirs = array();

    public function init($area)
    {
        try {
            // initialize start time and register error/exception handlers
            BDebug::i()->registerErrorHandlers();

            $this->initConfig($area);
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
        date_default_timezone_set('UTC');

        $config = BConfig::i();

        // $localConfig used to override saved config with settings from entry point
        $localConfig = array();
        $localConfig['fs']['fcom_root_dir'] = FULLERON_ROOT_DIR;

        $rootDir = $config->get('fs/root_dir');
        if (!$rootDir) {
            // not FULLERON_ROOT_DIR, but actual called entry point dir
            $localConfig['fs']['root_dir'] = $rootDir = BRequest::i()->scriptDir();
        }

        BDebug::debug('ROOTDIR='.$rootDir);

        $baseHref = $config->get('web/base_href');
        if (!$baseHref) {
            $baseHref = BRequest::i()->webRoot();
            $localConfig['web']['base_href'] = $baseHref;
        }
        if (!$config->get('web/base_src')) {
            $localConfig['web']['base_src'] = $baseHref;
        }
        if (!$config->get('web/base_store')) {
            $localConfig['web']['base_store'] = $baseHref;
        }

        $permissionErrors = array();

        $mediaDir = $config->get('fs/media_dir');
        if (!$mediaDir) {
            $mediaDir = $rootDir.'/media';
            if (!is_writable($mediaDir)) {
                $permissionErrors[] = '/media';
            }
            $config->set('fs/media_dir', $mediaDir);
        }

        if (!$config->get('web/media_dir')) {
            $mediaUrl = str_replace($rootDir, '', $mediaDir);
            $config->set('web/media_dir', $mediaUrl);
        }

        $imageFolder = $config->get('fs/image_folder');
        if (!$imageFolder) {
            $config->set('fs/image_folder', 'media/product/image');
        }

        $dlcDir = $config->get('fs/dlc_dir');
        if (!$dlcDir) {
            $dlcDir = $rootDir.'/dlc';
            $config->set('fs/dlc_dir', $dlcDir);
        }

        $storageDir = $config->get('fs/storage_dir');
        if (!$storageDir) {
            $storageDir = $rootDir.'/storage';
            if (!is_writable($storageDir)) {
                $permissionErrors[] = '/storage';
            }
            $config->set('fs/storage_dir', $storageDir);
        }

        // local configuration (db, enabled modules)
        $configDir = $config->get('fs/config_dir');
        if (!$configDir) {
            $configDir = $storageDir.'/config';
            $config->set('fs/config_dir', $configDir);
        }

        // for the rest of var dirs use writable tmp if storage is not writable
        // MD5 used to keep separate storage for each fulleron instance
        #if (!is_writable($storageDir)) {
        #    $storageDir = sys_get_temp_dir().'/fulleron/'.md5(__DIR__);
        #    $config->set('fs/storage_dir', $storageDir);
        #}

        // cache files
        $cacheDir = $config->get('fs/cache_dir');
        if (!$cacheDir) {
            $cacheDir = $storageDir.'/cache';
            $config->set('fs/cache_dir', $cacheDir);
        }

        // log files
        $logDir = $config->get('fs/log_dir');
        if (!$logDir) {
            $logDir = $storageDir.'/log';
            $config->set('fs/log_dir', $logDir);
        }

        if ($permissionErrors) {
            BLayout::i()
                ->addView('permissions', array('template' => __DIR__.'/views/permissions.php'))
                ->setRootView('permissions');
            BLayout::i()->view('permissions')->set('errors', $permissionErrors);
            BResponse::i()->output();
        }

#echo "<Pre>"; print_r($config->get()); exit;
        // add area module
        BApp::i()->set('area', $area, true);

        $config->add($localConfig);

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

        if ($area==='FCom_Admin' && BRequest::i()->get('RECOVERY')==='') {
            BDebug::mode('RECOVERY');
            return $this;
        }

        $modeByIp = trim($config->get('mode_by_ip/'.$area));

        if ($modeByIp) {
            $ipModes = array();
            $ipPatterns = array();
            foreach (explode("\n", $modeByIp) as $line) {
                $a = explode(':', $line);
                if (strpos($a[0], '*')>0) {
                    $ipPatterns[trim($a[0])] = strtoupper(trim($a[1]));
                    continue;
                }
                if (empty($a[1])) {
                    $a = array('*', $a[0]);
                }
                $ipModes[trim($a[0])] = strtoupper(trim($a[1]));
            }
            $ip = BRequest::i()->ip();
            if (PHP_SAPI==='cli' && !empty($ipModes['$'])) {
                BDebug::mode($ipModes['$']);
                return $this;
            }
            if (!empty($ipModes[$ip])) {
                BDebug::mode($ipModes[$ip]);
                return $this;
            }
            if (!empty($ipPatterns)) {
                foreach ($ipPatterns as $pat=>$mode) {
                    $pat = str_replace('*', '.*', str_replace('.', '\\.', $pat));
                    if (preg_match('#^'.$pat.'$#', $ip)) {
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
        $configDir = $config->get('fs/config_dir');
        if (file_exists($configDir.'/core.php')) {
            $config->addFile('core.php', true);
        }

        $this->initDebug();

        $mode = BDebug::mode();

        if ('DISABLED' === $mode) {
            BResponse::i()->status('404', 'Page not found', 'Page not found');
            die;
        }

        if ($config->get('install_status') === 'installed') {
            $runLevels = array($area => 'REQUIRED');
        } else {
            $config->set('module_run_levels', array());
            $runLevels = array('FCom_Install' => 'REQUIRED');
        }

        if ('RECOVERY' === $mode) { // load manifests for RECOVERY mode
            $recoveryModules = BConfig::i()->get('recovery_modules/'.$area);
            if ($recoveryModules) {
                $moduleNames = preg_split('#\s*(,|\n)\s*#', $recoveryModules);
                foreach ($moduleNames as $modName) {
                    $runLevels[$modName] = 'REQUESTED';
                }
            }
        } else { // load all manifests
            $runLevels += (array)$config->get('module_run_levels/request') +
                (array)$config->get('module_run_levels/'.$area) +
                (array)$config->get('module_run_levels/FCom_Core');
        }
        $config->add(array('module_run_levels'=>array('request'=>$runLevels)));

        //FCom::i()->registerBundledModules();
#$d = BDebug::debug('SCANNING MANIFESTS');

        // $rootDir is used and not FULLERON_ROOT_DIR, to allow symlinks and other configurations
        $rootDir = $config->get('fs/root_dir');

        if ('STAGING' === $mode || 'PRODUCTION' === $mode) {
            $manifestsLoaded = BModuleRegistry::i()->loadManifestCache();
        } else {
            $manifestsLoaded = false;
        }
        if (!$manifestsLoaded) {
            if (defined('BUCKYBALL_ROOT_DIR')) {
                $this->_modulesDirs[] = BUCKYBALL_ROOT_DIR.'/plugins';
                // if minified version used, need to load plugins manually
            }
            $this->_modulesDirs[] = $rootDir.'/FCom/*'; // Core modules
            $this->_modulesDirs[] = $rootDir.'/dlc/*'; // Downloaded modules (1st dir level)
            $this->_modulesDirs[] = $rootDir.'/dlc/*/*'; // Download modules (2nd dir level, including vendor)
            $this->_modulesDirs[] = $rootDir.'/local/*'; // Local modules
            $this->_modulesDirs[] = $rootDir.'/local/*/*'; // Local modules

            foreach ($this->_modulesDirs as $dir) {
                BModuleRegistry::i()->scan($dir);
            }
            BModuleRegistry::i()->saveManifestCache(); //TODO: call explicitly
        }
#BDebug::profile($d);

        BModuleRegistry::i()->processRequires()->processDefaultConfig();

        if (file_exists($configDir.'/db.php')) {
            $config->addFile('db.php', true);
        }
        if (file_exists($configDir.'/local.yml')) {
            $config->addFile('local.yml', true);
        }

        BClassAutoload::i(true, array('root_dir'=>$rootDir.'/local'));
        BClassAutoload::i(true, array('root_dir'=>$rootDir.'/dlc'));
        BClassAutoload::i(true, array('root_dir'=>$rootDir));

        return $this;
    }

    public function addModulesDir($dir)
    {
        $this->_modulesDirs[] = $dir;
        return $this;
    }

    static public function beforeBootstrap()
    {
        BLayout::i()->defaultViewClass('FCom_Core_View_Base');
    }

    public function writeDbConfig()
    {
        $c = array('db'=>BConfig::i()->get('db', true));
        BConfig::i()->writeFile('db.php', $c); // PHP for simpler loading
        return $this;
    }

    public function writeLocalConfig()
    {
        $config = BConfig::i();
        $c = $config->get(null, true);
        // collect configuration necessary for core startup
        $m = array(
            'install_status' => !empty($c['install_status']) ? $c['install_status'] : null,
            'module_run_levels' => !empty($c['module_run_levels']) ? $c['module_run_levels'] : array(),
            'recovery_modules' => !empty($c['recovery_modules']) ? $c['recovery_modules'] : null,
            'mode_by_ip' => !empty($c['mode_by_ip']) ? $c['mode_by_ip'] : array(),
            'cache' => !empty($c['cache']) ? $c['cache'] : null,
        );
        unset($c['db'], $c['install_status'], $c['module_run_levels'], $c['recovery_modules'],
            $c['mode_by_ip'], $c['cache']);
        $config->writeFile('core.php', $m); // PHP for simpler loading
        $config->writeFile('local.yml', $c);
        return $this;
    }

    public function resizeUrl($full=false)
    {
        static $url = array();
        if (empty($url[$full])) {
            $url[$full] = rtrim(BConfig::i()->get('web/base_href'), '/').'/resize.php';
            if ($full) {
                $r = BRequest::i();
                $url[$full] = BApp::baseUrl(true).$url[$full];
            }
        }
        return $url[$full];
    }

    public function thumbSrc($module, $path, $size)
    {
        $url = BApp::src($module, $path);
        $path = str_replace(BApp::baseUrl(true), '', $url);
        return $this->resizeUrl().'?f='.urlencode($path).'&s='.$size;
    }

    public function dir($path, $autocreate=true, $mode=0777)
    {
        $dir = BConfig::i()->get('fs/root_dir').'/'.$path;
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
            return BApp::href(''.$type.'/'.$args);
        }
        return false;
    }

    public static function frontendHref($url='')
    {
        $r = BRequest::i();
        $href = $r->scheme().'://'.$r->httpHost().BConfig::i()->get('web/base_store');
        return trim(rtrim($href, '/').'/'.ltrim($url, '/'), '/');
    }


    static public function lastNav($save=false)
    {
        $s = BSession::i();
        $r = BRequest::i();
        if ($save) {
            $s->data('lastNav', array($r->rawPath(), $r->get()));
        } else {
            $d = $s->data('lastNav');
            return BApp::href().($d ? $d[0].'?'.http_build_query((array)$d[1]) : '');
        }
    }

    public static function defaultThemeCustomLayout()
    {
        $cookieConfig = BConfig::i()->get('cookie');
        $head = BLayout::i()->view('head');

        $head->meta('csrf-token', BSession::i()->csrfToken());
        $head->js_raw('js_init', array('content'=>"
FCom = {};
FCom.cookie_options = ".BUtil::toJson(array('domain'=>$cookieConfig['domain'], 'path'=>$cookieConfig['path'])).";
FCom.base_href = '".BApp::baseUrl()."';
        "));
    }
}
