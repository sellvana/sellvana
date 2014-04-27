<?php

class FCom_Blog_Admin_Controller_Post extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'blog/post';
    protected $_modelClass = 'FCom_Blog_Model_Post';
    protected $_gridTitle = 'Blog Posts';
    protected $_formHref = 'blog/post/form';
    protected $_recordName = 'Blog Post';
    protected $_permission = 'blog';
    protected $_mainTableAlias = 'p';
    protected $_navPath = 'cms/blog';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config[ 'columns' ] = [
            [ 'type' => 'row_select' ],
            [ 'name' => 'id', 'label' => 'ID' ],
            [ 'name' => 'author', 'label' => 'Author' ],
            [ 'type' => 'input', 'name' => 'status', 'label' => 'Status', 'edit_inline' => false, 'editable' => true,
                'mass-editable' => true, 'editor' => 'select', 'mass-editable-show' => true,
                'options' => FCom_Blog_Model_Post::i()->fieldOptions( 'status' ),
                'index' => $this->_mainTableAlias . '.status' ],
            [ 'type' => 'input', 'name' => 'title', 'label' => 'Title', 'editable' => true, 'edit_inline' => true,
                'validation' => [ 'required' => true ]
//                'href' => BApp::href('blog/post/form/?id=:id')
            ],
            [ 'name' => 'url_key', 'label' => 'Url Key', 'hidden' => true ],
            [ 'name' => 'meta_title', 'label' => 'Meta Title', 'hidden' => true ],
            [ 'name' => 'meta_description', 'label' => 'Meta Description', 'hidden' => true ],
            [ 'name' => 'meta_keywords', 'label' => 'Meta Keywords', 'hidden' => true ],
            [ 'name' => 'create_ym', 'label' => 'Create ym' , 'hidden' => true ],
            [ 'name' => 'create_at', 'label' => 'Created', 'cell' => 'date' ],
            [ 'name' => 'update_at', 'label' => 'Updated', 'cell' => 'date' ],
            [ 'type' => 'btn_group', 'buttons' => [
                [ 'name' => 'edit' ],
                [ 'name' => 'delete', 'edit_inline' => false ]
            ] ]
        ];
        if ( !empty( $config[ 'orm' ] ) ) {
            if ( is_string( $config[ 'orm' ] ) ) {
                $config[ 'orm' ] = $config[ 'orm' ]::i()->orm( $this->_mainTableAlias )->select( $this->_mainTableAlias . '.*' );
            }
            $this->gridOrmConfig( $config[ 'orm' ] );
        }
        $config[ 'actions' ] = [
            'edit' => true,
            'delete' => true
        ];
        $config[ 'filters' ] = [
            [ 'field' => 'title', 'type' => 'text' ],
            [ 'field' => 'status', 'type' => 'multiselect' ],
        ];

        return $config;
    }

    public function gridOrmConfig( $orm )
    {
        parent::gridOrmConfig( $orm );
        $r = BRequest::i()->get();
        $orm->join( 'FCom_Admin_Model_User', [ 'p.author_user_id', '=', 'u.id' ], 'u' )
            ->select_expr( 'CONCAT_WS(" ", u.firstname,u.lastname)', 'author' );
        if ( !BRequest::i()->xhr() ) {
            BSession::i()->pop( 'categoryBlogPost' );
        }
        if ( isset( $r[ 'category' ] ) ) {//@todo: find other solution instead use session?
            BSession::i()->set( 'categoryBlogPost', $r[ 'category' ] );
        }
        if ( BSession::i()->get( 'categoryBlogPost' ) ) {
            $orm->join( 'FCom_Blog_Model_PostCategory', [ $this->_mainTableAlias . '.id', '=', 'c.post_id' ], 'c' )
                ->where( 'c.category_id', BSession::i()->get( 'categoryBlogPost' ) )
            ;
        }
    }

    public function formViewBefore( $args )
    {
        parent::formViewBefore( $args );
        $m = $args[ 'model' ];
        $args[ 'view' ]->set( [
            'title' => $m->id ? 'Edit Blog Post: ' . $m->title : 'Create New Blog Post',
        ] );
        $tagOptions = FCom_Blog_Model_Tag::i()->orm()->order_by_asc( 'tag_name' )
            ->select( 'tag_key', 'id' )->select( 'tag_name', 'name' )->find_many();
        $tagOptionsJson = BUtil::toJson( BDb::many_as_array( $tagOptions ) );
        $this->view( 'blog/post-form/main' )->set( 'tag_options_json', $tagOptionsJson );
    }

    /**
     * modal grid on blog/post tab
     */
    public function getAllPostConfig()
    {

        $config = parent::gridConfig();
        //$config['id'] = 'category_all_prods_grid-'.$model->id;
        $config[ 'id' ] = 'category_all_post_grid';
        $config[ 'columns' ] = [
            [ 'cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40 ],
            [ 'name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 55 ],
            [ 'name' => 'author', 'label' => 'Author' ],
            [ 'name' => 'title', 'label' => 'Title', 'href' => BApp::href( 'blog/post/form/?id=:id' ) ],
            [ 'name' => 'status', 'label' => 'Status', 'editable' => true, 'mass-editable' => true, 'editor' => 'select',
                'options' => FCom_Blog_Model_Post::i()->fieldOptions( 'status' ) ],
        ];
        $config[ 'actions' ] = [
            'add' => [ 'caption' => 'Add selected posts', 'modal' => true ]
        ];
        $config[ 'filters' ] = [
            [ 'field' => 'title', 'type' => 'text' ],
            [ 'field' => 'status', 'type' => 'multiselect' ],
        ];
        $config[ 'orm' ] = FCom_Blog_Model_Post::i()->orm( 'p' )
            ->select( 'p.*' )
            ->join( 'FCom_Admin_Model_User', [ 'p.author_user_id', '=', 'u.id' ], 'u' )
            ->select_expr( 'CONCAT_WS(" ", u.firstname,u.lastname)', 'author' );
        $config[ 'events' ] = [ 'add' ];
        /*$config['_callbacks'] = "{
            'add':'categoryProdsMng.addSelectedProds'
        }";*/


        return [ 'config' => $config ];
    }

    public function postGridConfig( $model )
    {
        $orm = FCom_Blog_Model_Post::i()->orm()->table_alias( 'p' )
            ->select( [ 'p.id', 'p.author_user_id', 'p.status', 'p.title' ] );
        $orm->join( 'FCom_Admin_Model_User', [ 'p.author_user_id', '=', 'u.id' ], 'u' )
            ->select_expr( 'CONCAT_WS(" ", u.firstname,u.lastname)', 'author' );
        $orm->join( 'FCom_Blog_Model_PostCategory', [ 'p.id', '=', 'cp.post_id' ], 'cp' )
            ->where( 'category_id', $model->id );
        $config = [
            'id'           => 'post_category',
            'data'         => null,
            'data_mode'     => 'local',
            //'caption'      =>$caption,
            'columns'      => [
                [ 'cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40 ],
                [ 'name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 80, 'hidden' => true ],
                [ 'name' => 'author', 'label' => 'Author', 'index' => 'u.author_user_id' ],
                [ 'name' => 'title', 'label' => 'Title' ],
                [ 'name' => 'status', 'label' => 'Status' ],
            ],
            'actions' => [
                'add' => [ 'caption' => 'Add Posts' ],
                'delete' => [ 'caption' => 'Remove' ]
            ],
            'filters' => [
                [ 'field' => 'title', 'type' => 'text' ],
                [ 'field' => 'status', 'type' => 'multiselect' ],
            ],
            'events' => [ 'init', 'add', 'mass-delete' ]
        ];


        //BEvents::i()->fire(__METHOD__.':orm', array('type'=>$type, 'orm'=>$orm));
        $data = BDb::many_as_array( $orm->find_many() );
        //unset unused columns
        /*$columnKeys = array_keys($config['columns']);
        foreach($data as &$prod){
            foreach($prod as $k=>$p) {
                if (!in_array($k, $columnKeys)) {
                    unset($prod[$k]);
                }
            }
        }*/

        $config[ 'data' ] = $data;

        //BEvents::i()->fire(__METHOD__.':config', array('type'=>$type, 'config'=>&$config));
        return [ 'config' => $config ];
    }

    public function formPostAfter( $args )
    {
        parent::formPostAfter( $args );
        $r = BRequest::i()->post();
        $model = $args[ 'model' ];
        if ( isset( $r[ 'category-id' ] ) ) {
            $cp = FCom_Blog_Model_PostCategory::i();

            $cp->delete_many( [
                'post_id' => $model->id,
            ] );

            if ( $r[ 'category-id' ] != '' ) {
                $tmp = explode( ',', $r[ 'category-id' ] );
                foreach ( $tmp as $categoryId ) {
                    $cp->create( [
                        'post_id' => $model->id,
                        'category_id' => $categoryId,
                    ] )->save();
                }
            }

        }
    }
}
