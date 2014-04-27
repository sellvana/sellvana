<?php

/**
 * Not sure yet if to allow SSO in emailed links
 *
 * @property int id
 * @property int user_id
 * @property varchar(20) nonce
 * @property datetime create_at
 */
class FCom_Admin_Model_UserNonce extends FCom_Core_Model_Abstract
{
    static protected $_origClass = __CLASS__;
    static protected $_table = 'fcom_admin_user_nonce';

    static public function generateNonce( $userId )
    {
        for ( $i = 0; $i < 10; $i++ ) {
            $nonce = BUtil::randomString( 20 );
            if ( !static::load( $nonce, 'nonce' ) ) {
                break;
            }
        }
        if ( $i === 10 ) {
            throw new BException( 'Unable to find available nonce' ); //???
        }
        $nonceRecord = static::create( array(
            'user_id' => $userId,
            'nonce' => $nonce,
            'create_at' => BDb::now(),
        ) )->save();
        return $nonce;
    }

    static public function login( $nonce )
    {
        $user = false;
        $userHlp = FCom_Admin_Model_User::i();
        if ( $userHlp->isLoggedIn() ) {
            $user = $userHlp->sessionUser();
        }
        if ( $nonce ) {
            $nonceRecord = static::load( $nonce, 'nonce' );
            if ( $nonceRecord ) {
                if ( !$user ) {
                    $user = $userHlp->load( $nonceRecord->user_id )->login();
                }
                $nonceRecord->delete();
            }
        }
        return $user;
    }

    static public function gc()
    {
        static::delete_many( 'create_at < ' . date( 'Y-m-d H:i:s', time()-60 * 60 * 24 * 7 ) );
    }
}
