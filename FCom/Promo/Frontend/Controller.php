<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Promo_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function hook_promotions()
    {
        $cart = $this->FCom_Sales_Model_Cart->sessionCart(true);
        $promoList = $this->FCom_Promo_Model_Promo->getPromosByCart($cart->id);
        $this->BLayout->view('promotions')->promoList = $promoList;
        return $this->BLayout->view('promotions')->render();
    }

    public function action_media()
    {
        $promoId = $this->BRequest->get('id');
        $this->view('promo/media')->promo = $this->FCom_Promo_Model_Promo->load($promoId);
        $this->layout('/promo/media');
    }
}
