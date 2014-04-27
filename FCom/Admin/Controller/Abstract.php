<?php

class FCom_Admin_Controller_Abstract extends FCom_Core_Controller_Abstract
{
    protected static $_origClass;
    protected $_permission;

    public function authenticate( $args = array() )
    {
        return FCom_Admin_Model_User::i()->isLoggedIn();
    }

    public function authorize( $args = array() )
    {
        if ( !parent::authorize( $args ) ) {
            return false;
        }
        if ( !empty( $this->_permission ) ) {
            $user = FCom_Admin_Model_User::i()->sessionUser();
            if ( !$user ) {
                return false;
            }
            return $user->getPermission( $this->_permission );
        }
        return true;
    }

    public function action_unauthenticated()
    {
        $r = BRequest::i();
        if ( $r->xhr() ) {
            BSession::i()->set( 'admin_login_orig_url', $r->referrer() );
            BResponse::i()->json( array( 'error' => 'login' ) );
        } else {
            BSession::i()->set( 'admin_login_orig_url', $r->currentUrl() );
            $this->layout( '/login' );
            BResponse::i()->status( 401, 'Unauthorized' ); // HTTP sic
        }
    }

    public function action_unauthorized()
    {
        $r = BRequest::i();
        if ( $r->xhr() ) {
            BSession::i()->set( 'admin_login_orig_url', $r->referrer() );
            BResponse::i()->json( array( 'error' => 'denied' ) );
        } else {
            BSession::i()->set( 'admin_login_orig_url', $r->currentUrl() );
            $this->layout( '/denied' );
            BResponse::i()->status( 403, 'Forbidden' );
        }
    }

    public function beforeDispatch()
    {
        if ( !parent::beforeDispatch() ) return false;

        $this->view( 'head' )->addTitle( BLocale::_( '%s Admin', BConfig::i()->get( 'modules/FCom_Core/site_title' ) ) );

        return true;
    }

    public function processFormTabs( $view, $model = null, $mode = 'edit', $allowed = null )
    {
        $r = BRequest::i();
        if ( $r->xhr() && !is_null( $r->get( 'tabs' ) ) ) {
            $this->outFormTabsJson( $view, $model, $mode );
        } else {
            $this->initFormTabs( $view, $model, $mode, $allowed );
        }
        return $this;
    }

    public function message( $msg, $type = 'success', $tag = 'admin', $options = array() )
    {
        if ( is_array( $msg ) ) {
            array_walk( $msg, 'BLocale::_' );
        } else {
            $msg = BLocale::_( $msg );
        }
        BSession::i()->addMessage( $msg, $type, $tag, $options );
        return $this;
    }

    public function initFormTabs( $view, $model, $mode = 'view', $allowed = null )
    {

        $r = BRequest::i();
        $layout = BLayout::i();
        $curTab = $r->request( 'tab' );
        if ( is_string( $allowed ) ) {
            $allowed = explode( ',', $allowed );
        }
        #$formId = $this->get('form_id');
        #$validator = $this->validator($formId, $model);
        $this->collectFormTabs( $view );

        $tabs = $view->tab_groups ? $view->tabs : $view->sortedTabs();
        if ( $tabs ) {
            foreach ( $tabs as $k => &$tab ) {
                if ( !is_null( $allowed ) && $allowed !== 'ALL' && !in_array( $k, $allowed ) ) {
                    $tab[ 'disabled' ] = true;
                    continue;
                }
                if ( $k === $curTab ) {
                    $tab[ 'active' ] = true;
                    $tab[ 'async' ] = false;
                }
                if ( !empty( $tab[ 'view' ] ) ) {
                    $tabView = $layout->view( $tab[ 'view' ] );
                    if ( $tabView ) {
                        $tabView->set( array(
                            'model' => $model,
                            #'validator' => $validator,
                            'mode' => $mode,
                        ) );
                    } else {
                        BDebug::warning( 'MISSING VIEW: ' . $tab[ 'view' ] );
                    }
                }
            }
            unset( $tab );
        }
        $view->tabs = $tabs;

        if ( $view->tab_groups ) {
            $tabGroups = $view->sortedTabGroups();
            foreach ( $tabs as $k => &$tab ) {
                $tabGroups[ $tab[ 'group' ] ][ 'tabs' ][ $k ] =& $tab;
                if ( !empty( $tab[ 'active' ] ) ) {
                    $tabGroups[ $tab[ 'group' ] ][ 'open' ] = true;
                }
            }
            unset( $tab );
            foreach ( $tabGroups as $k => &$tabGroup ) {
                if ( empty( $tabGroup[ 'tabs' ] ) ) {
                    unset( $tabGroups[ $k ] );
                } else {
                    uasort( $tabGroup[ 'tabs' ], function( $a, $b ) {
                        return $a[ 'pos' ] < $b[ 'pos' ] ? -1 : ( $a[ 'pos' ] > $b[ 'pos' ] ? 1 : 0 );
                    } );
                    if ( !$curTab ) {
                        foreach ( $tabGroup[ 'tabs' ] as $tabId => &$tab ) {
                            $curTab = $tabId;
                            $tabGroup[ 'open' ] = true;
                            $tab[ 'active' ] = true;
                            $tab[ 'async' ] = false;
                            break;
                        }
                        unset( $tab );
                    }
                }
            }
            unset( $tabGroup );
            $view->tab_groups = $tabGroups;
        } else {
            if ( !$curTab ) {
                $tabs = $view->tabs;
                foreach ( $tabs as $k => &$tab ) {
                    $curTab = $k;
                    $tab[ 'active' ] = true;
                    $tab[ 'async' ] = false;
                    break;
                }
                unset( $tab );
                $view->tabs = $tabs;
            }
        }

        $view->set( array(
            'tabs' => $tabs,
            'model' => $model,
            'mode' => $mode,
            'cur_tab' => $curTab,
        ) );
        return $this;
    }

