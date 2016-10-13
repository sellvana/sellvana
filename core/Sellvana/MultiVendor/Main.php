<?php

/**
 * Class Sellvana_MultiVendor_Main
 *
 * @property Sellvana_MultiVendor_Model_Vendor $Sellvana_MultiVendor_Model_Vendor
 * @property Sellvana_MultiVendor_Model_VendorProduct $Sellvana_MultiVendor_Model_VendorProduct
 * @property Sellvana_Sales_Model_Order_State_Custom $Sellvana_Sales_Model_Order_State_Custom
 */

class Sellvana_MultiVendor_Main extends BClass
{
    public function getAvailableOrderStates()
    {
        $states = [
            '@Overall' => [
                'overall:placed' => 'Placed',
                'overall:legit' => 'Passed Verification',
                'overall:complete' => 'Complete',
            ],
            '@Payment' => [
                'payment:free' => 'Free',
                'payment:processing' => 'Processing',
                'payment:paid' => 'Paid',
            ],
        ];

        $customStates = $this->Sellvana_Sales_Model_Order_State_Custom->getAllValueLabels();
        if ($customStates) {
            foreach ($customStates as $k => $l) {
                $states['@Custom']['custom:' . $k] = $l;
            }
        }

        return $states;
    }

    public function onOrderChangeState($args)
    {
        /** @var FCom_Core_Model_Abstract_State_Concrete $stateModel */
        $stateModel = $args['new_state'];
        $orderState = $stateModel->getType() . ':' . $stateModel->getValue();
        $confState = $this->BConfig->get('modules/Sellvana_MultiVendor/notify_on_order_states');
        if (in_array($orderState, (array)$confState)) {
            /** @var Sellvana_Sales_Model_Order $order */
            $order = $stateModel->getModel();
            $this->notifyOrderVendors($order);
        }
    }

    public function notifyOrderVendors(Sellvana_Sales_Model_Order $order)
    {
        $order->loadItemsProducts();
        $shippingMethod = $order->getShippingMethod();

        $vendorProducts = $this->Sellvana_MultiVendor_Model_VendorProduct->orm('vp')
            ->join('Sellvana_Sales_Model_Order_Item', ['oi.product_id', '=', 'vp.product_id'], 'oi')
            ->where('oi.order_id', $order->id())
            ->select('vp.*')->select('oi.id', 'item_id')->select('oi.product_name')->select('oi.product_sku')
            ->find_many_assoc('item_id');

        $vIds = [];
        foreach ($vendorProducts as $vp) {
            $vIds[$vp->get('vendor_id')] = 1;
        }
        if (!$vIds) {
            return;
        }
        $vendors = $this->Sellvana_MultiVendor_Model_Vendor->orm()->where_in('id', $vIds)->find_many();
        foreach ($vendors as $vendor) {
            // going through all vendors to account for future "digest" notification type
            if ($vendor->get('notify_type') !== 'realtime' && $vendor->get('email_notify')) {
                continue;
            }
            $items = [];
            foreach ($order->items() as $item) {
                $itemId = $item->id();
                if (!empty($vendorProducts[$itemId])) {
                    $vp = $vendorProducts[$itemId];
                    $items[] = [
                        'sku' => $vp->get('vendor_sku') ?: $item->get('product_sku'),
                        'name' => $vp->get('vendor_product_name') ?: $item->get('product_name'),
                        'qty' => $item->get('qty_ordered'),
                    ];
                }
            }
            if ($items) {
                $this->BLayout->getView('email/multivendor_vendor_notify')->set([
                    'order' => $order,
                    'vendor' => $vendor,
                    'items' => $items,
                    'shipping' => [
                        'method' => $shippingMethod->getDescription(),
                        'service' => $shippingMethod->getService($order->get('shipping_service')),
                    ],
                ])->email();
            }
        }
    }
}