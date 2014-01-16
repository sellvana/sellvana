<?php

class FCom_Checkout_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_cart()
    {
        $layout = BLayout::i();

        $layout->view('checkout/cart')->set('redirectLogin', false);
        if (BApp::m('FCom_Customer') && FCom_Customer_Model_Customer::isLoggedIn() == false) {
            $layout->view('checkout/cart')->set('redirectLogin', true);
        }


        $layout->view('breadcrumbs')->set('crumbs', array(array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Cart', 'active'=>true)));

        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        BEvents::i()->fire('FCom_Checkout_Frontend_Controller::action_cart:cart', array('cart'=>$cart));

        $shippingEstimate = BSession::i()->data('shipping_estimate');
        $layout->view('checkout/cart')->set('cart', $cart);
        $layout->view('checkout/cart')->set('shipping_esitmate', $shippingEstimate);
        $this->layout('/checkout/cart');
    }

    public function action_cart__POST()
    {
        $cartHref = BApp::href('cart');
        $post = BRequest::i()->post();
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        if (BRequest::i()->xhr() || (isset($post['action']) && $post['action'] == 'add')) {
            $result = array();
            switch ($post['action']) {
            case 'add':
                $p = FCom_Catalog_Model_Product::i()->load($post['id']);
                if (!$p){
                    BResponse::i()->json(array('title'=>"Incorrect product id"));
                    return;
                }

                $options=array('qty' => $post['qty'], 'price' => $p->base_price);
                if (Bapp::m('FCom_Customer') && FCom_Customer_Model_Customer::isLoggedIn()) {
                    $cart->customer_id = FCom_Customer_Model_Customer::sessionUserId();
                    $cart->save();
                }
                $cart->addProduct($p->id(), $options)->calculateTotals()->save();
                $result = array(
                    'title' => 'Added to cart',
                    'html' => '<img src="'.$p->thumbUrl(35, 35).'" width="35" height="35" style="float:left"/> '.htmlspecialchars($p->product_name)
                        .(!empty($post['qty']) && $post['qty']>1 ? ' ('.$post['qty'].')' : '')
                        .'<br><br><a href="'.$cartHref.'" class="button">Go to cart</a>',
                    'minicart_html' => BLayout::i()->view('checkout/cart/block')->render(),
                    'cnt' => $cart->itemQty(),
                    'subtotal' => $cart->subtotal,
                );
                break;
            }
            if (BRequest::i()->xhr()) {
                BResponse::i()->json($result);
            } else {
                BResponse::i()->redirect($cartHref); // not sure if this is the best way to go (most likely it is not)
            }
        } else {
            $cart->items();
            if (!empty($post['remove'])) {
                foreach ($post['remove'] as $id) {
                    $cart->removeItem($id);
                }

            }
            if (!empty($post['qty'])) {
                foreach ($post['qty'] as $id=>$qty) {
                    $item = $cart->childById('items', $id);
                    if ($item) {
                        $item->set('qty', $qty)->save();
                    }
                }
            }
            if (!empty($post['postcode'])) {
                $estimate = array();
                foreach (FCom_Sales_Main::i()->getShippingMethods() as $shipping) {
                    $estimate[] = array('estimate' => $shipping->getEstimate(), 'description' => $shipping->getDescription());
                }
                BSession::i()->data('shipping_estimate', $estimate);
            }
            $cart->calculateTotals()->save();
            BResponse::i()->redirect($cartHref);
        }
    }

    public static function onAddToCart($args)
    {
        $product = $args['product'];
        $qty = $args['qty'];
        if (!$product || !$product->id()) {
            return false;
        }

        $qty = !empty($qty) ? $qty : 1;
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        $cart->addProduct($product->id(), array('qty' => $qty, 'price' => $product->base_price));
    }
}
