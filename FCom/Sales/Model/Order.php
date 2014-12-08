<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Order
 * @property string $id
 * @property string $customer_id
 * @property string $customer_email
 * @property string $cart_id
 * @property string $status
 * @property string $item_qty
 * @property string $subtotal
 * @property string $shipping_method
 * @property string $shipping_service
 * @property string $payment_method
 * @property string $coupon_code
 * @property string $tax
 * @property string $balance
 * @property string $create_at
 * @property string $update_at
 * @property string $grand_total
 * @property string $shipping_service_title
 * @property string $data_serialized
 * @property string $unique_id
 * @property string $admin_id
 *
 * @property array $items
 *
 * DI
 * @property FCom_Core_Model_Seq $FCom_Core_Model_Seq
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_PushServer_Model_Channel $FCom_PushServer_Model_Channel
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Sales_Model_Order_Item $FCom_Sales_Model_Order_Item
 * @property FCom_Sales_Model_Order_Comment $FCom_Sales_Model_Order_Comment
 * @property FCom_Sales_Model_Order_History $FCom_Sales_Model_Order_History
 * @property FCom_Sales_Model_Order_State $FCom_Sales_Model_Order_State
 */
class FCom_Sales_Model_Order extends FCom_Core_Model_Abstract
{
    use FCom_Sales_Model_Trait_Address;

    protected static $_table = 'fcom_sales_order';

    protected static $_origClass = __CLASS__;

    /** @var FCom_Sales_Model_Cart */
    protected $_cart;

    protected $_state;

    protected $_addresses;

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        if (!$this->unique_id) {
            $this->set('unique_id', $this->FCom_Core_Model_Seq->getNextSeqId('order'));
        }