    public function collectFormTabs( $formView )
    {
        $views = BLayout::i()->findViewsRegex( '#^' . $formView->get( 'tab_view_prefix' ) . '#' );
        foreach ( $views as $viewName => $view ) {
            $id = basename( $viewName );
            if ( !empty( $formView->tabs[ $id ] ) ) {
                continue;
            }
            $view->collectMetaData();
            $params = $view->getParam( 'meta_data' );
            if ( !empty( $params[ 'disabled' ] ) ) {
                continue;
            }
            if ( !empty( $params[ 'model_new_hide' ] ) ) {
                $model = $formView->get( 'model' );
                if ( !$model || !$model->id() ) {
                    continue;
                }
            }
            $formView->addTab( $id, $params );
        }
        return $this;
    }

    public function outFormTabsJson( $view, $model, $defMode = 'view' )
    {
        $r = BRequest::i();
        $mode = $r->request( 'mode' );
        if ( !$mode ) {
            $mode = $defMode;
        }
        $outTabs = $r->request( 'tabs' );
        if ( $outTabs && $outTabs !== 'ALL' && is_string( $outTabs ) ) {
            $outTabs = explode( ',', $outTabs );
        }
        $out = array();
        if ( $outTabs ) {
            $layout = BLayout::i();
            $tabs = $view->tabs;
            foreach ( $tabs as $k => $tab ) {
                if ( $outTabs !== 'ALL' && !in_array( $k, $outTabs ) ) {
                    continue;
                }
                $view = $layout->view( $tab[ 'view' ] );
                if ( !$view ) {
                    BDebug::error( 'MISSING VIEW: ' . $tabs[ $k ][ 'view' ] );
                    continue;
                }
                $out[ 'tabs' ][ $k ] = (string)$view->set( array(
                    'model' => $model,
                    'mode' => $mode,
                ) );
            }
        }
        $out[ 'messages' ] = BSession::i()->messages( 'admin' );
        BResponse::i()->json( $out );
    }

    protected function _processGridDataPost( $class, $defData = array() )
    {
        $r = BRequest::i();
        $id = $r->post( 'id' );
        $data = $defData + $r->post();
        $hlp = $class::i();
        unset( $data[ 'id' ], $data[ 'oper' ] );

        $args = array( 'data' => &$data, 'oper' => $r->post( 'oper' ), 'helper' => $hlp );
        $this->gridPostBefore( $args );

        switch ( $args[ 'oper' ] ) {
        case 'add':
            //fix Undefined variable: set
            $set = $args[ 'model' ] = $hlp->create( $data )->save();
            $result = $set->as_array();
            break;

        case 'edit':
            //fix Undefined variable: set
            $set = $args[ 'model' ] = $hlp->load( $id )->set( $data )->save();
            $result = $set->as_array();
            break;

        case 'del':
            $args[ 'model' ] = $hlp->load( $id )->delete();
            $result = array( 'success' => true );
            break;

        case 'mass-delete':
            $args[ 'ids' ] = explode( ",", $id );
            foreach ( $args[ 'ids' ] as $id ) {
                $hlp->load( $id )->delete();
            }
            $result = array( 'success' => true );
            break;

        case 'mass-edit':
            $args[ 'ids' ] = explode( ',', $id );
            foreach ( $args[ 'ids' ] as $id ) {
                if ( isset( $data[ '_new' ] ) ) {
                    unset( $data[ '_new' ] );
                    $args[ 'models' ][] = $hlp->create( $data )->save();
                } else {
                    $args[ 'models' ][] = $hlp->load( $id )->set( $data )->save();
                }
            }
            $result = array( 'success' => true );
            break;
        }

        $args[ 'result' ] =& $result;
        $this->gridPostAfter( $args );

        //BResponse::i()->redirect('fieldsets/grid_data');
        BResponse::i()->json( $result );
    }

    public function gridPostBefore( $args )
    {
        BEvents::i()->fire( static::$_origClass . '::gridPostBefore', $args );
    }

    public function gridPostAfter( $args )
    {
        BEvents::i()->fire( static::$_origClass . '::gridPostAfter', $args );
    }
}
