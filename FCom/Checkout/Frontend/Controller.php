<?php

class FCom_Checkout_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_cart()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Cart', 'active'=>true));
        $cart = FCom_Checkout_Model_Cart::i()->sessionCart()->calcTotals();
        BPubSub::i()->fire('FCom_Checkout_Frontend_Controller::action_cart.cart', array('cart'=>$cart));
        $layout->view('checkout/cart')->cart = $cart;
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
                $cart->addProduct($post['id'], $post);
                $result = array(
                    'title' => 'Added to cart',
                    'html' => '<img src="'.$p->thumbUrl(35, 35).'" width="35" height="35" style="float:left"/> '.htmlspecialchars($p->product_name)
                        .(!empty($post['qty']) && $post['qty']>1 ? ' ('.$post['qty'].')' : '')
                        .'<br><a href="'.$cartHref.'" class="button">Go to cart</a>',
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
            $cart->calcTotals()->save();
            BResponse::i()->redirect($cartHref);
        }
    }
/*
    public function action_checkout()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Checkout', 'active'=>true));
        $cart = FCom_Checkout_Model_Cart::i()->sessionCart()->calcTotals();
        //get countries
        $countries = FCom_Checkout_Model_Countries::i()->getList();


        BPubSub::i()->fire('FCom_Checkout_Frontend_Controller::action_checkout.checkout', array('cart'=>$cart));
        $layout->view('checkout/checkout')->cart = $cart;
        $layout->view('checkout/checkout')->countries = $countries;
        $this->layout('/checkout/checkout');
        BResponse::i()->render();
    }
 * 
 */
}
