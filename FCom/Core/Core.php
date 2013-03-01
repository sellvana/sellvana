<?php

if (!defined('FULLERON_ROOT_DIR')) {
    define('FULLERON_ROOT_DIR', dirname(dirname(__DIR__)));
}

if (!defined('BUCKYBALL_ROOT_DIR')) {
    require_once FULLERON_ROOT_DIR.'/FCom/buckyball/buckyball.php';
}

class FCom_Core extends BClass
{
    protected $_modulesDirs = array();

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

        $mediaDir = $config->get('fs/media_dir');
        if (!$mediaDir) {
            $mediaDir = $rootDir.'/media';
            $config->set('fs/media_dir', $mediaDir);
        }

        $imageFolder = $config->get('fs/image_folder');
        if (!$imageFolder) {
            $config->set('fs/image_folder', 'media/product/image');
        }

        $storageDir = $config->get('fs/storage_dir');
        if (!$storageDir) {
            $storageDir = $rootDir.'/storage';
            $config->set('fs/storage_dir', $storageDir);
        }

        $marketModulesDir = $config->get('fs/market_modules_dir');
        if (!$marketModulesDir) {
            $marketModulesDir = $rootDir.'/market-modules';
            $config->set('fs/market_modules_dir', $marketModulesDir);
        }

        // local configuration (db, enabled modules)
        $configDir = $config->get('fs/config_dir');
        if (!$configDir) {
            $configDir = $storageDir.'/config';
            $config->set('fs/config_dir', $configDir);
        }

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

        // DB configuration is separate to gitignore
        // used as indication that app is already installed and setup
        $configFileStatus = true;
        if (file_exists($configDir.'/db.php')) {
            $config->addFile('db.php', true);
        } else {
            $configFileStatus = false;
        }
        if (file_exists($configDir.'/local.php')) {
            $config->addFile('local.php', true);
        } else {
            $configFileStatus = false;
        }
        if (file_exists($configDir.'/defaults.php')) {
            include_once $configDir.'/defaults.php';
        }
        if (!$configFileStatus || $config->get('install_status')!=='installed') {
            //$area = 'FCom_Admin'; //TODO: make sure works without (bootstrap considerations)
            BDebug::mode('INSTALLATION');
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

        $modeByIp = trim($config->get('modules/'.BApp::i()->get('area').'/mode_by_ip'));
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

        if (BDebug::is('DISABLED')) {
            BResponse::i()->status('404', 'Page not found', 'Page not found');
            die;
        }
        if (BDebug::is('INSTALLATION')) {
            $runLevels = array('FCom_Install' => 'REQUIRED');
        } else {
            $runLevels = array($area => 'REQUIRED');
        }
        if (BDebug::is('RECOVERY')) { // load manifests for RECOVERY mode
            $recoveryModules = BConfig::i()->get('modules/FCom_Core/recovery_modules');
            if ($recoveryModules) {
                $moduleNames = preg_split('#\s*(,|\n)\s*#', $recoveryModules);
                foreach ($moduleNames as $modName) {
                    $runLevels[$modName] = 'REQUESTED';
                }
            }
        } else { // load all manifests
            $runLevels += (array)$config->get('request/module_run_level') +
                (array)$config->get('modules/'.$area.'/module_run_level') +
                (array)$config->get('modules/FCom_Core/module_run_level');
        }
        $config->add(array('request'=>array('module_run_level'=>$runLevels)));

        //FCom::i()->registerBundledModules();
#$d = BDebug::debug('SCANNING MANIFESTS');

        if (defined('BUCKYBALL_ROOT_DIR')) {
            $this->_modulesDirs[] = BUCKYBALL_ROOT_DIR.'/plugins';
            // if minified version used, need to load plugins manually
        }
        // $rootDir is used and not FULLERON_ROOT_DIR, to allow symlinks and other configurations
        $rootDir = $config->get('fs/root_dir');
        $this->_modulesDirs[] = $rootDir.'/FCom';
        $this->_modulesDirs[] = $rootDir.'/FCom/*';
        //$this->_modulesDirs[] = $rootDir.'/market/*';
        //$this->_modulesDirs[] = $rootDir.'/market/*/*';
        $this->_modulesDirs[] = $rootDir.'/market-modules/*';
        $this->_modulesDirs[] = $rootDir.'/market-modules/*/*';
        $this->_modulesDirs[] = $rootDir.'/local/*';

        foreach ($this->_modulesDirs as $dir) {
            BModuleRegistry::i()->scan($dir);
        }
#BDebug::profile($d);

        BClassAutoload::i(true, array('root_dir'=>$rootDir.'/local'));
        //BClassAutoload::i(true, array('root_dir'=>$rootDir.'/market'));
        BClassAutoload::i(true, array('root_dir'=>$rootDir.'/market-modules'));
        BClassAutoload::i(true, array('root_dir'=>$rootDir));

        return $this;
    }

    public function addModulesDir($dir)
    {
        $this->_modulesDirs[] = $dir;
        return $this;
    }

