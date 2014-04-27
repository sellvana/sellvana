<?php

class FCom_Customer_Frontend_View_Sidebar extends FCom_Core_View_Abstract
{
    protected $_navItems = array();

    public function addNavItem( $itemKey, $item )
    {
        if ( empty( $item[ 'position' ] ) ) {
            $item[ 'position' ] = 1 + array_reduce( $this->_navItems, function( $a, $b ) {
                return max( $a[ 'position' ], $b[ 'position' ] );
            } );
        }
        $navItems = $this->get( 'items' );
        $navItems[ $itemKey ] = $item;
        $this->set( 'items', $navItems );
        return $this;
    }

    public function removeNavItem( $itemKey )
    {
        $navItems = $this->get( 'items' );
        unset( $navItems[ $itemKey ] );
        $this->set( 'items', $navItems );
        return $this;
    }

    public function getNavItems()
    {
        return $this->get( 'items' );
    }
}
