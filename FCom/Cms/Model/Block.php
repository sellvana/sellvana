<?php

class FCom_Cms_Model_Block extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_block';
    protected static $_origClass = __CLASS__;

    protected static $_validationRules = array(
        array( 'handle', '@required' ),
        /*array('version', '@required'),*/

        array( 'version', '@integer' ),
        array( 'page_enabled', '@integer' ),
        array( 'page_url', 'FCom_Cms_Model_Block::rulePageUrlUnique', 'Duplicate URL Key' ),
    );

    public function validateBlock()
    {
        return true;
    }

    public function onAfterCreate()
    {
        parent::onAfterCreate();
        $this->set( 'renderer', 'FCom_LibTwig' );
    }

    public function onBeforeSave()
    {
        if ( !parent::onBeforeSave() ) return false;

        if ( !$this->is_dirty() ) {
            return false;
        }

        if ( !$this->get( 'create_at' ) ) {
            $this->set( 'create_at', BDb::now() );
        }
        $this->set( 'version', $this->version ? $this->version + 1 : '1' );
//        $this->set('version_comments', $this->version ? $this->version : '1');
        $this->set( 'update_at', BDb::now() );
        $this->set( 'modified_time', time() ); // attempt to compare with filemtime() for caching

        if ( $this->get( 'page_url' ) === '' ) {
            $this->set( 'page_url', null );
        }
        return true;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        $user = FCom_Admin_Model_User::i()->sessionUser();
        $hist = FCom_Cms_Model_BlockHistory::i()->create( array(
            'block_id' => $this->id,
            'user_id' => $user ? $user->id : null,
            'username' => $user ? $user->username : null,
            'version' => $this->version,
            'comments' => $this->version_comments ? $this->version_comments : 'version ' . $this->version,
            'ts' => BDb::now(),
            'data' => BUtil::toJson( BUtil::arrayMask( $this->as_array(), 'content' ) ), // more fields?
        ) )->save();
    }

    /**
     * rule pageu url unique
     * @param $data
     * @param $args
     * @return bool
     */
    public static function rulePageUrlUnique( $data, $args )
    {
        if ( empty( $data[ $args[ 'field' ] ] ) ) {
            return true;
        }
        $orm = static::i()->orm()->where( 'page_url', $data[ $args[ 'field' ] ] );
        if ( !empty( $data[ 'id' ] ) ) {
            $orm->where_not_equal( 'id', $data[ 'id' ] );
        }
        if ( $orm->find_one() ) {
            return false;
        }
        return true;
    }

    public function createView( $params = array() )
    {
        return FCom_Cms_Frontend_View_Block::i()->createView( $this, $params );
    }
}
