<?php

class FCom_Checkout_Frontend_Controller_Cart extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $layout = BLayout::i();

        $layout->view('checkout/cart')->set('redirectLogin', false);
        if (BApp::m('FCom_Customer') && FCom_Customer_Model_Customer::isLoggedIn() == false) {
            $layout->view('checkout/cart')->set('redirectLogin', true);
        }


        $layout->view('breadcrumbs')->set('crumbs', array(array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Cart', 'active'=>true)));

        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        BEvents::i()->fire( __CLASS__ . '::action_cart:cart', array('cart'=>$cart));

        $shippingEstimate = BSession::i()->get('shipping_estimate');
        $layout->view('checkout/cart')->set(array('cart' => $cart, 'shipping_esitmate' => $shippingEstimate));
        $this->layout('/checkout/cart');
    }

    public function action_add__POST()
    {
        $cartHref = BApp::href('cart');
        $post = BRequest::i()->post();
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        if ( isset($post['action']) ) {
            switch ($post['action']) {
            case 'add':
                $p = FCom_Catalog_Model_Product::i()->load($post['id']);
                if (!$p){
                    // todo add message to be displayed
                    BResponse::i()->redirect('/');
                    return;
                }
                $qty = !empty($post['qty']) ? $post['qty'] : 1;
                $options=array('qty' => $qty, 'price' => $p->base_price);
                if (Bapp::m('FCom_Customer') && FCom_Customer_Model_Customer::isLoggedIn()) {
                    $cart->customer_id = FCom_Customer_Model_Customer::sessionUserId();
                    $cart->save();
                }
                $cart->addProduct($p->id(), $options)->calculateTotals()->save();
                $this->message('The product has been added to your cart');
                break;
            }
        } else {
            $items = $cart->items();
            if (count($items)) {
                if (!empty($post['remove'])) {
                    foreach ($post['remove'] as $id) {
                        $cart->removeItem($id);
                    }
                }
                if (!empty($post['qty'])) {
                    foreach ($post['qty'] as $id=>$qty) {
                        if ($qty > 0) {
                            $item = $cart->childById('items', $id);
                            if ($item) {
                                $item->set('qty', $qty)->save();
                            }
                        } //todo: else remove item?
                    }
                }
                if (!empty($post['postcode'])) {
                    $estimate = array();
                    foreach (FCom_Sales_Main::i()->getShippingMethods() as $shipping) {
                        $estimate[] = array('estimate' => $shipping->getEstimate(), 'description' => $shipping->getDescription());
                    }
                    BSession::i()->set('shipping_estimate', $estimate);
                }
                $cart->calculateTotals()->save();
                $this->message('Your cart has been updated');
            }
        }
        BResponse::i()->redirect($cartHref);
    }

    public function action_addxhr__POST()
    {
        $cartHref = BApp::href('cart');
        $post = BRequest::i()->post();
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
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

        BResponse::i()->json($result);
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
