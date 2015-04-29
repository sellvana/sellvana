<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_LibScssPhp_Main extends BClass
{
    public function bootstrap()
    {
        /** @var BViewHead $head */
        $head = $this->BLayout->view('head');
        $head->addDefaultTag('scss', [$this, 'tagCallback']);
    }

    public function tagCallback($args)
    {
        include_once __DIR__ . '/lib/scss.inc.php';

        $origFilename = $args['file'];
        if (preg_match('#^@([^/]+)(.*)$#', $origFilename, $m)) {
            $mod = $this->BModuleRegistry->module($m[1]);
            if (!$mod) {
                BDebug::notice('Module not found: ' . $origFilename);
                return '';
            }
            $origFilename = $this->BModuleRegistry->module($m[1])->root_dir . $m[2];
        }
        if (!file_exists($origFilename)) {
            $this->BDebug->warning('Original SCSS file does not exist: ' . $origFilename);
            return '';
        }

        $targetFilename = $this->BUtil->simplifyString(str_replace(FULLERON_ROOT_DIR, '', $origFilename)) . '.css';
        $compiledPath = $this->BConfig->get('fs/media_dir') . '/scss_build';
        $compiledFilename = $compiledPath . '/' . $targetFilename;

        $compile = true;
        if (file_exists($compiledFilename) && filemtime($compiledFilename) >= filemtime($origFilename)) {
            $compile = false;
        }

        if ($compile) {
            $this->BUtil->ensureDir($compiledPath);
            $scss = new Leafo\ScssPhp\Compiler();
            if (!empty($args['import'])) {
                foreach ((array)$args['import'] as $import) {
                    $scss->addImportPath($import);
                }
            }
            $formatter = !empty($args['formatter']) ? $args['formatter'] : 'compressed';
            $scss->setFormatter('Leafo\\ScssPhp\\Formatter\\' . ucfirst($formatter));
            $source = file_get_contents($origFilename);
            $output = $scss->compile($source, 'SOURCE');
            file_put_contents($compiledFilename, $output);
        }

        $webFile = $this->BApp->src($this->BConfig->get('web/media_dir') . '/scss_build/' . $targetFilename);
        $url = htmlspecialchars($webFile);
        $params = !empty($args['params']) ? $args['params'] : '';
        $tagHtml = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$url}\" {$params}/>";
        return $tagHtml;
    }
}