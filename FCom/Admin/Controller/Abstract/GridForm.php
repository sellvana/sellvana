<?php

abstract class FCom_Admin_Controller_Abstract_GridForm extends FCom_Admin_Controller_Abstract
{
    // Required parameters
    protected $_modelClass;# = 'Model_Class_Name';
    protected $_gridHref;# = 'feature';

    // Optional parameters
    protected $_permission;# = 'feature/permission';
    protected $_navPath;# = 'nav/subnav';
    protected $_recordName = 'Record';
    protected $_gridTitle = 'List of Records';
    protected $_gridPageViewName = 'admin/grid';
    protected $_gridViewName = 'core/backbonegrid';
    protected $_gridLayoutName;# = '/feature';
    protected $_gridConfig = [];
    protected $_formHref;# = 'feature/form';
    protected $_formLayoutName;# = '/feature/form';
    protected $_formViewPrefix;# = 'module/feature-form/';
    protected $_formViewName = 'admin/form';
    protected $_formTitle;# = 'Record';
    protected $_mainTableAlias = 'main';

    protected $_useDefaultLayout = true;

    public function __construct()
    {
        parent::__construct();
        $this->_gridHref = trim( $this->_gridHref, '/' );

        if ( is_null( $this->_permission ) )     $this->_permission = $this->_gridHref;
        if ( is_null( $this->_navPath ) )        $this->_navPath = $this->_permission;

        if ( is_null( $this->_gridLayoutName ) ) $this->_gridLayoutName = '/' . $this->_gridHref;
        if ( is_null( $this->_gridViewName ) )   $this->_gridViewName = 'core/backbonegrid';

        if ( is_null( $this->_formHref ) )       $this->_formHref = $this->_gridHref . '/form';
        if ( is_null( $this->_formLayoutName ) ) $this->_formLayoutName = $this->_gridLayoutName . '/form';
        if ( is_null( $this->_formViewName ) )   $this->_formViewName = 'admin/form';
        if ( is_null( $this->_formViewPrefix ) ) $this->_formViewPrefix = $this->_gridHref . '-form/';

        if ( is_null( $this->_mainTableAlias ) ) $this->_mainTableAlias = 'main';

    }

    public function gridView()
    {
        $view = $this->view( $this->_gridViewName );
        $config = $this->_processConfig( $this->gridConfig() );
        $view->set( 'grid', [ 'config' => $config ] );
        BEvents::i()->fire( static::$_origClass . '::gridView', [ 'view' => $view ] );
        return $view;
    }

    public function gridConfig()
    {
        $gridDataUrl = BApp::href( $this->_gridHref . '/grid_data' );
        #$gridHtmlUrl = BApp::href($this->_gridHref.'/grid_html');
        $gridHtmlUrl = BApp::href( $this->_gridHref );
        $formUrl = BApp::href( $this->_formHref );
        $modelClass = $this->_modelClass;
        $config = [
            'id' => static::$_origClass,
            'orm' => $modelClass ? $modelClass::i()->orm( $this->_mainTableAlias )->select( $this->_mainTableAlias . '.*' ) : null,
            #'orm' => $modelClass,
            'data_url' => $gridDataUrl,
            'edit_url' => $gridDataUrl,
            'grid_url' => $gridHtmlUrl,
            'form_url' => $formUrl,
            'columns' => [
            ],
        ];
        $config = array_merge( $config, $this->_gridConfig );
        return $config;
    }

    public function simpleGridConfig()
    {
        $config = [
            'columns' => [],
            'data' => [],
        ];

        return $config;

    }

    protected function _processConfig( $config )
    {
        return $config;
    }

    public function action_index()
    {
        if ( BRequest::i()->xhr() ) {
            BResponse::i()->set( $this->gridView() )->output();
        }

        if ( ( $head = $this->view( 'head' ) ) ) {
            $head->addTitle( $this->_gridTitle );
        }

        if ( ( $nav = $this->view( 'admin/nav' ) ) ) {
            $nav->setNav( $this->_navPath );
        }

        $pageView = $this->view( $this->_gridPageViewName );
        $view = $this->gridView();
        $this->gridViewBefore( [ 'view' => $view, 'page_view' => $pageView ] );

        $this->layout();
        if ( $this->_useDefaultLayout ) {
            BLayout::i()->applyLayout( 'default_grid' );
        }
        BLayout::i()->applyLayout( $this->_gridLayoutName );
    }

