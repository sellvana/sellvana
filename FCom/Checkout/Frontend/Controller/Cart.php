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


        $layout->view('breadcrumbs')->set('crumbs', [['label' => 'Home', 'href' =>  BApp::baseUrl()],
            ['label' => 'Cart', 'active' => true]]);

        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        BEvents::i()->fire(__CLASS__ . '::action_cart:cart', ['cart' => $cart]);

        $shippingEstimate = BSession::i()->get('shipping_estimate');
        $layout->view('checkout/cart')->set(['cart' => $cart, 'shipping_esitmate' => $shippingEstimate]);
        $this->layout('/checkout/cart');
    }

    public function action_add__POST()
    {
        $cartHref = BApp::href('cart');
        $post = BRequest::i()->post();
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        if (isset($post['action'])) {
            switch ($post['action']) {
            case 'add':
                $p = FCom_Catalog_Model_Product::i()->load($post['id']);
                $data_serialized = BUtil::objectToArray(json_decode($p->data_serialized));
                $price = $p->base_price;
                $qty = !empty($post['qty']) ? $post['qty'] : 1;
                if (isset($data_serialized['variants'])) {
                   $validate = false;
                   foreach($data_serialized['variants'] as $variant) {
                       $tmp = [];
                       foreach ($variant['fields'] as $key => $val) {
                           if ($post[$key] == $val) {
                               $tmp[$key] = $val;
                           }
                       }
                       if (in_array($tmp, $variant)) {
                           $validate = true;
                           $price = ($variant['price'] != '')? $variant['price'] : $price;
                           if ($variant['qty'] == '' || $variant['qty'] == 0) {
                               $validate = false;
                           }
                           if ($qty > $variant['qty']) {
                               $qty = $variant['qty'];
                               $this->message('This product variant current has '.$qty.' items in stock .', 'info');
                           }
                           if ($qty == 0) {
                               $this->message('Quantity must be larger 0.', 'error');
                               $validate = false;
                           }
                       }
                   }
                } else {
                    $validate = true;
                }
                if ($validate) {
                    $p = FCom_Catalog_Model_Product::i()->load($post['id']);
                    if (!$p) {
                        // todo add message to be displayed
                        BResponse::i()->redirect('/');
                        return;
                    }

                    $options = ['qty' => $qty, 'price' => $price];
                    if (Bapp::m('FCom_Customer') && FCom_Customer_Model_Customer::isLoggedIn()) {
                        $cart->customer_id = FCom_Customer_Model_Customer::sessionUserId();
                        $cart->save();
                    }
                    $cart->addProduct($p->id(), $options)->calculateTotals()->save();
                    $this->message('The product has been added to your cart');
                } else {
                    $this->message('This product variant does not exists.', 'error');
                }

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
                    foreach ($post['qty'] as $id => $qty) {
                        if ($qty > 0) {
                            $item = $cart->childById('items', $id);
                            if ($item) {
                                $item->set('qty', $qty)->save();
                            }
                        } //todo: else remove item?
                    }
                }
                if (!empty($post['postcode'])) {
                    $estimate = [];
                    foreach (FCom_Sales_Main::i()->getShippingMethods() as $shipping) {
                        $estimate[] = ['estimate' => $shipping->getEstimate(), 'description' => $shipping->getDescription()];
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
        $result = [];
        switch ($post['action']) {
        case 'add':
            $p = FCom_Catalog_Model_Product::i()->load($post['id']);
            if (!$p) {
                BResponse::i()->json(['title' => "Incorrect product id"]);
                return;
            }

            $options = ['qty' => $post['qty'], 'price' => $p->base_price];
            if (Bapp::m('FCom_Customer') && FCom_Customer_Model_Customer::isLoggedIn()) {
                $cart->customer_id = FCom_Customer_Model_Customer::sessionUserId();
                $cart->save();
            }
            $cart->addProduct($p->id(), $options)->calculateTotals()->save();
            $result = [
                'title' => 'Added to cart',
                'html' => '<img src="' . $p->thumbUrl(35, 35) . '" width="35" height="35" style="float:left"/> '
                    . htmlspecialchars($p->product_name)
                    . (!empty($post['qty']) && $post['qty'] > 1 ? ' (' . $post['qty'] . ')' : '')
                    . '<br><br><a href="' . $cartHref . '" class="button">Go to cart</a>',
                'minicart_html' => BLayout::i()->view('checkout/cart/block')->render(),
                'cnt' => $cart->itemQty(),
                'subtotal' => $cart->subtotal,
            ];
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
        $cart->addProduct($product->id(), ['qty' => $qty, 'price' => $product->base_price]);
    }
}