        return true;
    }

    /**
     * @return FCom_Sales_Model_Order_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->FCom_Sales_Model_Order_State->factory($this);
        }
        return $this->_state;
    }

    /**
     * @return FCom_Sales_Model_Cart|null
     * @throws BException
     */
    public function cart()
    {
        if (!$this->_cart) {
            if (!$this->get('cart_id')) {
                return null;
            }
            $this->_cart = $this->FCom_Sales_Model_Cart->load($this->get('cart_id'));
        }
        return $this->_cart;
    }

    public function addHistoryEvent($type, $description, $params = null)
    {
        $history = $this->FCom_Sales_Model_Order_History->create([
            'order_id' => $this->id(),
            'entity_type' => 'order',
            'entity_id' => $this->id(),
            'event_type' => $type,
            'event_description' => $description,
            'event_at' => isset($params['event_at']) ? $params['event_at'] : $this->BDb->now(),
            'user_id' => isset($params['user_id']) ? $params['user_id'] : $this->FCom_Admin_Model_User->sessionUserId(),
        ]);
        if (isset($params['data'])) {
            $history->setData($params['data']);
        }
        $history->save();
        return $this;
    }

    public function getBillingAddress()
    {
        return $this->addressAsObject('billing');
    }

    public function getShippingAddress()
    {
        return $this->addressAsObject('shipping');
    }

    /**
     * Return the order items
     * @param boolean $assoc
     * @return array
     */
    public function items($assoc = true)
    {
        $this->items = $this->FCom_Sales_Model_Order_Item->orm()->where('order_id', $this->id)->find_many_assoc();
        return $assoc ? $this->items : array_values($this->items);
    }

    public function findCustomerOrders($customerId)
    {
        return $this->orm()->where('customer_id', $customerId)->find_many_assoc();

    }


    /**
     * Verify if order exist if yes return the order data
     *
     * @param int $uniqueId
     * @param int $customerId
     * @return BModel | false
     */
    public function isOrderExists($uniqueId, $customerId)
    {
        return $this->orm()->where('unique_id', $uniqueId)->where('customer_id', $customerId)->find_one();
    }

    public function prepareApiData($orders, $includeItems = false)
    {
        $result = [];
        foreach ($orders as $i => $order) {
            $result[$i] = [
                'id'               => $order->id,
                'customer_id'      => $order->customer_id,
                'status'           => $order->status,
                'item_qty'         => $order->item_qty,
                'subtotal'         => $order->subtotal,
                'amount_due'       => $order->amount_due,
                'tax_amount'       => $order->tax_amount,
                'shipping_method'  => $order->shipping_method,
                'shipping_service' => $order->shipping_service,
                'payment_method'   => $order->payment_method,
                'coupon_code'      => $order->coupon_code
            ];
            if ($includeItems) {
                $items = $order->items();
                foreach ($items as $item) {
                    $result[$i]['items'][] = [
                        'product_id' => $item->product_id,
                        'qty' => $item->qty,
                        'total' => $item->total,
                        //get product info as object and prepare data for api
                        'product_info' => $this->FCom_Catalog_Model_Product->prepareApiData($this->BUtil->fromJson($item->product_info, true)),
                    ];
                }
            }
        }
        return $result;
    }

    public function formatApiPost($post)
    {
        $data = [];
        if (!empty($post['customer_id'])) {
            $data['customer_id'] = $post['customer_id'];
        }
        if (!empty($post['status'])) {
            $data['status'] = $post['status'];
        }
        if (!empty($post['item_qty'])) {
            $data['item_qty'] = $post['item_qty'];
        }
        if (!empty($post['subtotal'])) {
            $data['subtotal'] = $post['subtotal'];
        }
        if (!empty($post['balance'])) {
            $data['balance'] = $post['balance'];
        }
        if (!empty($post['tax'])) {
            $data['tax'] = $post['tax'];
        }
        if (!empty($post['shipping_method'])) {
            $data['shipping_method'] = $post['shipping_method'];
        }
        if (!empty($post['shipping_service'])) {
            $data['shipping_service'] = $post['shipping_service'];
        }
        if (!empty($post['payment_method'])) {
            $data['payment_method'] = $post['payment_method'];
        }
        if (!empty($post['coupon_code'])) {
            $data['coupon_code'] = $post['coupon_code'];
        }
        return $data;
    }

    public function importDataFromCart($cart)
    {
        $this->_cart = $cart;
        $this
            ->_importBasicFieldsFromCart()
            ->save() // create unique id

            ->_importAddressDataFromCart()
            ->_importItemsDataFromCart()
            ->_importTotalsDataFromCart()
            ->_importShippingDataFromCart()
            ->_importPaymentDataFromCart()
            ->_importDiscountDataFromCart()
            ->_setDefaultStates()
            ->save()
        ;
        return $this;
    }

    protected function _importBasicFieldsFromCart()
    {
        $cart = $this->_cart;
        $this->set([
            'cart_id' => $cart->id(),
            'admin_id' => $cart->get('admin_id'),
            'customer_id' => $cart->get('customer_id'),
            'customer_email' => $cart->get('customer_email'),
        ]);
        return $this;
    }

    protected function _importAddressDataFromCart()
    {
        $cart = $this->_cart;
        foreach (['billing', 'shipping'] as $atype) {
            foreach (['company', 'attn', 'firstname', 'lastname', 'street1', 'street2', 'city', 'region', 'postcode',
                      'country', 'phone', 'fax'] as $f) {
                $field = $atype . '_' . $f;
                $this->set($field, $cart->get($field));
            }
        }
        return $this;
    }

    protected function _importItemsDataFromCart()
    {
        $cart = $this->_cart;

        foreach ($cart->items() as $item) {
            $product = $item->product();
            if (!$product) {
                throw new BException('Can not order product that does not exist');
            }
            $orderItem = $this->FCom_Sales_Model_Order_Item->create([
                'order_id' => $this->id(),
                'cart_item_id' => $item->id(),
                'product_id' => $item->get('product_id'),
                'product_sku' => $item->get('product_sku'),
                'inventory_id' => $item->get('inventory_id'),
                'inventory_sku' => $item->get('inventory_sku'),
                'product_name' => $item->get('product_name'),
                'price' => $item->get('price'),
                'qty_ordered' => $item->get('qty'),
                'row_total' => $item->get('row_total'),
                'row_tax' => $item->get('row_tax'),
                'row_discount' => $item->get('row_discount'),
                'pack_separate' => $item->get('pack_separate'),
                'show_separate' => $item->get('show_separate'),
                'shipping_size' => $item->get('shipping_size'),
                'shipping_weight' => $item->get('shipping_weight'),
                'data_serialized' => $item->get('data_serialized'),
            ])->save();
        }
        return $this;
    }

    protected function _importTotalsDataFromCart()
    {
        $cart = $this->_cart;

        $this->set([
            'item_qty' => $cart->get('item_qty'),
            'subtotal' => $cart->get('subtotal'),
            'tax_amount' => $cart->get('tax_amount'),
            'discount_amount' => $cart->get('discount_amount'),
            'grand_total' => $cart->get('grand_total'),
            'amount_paid' => 0,
            'amount_due' => $cart->get('grand_total'),
        ]);

        $this->setData('totals', $cart->getData('totals'));
        return $this;
    }

    protected function _importShippingDataFromCart()
    {
        $cart = $this->_cart;

        $method = $cart->get('shipping_method');
        $service = $cart->get('shipping_service');
        $methods = $this->FCom_Sales_Main->getShippingMethods();
        $services = $methods[$method]->getServices();

        $this->set([
            'shipping_price' => $cart->get('shipping_price'),
            'shipping_method' => $method,
            'shipping_service' => $service,
            'shipping_service_title' => $methods[$method]->getDescription() . ' - ' . $services[$service]
        ]);

        return $this;
    }

    protected function _importPaymentDataFromCart()
    {
        $cart = $this->_cart;

        $this->set([
            'payment_method' => $cart->get('payment_method'),
        ]);

        return $this;
    }

    protected function _importDiscountDataFromCart()
    {
        $cart = $this->_cart;

        $this->set([
            'coupon_code' => $cart->get('coupon_code'),
        ]);

        return $this;
    }

    protected function _setDefaultStates()
    {
        $state = $this->state();
        $state->overall()->setPending();
        $state->delivery()->setPending();

        if ($this->isPayable()) {
            $state->payment()->setUnpaid();
        } else {
            $state->payment()->setFree();
        }

        $state->custom()->setDefault();
        return $this;
    }

    public function getTextDescription()
    {
        $description = [];
        foreach ($this->items() as $item) {
            $product_data = $this->BUtil->fromJson($item->get('product_info'));
            $name = isset($product_data['product_name']) ? $product_data['product_name'] : null;
            if (!isset($description[$name])) {
                $description[$name] = $item->qty;
            } else {
                $description[$name] += $item->qty;
            }
        }
        $result = [];
        foreach ($description as $name => $qty) {
            $line = $name . ' x (' . $qty . ')';
            $result[] = $line;
        }
        return join("\n", $result);
    }

    public function onGetFirstSeqId($args)
    {
        $orderNumber = $this->BConfig->get('modules/FCom_Sales/order_number');
        if ($orderNumber) {
            //todo: confirm with Boris about add prefix 1 to order number.
            $args['seq_id'] =  '1' . $orderNumber;
        }
    }

    public function getLastCustomerComment()
    {
        return $this->FCom_Sales_Model_Order_Comment->orm()
            ->where('order_id', $this->id())
            ->where('from_admin', 0)
            ->order_by_desc('create_at')
            ->find_one();
    }

    public function isShippable()
    {
        foreach ($this->items() as $item) {
            if ($item->isShippable()) {
                return true;
            }
        }
        return false;
    }

    public function isPayable()
    {
        return $this->get('amount_due') > 0;
    }

    public function __destruct()
    {
        unset($this->_cart, $this->_addresses);
    }
}
