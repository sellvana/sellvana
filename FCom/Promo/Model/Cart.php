<?php

class FCom_Promo_Model_Cart extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_cart';

    public function getPromos($cartId)
    {
        return self::orm('pc')
                ->join(FCom_Promo_Model_Promo::table(), "p.id = pc.promo_id", "p")
                ->where('cart_id', $cartId)
                ->find_many();
    }
}