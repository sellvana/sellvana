<?php

class FCom_Feedback_Controller extends FCom_Core_Controller_Abstract
{
    public function action_index__POST()
    {
        $r = BRequest::i();
        $result = array();
        try {
            $data = BUtil::arrayMask( $r->post( 'feedback' ), 'name,email,comments' );
            $data[ 'url' ] = $r->referrer();
            if ( BConfig::i()->get( 'modules/FCom_Feedback/send_mod_versions' ) ) {
                foreach ( BModuleRegistry::i()->getAllModules() as $modName => $mod ) {
                    if ( $mod->run_status === 'LOADED' ) {
                        $data[ 'mod_versions' ][ $modName ] = array(
                            'version' => $mod->version,
                            'channel' => $mod->channel,
                        );
                    }
                }
            }
            $response = BUtil::remoteHttp( 'POST', 'https://www.sellvana.com/api/v1/feedback', BUtil::toJson( $data ) );
            $result = BUtil::fromJson( $response );
            if ( !$result ) {
                $info = BUtil::lastRemoteHttpInfo();
//echo '<pre>'; var_dump($info); exit;
                throw new Exception( 'Server error (' . $info[ 'headers' ][ 'status' ] . ')' );
            }
        } catch ( Exception $e ) {
            $result[ 'msg' ] = 'Sending Feedback: ' . $e->getMessage();
            $result[ 'error' ] = true;
        }
        if ( $r->xhr() ) {
            BResponse::i()->json( $result );
        } else {
            $status = !empty( $result[ 'error' ] ) ? 'error' : 'success';
            $tag = BApp::i()->get( 'area' ) === 'FCom_Admin' ? 'admin' : 'frontend';
            BSession::i()->addMessage( $result[ 'msg' ], $status, $tag );
            BResponse::i()->redirect( $r->referrer() );
        }
    }
}
