<?php
class FCom_Wishlist_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'wishlist';
    protected $_modelClass = 'FCom_Wishlist_Model_Wishlist';
    protected $_gridTitle = 'Wishlist';
    protected $_mainTableAlias = 'w';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        return $config;
    }

    /**
     * get grid config for wishlist of customer
     * @param $customer FCom_Customer_Model_Customer
     * @return array
     */
    public function customerWishlistGridConfig( $customer )
    {
        $config = parent::gridConfig();
        $config[ 'id' ] = 'customer_grid_wishlist_' . $customer->id;
        $config[ 'columns' ] = array(
            array( 'type' => 'row_select' ),
            array( 'name' => 'wishlist_id', 'label' => 'Wishlist ID' ),
            array( 'name' => 'product_name', 'label' => 'Product Name' ),
            array( 'name' => 'local_sku', 'label' => 'SKU' ),
            array( 'name' => 'base_price', 'label' => 'Base Price' ),
            array( 'name' => 'sale_price', 'label' => 'Sale Price' ),
        );

        $data = array();
        $wishlistArr = FCom_Wishlist_Model_Wishlist::i()->orm()->where( 'customer_id', $customer->id )->find_many();
        if ( $wishlistArr ) {
            foreach ( $wishlistArr as $wishlist ) {
                $items = $wishlist->items();
                if ( $items ) {
                    foreach ( $items as $item ) {
                        $arr = $item->product()->as_array();
                        $arr[ 'wishlist_id' ] = $wishlist->id;
                        $data[] = $arr;
                    }
                }
            }
        }
        $config[ 'data' ] = $data;
        $config[ 'data_mode' ] = 'local';
        unset( $config[ 'orm' ] );
        return array( 'config' => $config );
    }
}
