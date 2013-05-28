<?php

class BHAML extends BClass
{
    protected static $_defaultFileExt = '.haml';

    protected static $_haml;

    protected static $_cacheDir;

    static public function bootstrap()
    {
        BLayout::i()->addExtRenderer(static::$_defaultFileExt, 'BHAML::renderer');
    }

    static public function haml()
    {
        if (!static::$_haml) {
            BApp::m('BHAML')->autoload(__DIR__.'/lib');

            $c = BConfig::i();
//$p = BDebug::debug('********** 1');
            $options = (array)$c->get('modules/BHAML/haml');
            static::$_haml = new MtHaml\Environment('php', $options);
//BDebug::profile($p);
            static::$_cacheDir = $c->get('fs/cache_dir').'/haml';
            BUtil::ensureDir(static::$_cacheDir);
        }
        return static::$_haml;
    }

    static public function renderer($view)
    {
        $viewName = $view->param('view_name');
        $pId = BDebug::debug('BHAML render: '.$viewName);
        $haml = static::haml();
        $sourceFile = $view->getTemplateFileName(static::$_defaultFileExt);
        //return $view->renderEval('?'.'>'.$haml->haml2PHP($sourceFile));

        $md5 = md5($sourceFile);
        $cacheDir = static::$_cacheDir.'/'.substr($md5, 0, 2);
        $cacheFilename = $cacheDir.'/'.$md5.'.php';
        if (!file_exists($cacheFilename) || filemtime($sourceFile) > filemtime($cacheFilename)) {
            BUtil::ensureDir($cacheDir);
            file_put_contents($cacheFilename, $haml->compileString(file_get_contents($sourceFile), $viewName));
        }
        $output = $view->renderFile($cacheFilename);
        BDebug::profile($pId);
        return $output;
    }
}
/*
class HamlTrFilter extends HamlBaseFilter
{
    public function run($text)
    {
        $text = str_replace('"', '\\"', (trim($text)));
        $args = array();
        $text = preg_replace_callback(HamlParser::MATCH_INTERPOLATION, function($matches) use(&$args) {
            $args[] = stripslashes(trim($matches[1]));
            return '%s';
        }, $text);
        return '<'.'?php echo BLocale::i()->_("'.$text.'"'.($args ? ', array('.join(', ', $args).')' : '').') ?'.'>';
    }
}
*/