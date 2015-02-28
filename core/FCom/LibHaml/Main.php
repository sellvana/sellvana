<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * HAML template format integration
 *
 * Had to make some changes to library:
 * - Disable HTML escaping within filters (javascript/css/plain)
 *   - MtHaml/NodeVisitor/RendererAbstract.php - lines 300-350
 *   - MtHaml/NodeVisitor/PhpRenderer.php - line 57
 *
 * @uses https://github.com/arnaud-lb/MtHaml
 *
 */
class FCom_LibHaml_Main extends BClass
{
    protected static $_haml;

    protected static $_cacheDir;

    public function bootstrap()
    {
        $this->BLayout->addRenderer('FCom_LibHaml', [
            'description' => 'HAML',
            'callback' => 'FCom_LibHaml_Main::renderer',
            'file_ext' => ['.haml'],
        ]);
    }

    /**
     * @return MtHaml\Environment
     */
    public function haml()
    {
        if (!static::$_haml) {
            $this->BApp->m('FCom_LibHaml')->autoload('lib');

            $c = $this->BConfig;
            $options = (array)$c->get('modules/FCom_LibHaml/haml');
            static::$_haml = new MtHaml\Environment('php', $options);
            static::$_cacheDir = $c->get('fs/cache_dir') . '/haml';
            $this->BUtil->ensureDir(static::$_cacheDir);
        }
        return static::$_haml;
    }

    public function renderer($view)
    {
        $viewName = $view->param('view_name');
        $pId = $this->BDebug->debug('FCom_LibHaml render: ' . $viewName);
        $haml = $this->haml();

        $source = $view->getParam('source');
        if ($source) {
            $sourceFile = $view->getParam('source_name');
            $md5 = md5($source);
            $mtime = $view->getParam('source_mtime');
        } else {
            $sourceFile = $view->getTemplateFileName();
            $md5 = md5($sourceFile);
            $mtime = filemtime($sourceFile);
        }

        $cacheDir = static::$_cacheDir . '/' . substr($md5, 0, 2);
        $cacheFilename = $cacheDir . '/.' . $md5 . '.php.cache'; // to help preventing direct php run
        if (!file_exists($cacheFilename) || $mtime > filemtime($cacheFilename)) {
            $this->BUtil->ensureDir($cacheDir);
            if (!$source) {
                $source = file_get_contents($sourceFile);
            }
            file_put_contents($cacheFilename, $haml->compileString($source, $sourceFile));
        }
        if ($view->getParam('source_untrusted')) {
            $output = file_get_contents($cacheFilename);
        } else {
            $output = $view->renderFile($cacheFilename);
        }
        $this->BDebug->profile($pId);
        return $output;
    }
}

