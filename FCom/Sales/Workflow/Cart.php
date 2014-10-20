<?php

class FCom_Sales_Workflow_Cart extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerAddsItems',
        'customerUpdatesCart',
/*
        'customerUpdatesItems',
        'customerRemovesItems',

        'customerLogsIn',
        'customerChoosesGuestCheckout',
        'customerCreatesAccount',

        'customerAddsPromoCode',
        'customerRequestsShippingEstimate',

        'customerCreatesShippingAddress',
        'customerCreatesBillingAddress',
        'customerUpdatesShippingAddress',
        'customerUpdatesBillingAddress',

        'customerUpdatesShippingMethod',
        'customerUpdatesBillingMethod',
*/
        'customerAbandonsCart',

        'customerPlacesOrder',
    ];

    /**
     * @todo normalize API
     */
    public function customerAddsItems($args)
    {
        $cart = $this->_getCart($args, true);
        $post = !empty($args['post']) ? $args['post'] : null;
        // prepare items data
        if (!empty($args['items'])) {
            $reqItems = $args['items'];
        } else {
            $reqItems = [$post];
        }
        $items = [];
        $ids = [];
        foreach ($reqItems as $reqItem) {
            if (is_array($reqItem)) {
                if (empty($reqItem['id']) || !is_numeric($reqItem['id'])) {
                    $item['error'] = 'Invalid item to add to cart';
                }
                $item = ['id' => $reqItem['id'], 'qty' => 1];
                if (!empty($reqItem['qty'])) {
                    $item['qty'] = $reqItem['qty'];
                }
                $ids[] = $item['id'];
            } elseif (is_numeric($reqItem)) {
                $item = ['id' => $reqItem, 'qty' => 1];
                $ids[] = $item['id'];
            } elseif ($reqItem instanceof FCom_Catalog_Model_Product) {
                $item = ['id' => $reqItem->getId(), 'qty' => 1, 'product' => $reqItem];
            }
            $items[] = $item;
        }

        // retrieve product records
        $products = $this->FCom_Catalog_Model_Product->orm('p')
            ->where_in('p.id', $ids)->find_many_assoc();
        foreach ($items as $i => $item) {
            if (!empty($item['error'])) {
                continue;
            }
            if (empty($products[$item['id']])) {
                $items[$i]['product'] = false;
                $items[$i]['error'] = 'Invalid product to add to cart';
                continue;
            }
            $items[$i]['product'] = $products[$item['id']];
        }

        // add items to cart
        foreach ($items as &$item) {
            if (!empty($item['error'])) {
                continue;
            }
            $p = $item['product'];
            $options = [
                'qty' => $item['qty'],
                'price' => $p->getPrice(),
                'sku' => $p->get('local_sku'),
            ];
            $result = [];
            $this->BEvents->fire(__METHOD__ . ':validate', [
                'product' => $p,
                'post' => $post,
                'options' => &$options,
                'result' => &$result,
            ]);
            if (!empty($result['error'])) {
                $item['error'] = $result['error'];
                continue;
            }
            $customer = $this->_getCustomer($args);
            if ($customer && !$cart->get('customer_id')) {
                $cart->set('customer_id', $customer->id());
            }
            $cart->addProduct($p->id(), $options);
            $item['status'] = 'added';
        }
        unset($item);
        $cart->calculateTotals()->save();

        $args['result']['items'] = $items;
    }

    /**
     * @todo normalize API
     * @todo move "variants" code to FCom_CustomFields module
     */
    public function customerUpdatesCart($args)
    {
        $cart = $this->_getCart($args, true);
        $post = !empty($args['post']) ? $args['post'] : null;
        $cartItems = $cart->items(true);
        $items = [];

        // remove items
        if (!empty($post['remove'])) {
            foreach ($post['remove'] as $id => $arrVariant) {
                if (empty($cartItems[$id])) {
                    $items[$id] = 'Item to delete not found';
                    continue;
                }
                $item = $cartItems[$id];
                $variants = $item->getData('variants');
                if (null === $variants || count($variants) == 1) { //TODO: explain and improve logic
                    $cart->removeItem($id);
                    $items[$id] = ['id' => $id, 'status' => 'removed', 'name' => $item->product()->get('product_name')];
                }
            }
        }

        // update qty (or remove if qty==0)
        if (!empty($post['qty'])) {
            foreach ($post['qty'] as $id => $arrQty) {
                if (!empty($items[$id])) { // already removed
                    continue;
                }
                if (empty($cartItems[$id])) {
                    $items[$id] = 'Item to update not found';
                    continue;
                }
                $item = $cartItems[$id];
                $variants = $item->getData('variants');
                $totalQty = 0;
                if (null !== $variants && is_array($arrQty)) {
                    foreach ($arrQty as $variantId => $qty) {
                        if ($qty > 0) {
                            $variants[$variantId]['variant_qty'] = $qty;
                            $totalQty += $qty;
                        }
                        if ($qty <= 0 || isset($post['remove'][$id][$variantId])) {
                            unset($variants[$variantId]);
                        }
                    }
                } else {
                    $totalQty = $arrQty[0];
                }
                if ($totalQty > 0) {
                    $item->set('qty', $totalQty)->setData('variants', $variants)->save();
                    $items[] = ['id' => $id, 'status' => 'updated', 'name' => $item->product()->get('product_name')];
                } elseif ($totalQty <= 0 || empty($variants)) {
                    $item->delete();
                    unset($cartItems[$id]);
                    $items[] = ['id' => $id, 'status' => 'deleted', 'name' => $item->product()->get('product_name')];
                }
            }
        }

        // update postcode and estimate shipping
        if (!empty($post['postcode'])) {
            $estimate = [];
            foreach ($this->FCom_Sales_Main->getShippingMethods() as $shipping) {
                $estimate[] = ['estimate' => $shipping->getEstimate(), 'description' => $shipping->getDescription()];
            }
            $cart->setData('shipping_estimate', $estimate);
        }

        $cart->calculateTotals()->save();

        $args['result']['items'] = $items;
    }

    public function customerUpdatesItems($args)
    {

    }

    public function customerRemovesItems($args)
    {

    }

    public function customerLogsIn($args)
    {

    }

    public function customerChoosesGuestCheckout($args)
    {
    }

    public function customerCreatesAccount($args)
    {
    }

    public function customerAddsPromoCode($args)
    {
    }

    public function customerRequestsShippingEstimate($args)
    {
    }

    public function customerCreatesShippingAddress($args)
    {
    }

    public function customerCreatesBillingAddress($args)
    {
    }

    public function customerUpdatesShippingAddress($args)
    {
    }

    public function customerUpdatesBillingAddress($args)
    {
    }

    public function customerUpdatesShippingMethod($args)
    {
    }

    public function customerUpdatesBillingMethod($args)
    {
    }

    public function customerAbandonsCart($args)
    {
        $cart = $this->_getCart($args);
        $cart->setStatusAbandoned()->save();
        $this->BLayout->view('email/sales/cart-state-abandoned.html.twig')->email();
    }

    public function customerPlacesOrder($args)
    {
        $customer = $this->_getCustomer($args);
        $cart = $this->_getCart($args);

        $order = $this->FCom_Sales_Model_Order->create();

        $order->importDataFromCart($cart);

        $this->FCom_Sales_Main->workflowAction('customerSubmitsPayment', [
            'cart' => $cart,
            'order' => $order,
            'result' => &$result,
        ]);

        $cart->setStateOrdered()->save();

        $args['result']['order'] = $order;
    }
}
