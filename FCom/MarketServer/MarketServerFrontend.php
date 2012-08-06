<?php

class FCom_MarketServer_Frontend extends BClass
{
    public static function bootstrap()
    {
        
        BFrontController::i()
            ->route( 'GET /market', 'FCom_MarketServer_Frontend_Controller.market')
            ->route( 'GET /market/view', 'FCom_MarketServer_Frontend_Controller.view')
            ->route( 'GET /marketserver/modules', 'FCom_MarketServer_Frontend_Controller.modules')
            ->route( 'GET /marketserver/download', 'FCom_MarketServer_Frontend_Controller.downlaod')


            ->route( 'GET /market/account', 'FCom_MarketServer_Frontend_Controller_Account.index')
            ->route( 'POST /market/account', 'FCom_MarketServer_Frontend_Controller_Account.index__POST')
            ->route( 'GET|POST /market/account/.action', 'FCom_MarketServer_Frontend_Controller_Account')
        ;

        BLayout::i()->addAllViews('Frontend/views');

        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_MarketServer_Frontend::layout');
    }

    static public function layout()
    {
        return BLayout::i()->layout(array(
             '/market/list'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('market/list')),
            ),
             '/market/view'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('market/view')),
            ),
            '/market/account'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('market/account')),
            ),
        ));
    }
}