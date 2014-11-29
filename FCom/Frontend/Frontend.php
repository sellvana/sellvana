<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Frontend_Frontend extends BClass
{
    public function bootstrap()
    {
        if ($this->BRequest->https()) {
            $this->BResponse->httpSTS();
        }

        if ($this->BDebug->is('RECOVERY,MIGRATION')) {
            $this->BLayout->setRootView('under_construction');
            $this->BResponse->render();
        }
    }

    public function layout($args)
    {
        if (($head = $this->BLayout->view('head'))) {
            /** @type FCom_Core_View_Head $head */
            $head->js_raw('frontend_init', '
FCom.Frontend = {}
            ');
            $config = $this->BConfig->get('modules/FCom_Frontend');
            if (!empty($config['add_js_files'])) {
                foreach (explode("\n", $config['add_js_files']) as $js) {
                    $head->js(trim($js));
                }
            }
            if (!empty($config['add_js_code'])) {
                $head->js_raw('add_js_code', $config['add_js_code']);
            }
            if (!empty($config['add_css_files'])) {
                foreach (explode("\n", $config['add_css_files']) as $css) {
                    $head->css(trim($css));
                }
            }
            if (!empty($config['add_css_style'])) {
                $head->css_raw('add_css_style', $config['add_css_style']);
            }
        }
    }
}
