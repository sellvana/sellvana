<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_LibMarkdown_Main extends BClass
{
    static protected $_parser;

    protected static $_cacheDir;

    public function bootstrap()
    {
        $this->BLayout->addRenderer('FCom_LibMarkdown', [
            'description' => 'Markdown Extra',
            'callback' => 'FCom_LibMarkdown_Main::renderer',
            'file_ext' => ['.md'],
        ]);
    }

    public function parser()
    {
        if (!static::$_parser) {
            require_once __DIR__ . '/lib/markdown.php';
            static::$_parser = new MarkdownExtra_Parser;
            static::$_cacheDir = $this->BConfig->get('fs/cache_dir') . '/markdown';
            $this->BUtil->ensureDir(static::$_cacheDir);
        }
        return static::$_parser;
    }

    public function renderer($view)
    {
        $viewName = $view->param('view_name');
        $pId = $this->BDebug->debug('BMarkdown render: ' . $viewName);
        $parser = $this->parser();

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
            $output = $parser->transform($source);
            file_put_contents($cacheFilename, $output);
        } else {
            $output = file_get_contents($cacheFilename);
        }
        $this->BDebug->profile($pId);

        return $output;
    }
}
