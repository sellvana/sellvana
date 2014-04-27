<?php

class FCom_Cms_Frontend_View_Block extends FCom_Core_View_Abstract
{
    static protected $_layoutHlp;
    static protected $_origClass = __CLASS__;
    /**
     * Create a new block view instance within layout
     *
     * @param string $block block handle or instance
     * @param array $params block view creation parameters
     * @return FCom_Cms_Frontend_View_Block
     */
    static public function createView( $block, array $params = array() )
    {
        if ( !static::$_layoutHlp ) {
            static::$_layoutHlp = BLayout::i();
        }
        if ( empty( $params[ 'view_class' ] ) ) {
            $params[ 'view_class' ] = static::$_origClass;
        }
        if ( $block instanceof FCom_Cms_Model_Block ) {
            $params[ 'block' ] = $block->handle;
            $params[ 'model' ] = $block;
        } else {
            $params[ 'block' ] = $block;
        }
        $viewName = '_cms_block/' . $params[ 'block' ];
        $view = static::$_layoutHlp->getView( $viewName );
        if ( !$view instanceof FCom_Cms_Frontend_View_Block ) {
            $view = static::$_layoutHlp->addView( $viewName, $params )->getView( $viewName );
        }
        return $view;
    }

    /**
     * Get block model instance for the current view
     */
    static public function getBlockModel( $view )
    {
        $model = $view->getParam( 'model' );
        if ( !$model  || !is_object( $model ) || !$model instanceof FCom_Cms_Model_Block ) {
            $model = $view->get( 'block' );
            if ( is_numeric( $model ) ) {
                $model = FCom_Cms_Model_Block::i()->load( $model );
            } elseif ( is_string( $model ) ) {
                $model = FCom_Cms_Model_Block::i()->load( $model, 'handle' );
            }
            if ( !$model || !is_object( $model ) || !$model instanceof FCom_Cms_Model_Block ) {
                BDebug::warning( 'CMS Block not found or invalid' );
                return false;
            }
            $view->setParam( 'model', $model );
        }
        return $model;
    }

    /**
     * Renderer for use with other views
     *
     * @param BView $view
     * @return string
     */
    static public function renderer( $view )
    {
        $model = static::getBlockModel( $view );
        if ( !$model ) {
            return '';
        }

        $subRenderer = BLayout::i()->getRenderer( $model->renderer ? $model->renderer : 'FCom_LibTwig' );

        $view->setParam( array(
            //'renderer'    => $subRenderer,
            'source'      => $model->content ? $model->content : ' ',
            'source_name' => 'cms_block:' . get_class( $model ) . ':' . $model->handle,
            'source_mtime' => $model->modified_time,
            'source_untrusted' => true,
        ) );

        $content = call_user_func( $subRenderer[ 'callback' ], $view );

        return $content;
    }

    /**
     * Override _render() for performance, instead of using renderer callback
     *
     * @return string
     */
    public function _render()
    {
        return static::renderer( $this );
    }
}
