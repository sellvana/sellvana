<?php

/**
 * Class Sellvana_Checkout_Frontend_Controller_Cart
 *
 * Uses:
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_Sales_Frontend_Controller_Cart extends FCom_Frontend_Controller_Abstract
{
    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function action_index()
    {
        $layout = $this->BLayout;

        $this->layout('/cart');
        $layout->getView('cart')->set('redirectLogin', false);
        if ($this->BApp->m('Sellvana_Customer') && $this->Sellvana_Customer_Model_Customer->isLoggedIn() == false) {
            $layout->getView('cart')->set('redirectLogin', true);
        }

        $layout->getView('breadcrumbs')->set('crumbs', [['label' => 'Home', 'href' =>  $this->BApp->baseUrl()],
            ['label' => 'Cart', 'active' => true]]);

        $cart = $this->Sellvana_Sales_Model_Cart->sessionCart(true);
        $this->BEvents->fire(__CLASS__ . '::action_cart:cart', ['cart' => $cart]);

        $shippingEstimate = $cart->getData('shipping_rates');
        $layout->getView('cart')->set(['cart' => $cart, 'shipping_estimate' => $shippingEstimate]);
    }

    public function action_add__POST()
    {
        $redirHref = $this->BApp->href('cart');
        $post = $this->BRequest->post();
        $result = [];

        // Sellvana_Sales_Workflow_Cart -> Sellvana_CatalogFields_Frontend -> Sellvana_Sales_Model_Cart
        $this->Sellvana_Sales_Main->workflowAction('customerAddsItemsToCart', ['post' => $post, 'result' => &$result]);

        $item = $result['items'][0];
        if (!empty($item['status']) && $item['status'] === 'added') {
            $this->message('The product has been added to your cart');
        } elseif (!empty($item['error'])) {
            $this->message($item['error'], 'error');
            if (!empty($item['product'])) {
                $redirHref = $item['product']->url();
            }
        } else {
            $this->message("Unknown error, couldn't add item to cart", 'error');
            if (!empty($item['product'])) {
                $redirHref = $item['product']->url();
            }
        }

        $this->BResponse->redirect($redirHref);
    }

    public function action_addxhr__POST()
    {
        $cartHref = $this->BApp->href('cart');
        $post = $this->BRequest->post();
        $result = [];
        switch ($post['action']) {
            case 'add':
                $this->Sellvana_Sales_Main->workflowAction('customerAddsItemsToCart', ['post' => $post, 'result' => &$result]);

                $item = $result['items'][0];
                if (empty($item['error'])) {
                    $result = ['error' => $item['error']];
                } else {
                    $cart = $this->Sellvana_Sales_Model_Cart->sessionCart();
                    $p = $result['items'][0]->getProduct();
                    $result = [
                        'title' => 'Added to cart',
                        'html' => '<img src="' . $p->thumbUrl(35, 35) . '" width="35" height="35" style="float:left"/> '
                            . htmlspecialchars($p->product_name)
                            . (!empty($post['qty']) && $post['qty'] > 1 ? ' (' . $post['qty'] . ')' : '')
                            . '<br><br><a href="' . $cartHref . '" class="button">Go to cart</a>',
                        'minicart_html' => $this->BLayout->getView('checkout/cart/block')->render(),
                        'cnt' => $cart->itemQty(),
                        'subtotal' => $cart->subtotal,
                    ];
                }

                break;
        }

        $this->BResponse->json($result);
    }

    public function action_update__POST()
    {
        $post = $this->BRequest->post();
        $result = [];

        $this->Sellvana_Sales_Main->workflowAction('customerUpdatesCart', ['post' => $post, 'result' => &$result]);

        if (!empty($result['items'])) {
            foreach ($result['items'] as $item) {
                if (!empty($item['status'])) {
                    switch ($item['status']) {
                        case 'updated':
                            #$this->message('Cart item has been updated');
                            break;
                        case 'removed':
                            $this->message('Cart item has been removed');
                            break;
                        case 'error':
                            $this->message($item['message'], 'error');
                            break;
                    }
                }
            }
            $this->message('Cart has been updated');
        }
        $this->BResponse->redirect('cart');
    }

    public function action_shipping_estimate__POST()
    {
        $redirUrl = 'cart';
        $post = $this->BRequest->post();
        $result = [];

        $this->Sellvana_Sales_Main->workflowAction('customerRequestsShippingEstimate', ['post' => $post, 'result' => &$result]);

        $this->BResponse->redirect($redirUrl);
    }

    public function action_shipping_method__POST()
    {
        $redirUrl = 'cart';
        $post = $this->BRequest->post();
        $result = [];

        $this->Sellvana_Sales_Main->workflowAction('customerUpdatesShippingMethod', ['post' => $post, 'result' => &$result]);

        $this->BResponse->redirect($redirUrl);
    }

    public function action_add_coupon__POST()
    {
        $post = $this->BRequest->post();
        $result = [];

        $this->Sellvana_Sales_Main->workflowAction('customerAddsCouponCode', [
            'post' => $post,
            'result' => &$result,
        ]);

        if (!empty($result['error'])) {
            $this->message($result['error']['message'], 'error');
        } else {
            $this->message('Coupon code has been applied to cart');
        }
        $this->BResponse->redirect('cart');
    }

    public function action_remove_coupon__POST()
    {
        $post = $this->BRequest->post();
        $result = [];

        $this->Sellvana_Sales_Main->workflowAction('customerRemovesCouponCode', [
            'post' => $post,
            'result' => &$result,
        ]);

        if (!empty($result['error'])) {
            $this->message($result['message'], 'error');
        } else {
            $this->message('Coupon code has been removed from the cart');
        }
        $this->BResponse->redirect('cart');
    }
}
