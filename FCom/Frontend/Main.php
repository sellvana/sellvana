<?php

class FCom_Frontend_Main extends BClass
{
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