    public function gridViewBefore( $args )
    {
        $view = $args[ 'page_view' ];
        $view->set( [
            'title' => $this->_gridTitle,
            'actions' => [
                'new' => ' <button type="button" class="btn btn-primary btn-sm" onclick="location.href=\'' . BApp::href( $this->_formHref ) . '\'"><span>New ' . BView::i()->q( $this->_recordName ) . '</span></button>',
            ],
        ] );
        BEvents::i()->fire( static::$_origClass . '::gridViewBefore', $args );
    }

    public function action_grid_html()
    {
        BResponse::i()->set( $this->gridView() )->output();
    }

    public function action_grid_data()
    {
        $view = $this->gridView();
        $grid = $view->get( 'grid' );
        $config = $grid[ 'config' ];

        if ( isset( $config[ 'data' ] ) && ( !empty( $config[ 'data' ] ) ) ) {
            $data = $config[ 'data' ];
            $data = $this->gridDataAfter( $data );
            BResponse::i()->json( [ [ 'c' => 1 ], $data ] );
        } else {
            $r = BRequest::i()->get();
            if ( empty( $grid[ 'orm' ] ) ) {
                $mc = $this->_modelClass;
                $grid[ 'orm' ] = $mc::i()->orm( $this->_mainTableAlias )->select( $this->_mainTableAlias . '.*' );
                $view->set( 'grid', $grid );
            }
            if ( isset( $r[ 'filters' ] ) ) {
                $filters = BUtil::fromJson( $r[ 'filters' ] );
                if ( isset( $filters[ 'exclude_id' ] ) && $filters[ 'exclude_id' ] != '' ) {
                    $arr = explode( ',', $filters[ 'exclude_id' ] );
                    $grid[ 'orm' ] =  $grid[ 'orm' ]->where_not_in( $this->_mainTableAlias . '.id', $arr );
                }
            }
            $this->gridOrmConfig( $grid[ 'orm' ] );

            $oc = static::$_origClass;

            $gridId = !empty( $config[ 'id' ] ) ? $config[ 'id' ] : $oc;

            if ( BRequest::i()->request( 'export' ) ) {
                $data = $view->generateOutputData( true );
                $view->export( $data[ 'rows' ], $oc );
            } else {

                //$data = $view->processORM($orm, $oc.'::action_grid_data', $gridId);
                $data = $view->generateOutputData();
                $data = $this->gridDataAfter( $data );
                BResponse::i()->json( [
                    [ 'c' => $data[ 'state' ][ 'c' ] ],
                    BDb::many_as_array( $data[ 'rows' ] ),
                ] );
            }
        }
    }

    public function gridDataAfter( $data )
    {
        BEvents::i()->fire( static::$_origClass . '::gridDataAfter', [ 'data' => &$data ] );
        return $data;
    }

    public function gridOrmConfig( $orm )
    {
        BEvents::i()->fire( static::$_origClass . '::gridOrmConfig', [ 'orm' => &$orm ] );
    }

    public function action_grid_data__POST()
    {
        $this->_processGridDataPost( $this->_modelClass );
    }

    public function action_form()
    {
        $class = $this->_modelClass;
        $id = BRequest::i()->param( 'id', true );
        if ( $id && !( $model = $class::i()->load( $id ) ) ) {
            /*BDebug::error('Invalid ID: '.$id);*/
            $this->message( 'This item does not exist', 'error' );
        }
        if ( empty( $model ) ) {
            $model = $class::i()->create();
        }
        $this->formMessages();
        $view = $this->view( $this->_formViewName )->set( 'model', $model );
        $this->formViewBefore( [ 'view' => $view, 'model' => $model ] );

        if ( $this->_formTitle && ( $head = $this->view( 'head' ) ) ) {
            $head->addTitle( $this->_formTitle );
        }

        if ( ( $nav = $this->view( 'admin/nav' ) ) ) {
            $nav->setNav( $this->_navPath );
        }

        $this->layout();
        BLayout::i()->view( 'admin/form' )->set( 'tab_view_prefix', $this->_formViewPrefix );
        if ( $this->_useDefaultLayout ) {
            BLayout::i()->applyLayout( 'default_form' );
        }
        BLayout::i()->applyLayout( $this->_formLayoutName );

        $this->processFormTabs( $view, $model );
    }

