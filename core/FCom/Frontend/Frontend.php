<?php

class FCom_Frontend_Frontend extends BClass
{
    public function bootstrap()
    {
        if ($this->BConfig->get('modules/FCom_Admin/web/cors_enable')) {
            $this->BResponse->cors();
        }
        if ($this->BConfig->get('modules/FCom_Admin/web/csp_enable')) {
            $this->BResponse->csp();
        }
        if ($this->BRequest->https() && $this->BConfig->get('modules/FCom_Admin/web/hsts_enable')) {
            $this->BResponse->httpSTS();
        }

        if ($this->BDebug->is(['RECOVERY', 'MIGRATION'])) {
            $this->BLayout->setRootView('under_construction');
            $this->BResponse->render();
        }

        $this->BSession->set('current_url', $this->BRequest->currentUrl());
    }

    public function layout()
    {
        /** @var FCom_Core_View_Head $head */
        $head = $this->BLayout->getView('head');
        /** @var FCom_Core_View_Text $script */
        $script = $this->BLayout->getView('head_script');
        /** @var FCom_Core_View_Text $css */
        $css = $this->BLayout->getView('head_css');

        $text = '
FCom.Frontend = {}
        ';
        $head->js_raw('frontend_init', $text);
        $script->addText('FCom_Frontend:init', $text);

        $config = $this->BConfig->get('modules/FCom_Frontend');
        if (!empty($config['add_js_files'])) {
            foreach (explode("\n", $config['add_js_files']) as $js) {
                $head->js(trim($js));
            }
        }
        if (!empty($config['add_js_code'])) {
            $script->addText('FCom_Frontend:add_js', $config['add_js_code']);
            $head->js_raw('add_js_code', $config['add_js_code']);
        }
        if (!empty($config['add_css_files'])) {
            foreach (explode("\n", $config['add_css_files']) as $css) {
                $head->css(trim($css));
            }
        }
        if (!empty($config['add_css_style'])) {
            $css->addText('FCom_Frontend:add_css', $config['add_css_style']);
            $head->css_raw('add_css_style', $config['add_css_style']);
        }
    }
}
