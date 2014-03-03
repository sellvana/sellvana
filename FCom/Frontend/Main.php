<?php

class FCom_Frontend_Main extends BClass
{
    protected $_layout;

    public static function adminHref($url='')
    {
        $href = BConfig::i()->get('web/base_admin');
        if (!$href) {
            $href = BApp::baseUrl(true) . '/admin';
        }
        return trim($href.'/'.ltrim($url, '/'), '/');
    }

    public static function href($url='')
    {
        $r = BRequest::i();
        $href = $r->scheme().'://'.$r->httpHost().BConfig::i()->get('web/base_store');
        return trim(rtrim($href, '/').'/'.ltrim($url, '/'), '/');
    }

    public function getLayout()
    {
        if (empty($this->_layout)) {
            $this->_layout = BLayout::i(true);

            $modules = BModuleRegistry::i()->getAllModules();
            foreach ($modules as $mod) {
                $autoUse = !empty($mod->auto_use) ? array_flip((array)$mod->auto_use) : array();
                $frontendAutoUse = !empty($mod->areas['FCom_Frontend']['auto_use'])
                    ? array_flip((array)$mod->areas['FCom_Frontend']['auto_use'])
                    : array();
                if (empty($autoUse['views']) && empty($frontendAutoUse['views'])) {
                    continue;
                }
                if (is_dir($mod->root_dir.'/views')) {
                    $this->_layout->addAllViewsDir($mod->root_dir.'/views');
                }
                if (is_dir($mod->root_dir.'/Frontend/views')) {
                    $this->_layout->addAllViewsDir($mod->root_dir.'/Frontend/views');
                }
            }
            $this->_layout->collectAllViewsFiles('FCom_Frontend');
        }
        return $this->_layout;
    }
}


