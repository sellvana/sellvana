<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Frontend_Main extends BClass
{
    protected $_layout;

    public function getLayout()
    {
        if (empty($this->_layout)) {
            $this->_layout = $this->BLayout->i(true);

            $modules = $this->BModuleRegistry->getAllModules();
            foreach ($modules as $mod) {
                $autoUse = !empty($mod->auto_use) ? array_flip((array)$mod->auto_use) : [];
                $frontendAutoUse = !empty($mod->areas['FCom_Frontend']['auto_use'])
                    ? array_flip((array)$mod->areas['FCom_Frontend']['auto_use'])
                    : [];
                if (empty($autoUse['views']) && empty($frontendAutoUse['views'])) {
                    continue;
                }
                if (is_dir($mod->root_dir . '/views')) {
                    $this->_layout->addAllViewsDir($mod->root_dir . '/views');
                }
                if (is_dir($mod->root_dir . '/Frontend/views')) {
                    $this->_layout->addAllViewsDir($mod->root_dir . '/Frontend/views');
                }
            }
            $this->_layout->collectAllViewsFiles('FCom_Frontend');
        }
        return $this->_layout;
    }

    public function adminHref($url = '')
    {
        return $this->BApp->adminHref($url);
    }

    public function href($url = '')
    {
        return $this->BApp->frontendHref($url);
    }
}


