<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Promo_Frontend_Controller_Cart
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_Promo_Model_PromoCart $Sellvana_Promo_Model_PromoCart
 */
class Sellvana_Promo_Frontend_Controller_Cart extends FCom_Frontend_Controller_Abstract
{

    public function action_add_free_item()
    {
        $post = $this->BRequest->request();

        if (!$this->BSession->validateCsrfToken($post['token'])) {
            $this->BResponse->redirect('cart');
            return;
        }

        $result = [];

        try {
            $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();
            $this->Sellvana_Promo_Model_PromoCart->addFreeItem($post, $cart);
            $cart->calculateTotals()->saveAllDetails();

            $this->Sellvana_Sales_Main->workflowAction('customerAddsFreeItemsToCart', [
                'post' => $post,
                'result' => &$result,
            ]);
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        if (!empty($result['error'])) {
            $this->message($result['message'], 'error');
        } else {
            $this->message('Free item has been added to cart');
        }
        $this->BResponse->redirect('cart');
    }
}