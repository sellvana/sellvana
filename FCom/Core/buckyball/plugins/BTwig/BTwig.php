<?php

class BTwig extends BClass
{
    protected static $_cacheDir;

    protected static $_fcomVars;

    protected static $_fileLoader;
    protected static $_fileTwig;

    protected static $_stringLoader;
    protected static $_stringTwig;

    public static function bootstrap()
    {
        BLayout::i()->addRenderer('BTwig', array(
            'description' => 'Twig (HTML)',
            'callback'    => 'BTwig::renderer',
            'file_ext'    => array('.html.twig', '.twig.html'),
            'editor'      => 'html',
        ));

        BEvents::i()
            ->on('BLayout::addAllViews', 'BTwig::onLayoutAddAllViews')
            ->on('BLayout::addAllViewsDir', 'BTwig::onLayoutAddAllViews')
        ;
    }

    public static function init($path=null)
    {
        BClassAutoload::i(true, array('root_dir' => __DIR__.'/lib'));
        /*
        require_once __DIR__.'/lib/Twig/Autoloader.php';
        Twig_Autoloader::register();
        */

        $config = BConfig::i();

        static::$_cacheDir = $config->get('fs/cache_dir').'/twig';
        BUtil::ensureDir(static::$_cacheDir);
        $cacheConfig = BConfig::i()->get('core/cache/twig');
        $useCache = !$cacheConfig && BDebug::is('DEBUG,DEVELOPMENT') || $cacheConfig === 'enable';
        $options = array(
            'cache' => $useCache ? static::$_cacheDir : false,
            'debug' => false,#$config->get('modules/BTwig/debug'),
            'auto_reload' => true, #$useCache ? false : true, #$config->get('modules/BTwig/auto_reload'),
            'optimizations' => -1,
        );

        static::$_fileLoader = new Twig_Loader_Filesystem($path); //TODO: possible not to add path?
        static::$_fileTwig = new Twig_Environment(static::$_fileLoader, $options);

        static::$_stringLoader = new Twig_Loader_String();
        static::$_stringTwig = new Twig_Environment(static::$_stringLoader, $options);

        if (!empty($options['debug'])) {
            static::$_fileTwig->addExtension(new Twig_Extension_Debug());
            static::$_stringTwig->addExtension(new Twig_Extension_Debug());
        }

        foreach (array(
            '_' => 'BLocale::_',
            'currency' => 'BLocale::currency',
            'min' => 'min',
            'max' => 'max',
            'floor' => 'floor',
            'debug' => function($v) { echo "<pre>"; print_r($v); echo "</pre>"; },
        ) as $filterName => $filterCallback) {
            $filter = new Twig_SimpleFilter($filterName, $filterCallback);
            static::$_fileTwig->addFilter($filter);
            static::$_stringTwig->addFilter($filter);
        }

        foreach (array(
            'APP' => 'BApp',
            'CONFIG' => 'BConfig',
            'LAYOUT' => 'BLayout',
            'REQUEST' => 'BRequest',
            'SESSION' => 'BSession',
            'UTIL' => 'BUtil',
            'DEBUG' => 'BDebug',
            'MODULES' => 'BModuleRegistry',
            'LOCALE' => 'BLocale'
        ) as $global => $class) {
            $instance = $class::i();
            static::$_fileTwig->addGlobal($global, $instance);
            static::$_stringTwig->addGlobal($global, $instance);
        }

        BEvents::i()->fire(__METHOD__, array(
            'options' => $options,
            'file_adapter' => static::$_fileTwig,
            'string_adapter' => static::$_stringTwig,
        ));
    }

    public static function onLayoutAddAllViews($args)
    {
        $moduleName = is_string($args['module']) ? $args['module'] :
            (is_object($args['module']) ? $args['module']->name : null);
        static::addPath($args['root_dir'], $moduleName);
    }

    public static function addPath($path, $namespace)
    {
        if (!static::$_fileLoader) {
            static::init($path);
        }
        static::$_fileLoader->prependPath($path, $namespace);
    }

    public static function renderer($view)
    {
        $viewName = $view->getParam('view_name');

        $pId = BDebug::debug('BTwig render: '.$viewName);

        $source = $view->getParam('source');
        $args = $view->getAllArgs();
        //TODO: add BRequest and BLayout vars?
        $args['THIS'] = $view;

        if (!$source) {

            //$filename = $view->getTemplateFileName(static::$_defaultFileExt);
            $modName = $view->getParam('module_name');
            $template = static::$_fileTwig->loadTemplate('@'.$modName.'/'.$viewName . $view->getParam('file_ext'));
            $output = $template->render($args);

        } else {

            $output = static::$_stringTwig->render($source, $args);

        }

        BDebug::profile($pId);
        return $output;
    }
}
