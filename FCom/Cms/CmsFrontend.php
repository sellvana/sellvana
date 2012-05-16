<?php

class FCom_Cms_Frontend extends BClass
{
    public static function bootstrap()
    {
        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_Cms_Frontend::layout')
        ;

        $config = BConfig::i()->get('modules/FCom_Cms');
        if (!empty($config['page_enable'])) {
            $prefix = !empty($config['page_url_prefix']) ? $config['page_url_prefix'].'/' : '';
            BFrontController::i()->route('GET /'.$prefix.'*page', 'FCom_Cms_Frontend_Controller.page');
        }
        if (!empty($config['nav_enable'])) {
            $prefix = !empty($config['nav_url_prefix']) ? $config['nav_url_prefix'].'/' : '';
            BFrontController::i()->route('GET /'.$prefix.'*nav', 'FCom_Cms_Frontend_Controller.nav');
        }

        //BLayout::i()->addAllViews('Frontend/views');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            '/cms'=>array(

            ),
        ));
    }
}
