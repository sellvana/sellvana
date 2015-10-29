<?php

/**
 * Class Sellvana_Sales_Workflow_Cart
 *
 * Uses:
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_Sales_Workflow_Cart extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_customerCreatesNewCart($args)
    {
        // get cookie token ttl from config
        $ttl = $this->BConfig->get('modules/Sellvana_Sales/cart_cookie_token_ttl_days') * 86400;
        // get logged in customer
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        // generate token
        $cookieToken = $this->BUtil->randomString(32);

        // create cart record
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = $this->Sellvana_Sales_Model_Cart->create([
            'cookie_token' => (string)$cookieToken,
        ]);
        $cart->state()->overall()->setActive();
        $cart->setStoreCurrency();

        if ($customer) {
            $cart->set([
                'customer_id' => $customer->id(),
                'customer_email' => $customer->get('email'),
            ]);
            $cart->importAddressesFromCustomer($customer);
            $cart->calculateTotals();
        }

        $cart->save();

        $this->Sellvana_Sales_Model_Cart->resetSessionCart($cart);

        // set cookie cart token
        $this->BResponse->cookie('cart', $cookieToken, $ttl);
    }

    /**
     * @throws BException
     */
    public function action_customerLogsIn($args)
    {
        if ($this->BSession->get('admin_customer_id')) {
            return;
        }

        // load just logged in customer
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        // something wrong, abort abort!
        if (!$customer) {
            $this->BDebug->warning('Customer model expected');
            return;
        }
        $cartHlp = $this->Sellvana_Sales_Model_Cart;
        // get session cart id
        $sessCart = $cartHlp->sessionCart();
        // try to load customer cart which is new (not abandoned or converted to order)
        $custCart = $cartHlp->loadWhere([
            'customer_id' => $customer->id(),
            'state_overall' => Sellvana_Sales_Model_Cart_State_Overall::ACTIVE
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
            $sessCart->importAddressesFromCustomer($customer)->calculateTotals()->saveAllDetails();
        }
    }

    /**
     *
     */
    public function action_customerLogsOut($args)
    {
        $this->Sellvana_Sales_Model_Cart->resetSessionCart();
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
    public function action_customerAddsItemsToCart($args)
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
            } elseif ($reqItem instanceof Sellvana_Catalog_Model_Product) {
                $item = ['id' => $reqItem->id(), 'qty' => 1, 'product' => $reqItem];
            } else {
                $this->BDebug->log('Invalid reqItem: ' . print_r($reqItem, 1));
                continue;
            }
            $itemsData[] = $item;
        }

        // retrieve product records
        /** @var Sellvana_Catalog_Model_Product[] $products */
        if ($ids) {
            $products = $this->Sellvana_Catalog_Model_Product->orm('p')->where_in('p.id', $ids)->find_many_assoc();
        } else {
            $products = [];
        }
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
            $costModel = $p->getPriceModelByType('cost');
            // Basic details and signature, can be adjusted in :calcDetails event
            $item['details'] = [
                'qty' => $item['qty'],
                'product_id' => $p->id(),
                'product_sku' => $p->get('product_sku'),
                'inventory_sku' => $p->get('inventory_sku'),
                'cost' => $costModel ? $costModel->getPrice() : null,
                #'manage_inventory' => $p->get('manage_inventory'),
                'signature' => [
                    'product_sku' => $p->get('product_sku'),
                    'inventory_sku' => $p->get('inventory_sku'),
                ],
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
                //TODO: handle item error
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
     * @todo move "variants" code to Sellvana_CatalogFields module
     */
    public function action_customerUpdatesCart($args)
    {
        $cart = $this->_getCart($args, true);
        $post = !empty($args['post']) ? $args['post'] : null;
        $cartItems = $cart->items(true);
        $items = [];
        $recalc = false;

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
                    $recalc = true;
                    $cart->removeItem($id);
                    $items[$id] = ['id' => $id, 'status' => 'removed', 'name' => $item->getProduct()->get('product_name')];
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
                    $totalQty = $arrQty;
                }
                $product = $item->getProduct();
                if ($totalQty > 0) {
                    if ($item->get('qty') !== $totalQty) {
                        $recalc = true;
                    }
                    $item->set('qty', $totalQty)->setData('variants', $variants)->save();
                    $items[] = ['id' => $id, 'status' => 'updated', 'name' => $product ? $product->get('product_name') : ''];
                } elseif ($totalQty <= 0 || empty($variants)) {
                    $recalc = true;
                    $item->delete();
                    unset($cartItems[$id]);
                    $items[] = ['id' => $id, 'status' => 'deleted', 'name' => $product ? $product->get('product_name') : ''];
                }
            }
        }

        if ($recalc) {
            $cart->set('recalc_shipping_rates', 1);
        }
        $cart->calculateTotals()->saveAllDetails();

        $args['result']['items'] = $items;
    }

    public function action_customerRequestsShippingEstimate($args)
    {
        $postcode = $args['post']['shipping']['postcode'];
        $cart = $this->_getCart($args);
        $cart->set(['shipping_postcode' => $postcode, 'recalc_shipping_rates' => 1])->calculateTotals()->saveAllDetails();
        $args['result']['status'] = 'success';
    }


    public function action_customerAddsCouponCode($args)
    {
        if (empty($args['post']['coupon_code'])) {
            $args['result']['error']['message'] = 'No coupon code provided';
            return;
        }

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = !empty($args['cart']) ? $args['cart'] : $this->Sellvana_Sales_Model_Cart->sessionCart();
        $cartCouponCodes = $cart->get('coupon_code');
        $cartCouponCodesArr = $cartCouponCodes ? explode(',', $cartCouponCodes) : [];

        $post = $args['post'];
        $couponCode = $post['coupon_code'];

        if (in_array($couponCode, $cartCouponCodesArr)) {
            $args['result']['error']['message'] = "Coupon code is already applied to your cart";
            return;
        }

        $result = [];
        $this->BEvents->fire(__METHOD__, [
            'post' => $post,
            'cart' => $cart,
            'coupon_code' => $couponCode,
            'result' => &$result,
        ]);

        if (!empty($result['error'])) {
            $args['result']['error'] = $result['error'];
            return;
        }

        $cartCouponCodesArr[] = $couponCode;
        $cartCouponCodes = join(',', $cartCouponCodesArr);
        $cart->set('coupon_code', $cartCouponCodes)->calculateTotals()->saveAllDetails();
    }

    public function action_customerRemovesCouponCode($args)
    {
        if (empty($args['post']['coupon_code'])) {
            $args['result']['error']['message'] = 'No coupon code provided';
            return;
        }

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = !empty($args['cart']) ? $args['cart'] : $this->Sellvana_Sales_Model_Cart->sessionCart();
        $cartCouponCodes = $cart->get('coupon_code');
        $cartCouponCodesArr = $cartCouponCodes ? explode(',', $cartCouponCodes) : [];

        $post = $args['post'];
        $couponCode = $post['coupon_code'];

        $idx = array_search($couponCode, $cartCouponCodesArr);

        if (false === $idx) {
            $args['result']['error']['message'] = "Coupon code was already removed";
            return;
        }

        unset($cartCouponCodesArr[$idx]);
        $cartCouponCodes = join(',', $cartCouponCodesArr);
        $cart->set('coupon_code', $cartCouponCodes)->calculateTotals()->saveAllDetails();
    }

    public function action_customerAbandonsCart($args)
    {
        $cart = $this->_getCart($args);
        $cart->state()->overall()->setAbandoned();
        $cart->save();
        $this->BLayout->view('email/sales/cart-state-abandoned.html.twig')->email();
    }
}
