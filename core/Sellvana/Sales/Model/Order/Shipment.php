<?php

/**
 * Class Sellvana_Sales_Model_Order_Shipment
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property Sellvana_Sales_Model_Order_History $Sellvana_Sales_Model_Order_History
 * @property Sellvana_Sales_Model_Order_Shipment_State $Sellvana_Sales_Model_Order_Shipment_State
 * @property Sellvana_Sales_Model_Order_Shipment_Package $Sellvana_Sales_Model_Order_Shipment_Package
 * @property Sellvana_Sales_Model_Order_Shipment_Item $Sellvana_Sales_Model_Order_Shipment_Item
 * @property BFile $BFile
 */
class Sellvana_Sales_Model_Order_Shipment extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_shipment';
    protected static $_origClass = __CLASS__;

    /**
     * @var Sellvana_Sales_Model_Order_Shipment_State
     */
    protected $_state;

    /**
     * @var Sellvana_Sales_Model_Order_Shipment_Item[]
     */
    protected $_items;

    /**
     * @var Sellvana_Sales_Model_Order_Shipment_Package[]
     */
    protected $_packages;

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

    public function importFromOrder(Sellvana_Sales_Model_Order $order, array $qtys = null)
    {
        $this->order($order);

        $shippingMethod = $order->getShippingMethod();
        $shippingServices = $shippingMethod->getServices();
        $serviceCode = $order->get('shipping_service');

        $this->set([
            'carrier_code' => $order->get('shipping_method'),
            'service_code' => $order->get('shipping_service'),
            'carrier_desc' => $shippingMethod->getDescription(),
            'service_desc' => !empty($shippingServices[$serviceCode]) ? $shippingServices[$serviceCode] : null,
        ]);

        $this->state()->overall()->setDefaultState();
        $this->state()->carrier()->setDefaultState();
        $this->state()->custom()->setDefaultState();

        $this->save();

        $items = $order->items();
        if ($qtys === null) {
            $this->set([
                'customer_price' => $order->get('shipping_price'),
                'shipping_size' => $order->get('shipping_size'),
                'shipping_weight' => $order->get('shipping_weight'),
            ]);
            $qtys = [];
            foreach ($items as $item) {
                $qtys[$item->id()] = true;
            }
        }

        $pkgHlp = $this->Sellvana_Sales_Model_Order_Shipment_Package;
        $shipItemHlp = $this->Sellvana_Sales_Model_Order_Shipment_Item;

        $firstPackage = $pkgHlp->create([
            'order_id' => $order->id(),
            'shipment_id' => $this->id(),
        ])->save();

        $numItems = 0;
        foreach ($qtys as $itemId => $qty) {
            if (empty($items[$itemId])) {
                throw new BException($this->_('Invalid item id: %s', $itemId));
            }
            /** @var Sellvana_Sales_Model_Order_Item $item */
            $item = $items[$itemId];
            if ($item->state()->delivery()->getValue() == Sellvana_Sales_Model_Order_Item_State_Delivery::VIRTUAL) {
                continue;
            }
            $qtyCanShip = $item->getQtyCanShip();
            if ($qty === true) {
                $qty = $qtyCanShip;
            } elseif ($qty <= 0 || $qty > $qtyCanShip) {
                throw new BException($this->_('Invalid quantity to ship for %s: %s', [$item->get('product_sku'), $qty]));
            }

            $shipData = [
                'order_id' => $order->id(),
                'shipment_id' => $this->id(),
                'order_item_id' => $item->id(),
            ];
            if ($item->get('pack_separate')) {
                $shipData['qty'] = 1;
                for ($i = 0; $i < $qty; $i++) {
                    $package = $pkgHlp->create([
                        'order_id' => $order->id(),
                        'shipment_id' => $this->id(),
                    ])->save();
                    $shipData['package_id'] = $package->id();
                    $shipItemHlp->create($shipData)->save();
                }
            } else {
                $shipData['package_id'] = $firstPackage->id();
                $shipData['qty'] = $qty;
                $shipItemHlp->create($shipData)->save();
            }
            $numItems += $qty;
        }
        $this->set([
            'num_items' => $numItems,
        ])->save();

        return $this;
    }

    /**
     * @return Sellvana_Sales_Model_Order_Shipment_Item[]
     */
    public function items()
    {
        if (null === $this->_items) {
            $this->_items = $this->Sellvana_Sales_Model_Order_Shipment_Item->orm('osi')
                ->where('shipment_id', $this->id())
                ->join('Sellvana_Sales_Model_Order_Item', ['oi.id', '=', 'osi.order_item_id'], 'oi')
                ->select('osi.*')->select(['oi.product_id', 'product_sku', 'inventory_id', 'inventory_sku',
                    'product_name', 'shipping_size', 'shipping_weight'])
                ->find_many();
        }
        return $this->_items;
    }

    /**
     * @return Sellvana_Sales_Model_Order_Shipment_Package[]
     */
    public function packages()
    {
        if (null === $this->_packages) {
            $this->_packages = $this->Sellvana_Sales_Model_Order_Shipment_Package->orm()
                ->where('shipment_id', $this->id())->find_many();
        }
        return $this->_packages;
    }

    public function register()
    {
        $order = $this->order();
        $orderItems = $order->items();
        $shipmentItems = $this->items();

        foreach ($shipmentItems as $sItem) {
            $oItem = $orderItems[$sItem->get('order_item_id')];
            $oItem->add('qty_shipped', $sItem->get('qty'));
        }

        return $this;
    }

    public function unregister()
    {
        $order = $this->order();
        $orderItems = $order->items();
        $shipmentItems = $this->items();

        foreach ($shipmentItems as $sItem) {
            $oItem = $orderItems[$sItem->get('order_item_id')];
            $oItem->add('qty_shipped', -$sItem->get('qty'));
        }

        return $this;
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_order, $this->_items, $this->_state);
    }

    public function onBeforeDelete()
    {
        $this->deleteFiles();
        return parent::onBeforeDelete();
    }

    /**
     * @return bool
     * @throws BException
     */
    public function deleteFiles()
    {
        $path = $this->getStoragePath();

        return $this->BFile->delTree($path);
    }

    /**
     * @return string
     * @throws BException
     */
    public function getStoragePath()
    {
        if (null === $this->get('id')) {
            throw new BException('Can\'t get shipment id.');
        }

        $randomPath = $this->BApp->storageRandomDir();
        $path = $randomPath . '/order/shipment/' . $this->get('id');

        $this->BUtil->ensureDir($path);

        return $path;
    }
}
