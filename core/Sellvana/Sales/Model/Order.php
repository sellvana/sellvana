<?php

/**
 * Class Sellvana_Sales_Model_Order
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
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_Sales_Model_Order_Item $Sellvana_Sales_Model_Order_Item
 * @property Sellvana_Sales_Model_Order_Comment $Sellvana_Sales_Model_Order_Comment
 * @property Sellvana_Sales_Model_Order_History $Sellvana_Sales_Model_Order_History
 * @property Sellvana_Sales_Model_Order_State $Sellvana_Sales_Model_Order_State
 * @property Sellvana_Sales_Model_Order_Shipment $Sellvana_Sales_Model_Order_Shipment
 * @property Sellvana_Sales_Model_Order_Payment $Sellvana_Sales_Model_Order_Payment
 * @property Sellvana_Sales_Model_Order_Return $Sellvana_Sales_Model_Order_Return
 * @property Sellvana_Sales_Model_Order_Refund $Sellvana_Sales_Model_Order_Refund
 * @property Sellvana_Sales_Model_Order_Cancel $Sellvana_Sales_Model_Order_Cancel
 *
 * @property Sellvana_Sales_Model_Order_Cancel_Item $Sellvana_Sales_Model_Order_Cancel_Item
 * @property Sellvana_Sales_Model_Order_Shipment_Item $Sellvana_Sales_Model_Order_Shipment_Item
 * @property Sellvana_Sales_Model_Order_Payment_Item $Sellvana_Sales_Model_Order_Payment_Item
 * @property Sellvana_Sales_Model_Order_Return_Item $Sellvana_Sales_Model_Order_Return_Item
 * @property Sellvana_Sales_Model_Order_Refund_Item $Sellvana_Sales_Model_Order_Refund_Item
 */
