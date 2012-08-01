<?php

class FCom_MarketServer_Frontend extends BClass
{
    public static function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /market', 'FCom_MarketServer_Frontend_Controller.market')

            ->route( 'GET /market/account', 'FCom_MarketServer_Frontend_Controller_Account.index')
            ->route( 'GET|POST /market/account/.action', 'FCom_MarketServer_Frontend_Controller_Account')
        ;

        BLayout::i()->addAllViews('Frontend/views');

        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_MarketServer_Frontend::layout');
    }

    static public function layout()
    {
        return BLayout::i()->layout(array());
    }
}