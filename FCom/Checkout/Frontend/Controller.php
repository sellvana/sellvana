<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Checkout_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function action_cart()
    {
        $layout = $this->BLayout;

        $layout->view('checkout/cart')->set('redirectLogin', false);
        if ($this->BApp->m('FCom_Customer') && $this->FCom_Customer_Model_Customer->isLoggedIn() == false) {
            $layout->view('checkout/cart')->set('redirectLogin', true);
        }


        $layout->view('breadcrumbs')->set('crumbs', [['label' => 'Home', 'href' =>  $this->BApp->baseUrl()],
            ['label' => 'Cart', 'active' => true]]);

        $cart = $this->FCom_Sales_Model_Cart->sessionCart();
        BEvents::i()->fire('FCom_Checkout_Frontend_Controller::action_cart:cart', ['cart' => $cart]);

        $shippingEstimate = $this->BSession->get('shipping_estimate');
        $layout->view('checkout/cart')->set('cart', $cart);
        $layout->view('checkout/cart')->set('shipping_esitmate', $shippingEstimate);
        $this->layout('/checkout/cart');
    }

    public function action_cart__POST()
    {
        $cartHref = $this->BApp->href('cart');
        $post = $this->BRequest->post();
        $cart = $this->FCom_Sales_Model_Cart->sessionCart();
        if ($this->BRequest->xhr() || (isset($post['action']) && $post['action'] == 'add')) {
            $result = [];
            switch ($post['action']) {
            case 'add':
                $p = $this->FCom_Catalog_Model_Product->load($post['id']);
                if (!$p) {
                    $this->BResponse->json(['title' => "Incorrect product id"]);
                    return;
                }

                $options = ['qty' => $post['qty'], 'price' => $p->base_price];
                if ($this->BApp->m('FCom_Customer') && $this->FCom_Customer_Model_Customer->isLoggedIn()) {
                    $cart->customer_id = $this->FCom_Customer_Model_Customer->sessionUserId();
                    $cart->save();
                }
                $cart->addProduct($p->id(), $options)->calculateTotals()->save();
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
                break;
            }
            if ($this->BRequest->xhr()) {
                $this->BResponse->json($result);
                return;
            } else {
                $this->BResponse->redirect($cartHref); // not sure if this is the best way to go (most likely it is not)
            }
        } else {
            $cart->items();
            if (!empty($post['remove'])) {
                foreach ($post['remove'] as $id) {
                    $cart->removeItem($id);
                }

            }
            if (!empty($post['qty'])) {
                foreach ($post['qty'] as $id => $qty) {
                    $item = $cart->childById('items', $id);
                    if ($item) {
                        $item->set('qty', $qty)->save();
                    }
                }
            }
            if (!empty($post['postcode'])) {
                $estimate = [];
                foreach ($this->FCom_Sales_Main->getShippingMethods() as $shipping) {
                    $estimate[] = ['estimate' => $shipping->getEstimate(), 'description' => $shipping->getDescription()];
                }
                $this->BSession->set('shipping_estimate', $estimate);
            }
            $cart->calculateTotals()->save();
            $this->BResponse->redirect($cartHref);
        }
    }

    public function onAddToCart($args)
    {
        $product = $args['product'];
        $qty = $args['qty'];
        if (!$product || !$product->id()) {
            return false;
        }

        $qty = !empty($qty) ? $qty : 1;
        $cart = $this->FCom_Sales_Model_Cart->sessionCart();
        $cart->addProduct($product->id(), ['qty' => $qty, 'price' => $product->base_price]);
    }
}