    static public function bootstrap()
    {
        BLayout::i()
            ->defaultViewClass('FCom_Core_View_Abstract')
            ->view('head', array('view_class'=>'FCom_Core_View_Head'))
        ;
    }

    public function writeDbConfig()
    {
        BConfig::i()->writeFile('db.php', array('db'=>BConfig::i()->get('db', true)));
        return $this;
    }

    public function writeLocalConfig()
    {
        $c = BConfig::i()->get(null, true);
        unset($c['db']);
        BConfig::i()->writeFile('local.php', $c);
        return $this;
    }

    public function resizeUrl()
    {
        static $url;
        if (!$url) {
            $url = BApp::href('resize.php', 1, 1);
        }
        return $url;
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
    * Run bootstrap depending on area
    *
    * @deprecated by declaring different bootstrap per area in manifest
    * @param mixed $class
    */
    public static function bootstrapByArea($class)
    {
        switch (BApp::i()->get('area')) {
            case 'FCom_Admin': $class .= '_Admin'; break;
            case 'FCom_Frontend': $class .= '_Frontend'; break;
        }
        $class::bootstrap();
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
}

class FCom_Core_Controller_Abstract extends BActionController
{
    public function beforeDispatch()
    {
        if (BRequest::i()->csrf()) {
            BResponse::i()->status(403, 'Possible CSRF detected', 'Possible CSRF detected');
        }

        if (($root = BLayout::i()->view('root'))) {
            $root->bodyClass = BRequest::i()->path(0, 1);
        }
        return parent::beforeDispatch();
    }

    public function afterDispatch()
    {
        BResponse::i()->render();
    }

    public function layout($name)
    {
        $theme = BConfig::i()->get('modules/'.BApp::i()->get('area').'/theme');
        if (!$theme) {
            $theme = BLayout::i()->getDefaultTheme();
        }
        $layout = BLayout::i();
        if ($theme) {
            $layout->applyTheme($theme);
        }
        foreach ((array)$name as $l) {
            $layout->layout($l);
        }
        return $this;
    }

    public function messages($viewName, $namespace='frontend')
    {
        $this->view($viewName)->messages = BSession::i()->messages($namespace);
        return $this;
    }

    public function action_noroute()
    {
        $this->layout('404');
    }

    public function viewProxy($viewPrefix, $defaultView='index')
    {
        $viewPrefix = trim($viewPrefix, '/').'/';
        $page = BRequest::i()->params('view');
        if (!$page) {
            $page = $defaultView;
        }
        if (!$page || !($view = $this->view($viewPrefix.$page))) {
            $this->forward(true);
            return false;
        }
        $this->layout('base');
        BLayout::i()->applyLayout($viewPrefix.$page);
        $view->render();
        $metaData = $view->param('meta_data');
        if ($metaData && ($head = $this->view('head'))) {
            foreach ($metaData as $k=>$v) {
                $k = strtolower($k);
                switch ($k) {
                case 'title':
                    $head->addTitle($v); break;
                case 'meta_title': case 'meta_description': case 'meta_keywords':
                    $head->meta(str_replace('meta_','',$k), $v); break;
                }
            }
        }
        if (($root = BLayout::i()->view('root'))) {
            $root->addBodyClass('page-'.$page);
        }
        BLayout::i()->hookView('main', $viewPrefix.$page);
        return $page;
    }
}

class FCom_Core_Model_Abstract extends BModel
{

}

class FCom_Core_View_Abstract extends BView
{
    public function messagesHtml($namespace=null)
    {
        $messages = $this->messages;
        if (!$messages && $namespace) {
            $messages = BSession::i()->messages($namespace);
        }
        $html = '';
        if ($messages) {
            $html .= '<ul class="msgs">';
            foreach ($messages as $m) {
                $html .= '<li class="'.$m['type'].'-msg">'.$this->q($m['msg']).'</li>';
            }
            $html .= '</ul>';
        }
        return $html;
    }
}

class FCom_Core_View_Root extends FCom_Core_View_Abstract
{
    protected $_htmlAttr = array('lang'=>'en');

    public function __construct(array $params)
    {
        parent::__construct($params);
        $this->addBodyClass(strtolower(trim(preg_replace('#[^a-z0-9]+#i', '-', BRequest::i()->rawPath()), '-')));
    }

    public function addBodyClass($class)
    {
        $this->body_class = !$this->body_class ? (array)$class
            : array_merge($this->body_class, (array)$class);
        return $this;
    }

    public function getBodyClass()
    {
        return $this->body_class ? join(' ', $this->body_class) : '';
    }

    public function getHtmlAttributes()
    {
        $xmlns = array();
        foreach ($this->_htmlAttr as $a=>$v) {
            $xmlns[] = $a.'="'.$this->q($v).'"';
        }
        return join(' ', $xmlns);
    }

    public function xmlns($ns, $href)
    {
        $this->_htmlAttr['xmlns:'.$ns] = $href;
        return $this;
    }
}

class FCom_Core_View_Head extends BViewHead
{

}
