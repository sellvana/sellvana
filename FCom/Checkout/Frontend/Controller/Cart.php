<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Checkout_Frontend_Controller_Cart extends FCom_Frontend_Controller_Abstract
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function action_index()
    {
        $layout = $this->BLayout;

        $this->layout('/checkout/cart');
        $layout->view('checkout/cart')->set('redirectLogin', false);
        if ($this->BApp->m('FCom_Customer') && $this->FCom_Customer_Model_Customer->isLoggedIn() == false) {
            $layout->view('checkout/cart')->set('redirectLogin', true);
        }

        $layout->view('breadcrumbs')->set('crumbs', [['label' => 'Home', 'href' =>  $this->BApp->baseUrl()],
            ['label' => 'Cart', 'active' => true]]);

        $cart = $this->FCom_Sales_Model_Cart->sessionCart(true);
        $this->BEvents->fire(__CLASS__ . '::action_cart:cart', ['cart' => $cart]);

        $shippingEstimate = $cart->getData('shipping_estimate');
        $layout->view('checkout/cart')->set(['cart' => $cart, 'shipping_esitmate' => $shippingEstimate]);
    }

    public function action_add__POST()
    {
        $cartHref = $this->BApp->href('cart');
        $post = $this->BRequest->post();
        if (isset($post['action'])) {
            switch ($post['action']) {
            case 'add':
                $this->BEvents->fire('FCom_Sales_Workflow::customerAddsItems', [
                    'post' => $post,
                    'result' => &$result,
                ]);

                $item = $result['items'][0];
                if (!empty($item['status']) && $item['status'] === 'added') {
                    $this->message('The product has been added to your cart');
                } elseif (!empty($item['error'])) {
                    $this->message($item['error'], 'error');
                    $this->BResponse->redirect($item['product'] ? $item['product']->url() : $cartHref);
                    return;
                } else {
                    $this->message("Unknown error, couldn't add item to cart", 'error');
                    $this->BResponse->redirect($item['product'] ? $item['product']->url() : $cartHref);
                    return;
                }
                break;
            }
        } else {
            $this->BEvents->fire('FCom_Sales_Workflow::customerUpdatesCart', [
                'post' => $post,
                'result' => &$result,
            ]);
            if (!empty($result['items'])) {
                foreach ($result['items'] as $item) {
                    if (!empty($item['status'])) {
                        switch ($item['status']) {
                            case 'updated':
                                $this->message('Cart item has been updated');
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
            }
        }
        $this->BResponse->redirect($cartHref);
    }

    public function action_addxhr__POST()
    {
        $cartHref = $this->BApp->href('cart');
        $post = $this->BRequest->post();
        $result = [];
        switch ($post['action']) {
        case 'add':
            $this->BEvents->fire('FCom_Sales_Workflow::customerAddsItems', [
                'post' => $post,
                'result' => &$result,
            ]);

            $item = $result['items'][0];
            if (empty($item['error'])) {
                $result = ['error' => $item['error']];
            } else {
                $cart = $this->FCom_Sales_Model_Cart->sessionCart();
                $p = $result['items'][0]->product();
                $result = [
                    'title' => 'Added to cart',
                    'html' => '<img src="' . $p->thumbUrl(35, 35) . '" width="35" height="35" style="float:left"/> '
                        . htmlspecialchars($p->product_name)
                        . (!empty($post['qty']) && $post['qty'] > 1 ? ' (' . $post['qty'] . ')' : '')
                        . '<br><br><a href="' . $cartHref . '" class="button">Go to cart</a>',
                    'minicart_html' => $this->BLayout->view('checkout/cart/block')->render(),
                    'cnt' => $cart->itemQty(),
                    'subtotal' => $cart->subtotal,
                ];
            }

            break;
        }

        $this->BResponse->json($result);
    }
}
