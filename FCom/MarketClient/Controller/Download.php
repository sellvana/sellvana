<?php

class FCom_MarketClient_Controller_Download extends FCom_Core_Controller_Abstract
{
    public function action_index__POST()
    {
        #echo 1; exit;
        BLayout::i()->setRootView( 'marketclient/container' );
        $this->view( 'marketclient/container' )->set( [
            'modules' => BRequest::i()->request( 'modules' ),
            'redirect_to' => BRequest::i()->request( 'redirect_to' ),
        ] );
        FCom_MarketClient_Main::i()->progress( [], true );
    }

    public function action_start__POST()
    {
        BResponse::i()->startLongResponse( false );
        ignore_user_abort();

        $modules = BRequest::i()->post( 'modules' );
        $force = BRequest::i()->post( 'force' );

        FCom_MarketClient_Main::i()->downloadAndInstall( $modules, $force );
    }

    public function action_stop__POST()
    {
        FCom_MarketClient_Main::i()->stopDownloading();
    }

    public function action_progress()
    {
        if ( !BRequest::i()->xhr() ) {
            BResponse::i()->status( 403 );
        }

        $progress = FCom_MarketClient_Main::i()->progress();
        BResponse::i()->json( [
            'progress' => $progress,
            'html' => (string)$this->view( 'marketclient/progress' )->set( 'progress', $progress ),
        ] );
    }
}
