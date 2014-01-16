<?php

class FCom_Promo_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function hook_promotions()
    {
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        $promoList = FCom_Promo_Model_Promo::i()->getPromosByCart($cart->id);
        BLayout::i()->view('promotions')->promoList = $promoList;
        return BLayout::i()->view('promotions')->render();
    }

    public function action_media()
    {
        $promoId = BRequest::i()->get('id');
        $this->view('promo/media')->promo = FCom_Promo_Model_Promo::i()->load($promoId);
        $this->layout('/promo/media');
    }
}
