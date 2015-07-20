<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Model_Order_Shipment
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property Sellvana_Sales_Model_Order_History $Sellvana_Sales_Model_Order_History
 * @property Sellvana_Sales_Model_Order_Shipment_State $Sellvana_Sales_Model_Order_Shipment_State
 * @property Sellvana_Sales_Model_Order_Shipment_Package $Sellvana_Sales_Model_Order_Shipment_Package
 * @property Sellvana_Sales_Model_Order_Shipment_Item $Sellvana_Sales_Model_Order_Shipment_Item
 */

class Sellvana_Sales_Model_Order_Shipment extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_shipment';
    protected static $_origClass = __CLASS__;

    protected $_state;

    protected $_items;

    /**
     * @return Sellvana_Sales_Model_Order_Shipment_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->Sellvana_Sales_Model_Order_Shipment_State->factory($this);
        }
        return $this->_state;
    }

    public function createShipmentFromOrder(Sellvana_Sales_Model_Order $order)
    {
        $shippingMethod = $order->getShippingMethod();
        $shippingServices = $shippingMethod->getServices();
        $serviceCode = $order->get('shipping_service');

            /** @var Sellvana_Sales_Model_Order_Shipment $shipment */
        $shipment = $this->create([
            'order_id' => $order->id(),
            'carrier_code' => $order->get('shipping_method'),
            'service_code' => $order->get('shipping_service'),
            'carrier_desc' => $shippingMethod->getDescription(),
            'service_desc' => !empty($shippingServices[$serviceCode]) ? $shippingServices[$serviceCode] : null,
            'customer_price' => $order->get('shipping_price'),
            'shipping_size' => $order->get('shipping_size'),
            'shipping_weight' => $order->get('shipping_weight'),
        ]);
        $shipment->state()->overall()->setDefaultState();
        $shipment->state()->carrier()->setDefaultState();
        $shipment->state()->custom()->setDefaultState();
        $shipment->save();

        $firstPackage = $this->Sellvana_Sales_Model_Order_Shipment_Package->create([
            'order_id' => $order->id(),
            'shipment_id' => $shipment->id(),
        ])->save();

        $numItems = 0;
        /** @var Sellvana_Sales_Model_Order_Item $item */
        foreach ($order->items() as $item) {
            if ($item->get('state_delivery') == Sellvana_Sales_Model_Order_Item_State_Delivery::VIRTUAL) {
                continue;
            }
            $qtyToShip = $item->getQtyCanShip();
            if ($qtyToShip <= 0) {
                continue;
            }
            $shipData = [
                'order_id' => $order->id(),
                'shipment_id' => $shipment->id(),
                'order_item_id' => $item->id(),
            ];
            if ($item->get('pack_separate')) {
                $shipData['qty'] = 1;
                for ($i = 0; $i < $qtyToShip; $i++) {
                    $package = $this->Sellvana_Sales_Model_Order_Shipment_Package->create([
                        'order_id' => $order->id(),
                        'shipment_id' => $shipment->id(),
                    ])->save();
                    $shipData['package_id'] = $package->id();
                    $this->Sellvana_Sales_Model_Order_Shipment_Item->create($shipData)->save();
                }
            } else {
                $shipData['package_id'] = $firstPackage->id();
                $shipData['qty'] = $qtyToShip;
                $this->Sellvana_Sales_Model_Order_Shipment_Item->create($shipData)->save();
            }
            $numItems += $qtyToShip;
        }

        $shipment->set('num_items', $numItems)->save();

        return $shipment;
    }

    public function shipOrderItems(Sellvana_Sales_Model_Order $order, $itemsData = null)
    {
        $qtys = null;
        if ($itemsData !== null) {
            $itemLines = preg_match_all('#^\s*([^\s]+)(\s*:\s*([^\s]+))?\s*$#', $itemsData, $matches, PREG_PATTERN_ORDER);
            $qtys = [];
            foreach ($matches as $m) {
                $qtys[$m[1]] = $m[3];
            }
        }
        $items = $order->items();
        foreach ($items as $item) {
            if (null === $qtys || empty($qtys[$item->get('product_sku')])) {
                continue;
            }

        }
    }

    /**
     * @return Sellvana_Sales_Model_Order_Shipment_Item[]
     */
    public function items()
    {
        if (null === $this->_items) {
            $this->_items = $this->Sellvana_Sales_Model_Order_Shipment_Item->orm()
                ->where('shipment_id', $this->id())->find_many();
        }
        return $this->_items;
    }

    public function shipItems()
    {
        $order = $this->order();
        $orderItems = $order->items();
        $shipmentItems = $this->items();

        foreach ($shipmentItems as $sItem) {
            $oItem = $orderItems[$sItem->get('order_item_id')];
            $oItem->add('qty_shipped', $sItem->get('qty'));
        }

        $this->state()->overall()->setShipped();
        $this->save();

        return $this;
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_order, $this->_items, $this->_state);
    }
}
