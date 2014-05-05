<?php

class FCom_Blog_Model_Post extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_blog_post';
    static protected $_origClass = __CLASS__;
    static protected $_fieldOptions = [
        'status' => [
            'pending'  => 'Pending',
            'published' => 'Published',
        ],
    ];

    protected static $_validationRules = [
        /*array('author_user_id', '@required'),*/
        [ 'title', '@required' ],
        [ 'url_key', 'FCom_Blog_Model_Post::validateDupUrlKey' ]
        /*array('url_key', '@required'),*/
    ];


    static public function getPostsOrm()
    {
        return FCom_Blog_Model_Post::i()->orm( 'p' )
            ->select( 'p.*' )
            ->join( 'FCom_Admin_Model_User', [ 'p.author_user_id', '=', 'u.id' ], 'u' )
            ->select( 'u.firstname' )->select( 'u.lastname' )
            ->where_in( 'p.status', [ 'published' ] )
            ->order_by_desc( 'create_at' );
    }

    static public function getArchiveTree()
    {

    }

    public function onBeforeSave()
    {
        if ( !parent::onBeforeSave() ) return false;

        if ( !$this->create_at ) {
            $this->set( [ 'create_at' => BDb::now(), 'create_ym' => date( 'Ym' ) ] );
        }
        $this->set( 'update_at', BDb::now() );


        if ( !$this->url_key ) {
            $this->url_key = BLocale::transliterate( $this->title );
        }

        if ( !$this->author_user_id ) {
            $this->author_user_id = FCom_Admin_Model_User::i()->sessionUserId();
        }

        return true;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();
        FCom_Blog_Model_PostTag::i()->delete_many( [ 'post_id' => $this->id ] );
        if ( $this->tags ) {
            $tagNames = preg_split( '#[ ,;]+#', $this->tags );
            $exists = FCom_Blog_Model_Tag::i()->orm()->where_in( 'tag_name', $tagNames )->find_many_assoc( 'tag_name' );
            foreach ( $tagNames as $t ) {
                if ( isset( $exists[ $t ] ) ) {
                    $tagId = $exists[ $t ]->id;
                } else {
                    $tag = FCom_Blog_Model_Tag::i()->create( [ 'tag_key' => $t, 'tag_name' => $t ] )->save();
                    $tagId = $tag->id;
                }
                FCom_Blog_Model_PostTag::i()->create( [ 'post_id' => $this->id, 'tag_id' => $tagId ] )->save();
            }
        }
    }

    public function getUrl()
    {
        return BApp::href( 'blog/' . $this->get( 'url_key' ) );
    }

    public function getAuthor()
    {
        if ( !$this->author ) {
            $this->author = FCom_Admin_Model_User::i()->load( $this->get( 'author_user_id' ) );
        }
        return $this->author;
    }

    public function getAuthorName()
    {
        $user = $this->getAuthor();
        return $user->get( 'firstname' ) . ' ' . $user->get( 'lastname' );
    }

    public function getTags()
    {
        if ( !$this->tag_models ) {
            $this->tag_models = FCom_Blog_Model_Tag::i()->orm( 't' )
                ->join( 'FCom_Blog_Model_PostTag', [ 'pt.tag_id', '=', 't.id' ], 'pt' )
                ->where( 'pt.post_id', $this->id() )
                ->find_many();
        }
        return $this->tag_models;
    }

    public function getCategories()
    {
        if ( !$this->category_models ) {
            $this->category_models = FCom_Blog_Model_Category::i()->orm( 'c' )
                ->join( 'FCom_Blog_Model_PostCategory', [ 'pc.category_id', '=', 'c.id' ], 'pc' )
                ->where( 'pc.post_id', $this->id() )
                ->find_many();
        }
        return $this->category_models;
    }

    public function getTagsString()
    {
        return join( ' ', BUtil::arrayToOptions( BDb::many_as_array( $this->getTags() ), 'tag_name' ) );
    }

    public function getRelatedPosts()
    {
        return [];
    }

    public static function validateDupUrlKey( $data, $args )
    {
        if ( !empty( static::$_flags[ 'skip_duplicate_checks' ] ) ) {
            return true;
        }
        if ( empty( $data[ $args[ 'field' ] ] ) ) {
            return true;
        }
        $orm = static::orm( 'p' )->where( 'url_key', $data[ $args[ 'field' ] ] );
        if ( !empty( $data[ 'id' ] ) ) {
            $orm->where_not_equal( 'p.id', $data[ 'id' ] );
        }
        if ( $orm->find_one() ) {
            return BLocale::_( 'The URL Key entered is already in use. Please enter a valid URL Key.' );
        }
        return true;
    }
}
