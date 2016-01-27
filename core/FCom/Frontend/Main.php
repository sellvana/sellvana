<?php

class FCom_Frontend_Main extends BClass
{
    protected $_layout;

    public function getLayout()
    {
        if (empty($this->_layout)) {
#echo "<pre>"; print_r($this->BLayout->view('root')); echo "</pre>";
            //TODO: permanent solution to Twig namespaces conflict between Frontend and Admin areas
            $this->BEvents->off('BLayout::addAllViewsDir', 'FCom_LibTwig_Main.onLayoutAddAllViews');
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
                    $this->_layout->addAllViewsDir($mod->root_dir . '/views', '', $mod);
                }
                if (is_dir($mod->root_dir . '/Frontend/views')) {
                    $this->_layout->addAllViewsDir($mod->root_dir . '/Frontend/views', '', $mod);
                }
            }
            $this->_layout->collectAllViewsFiles('FCom_Frontend');
        }
#echo "<pre>"; print_r($this->BLayout->view('root')->param()); exit;
        return $this->_layout;
    }

    /**
     * @param string $url
     * @return string
     */
    public function adminHref($url = '')
    {
        return $this->BApp->adminHref($url);
    }

    /**
     * @param string $url
     * @return string
     */
    public function href($url = '')
    {
        return $this->BApp->frontendHref($url);
    }

    public function onFetchLibrary($args)
    {

    }
}


