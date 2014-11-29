<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Frontend_Controller
 *
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Promo_Model_Promo $FCom_Promo_Model_Promo
 */
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
        $this->layout('/promo/media');
        $this->view('promo/media')->promo = $this->FCom_Promo_Model_Promo->load($promoId);
    }
}
