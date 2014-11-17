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
 * @property string $grandtotal
 * @property string $shipping_service_title
 * @property string $data_serialized
 * @property string $unique_id
 * @property string $admin_id
 *
 * @property array $items
 *
 * DI
 * @property FCom_Core_Model_Seq $FCom_Core_Model_Seq
 * @property FCom_Sales_Model_Order_Item $FCom_Sales_Model_Order_Item
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 * @property FCom_PushServer_Model_Channel $FCom_PushServer_Model_Channel
 * @property FCom_Sales_Model_Order_Address $FCom_Sales_Model_Order_Address
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Sales_Model_Cart_Address $FCom_Sales_Model_Cart_Address
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Sales_Model_Order_Comment $FCom_Sales_Model_Order_Comment
 * @property FCom_Sales_Model_Order_CustomStatus $FCom_Sales_Model_Order_CustomStatus
 * @property FCom_Sales_Model_Order_History $FCom_Sales_Model_Order_History
 */
class FCom_Sales_Model_Order extends FCom_Core_Model_Abstract
{
    use FCom_Sales_Model_Trait_Address;

    protected static $_table = 'fcom_sales_order';

    protected static $_origClass = __CLASS__;

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

    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->BClassRegistry->instance('FCom_Sales_Model_Order_State', true, [$this]);
        }
        return $this->_state;
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

    public function billing()
    {
        return $this->getBillingAddress();
    }

    public function shipping()
    {
        return $this->getShippingAddress();
    }

    public function addNew($data)
    {
        $status = $this->FCom_Sales_Model_Order_CustomStatus->statusNew();
        $data['status'] = $status->name;
        $data['status_id'] = $status->id;
        $this->BEvents->fire(__CLASS__ . '.addNew', ['order' => $data]);
        return $this->create($data);//->save();
    }

    public function update($data)
    {
        $this->BEvents->fire(__CLASS__ . '.update', ['order' => $data]);
        return $this->set($data);//->save();
    }

    public function paid()
    {
        $status = $this->FCom_Sales_Model_Order_CustomStatus->statusPaid();
        $data = [];
        $data['status'] = $status->name;
        $data['status_id'] = $status->id;
        $data['update_at'] = date("Y-m-d H:i:s");
        $this->set($data)->save();
    }

    public function pending()
    {
        $status = $this->FCom_Sales_Model_Order_CustomStatus->statusPending();
        $data = [];
        $data['status'] = $status->name;
        $data['status_id'] = $status->id;
        $this->set($data)->save();
    }

    public function status()
    {
        return $this->FCom_Sales_Model_Order_CustomStatus->orm()->where('id', $this->status_id)->find_one();
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

    public function getOrders($customerId)
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
                'id'                => $order->id,
                'customer_id'      => $order->customer_id,
                'status'               => $order->status,
                'item_qty'             => $order->item_qty,
                'subtotal'               => $order->subtotal,
                'balance'            => $order->balance,
                'tax'       => $order->tax,
                'shipping_method' => $order->shipping_method,
                'shipping_service'       => $order->shipping_service,
                'payment_method'       => $order->payment_method,
                'coupon_code'       => $order->coupon_code
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

        $orderData                    = [];
        $orderData['cart_id']         = $cart->id();
        $orderData['admin_id']        = $cart->admin_id;
        $orderData['customer_id']     = $cart->customer_id;
        $orderData['customer_email']  =  $cart->customer_email;
        $orderData['create_at'] = $orderData['update_at'] = $this->BDb->now();

        $this->set($orderData);
        return $this;
    }

    protected function _importAddressDataFromCart()
    {
        $cart = $this->_cart;
        foreach (['billing', 'shipping'] as $atype) {
            foreach (['company', 'attn', 'firstname', 'lastname', 'street', 'city', 'region', 'postcode', 'country', 'phone', 'fax'] as $f) {
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
            /* @var $item FCom_Sales_Model_Cart_Item */
            if (!$this->itemAllowed($options, $item)) {
                continue;
            }

            $product = $item->product();
            if (!$product) {
                continue;
            }
            $productInfo = $product->as_array();
            $productInfo['variants'] = $item->getData('variants');
            $orderItem                 = [];
            $orderItem['order_id']     = $this->id();
            $orderItem['product_id']   = $item->product_id;
            $orderItem['qty']          = $item->qty;
            $orderItem['total']        = $item->rowTotal();
            $orderItem['product_info'] = $this->BUtil->toJson($productInfo);

            /* @var $testItem FCom_Sales_Model_Order_Item */
            $testItem = $this->FCom_Sales_Model_Order_Item->isItemExist($orderId, $item->product_id);
            if ($testItem) {
                $testItem->update($orderItem);
            } else {
                $this->FCom_Sales_Model_Order_Item->addNew($orderItem);
            }
        }
        return $this;
    }

    protected function _importTotalsDataFromCart()
    {
        $cart = $this->_cart;

        $orderData = [];
        $orderData['item_qty'] = $cart->item_qty;
        $orderData['subtotal'] = $cart->subtotal;
        $orderData['tax']      = $cart->tax;
        //$orderData['total_json'] = $cart->total_json;
        $orderData['balance']    = $cart->grand_total; // this has been calculated in cart
        $orderData['grandtotal'] = $cart->grand_total; // full grand total

        $this->set($orderData);

        $this->setData('totals', $cart->getData('totals'));
        $this->setData('shipping_service', $cart->get('shipping_service'));
        return $this;
    }

    protected function _importShippingDataFromCart()
    {
        $cart = $this->_cart;

        $shippingMethod       = $cart->getShippingMethod();
        $shippingServiceTitle = '';
        if (is_object($shippingMethod)) {
            $shippingServiceTitle = $shippingMethod->getService($cart->shipping_service);
        }
        $orderData = [];
        $orderData['shipping_method'] = $cart->shipping_method;
        //        $orderData['shipping_service']       = $cart->shipping_service;
        $orderData['shipping_service_title'] = $shippingServiceTitle;
        $this->set($orderData);
        return $this;
    }

    protected function _importPaymentDataFromCart()
    {
        $cart = $this->_cart;

        $orderData = [];
        $orderData['payment_method'] = $cart->payment_method;
        $this->set($orderData);
        return $this;
    }

    protected function _importDiscountDataFromCart()
    {
        $cart = $this->_cart;

        $orderData = [];
        $orderData['coupon_code'] = $cart->coupon_code;
        $this->set($orderData);
        return $this;
    }

    protected function _setDefaultStates()
    {
        $state = $this->state();
        $state->overall()->setNew();
        $state->delivery()->setNew();

        if ($this->isPayable()) {
            $state->payment()->setUnpaid();
        } else {
            $state->payment()->setFree();
        }

        $state->custom()->setDefault();
        return $this;
    }

    /**
     * @param FCom_Sales_Model_Cart $cart
     * @param array $options
     * @return FCom_Sales_Model_Order
     */
    public function createFromCart($cart, $options = [])
    {
        $cart->calculateTotals();
        $salesOrder = $this->_createFromCart($cart);

        $salesOrder->save(); // save to have valid unique_id

        $options['order_id'] = $salesOrder->id();
        $this->createOrderItems($cart, $options);

        //Made payment
        $cart->setPaymentDetails($this->BUtil->fromJson($cart->payment_details));
        $paymentMethod = $cart->getPaymentMethod();
        $this->createOrderPayment($paymentMethod, $salesOrder, $options);

        $this->BEvents->fire(__METHOD__ . ':after', [
            'cart'           => $cart,
            'options'        => $options,
            'payment_method' => $paymentMethod,
            'order'          => $salesOrder,
        ]);
        return $salesOrder;
    }

    /**
     * @param FCom_Sales_Method_Payment_Abstract $payment
     * @param FCom_Sales_Model_Order $salesOrder
     * @param array $options
     */
    public function createOrderPayment($payment, $salesOrder, $options)
    {
        if (!$payment instanceof FCom_Sales_Method_Payment_Interface) {
            return;
        }
        /* @var $payment FCom_Sales_Method_Payment_Abstract */
        $payment->setSalesOrder($salesOrder, $options)
                ->payOnCheckout();
        $salesOrder->setData('payment_details', $payment->asArray());
    }

    public function getAddresses()
    {
        if (!$this->_addresses) {
            $this->_addresses = $this->FCom_Sales_Model_Order_Address->orm()
                ->where("order_id", $this->id())
                ->find_many_assoc('atype');
        }
        return $this->_addresses;
    }

    public function getBillingAddress()
    {
        return $this->addressAsObject('billing');
    }

    public function getShippingAddress()
    {
        return $this->addressAsObject('shipping');
    }

    protected function itemAllowed($options, $item)
    {
        if (isset($options['items'])) {
            foreach ($options['items'] as $i) {
                if ($i['id'] == $item->id) {
                    return true; // item id matches
                }
            }
            return false; // item is not with passed filter
        }

        return true; // no items filter passed
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
