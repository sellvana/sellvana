<?php

/**
 * Class FCom_Sales_Workflow_Cart
 *
 * Uses:
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Order $FCom_Sales_Model_Order
 */
class FCom_Sales_Workflow_Cart extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerAddsItemsToCart',
        'customerUpdatesCart',

        'customerAbandonsCart',

        'customerPlacesOrder',
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
    ];

    /**
     * STEP: Customer Adds Items To Cart
     *
     * Handles default logic of adding item from post data as cart item
     * Fires event to calculate details for variants, frontend fields, bundles, etc.
     *
     * @todo normalize API
     *
     * @param array $args
     *      post:
     *          id:
     *          qty:
     *      result:
     *          items:
     *              - { status: added|error }
     */
    public function customerAddsItemsToCart($args)
    {
        $cart = $this->_getCart($args, true);
        $post = !empty($args['post']) ? $args['post'] : null;
        // prepare items data
        if (!empty($args['items'])) {
            $reqItems = $args['items'];
        } else {
            $reqItems = [$post];
        }
        $itemsData = [];
        $ids = [];
        foreach ($reqItems as $reqItem) {
            if (is_array($reqItem)) {
                if (empty($reqItem['id']) || !is_numeric($reqItem['id'])) {
                    $item['error'] = 'Invalid item to add to cart';
                }
                $item = $reqItem;
                if (empty($item['qty'])) {
                    $item['qty'] = 1;
                }
                $ids[] = $item['id'];
            } elseif (is_numeric($reqItem)) {
                $item = ['id' => $reqItem, 'qty' => 1];
                $ids[] = $item['id'];
            } elseif ($reqItem instanceof FCom_Catalog_Model_Product) {
                $item = ['id' => $reqItem->getId(), 'qty' => 1, 'product' => $reqItem];
            }
            $itemsData[] = $item;
        }

        // retrieve product records
        $products = $this->FCom_Catalog_Model_Product->orm('p')
            ->where_in('p.id', $ids)
            ->left_outer_join('FCom_Catalog_Model_InventorySku', ['i.inventory_sku', '=', 'p.inventory_sku'], 'i')
            ->select('p.*')
            ->select('i.id', 'inventory_id')
            ->find_many_assoc();
        foreach ($itemsData as $i => &$item) {
            if (!empty($item['error'])) {
                continue;
            }
            if (empty($products[$item['id']])) {
                $item['product'] = false;
                $item['error'] = 'Invalid product to add to cart';
                continue;
            }
            $p = $item['product'] = $products[$item['id']];
            $item['details'] = [
                'qty' => $item['qty'],
                'price' => $p->getPrice(),
                'product_id' => $p->id(),
                'product_sku' => $p->get('product_sku'),
                'inventory_id' => $p->get('inventory_id'),
                'inventory_sku' => $p->get('inventory_sku'),
            ];

            $item['details']['signature'] = [
                'product_sku' => $p->get('product_sku'),
                'inventory_sku' => $p->get('inventory_sku'),
            ];

        }
        unset($item);

        $this->BEvents->fire(__METHOD__ . ':calcDetails', [
            'post' => $post,
            'items' => &$itemsData,
        ]);
        //echo "<pre>"; var_dump($itemsData); exit;
        // add items to cart
        foreach ($itemsData as &$item) {
            if (empty($item['error'])) {
                $cart->addProduct($item['product'], $item['details']);
                $item['status'] = 'added';
            } else {
            }
        }
        unset($item);

        $customer = $this->_getCustomer($args);
        if ($customer && !$cart->get('customer_id')) {
            $cart->set('customer_id', $customer->id());
        }

        $cart->calculateTotals()->save();

        $args['result']['items'] = $itemsData;
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

        $cart->calculateTotals()->saveAllDetails();

        $args['result']['items'] = $items;
    }
    /*
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
    */
    public function customerAbandonsCart($args)
    {
        $cart = $this->_getCart($args);
        $cart->setStatusAbandoned()->save();
        $this->BLayout->view('email/sales/cart-state-abandoned.html.twig')->email();
    }

    public function customerPlacesOrder($args)
    {
        /** @var FCom_Customer_Model_Customer $customer */
        $customer = $this->_getCustomer($args);

        /** @var FCom_Sales_Model_Cart $cart */
        $cart = $this->_getCart($args);

        /** @var FCom_Sales_Model_Order $order */
        $order = $this->FCom_Sales_Model_Order->create();

        $order->importDataFromCart($cart);

        if ($order->isPayable()) {
            $result = [];
            $this->FCom_Sales_Main->workflowAction('customerPaysOnCheckout', [
                'cart' => $cart,
                'order' => $order,
                'result' => &$result,
            ]);
        }

        $cart->setStateOrdered()->save();

        $args['result']['order'] = $order;
    }
}
