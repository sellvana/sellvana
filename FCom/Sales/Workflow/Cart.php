<?php

/**
 * Class FCom_Sales_Workflow_Cart
 *
 * Uses:
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Sales_Model_Order $FCom_Sales_Model_Order
 */
class FCom_Sales_Workflow_Cart extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerCreatesNewCart',

        'customerLogsIn',
        'customerLogsOut',

        'customerAddsItemsToCart',
        'customerUpdatesCart',

        'customerRequestsShippingEstimate',

        'customerAbandonsCart',
    ];

    public function customerCreatesNewCart($args)
    {
        // get cookie token ttl from config
        $ttl = $this->BConfig->get('modules/FCom_Sales/cart_cookie_token_ttl_days') * 86400;
        // get logged in customer
        $customer = $this->FCom_Customer_Model_Customer->sessionUser();
        // generate token
        $cookieToken = $this->BUtil->randomString(32);

        // create cart record
        /** @var FCom_Sales_Model_Cart $cart */
        $cart = $this->FCom_Sales_Model_Cart->create([
            'cookie_token' => (string)$cookieToken,
        ]);
        $cart->state()->overall()->setActive();

        if ($customer) {
            $cart->set([
                'customer_id' => $customer->id(),
                'customer_email' => $customer->get('email'),
            ])->importAddressesFromCustomer($customer)->calculateTotals();
        }

        $cart->save();

        $this->FCom_Sales_Model_Cart->resetSessionCart($cart);

        // set cookie cart token
        $this->BResponse->cookie('cart', $cookieToken, $ttl);
    }

    /**
     * @throws BException
     */
    public function customerLogsIn($args)
    {
        // load just logged in customer
        $customer = $this->FCom_Customer_Model_Customer->sessionUser();
        // something wrong, abort abort!
        if (!$customer) {
            $this->BDebug->warning('Customer model expected');
            return;
        }
        $cartHlp = $this->FCom_Sales_Model_Cart;
        // get session cart id
        $sessCart = $cartHlp->sessionCart();
        // try to load customer cart which is new (not abandoned or converted to order)
        $custCart = $cartHlp->loadWhere([
            'customer_id' => $customer->id(),
            'state_overall' => FCom_Sales_Model_Cart_State_Overall::ACTIVE
        ]);

        if ($sessCart && $custCart && $sessCart->id() !== $custCart->id()) {

            // if both current session cart and customer cart exist and they're different carts
            $custCart->merge($sessCart); // merge them into customer cart
            $cartHlp->resetSessionCart($custCart); // and set it as session cart

        } elseif ($sessCart && !$custCart) { // if only session cart exist

            $sessCart->set([
                'customer_id' => $customer->id(),
                'customer_email' => $customer->get('email'),
            ])->save(); // assign it to customer

        } elseif (!$sessCart && $custCart) { // if only customer cart exist

            $cartHlp->resetSessionCart($custCart); // set it as session cart

        }

        if (!$sessCart->hasCompleteAddress('shipping')) {
            $sessCart->importAddressesFromCustomer($customer)->calculateTotals()->save();
        }
    }

    /**
     *
     */
    public function customerLogsOut($args)
    {
        $this->FCom_Sales_Model_Cart->resetSessionCart();
    }

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
            ->select(['inventory_id' => 'i.id', 'i.unit_cost', 'i.net_weight', 'i.shipping_weight', 'i.shipping_size',
                    'i.pack_separate', 'i.qty_in_stock', 'i.qty_cart_min', 'i.qty_cart_inc', 'i.qty_buffer', 'i.qty_reserved',
                    'i.allow_backorder'])
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

        $cart->set('recalc_shipping_rates', 1)->calculateTotals()->saveAllDetails();

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

        $cart->set('recalc_shipping_rates', 1)->calculateTotals()->saveAllDetails();

        $args['result']['items'] = $items;
    }

    public function customerRequestsShippingEstimate($args)
    {
        $postcode = $args['post']['shipping']['postcode'];
        $cart = $this->_getCart($args);
        $cart->set(['shipping_postcode' => $postcode, 'recalc_shipping_rates' => 1])->calculateTotals()->saveAllDetails();
        $args['result']['status'] = 'success';
    }

    public function customerAbandonsCart($args)
    {
        $cart = $this->_getCart($args);
        $cart->state()->overall()->setAbandoned();
        $cart->save();
        $this->BLayout->view('email/sales/cart-state-abandoned.html.twig')->email();
    }
}
