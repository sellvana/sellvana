<?php

class FCom_LibTwig_Main extends BClass
{
    protected static $_cacheDir;

    protected static $_fcomVars;

    protected static $_fileLoader;
    protected static $_fileTwig;

    protected static $_stringLoader;
    protected static $_stringTwig;

    public function bootstrap()
    {
        $this->BLayout->addRenderer('FCom_LibTwig', [
            'description' => 'Twig (HTML)',
            'callback'    => 'FCom_LibTwig_Main::renderer',
            'file_ext'    => ['.html.twig', '.twig.html'],
            'editor'      => 'html',
        ]);
    }

    public function init($path = null)
    {
        $this->BClassAutoload->addPath(__DIR__ . '/lib');
        /*
        require_once __DIR__.'/lib/Twig/Autoloader.php';
        Twig_Autoloader::register();
        */

        $config = $this->BConfig;

        static::$_cacheDir = $config->get('fs/cache_dir') . '/twig';
        $this->BUtil->ensureDir(static::$_cacheDir);
        $cacheConfig = $this->BConfig->get('core/cache/twig');
        $useCache = !$cacheConfig && $this->BDebug->is(['DEBUG', 'DEVELOPMENT']) || $cacheConfig === 'enable';
        $options = [
            'cache' => $useCache ? static::$_cacheDir : false,
            'debug' => $this->BDebug->is(['DEBUG']), #$config->get('modules/FCom_LibTwig/debug'),
            'auto_reload' => true, #$useCache ? false : true, #$config->get('modules/FCom_LibTwig/auto_reload'),
            'optimizations' => -1,
        ];

        static::$_fileLoader = new Twig_Loader_Filesystem($path); //TODO: possible not to add path?
        static::$_fileTwig = new Twig_Environment(static::$_fileLoader, $options);

        static::$_stringLoader = new Twig_Loader_String();
        static::$_stringTwig = new Twig_Environment(static::$_stringLoader, $options);

        if (!empty($options['debug'])) {
            static::$_fileTwig->addExtension(new Twig_Extension_Debug());
            static::$_stringTwig->addExtension(new Twig_Extension_Debug());
        }

        foreach ([
            '_' => [$this->BLocale, '_'],
            'currency' => [$this->BLocale, 'currency'],
            'safehtml' => [$this->BResponse, 'safeHtml'],
            'min' => 'min',
            'max' => 'max',
            'floor' => 'floor',
            'debug' => function($v) { echo "<pre>"; print_r($v); echo "</pre>"; },
        ] as $filterName => $filterCallback) {
            $filter = new Twig_SimpleFilter($filterName, $filterCallback);
            static::$_fileTwig->addFilter($filter);
            static::$_stringTwig->addFilter($filter);
        }

        foreach ([
            'APP' => 'BApp',
            'CONFIG' => 'BConfig',
            'LAYOUT' => 'BLayout',
            'REQUEST' => 'BRequest',
            'SESSION' => 'BSession',
            'UTIL' => 'BUtil',
            'DEBUG' => 'BDebug',
            'MODULES' => 'BModuleRegistry',
            'LOCALE' => 'BLocale'
        ] as $global => $class) {
            $instance = $class::i();
            static::$_fileTwig->addGlobal($global, $instance);
            static::$_stringTwig->addGlobal($global, $instance);
        }

        $this->BEvents->fire(__METHOD__, [
            'options' => $options,
            'file_adapter' => static::$_fileTwig,
            'string_adapter' => static::$_stringTwig,
        ]);
    }

    public function onLayoutAddAllViews($args)
    {
        $moduleName = is_string($args['module']) ? $args['module'] :
            (is_object($args['module']) ? $args['module']->name : null);
        $this->addPath($args['root_dir'], $moduleName);
    }

    public function addPath($path, $namespace)
    {
        if (!static::$_fileLoader) {
            $this->init($path);
        }
        static::$_fileLoader->prependPath($path, $namespace);
    }

    public function renderer($view)
    {
        $viewName = $view->getParam('view_name');

        $pId = $this->BDebug->debug('FCom_LibTwig render: ' . $viewName);

        $source = $view->getParam('source');
        $args = $view->getAllArgs();
        //TODO: add BRequest and BLayout vars?
        $args['THIS'] = $view;

        if (!$source) {

            $template = static::$_fileTwig->loadTemplate($view->twigName());
            $output = $template->render($args);

        } else {

            $output = static::$_stringTwig->render($source, $args);

        }

        $this->BDebug->profile($pId);
        return $output;
    }
}