class Sellvana_Sales_Model_Order extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_Address;

    protected static $_table = 'fcom_sales_order';

    protected static $_origClass = __CLASS__;

    protected static $_cacheAuto = true;

    /** @var Sellvana_Sales_Model_Cart */
    protected $_cart;

    protected $_customer;

    protected $_state;

    protected $_addresses;

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        if (!$this->get('unique_id')) {
            $this->set('unique_id', $this->FCom_Core_Model_Seq->getNextSeqId('order'));
        }

        return true;
    }

    /**
     * @return Sellvana_Sales_Model_Order_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->Sellvana_Sales_Model_Order_State->factory($this);
        }
        return $this->_state;
    }

    /**
     * @return Sellvana_Sales_Model_Cart|null
     * @throws BException
     */
    public function cart()
    {
        if (!$this->_cart) {
            if (!$this->get('cart_id')) {
                return null;
            }
            $this->_cart = $this->Sellvana_Sales_Model_Cart->load($this->get('cart_id'));
        }
        return $this->_cart;
    }

    public function customer()
    {
        if (!$this->_customer) {
            if (!$this->get('customer_id')) {
                return null;
            }
            $this->_customer = $this->Sellvana_Customer_Model_Customer->load($this->get('customer_id'));
        }
        return $this->_customer;
    }

    /**
     * Return the order items
     * @param boolean $assoc
     * @return Sellvana_Sales_Model_Order_Item[]
     */
    public function items($assoc = true)
    {
        if (!$this->items) {
            $this->items = $this->Sellvana_Sales_Model_Order_Item->orm()
                ->where('order_id', $this->id())->find_many_assoc();
        }
        return $assoc ? $this->items : array_values($this->items);
    }

    /**
     * @param bool $assoc
     * @return Sellvana_Sales_Model_Order_Shipment[]
     */
    public function shipments($assoc = true)
    {
        if (!$this->shipments) {
            $this->shipments = $this->Sellvana_Sales_Model_Order_Shipment->orm()
                ->where('order_id', $this->id())->find_many_assoc();
        }
        return $assoc ? $this->shipments : array_values($this->shipments);
    }

    /**
     * @param bool $assoc
     * @return Sellvana_Sales_Model_Order_Payment[]
     */
    public function payments($assoc = true)
    {
        if (!$this->payments) {
            $this->payments = $this->Sellvana_Sales_Model_Order_Payment->orm()
                ->where('order_id', $this->id())->find_many_assoc();
        }
        return $assoc ? $this->payments : array_values($this->payments);
    }

    public function addHistoryEvent($type, $description, $params = null)
    {
        /** @var Sellvana_Sales_Model_Order_History $history */
        $history = $this->Sellvana_Sales_Model_Order_History->create([
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
            /** @var Sellvana_Sales_Model_Order $order */
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
                        'product_info' => $this->Sellvana_Catalog_Model_Product->prepareApiData($this->BUtil->fromJson($item->product_info, true)),
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
            ->_importTotalsDataFromCart()
            ->save() // create unique id

            ->_importAddressDataFromCart()
            ->_importItemsDataFromCart()
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
            'item_qty' => $cart->get('item_qty'),
            'store_currency_code' => $cart->get('store_currency_code'),
            'same_address' => $cart->get('same_address'),
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
            if ($item->get('qty') == 0) {
                continue;
            }

            $product = $item->getProduct();
            if (!$product) {
                throw new BException('Can not order product that does not exist');
            }
            /** @var Sellvana_Sales_Model_Order_Item $orderItem */
            $orderItem = $this->Sellvana_Sales_Model_Order_Item->create([
                'order_id' => $this->id(),
                'cart_item_id' => $item->id(),
                'product_id' => $item->get('product_id'),
                'product_sku' => $item->get('product_sku'),
                'inventory_id' => $item->get('inventory_id'),
                'inventory_sku' => $item->get('inventory_sku'),
                'product_name' => $item->get('product_name'),
                'price' => $item->get('price'),
                'qty_ordered' => $item->get('qty'),
                'qty_backordered' => $item->get('qty_backordered'),
                'row_total' => $item->get('row_total'),
                'row_tax' => $item->get('row_tax'),
                'row_discount' => $item->get('row_discount'),
                'pack_separate' => $item->get('pack_separate'),
                'show_separate' => $item->get('show_separate'),
                'shipping_size' => $item->get('shipping_size'),
                'shipping_weight' => $item->get('shipping_weight'),
                'data_serialized' => $item->get('data_serialized'),
                'cost' => $item->get('cost'),
            ])->save();

            if ($orderItem->get('qty_backordered') == $orderItem->get('qty_ordered')) {
                $orderItem->state()->overall()->setBackordered();
            } else {
                $orderItem->state()->overall()->setPending();
            }
            if ($orderItem->get('shipping_weight') == 0) {
                $orderItem->state()->delivery()->setVirtual();
            } else {
                $orderItem->state()->delivery()->setPending();
            }
            if ($orderItem->get('row_total') == 0) {
                $orderItem->state()->payment()->setFree();
            } else {
                $orderItem->state()->payment()->setUnpaid();
            }
            $orderItem->state()->custom()->setDefaultState();

            $orderItem->save();
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
            'amount_paid' => $cart->get('amount_paid'),
            'amount_due' => $cart->get('amount_due'),
        ]);

        $this->setData('totals', $cart->getData('totals'));
        $this->setData('store_currency', $cart->getData('store_currency'));
        return $this;
    }

    protected function _importShippingDataFromCart()
    {
        $cart = $this->_cart;

        $method = $cart->get('shipping_method');
        $service = $cart->get('shipping_service');
        $methods = $this->Sellvana_Sales_Main->getShippingMethods();
        $services = $methods[$method]->getServices();
        $serviceTitle = isset($services[$service]) ? $services[$service] : $service;

        $this->set([
            'shipping_price' => $cart->get('shipping_price'),
            'shipping_method' => $method,
            'shipping_service' => $service,
            'shipping_service_title' => $methods[$method]->getDescription() . ' - ' . $serviceTitle
        ]);

        return $this;
    }

    protected function _importPaymentDataFromCart()
    {
        $cart = $this->_cart;

        $this->set([
            'payment_method' => $cart->get('payment_method'),
        ])->setData([
            'payment_details' => $cart->getData('payment_details'),
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
        $this->state()->setDefaultStates()->calcAllStates();
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
        $orderNumber = $this->BConfig->get('modules/Sellvana_Sales/order_number');
        if ($orderNumber) {
            //todo: confirm with Boris about add prefix 1 to order number.
            $args['seq_id'] =  '1' . $orderNumber;
        }
    }

    public function getLastCustomerComment()
    {
        return $this->Sellvana_Sales_Model_Order_Comment->orm()
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

    /**
     * @return null|Sellvana_Sales_Method_Shipping_Interface
     */
    public function getShippingMethod()
    {
        if (!$this->get('shipping_method')) {
            return null;
        }
        $methods = $this->Sellvana_Sales_Main->getShippingMethods();
        return $methods[$this->get('shipping_method')];
    }

    public function getShippingServiceTitle()
    {
        $method = $this->getShippingMethod();
        $services = $method->getServices();
        $svc = $this->get('shipping_service');
        return !empty($services[$svc]) ? $services[$svc] : null;
    }

    /**
     * @return null|Sellvana_Sales_Method_Payment_Interface
     */
    public function getPaymentMethod()
    {
        if (!$this->get('payment_method')) {
            return null;
        }
        $methods = $this->Sellvana_Sales_Main->getPaymentMethods();
        return $methods[$this->get('payment_method')];
    }

    public function loadItemsProducts()
    {
        $pIds = [];
        foreach ($this->items() as $item) {
            $pIds[] = $item->get('product_id');
        }
        $products = $this->Sellvana_Catalog_Model_Product->orm('p')->where_in('p.id', $pIds)->find_many_assoc();
        foreach ($this->items() as $item) {
            $pId = $item->get('product_id');
            if (!empty($products[$pId])) {
                $item->setProduct($products[$pId]);
            }
        }
        return $this;
    }

    /**
     * @param string|array $types [shipments, payments, cancels, returns, refunds]
     * @return $this
     */
    public function calcItemQuantities($types = null)
    {
        $types = (array)$types;
        $qtys = [];
        $items = $this->items();
        $entities = [
            'shipments' => 'Sellvana_Sales_Model_Order_Shipment_Item',
            'payments' => 'Sellvana_Sales_Model_Order_Payment_Item',
            'cancels' => 'Sellvana_Sales_Model_Order_Cancel_Item',
            'returns' => 'Sellvana_Sales_Model_Order_Return_Item',
            'refunds' => 'Sellvana_Sales_Model_Order_Refund_Item',
        ];
        foreach ($entities as $type => $itemClass) {
            if (null === $types || in_array($type, $types)) {
                $qtys1 = $this->{$itemClass}->getOrderItemsQtys($items);
                $qtys = array_replace_recursive($qtys, $qtys1);
            }
        }
        foreach ($items as $itemId => $item) {
            if (empty($qtys[$itemId])) {
                continue;
            }

            $itemQtys = $qtys[$itemId];
            foreach($entities as $type => $itemClass) {
                if (null === $types || in_array($type, $types)) {
                    $allField = $this->{$itemClass}->getAllField();
                    if (!isset($itemQtys[$allField])) {
                        $itemQtys[$allField] = 0;
                    }

                    $doneField = $this->{$itemClass}->getDoneField();
                    if (!isset($itemQtys[$doneField])) {
                        $itemQtys[$doneField] = 0;
                    }
                }
            }

            $item->set($itemQtys);
        }
        return $this;
    }

     /**
     * Save order with items and other details
     *
     * @param array $options
     * @return static
     */
    public function saveAllDetails($options = [])
    {
        $this->save();
        foreach ($this->items() as $item) {
            $item->save();
        }
        return $this;
    }

    public function shipAllShipments()
    {
        $shipments = $this->shipments();
        foreach ($shipments as $shipment) {
            $shipment->state()->overall()->setShipped();
            $shipment->save();
        }
        $this->calcItemQuantities('shipments');
        $this->state()->calcAllStates();
        $this->saveAllDetails();
    }

    public function generateToken()
    {
        $this->set(['token' => $this->BUtil->randomString(20), 'token_at' => $this->BDb->now()]);
        return $this;
    }

    public function accountExistsForGuestEmail()
    {
        return $this->Sellvana_Customer_Model_Customer->load($this->get('customer_email'), 'email');
    }

    public function markAsPaid()
    {
        /** @var Sellvana_Sales_Model_Order_Payment $payment */
        foreach ($this->payments() as $payment) {
            $payment->markAsPaid();
        }

        /** @var Sellvana_Sales_Model_Order_Item $item */
        foreach ($this->items() as $item) {
            $item->markAsPaid();
        }

        $this->addHistoryEvent('processing', 'Admin user has marked the order as paid');
        $this->state()->calcAllStates();
        $this->saveAllDetails();
    }

    public function getShippableItems()
    {
        $items = [];
        foreach ($this->items() as $i => $item) {
            if ($item->isShippable() && $item->getQtyCanShip()) {
                $items[] = $item;
            }
        }
        return $items;
    }

    public function getPayableItems()
    {
        $items = [];
        foreach ($this->items() as $i => $item) {
            if ($item->getQtyCanPay()) {
                $items[] = $item;
            }
        }
        return $items;
    }

    public function getCancelableItems()
    {
        $items = [];
        foreach ($this->items() as $i => $item) {
            if ($item->getQtyCanCancel()) {
                $items[] = $item;
            }
        }
        return $items;
    }

    public function getReturnableItems()
    {
        $items = [];
        foreach ($this->items() as $i => $item) {
            if ($item->getQtyCanReturn()) {
                $items[] = $item;
            }
        }
        return $items;
    }

    public function getRefundableItems()
    {
        $items = [];
        foreach ($this->items() as $i => $item) {
            if ($item->getQtyCanRefund()) {
                $items[] = $item;
            }
        }
        return $items;
    }

    public function getAllPayments()
    {
        return $this->Sellvana_Sales_Model_Order_Payment->orm()
            ->where('order_id', $this->id())
            ->order_by_asc('create_at')
            ->find_many();
    }

    public function getAllShipments()
    {
        return $this->Sellvana_Sales_Model_Order_Shipment->orm()
            ->where('order_id', $this->id())
            ->order_by_asc('create_at')
            ->find_many();
    }

    public function getAllReturns()
    {
        return $this->Sellvana_Sales_Model_Order_Return->orm()
            ->where('order_id', $this->id())
            ->order_by_asc('create_at')
            ->find_many();
    }

    public function getAllRefunds()
    {
        return $this->Sellvana_Sales_Model_Order_Refund->orm()
            ->where('order_id', $this->id())
            ->order_by_asc('create_at')
            ->find_many();
    }

    public function getAllCancellations()
    {
        return $this->Sellvana_Sales_Model_Order_Cancel->orm()
            ->where('order_id', $this->id())
            ->order_by_asc('create_at')
            ->find_many();
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_cart, $this->_addresses);
    }
}
