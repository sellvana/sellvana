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

        $layout->view('checkout/cart')->set('redirectLogin', false);
        if ($this->BApp->m('FCom_Customer') && $this->FCom_Customer_Model_Customer->isLoggedIn() == false) {
            $layout->view('checkout/cart')->set('redirectLogin', true);
        }


        $layout->view('breadcrumbs')->set('crumbs', [['label' => 'Home', 'href' =>  $this->BApp->baseUrl()],
            ['label' => 'Cart', 'active' => true]]);

        $cart = $this->FCom_Sales_Model_Cart->sessionCart();
        $this->BEvents->fire(__CLASS__ . '::action_cart:cart', ['cart' => $cart]);

        $shippingEstimate = $this->BSession->get('shipping_estimate');
        $layout->view('checkout/cart')->set(['cart' => $cart, 'shipping_esitmate' => $shippingEstimate]);
        $this->layout('/checkout/cart');
    }

    public function action_add__POST()
    {
        $cartHref = $this->BApp->href('cart');
        $post = $this->BRequest->post();
        $cart = $this->FCom_Sales_Model_Cart->sessionCart();
        if (isset($post['action'])) {
            switch ($post['action']) {
            case 'add':
                $p = $this->FCom_Catalog_Model_Product->load($post['id']);
                $variants = $this->BDb->many_as_array($this->FCom_CustomField_Model_ProductVariant->orm()->where('product_id', $post['id'])->find_many());
                $price = $p->base_price;
                $qty = !empty($post['qty']) ? $post['qty'] : 1;
                $prod_variant = [];
                if ($variants) {
                   $validate = false;
                   foreach ($variants as $variant) {
                       $tmp = [];
                       $variant['field_values'] = $this->BUtil->objectToArray(json_decode($variant['field_values']));
                       foreach ($variant['field_values'] as $key => $val) {
                           if (!empty($post[$key]) && $post[$key] == $val) {
                               $tmp[$key] = $val;
                           }
                       }
                       if (in_array($tmp, $variant)) {
                           $validate = true;
                           $prod_variant = $variant;
                           $price = ($variant['variant_price'] != '')? $variant['variant_price'] : $price;
                           if ($variant['variant_qty'] == '' || $variant['variant_qty'] == 0) {
                               $validate = false;
                           }
                           if ($qty > $variant['variant_qty']) {
                               $qty = $variant['variant_qty'];
                               $this->message('This product variant currently has '.$qty.' items in stock .', 'info');
                           }
                           if ($qty == 0) {
                               $this->message('The variant is not in stock', 'error');
                               $validate = false;
                           }
                       }
                   }
                } else {
                    $validate = true;
                }
                if ($validate) {
                    $p = $this->FCom_Catalog_Model_Product->load($post['id']);
                    if (!$p) {
                        // todo add message to be displayed
                        $this->BResponse->redirect('/');
                        return;
                    }
                    $options = ['qty' => $qty, 'price' => $price];
                    $prod_variant['variant_qty'] = $qty;
                    if (!empty($prod_variant)) {
                        $options['data']['variants'] = $prod_variant;
                    }
                    if ($this->BApp->m('FCom_Customer') && $this->FCom_Customer_Model_Customer->isLoggedIn()) {
                        $cart->customer_id = $this->FCom_Customer_Model_Customer->sessionUserId();
                        $cart->save();
                    }
                    $cart->addProduct($p->id(), $options)->calculateTotals()->save();
                    $this->message('The product has been added to your cart');
                } else {
                    $this->message('This product variant does not exists. Please choose other', 'error');
                    $this->BResponse->redirect($p->url());
                    return;
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
                    foreach ($this->FCom_Sales_Main->getShippingMethods() as $shipping) {
                        $estimate[] = ['estimate' => $shipping->getEstimate(), 'description' => $shipping->getDescription()];
                    }
                    $this->BSession->set('shipping_estimate', $estimate);
                }
                $cart->calculateTotals()->save();
                $this->message('Your cart has been updated');
            }
        }
        $this->BResponse->redirect($cartHref);
    }

    public function action_addxhr__POST()
    {
        $cartHref = $this->BApp->href('cart');
        $post = $this->BRequest->post();
        $cart = $this->FCom_Sales_Model_Cart->sessionCart();
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

        $this->BResponse->json($result);
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
