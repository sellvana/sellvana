<?php

class FCom_MarketClient_Admin extends BClass
{
    static public function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission( array(
            'market_client' => 'Market Client',
            'market_client/public' => 'Public',
            'market_client/remote' => 'Remote',
        ) );
    }

    public static function onModulesGridViewBefore( $args )
    {
        $view = $args[ 'page_view' ];
        $actions = (array)$view->get( 'actions' );
        $actions += array(
            'check_updates' => '<button class="btn btn-primary" type="button" onclick="$(\'#util-form\').attr(\'action\', \'' . BApp::href( 'marketclient/site/check_updates?install=true' ) . '\').submit()"><span>' . BLocale::_( 'Check For Updates' ) . '</span></button>',
        );
        $view->set( 'actions', $actions );
    }

    public static function onModulesGridView( $args )
    {
        $grid = $args[ 'view' ]->get( 'grid' );

        $grid[ 'config' ][ 'columns' ] = BUtil::arrayInsert( $grid[ 'config' ][ 'columns' ], array(
            array( 'name' => 'market_version', 'label' => 'Available', 'width' => 80, 'overflow' => true ),
        ), 'arr.before.name==version' );

        try {
            $marketModulesData = FCom_MarketClient_RemoteApi::i()->getModulesVersions( true );
            $preferData = BConfig::i()->get( 'modules/FCom_MarketClient/prefer' );

            foreach ( $grid[ 'config' ][ 'data' ] as &$mod ) {
                if ( empty( $marketModulesData[ $mod[ 'name' ] ] ) ) {
                    continue;
                }
                $rem = $marketModulesData[ $mod[ 'name' ] ];
                $channels = $rem[ 'channels' ];
                #$channels = $rem->getData('channels');
                if ( !empty( $channels[ $mod[ 'channel' ] ] ) ) {
                    $channel = $mod[ 'channel' ];
                    $version = $channels[ $mod[ 'channel' ] ][ 'version_uploaded' ];
                } else {
                    #$channel = $rem->channel;
                    #$version = $rem->version;
                    $channel = $rem[ 'channel' ];
                    $version = $rem[ 'version' ];
                }
                if ( $version ) {
                    $mod[ 'market_version' ] = $version . ( $channel !== $mod[ 'channel' ] ? ( ' @ ' . $channel ) : '' );
                }
                $mod[ 'prefer_channel' ] = !empty( $prefer[ $mod[ 'name' ] ][ 'channel' ] ) ? $prefer[ $mod[ 'name' ] ][ 'channel' ] : null;
            }
        } catch ( Exception $e ) {
            BDebug::debug( 'ERROR: Could not retrieve Market updates' );
        }

        $args[ 'view' ]->set( 'grid', $grid );
    }
}
