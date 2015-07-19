<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_LibLessPhp_Main extends BClass
{
    public function bootstrap()
    {
        /** @var BViewHead $head */
        $head = $this->BLayout->view('head');
        $head->addDefaultTag('less', [$this, 'tagCallback']);
    }

    public function tagCallback($args)
    {
        include_once __DIR__ . '/lib/lessc.inc.php';

        $origFilepath = $args['file'];
        if (preg_match('#^@([^/]+)(.*)$#', $origFilepath, $m)) {
            $mod = $this->BModuleRegistry->module($m[1]);
            if (!$mod) {
                BDebug::notice('Module not found: ' . $origFilepath);
                return '';
            }
            $origFilepath = $this->BModuleRegistry->module($m[1])->root_dir . $m[2];
        }
        if (!file_exists($origFilepath)) {
            $this->BDebug->warning('Original LESS file does not exist: ' . $origFilepath);
            return '';
        }

        /*
        $targetFilename = $this->BUtil->simplifyString(str_replace(FULLERON_ROOT_DIR, '', $origFilepath)) . '.css';
        $compiledPath = $this->BConfig->get('fs/media_dir') . '/less_build';
        $webFile = $this->BApp->src($this->BConfig->get('web/media_dir') . '/less_build/' . $targetFilename);
        $compiledFilename = $compiledPath . '/' . $targetFilename;
        */
        $compiledFilename = preg_replace('#\.less$#', '.build.css', $origFilepath);
        $webFile = preg_replace('#\.less$#', '.build.css', $this->BApp->src($args['file']));

        #$this->BUtil->ensureDir(dirname($compiledFilename)); // neeeded if target dir is different
        $lessc = new lessc();
        try {
            $lessc->checkedCompile($origFilepath, $compiledFilename);
        } catch (Exception $e) {
            $this->BDebug->error('Error while compiling LESS file (' . $origFilepath . '): ' . $e->getMessage());
        }

        $url = htmlspecialchars($webFile);
        $params = !empty($args['params']) ? $args['params'] : '';
        $tagHtml = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$url}\" {$params}/>";
        return $tagHtml;
    }
}