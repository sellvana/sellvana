<?php

class FCom_Wishlist_Frontend extends BClass
{
    public static function bootstrap()
    {
        BRouting::i()
            ->any('/wishlist', 'FCom_Wishlist_Frontend_Controller.wishlist');

        //add to wishlist
        BEvents::i()->on('FCom_Catalog_Frontend_Controller::action_product.addToWishlist',
                'FCom_Wishlist_Frontend_Controller::onAddToWishlist');

        BLayout::i()->addAllViews('Frontend/views')
                ->loadLayoutAfterTheme('Frontend/layout.yml');
    }
}