    public function formViewBefore( $args )
    {
        $m = $args[ 'model' ];
        $actions = [];

        $actions[ 'back' ] = '<button type="button" class="btn btn-link" onclick="location.href=\'' . BApp::href( $this->_gridHref ) . '\'"><span>' .  BLocale::_( 'Back to list' ) . '</span></button>';
        if ( $m->id ) {
            $actions[ 'delete' ] = '<button type="submit" class="btn btn-warning" name="do" value="DELETE" onclick="return confirm(\'Are you sure?\')"><span>' .  BLocale::_( 'Delete' ) . '</span></button>';
        }
        $actions[ 'save' ] = '<button type="submit" class="btn btn-primary" onclick="return adminForm.saveAll(this)"><span>' .  BLocale::_( 'Save' ) . '</span></button>';

        $id = method_exists( $m, 'id' ) ? $m->id() : $m->id;
        $title = $id ? BLocale::_( 'Edit %s: %s', [ $this->_recordName, $m->title ] ) : BLocale::_( 'Create New %s', [ $this->_recordName ] );

        $args[ 'view' ]->set( [
            'form_id' => $this->formId(),
            'form_url' => BApp::href( $this->_formHref ) . '?id=' . $m->id,
            'title' => $title,
            'actions' => $actions,
        ] );
        BEvents::i()->fire( static::$_origClass . '::formViewBefore', $args );
    }

    public function action_form__POST()
    {
        $r = BRequest::i();
        $args = [];
        $formId = $this->formId();
        $redirectUrl = BApp::href( $this->_gridHref );
        try {
            $class = $this->_modelClass;
            $id = $r->param( 'id', true );
            $model = $id ? $class::i()->load( $id ) : $class::i()->create();
            if ( !$model ) {
                throw new BException( "This item does not exist" );
            }
            $data = $r->post( 'model' );
            $args = [ 'id' => $id, 'do' => $r->post( 'do' ), 'data' => &$data, 'model' => &$model ];
            $this->formPostBefore( $args );
            $args[ 'validateFailed' ] = false;
            if ( $r->post( 'do' ) === 'DELETE' ) {
                $model->delete();
                $this->message( 'The record has been deleted' );
            } else {
                $model->set( $data );

                if ( $model->validate( $model->as_array(), [], $formId ) ) {
                    $model->save();
                    $this->message( 'Changes have been saved' );
                    if ( $r->post( 'do' ) === 'saveAndContinue' ) {
                        $redirectUrl = BApp::href( $this->_formHref ) . '?id=' . $model->id;
                    }
                } else {
                    $this->message( 'Cannot save data, please fix above errors', 'error', 'validator-errors:' . $formId );
                    $args[ 'validateFailed' ] = true;
                    $redirectUrl = BApp::href( $this->_formHref ) . '?id=' . $id;
                }

            }
            $this->formPostAfter( $args );
        } catch ( Exception $e ) {
            //BDebug::exceptionHandler($e);
            $this->formPostError( $args );
            $this->message( $e->getMessage(), 'error' );
            $redirectUrl = BApp::href( $this->_formHref ) . '?id=' . $id;
        }
        if ( $r->xhr() ) {
            $this->forward( 'form', null, [ 'id' => $id ] );
        } else {
            BResponse::i()->redirect( $redirectUrl );
        }
    }

    public function formPostBefore( $args )
    {
        BEvents::i()->fire( static::$_origClass . '::formPostBefore', $args );
    }

    public function formPostAfter( $args )
    {
        BEvents::i()->fire( static::$_origClass . '::formPostAfter', $args );
    }

    public function formPostError( $args )
    {
        BEvents::i()->fire( static::$_origClass . '::formPostError', $args );
    }

    /**
     * use form id for html and namespace in messages
     * @return string
     */
    public function formId()
    {
        return BLocale::transliterate( $this->_formLayoutName );
    }

    /**
     * Prepare message for form
     *
     * This is a temporary solution to save dev time
     *
     * @todo implement form errors inside form as error labels instead of group on top
     * @param string $viewName
     */
    public function formMessages( $viewName = 'core/messages' )
    {
        $formId = $this->formId();
        $messages = BSession::i()->messages( 'validator-errors:' . $formId );
        if ( count( $messages ) ) {
            $msg = [];
#BDebug::dump($messages); exit;
            foreach ( $messages as $m ) {
                $msg[] = is_array( $m[ 'msg' ] ) ? $m[ 'msg' ][ 'error' ] : $m[ 'msg' ];
            }
            $this->message( $msg, 'error' );
        }
    }
}
