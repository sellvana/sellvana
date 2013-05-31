<?php

class FCom_Frontend_Main extends BClass
{
    static public function bootstrap()
    {
        if (BRequest::i()->https()) {
            BResponse::i()->httpSTS();
        }

        if (BDebug::is('RECOVERY,MIGRATION')) {
            BLayout::i()->setRootView('under_construction');
            BResponse::i()->render();
        }
    }


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
}


