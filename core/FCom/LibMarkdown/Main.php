<?php

/**
 * Class FCom_LibMarkdown_Main
 * 
 * @property FCom_LibTwig_Main $FCom_LibTwig_Main
 */
class FCom_LibMarkdown_Main extends BClass
{
    /**
     * @var \Michelf\MarkdownExtra
     */
    static protected $_parser;

    static protected $_cacheDir;
    
    static protected $_isTwigEnabled = false;

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
            #require_once __DIR__ . '/lib/markdown.php';
            #static::$_parser = new MarkdownExtra_Parser;

            require_once __DIR__ . '/lib/Michelf/MarkdownExtra.inc.php';
            static::$_parser = new \Michelf\MarkdownExtra;
            static::$_cacheDir = $this->BConfig->get('fs/cache_dir') . '/markdown';
            $this->BUtil->ensureDir(static::$_cacheDir);
            
            if ($this->BModuleRegistry->isLoaded('FCom_LibTwig')) {
                static::$_isTwigEnabled = true;
                $this->FCom_LibTwig_Main->addPath(static::$_cacheDir, 'markdown');
            }
        }
        return static::$_parser;
    }

    /**
     * @param BView $view
     * @return bool|mixed|null|string
     */
    public function renderer(BView $view)
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
        $cacheFilename = $cacheDir . '/.' . $md5 . '.php';
        $output = null;
        if (!file_exists($cacheFilename) || $mtime > filemtime($cacheFilename)) {
            $this->BUtil->ensureDir($cacheDir);
            if (!$source) {
                $source = file_get_contents($sourceFile);
            }
            $output = static::$_parser->transform($source);
            file_put_contents($cacheFilename, $output);
        }
        if (static::$_isTwigEnabled) {
            $file = '@markdown/' . substr($md5, 0, 2) . '/.' . $md5 . '.php';
            $output = $this->FCom_LibTwig_Main->renderFile($file, $view->getAllArgs());
        } else {
            $output = file_get_contents($cacheFilename);
            $output = preg_replace_callback('#\{\{\s*([a-zA-Z0-9_]+)\s*\}\}#', function($m) use ($view) {
                return $view->get($m[1]);
            }, $output);
        }

        $this->BDebug->profile($pId);

        return $output;
    }
}
