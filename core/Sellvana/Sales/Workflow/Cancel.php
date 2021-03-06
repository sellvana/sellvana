<?php

/**
 * Class Sellvana_Sales_Workflow_Cancel
 *
 * @property Sellvana_Sales_Model_Order_Cancel $Sellvana_Sales_Model_Order_Cancel
 * @property Sellvana_Sales_Model_Order_Cancel_Item $Sellvana_Sales_Model_Order_Cancel_Item
 * @property Sellvana_Sales_Model_Order_Item $Sellvana_Sales_Model_Order_Item
 */
class Sellvana_Sales_Workflow_Cancel extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_customerRequestsToCancelItems($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $qtys = $args['qtys'];

        /** @var Sellvana_Sales_Model_Order_Cancel $cancelModel */
        $cancelModel = $this->Sellvana_Sales_Model_Order_Cancel->create();
        $cancelModel->importFromOrder($order, $qtys);

        $cancelModel->state()->overall()->setRequested();
        $cancelModel->state()->custom()->setDefaultState();
        $cancelModel->save();

        $items = $order->items();
        foreach ($cancelModel->items() as $cItem) {
            $items[$cItem->get('order_item_id')]->state()->cancel()->setRequested();
        }

        $order->addHistoryEvent('cancel_req', 'Customer has requested order items cancellation');
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminCancelsOrderItems($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];

        /** @var Sellvana_Sales_Model_Order_Cancel $cancelModel */
        $cancelModel = $this->Sellvana_Sales_Model_Order_Cancel->create([
            'order_id' => $order->id(),
            'canceled_at' => $this->BDb->now(),
        ]);
        $cancelModel->state()->overall()->setDefaultState();
        $cancelModel->state()->custom()->setDefaultState();
        $cancelModel->save();

        /** @var Sellvana_Sales_Model_Order_Item $item */
        foreach ($args['items'] as $item) {
            $qtyToCancel = min($item->getQtyCanCancel(), $item->get('qty_to_cancel'));

            $qtyBackordered = $item->get('qty_backordered');
            if ($qtyBackordered) {
                $item->set('qty_backordered', max(0, $qtyBackordered - $qtyToCancel));
            }

            $item->add('qty_in_cancels', $qtyToCancel);

            $this->Sellvana_Sales_Model_Order_Cancel_Item->create([
                'order_id' => $order->id(),
                'cancel_id' => $cancelModel->id(),
                'order_item_id' => $item->id(),
                'qty' => $qtyToCancel,
            ])->save();
        }
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminChangesCancelCustomState($args)
    {
        $newState = $args['cancel']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['cancel']->addHistoryEvent('custom_state', $this->_((('Admin user has changed custom cancel state to "%s"')), $label));
        $args['cancel']->save();
    }

    public function action_adminCreatesCancel($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $data = $this->BRequest->sanitize($args['data'], []);
        $qtys = isset($args['qtys']) ? $args['qtys'] : null;
        foreach ($qtys as $id => $qty) {
            if ($qty < 1) {
                unset($qtys[$id]);
            }
        }
        if (!$qtys) {
            throw new BException('Please add some items to create a cancel');
        }
        /** @var Sellvana_Sales_Model_Order_Cancel $cancel */
        $cancel = $this->Sellvana_Sales_Model_Order_Cancel->create($data);
        $cancel->importFromOrder($order, $qtys);
        $cancel->state()->overall()->setApproved();
        $cancel->save();

        $order->calcItemQuantities('cancels');
        $order->state()->calcAllStates();
        $order->saveAllDetails();

        return ['new_cancel' => $cancel];
    }

    public function action_adminUpdatesCancel($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $cancelId = $args['cancel_id'];
        $data = $args['data'];
        $cancel = $this->Sellvana_Sales_Model_Order_Cancel->load($cancelId);
        if (!$cancel || $cancel->get('order_id') != $order->id()) {
            throw new BException('Invalid cancellation to update');
        }
        if (isset($data['state_custom'])) {
            $cancel->state()->custom()->changeState($data['state_custom']);
        }
        if (isset($data['state_overall'])) {
            foreach ($data['state_overall'] as $state => $_) {
                $cancel->state()->overall()->invokeStateChange($state);

                if ($state == Sellvana_Sales_Model_Order_Cancel_State_Overall::COMPLETE) {
                    foreach ($cancel->items() as $item) {
                        $oItemId = $item->get('order_item_id');
                        $oItem = $this->Sellvana_Sales_Model_Order_Item->load($oItemId);
                        /** @var Sellvana_Catalog_Model_Product $product */
                        $product = $oItem->product();
                        $invModel = $product->getInventoryModel();
                        $invModel->add('qty_in_stock', $item->get('qty'));
                        $invModel->save();
                    }
                }
            }
        }
        $cancel->save();

        $order->calcItemQuantities('cancels');
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminDeletesCancel($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $cancelId = $args['cancel_id'];
        $cancel = $this->Sellvana_Sales_Model_Order_Cancel->load($cancelId);
        if (!$cancel || $cancel->get('order_id') != $order->id()) {
            throw new BException('Invalid cancellation to delete');
        }
        $cancel->delete();

        $order->calcItemQuantities('cancels');
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }
}
