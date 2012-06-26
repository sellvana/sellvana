<?php

class FCom_Checkout_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_cart()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array(array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Cart', 'active'=>true));

        $cart = FCom_Checkout_Model_Cart::i()->sessionCart()->calcTotals();
        BPubSub::i()->fire('FCom_Checkout_Frontend_Controller::action_cart.cart', array('cart'=>$cart));
        $shippingEstimate = BSession::i()->data('shipping_estimate');
        $layout->view('checkout/cart')->cart = $cart;
        $layout->view('checkout/cart')->shipping_esitmate = $shippingEstimate;
        $this->layout('/checkout/cart');
        BResponse::i()->render();
    }

    public function action_cart_post()
    {
        $cartHref = BApp::href('cart');
        $post = BRequest::i()->post();
        $cart = FCom_Checkout_Model_Cart::i()->sessionCart();
        if (BRequest::i()->xhr()) {
            $result = array();
            switch ($post['action']) {
            case 'add':
                $p = FCom_Catalog_Model_Product::i()->load($post['id']);
                if (!$p){
                    BResponse::i()->json(array('title'=>"Incorrect product id"));
                    return;
                }
                $options=array('qty' => $post['qty'], 'price' => $p->base_price);
                $cart->addProduct($p->id(), $options);
                $result = array(
                    'title' => 'Added to cart',
                    'html' => '<img src="'.$p->thumbUrl(35, 35).'" width="35" height="35" style="float:left"/> '.htmlspecialchars($p->product_name)
                        .(!empty($post['qty']) && $post['qty']>1 ? ' ('.$post['qty'].')' : '')
                        .'<br><br><a href="'.$cartHref.'" class="button">Go to cart</a>',
                    'cnt' => $cart->itemQty(),
                );
                break;
            }
            BResponse::i()->json($result);
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
                    if ($item) $item->set('qty', $qty)->save();
                }
            }
            if (!empty($post['postcode'])) {
                $estimate = array();
                foreach (FCom_Checkout_Model_Cart::i()->getShippingMethods() as $shipping) {
                    $estimate[] = array('estimate' => $shipping->getEstimate(), 'description' => $shipping->getDescription());
                }
                BSession::i()->data('shipping_estimate', $estimate);
            }
            $cart->calcTotals()->save();
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
        $cart = FCom_Checkout_Model_Cart::i()->sessionCart();
        $options=array('qty' => $qty, 'price' => $product->base_price);
        $cart->addProduct($product->id(), $options);
    }
}
