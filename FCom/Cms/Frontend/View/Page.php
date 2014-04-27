<?php

class FCom_Cms_Frontend_View_Page extends FCom_Core_View_Abstract
{
    public function getBlockModel( $view )
    {
        if ( !$this->getParam( 'page_model' ) ) {
            $page = $this->get( 'page' );
            if ( is_numeric( $page ) ) {
                $page = FCom_Cms_Model_Page::i()->load( $page );
            } elseif ( is_string( $page ) ) {
                $page = FCom_Cms_Model_Page::i()->load( $page, 'handle' );
            }
            if ( !$page || !is_object( $page ) || !$page instanceof FCom_Cms_Model_Page ) {
                BDebug::warning( 'CMS Page not found or invalid' );
                return false;
            }
            $this->setParam( 'page_model', $page );
        }
        return $this->getParam( 'page_model' );
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

    /**
     * Renderer for use with other views
     *
     * @param BView $view
     * @return string
     */
    static public function renderer( $view )
    {
        $page = $this->getPageModel( $view );
        if ( !$page ) {
            return '';
        }

        $renderer = $page->renderer ? $page->renderer : 'FCom_LibTwig_Main::renderer';
        $view->setParam( array(
            //'renderer'    => $block->renderer ? $block->renderer : 'FCom_LibTwig_Main::renderer',
            'source'      => $page->content ? $page->content : ' ',
            'source_name' => 'cms_block:' . $page->handle . ':' . strtotime( $page->update_at ),
        ) );

        $content = call_user_func( $renderer, $view );

        return $content;
    }
}
