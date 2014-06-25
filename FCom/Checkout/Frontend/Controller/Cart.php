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

        $cart = $this->FCom_Sales_Model_Cart->sessionCart(true);
        $this->BEvents->fire(__CLASS__ . '::action_cart:cart', ['cart' => $cart]);

        $shippingEstimate = $this->BSession->get('shipping_estimate');
        $layout->view('checkout/cart')->set(['cart' => $cart, 'shipping_esitmate' => $shippingEstimate]);
        $this->layout('/checkout/cart');
    }

    public function action_add__POST()
    {
        $cartHref = $this->BApp->href('cart');
        $post = $this->BRequest->post();
        $cart = $this->FCom_Sales_Model_Cart->sessionCart(true);
        if (isset($post['action'])) {
            switch ($post['action']) {
            case 'add':
                $p = $this->FCom_Catalog_Model_Product->load($post['id']);
                if (!$p) {
                    // todo add message to be displayed
                    $this->BResponse->redirect('/');
                    return;
                }
                $options = [
                    'qty' => !empty($post['qty']) ? $post['qty'] : 1, 
                    'price' => $p->getPrice(),
                    'sku' => $p->get('local_sku'),
                ];
                $result = [];
                $validate = $this->BEvents->fire(__METHOD__ . ':validate', [
                    'controller' => $this, 
                    'product' => $p, 
                    'post' => $post,
                    'options' => &$options,
                    'result' => &$result,
                ]);

                if (empty($result['error'])) {
                    if ($this->BApp->m('FCom_Customer') && $this->FCom_Customer_Model_Customer->isLoggedIn()) {
                        $cart->customer_id = $this->FCom_Customer_Model_Customer->sessionUserId();
                        $cart->save();
                    }
                    if (isset($post['shopper'])) {
                        $options['shopper'] = $post['shopper'];
                        foreach($options['shopper'] as $key => $value) {
                            if (!isset($value['val']) || $value['val'] == '') {
                                unset($options['shopper'][$key]);
                            }
                            if ($value['val'] == 'checkbox') {
                                unset($options['shopper'][$key]['val']);
                            }
                        }
                    };
                    $cart->addProduct($p->id(), $options)->calculateTotals()->save();
                    $this->message('The product has been added to your cart');
                } else {
                    $this->message($result['error'], 'error');
                    $this->BResponse->redirect($p->url());
                    return;
                }
                break;
            }
        } else {
            $items = $cart->items();
            if (count($items)) {
                if (!empty($post['remove'])) {
                    foreach ($post['remove'] as $id => $arr_variant) {
                        $item = $cart->childById('items', $id);
                        $variants = $item->getData('variants');
                        if (null === $variants || count($variants) == 1) {
                            $cart->removeItem($id);
                        }
                    }
                }
                if (!empty($post['qty'])) {
                    foreach ($post['qty'] as $id => $arr_qty) {
                        $item = $cart->childById('items', $id);
                        if ($item) {
                            $variants = $item->getData('variants');
                            $totalQty = 0;
                            if (null !== $variants) {
                                foreach ($arr_qty as $variantId => $qty) {
                                    if ($qty > 0) {
                                        $variants[$variantId]['variant_qty'] = $qty;
                                        $totalQty += $qty;
                                    }
                                    if ($qty <= 0 || isset($post['remove'][$id][$variantId])) {
                                        unset($variants[$variantId]);
                                    }
                                }
                            } else {
                                $totalQty = $arr_qty[0];
                            }
                            if ($totalQty > 0) {
                                $item->set('qty', $totalQty)->setData('variants', $variants)->save();
                            }
                            if ($totalQty <= 0 || empty($variants)){
                                $cart->removeItem($id);
                            }
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
                $this->message('Your cart has been updated');
            }
        }
        $this->BResponse->redirect($cartHref);
    }

    public function action_addxhr__POST()
    {
        $cartHref = $this->BApp->href('cart');
        $post = $this->BRequest->post();
        $cart = $this->FCom_Sales_Model_Cart->sessionCart(true);
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
        $cart = $this->FCom_Sales_Model_Cart->sessionCart(true);
        $cart->addProduct($product->id(), ['qty' => $qty, 'price' => $product->base_price]);
    }
}
