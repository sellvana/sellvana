<?php

/**
 * Class Sellvana_Sales_Workflow_Return
 *
 * @property Sellvana_Sales_Model_Order_Return $Sellvana_Sales_Model_Order_Return
 * @property Sellvana_Sales_Model_Order_Return_Item $Sellvana_Sales_Model_Order_Return_Item
 */
class Sellvana_Sales_Workflow_Return extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    static protected $_overallStates = [
        'requested' => 'setRequested',
        'pending'   => 'setPending',
        'rma_sent'  => 'setRMASent',
        'expired'   => 'setExpired',
        'canceled'  => 'setCanceled',
        'received'  => 'setReceived',
        'approved'  => 'setApproved',
        'restocked' => 'setRestocked',
        'declined'  => 'setDeclined',
    ];

    static protected $_stateRegistration = [
        'requested' => false,
        'pending'   => true,
        'rma_sent'  => true,
        'expired'   => false,
        'canceled'  => false,
        'received'  => true,
        'approved'  => true,
        'restocked' => true,
        'declined'  => false,
    ];

    public function action_customerRequestsToReturnItems($args)
    {
        $order = $args['order'];
        $qtys = $args['qtys'];

        /** @var Sellvana_Sales_Model_Order_Return $returnModel */
        $returnModel = $this->Sellvana_Sales_Model_Order_Return->create()->importFromOrder($order, $qtys);

        $returnModel->state()->overall()->setRequested();
        $returnModel->state()->custom()->setDefaultState();
        $returnModel->save();

        $items = $order->items();
        foreach ($returnModel->items() as $cItem) {
            $items[$cItem->get('order_item_id')]->state()->returns()->setRequested();
        }

    }

    public function action_adminReturnsOrderItems($args)
    {
        /** @var Sellvana_Sales_Model_Order_Return $returnModel */
        $returnModel = $this->Sellvana_Sales_Model_Order_Return->create([
            'order_id' => $args['order']->id(),
            'rma_at' => $this->BDb->now(),
        ]);
        $returnModel->state()->overall()->setDefaultState();
        $returnModel->state()->custom()->setDefaultState();
        $returnModel->save();

        /** @var Sellvana_Sales_Model_Order_Item $item */
        foreach ($args['items'] as $item) {
            $qtyToReturn = min($item->getQtyCanReturn(), $item->get('qty_to_return'));

            $item->add('qty_in_returns', $qtyToReturn);

            $this->Sellvana_Sales_Model_Order_Return_Item->create([
                'order_id' => $args['order']->id(),
                'return_id' => $returnModel->id(),
                'order_item_id' => $item->id(),
                'qty' => $qtyToReturn,
            ])->save();
        }
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminChangesReturnCustomState($args)
    {
        $newState = $args['return']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['return']->addHistoryEvent('custom_state', 'Admin user has changed custom return state to "' . $label . '"');
        $args['return']->save();
    }

    public function action_adminCreatesReturn($args)
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
            throw new BException('Please add some items to create a return');
        }
        /** @var Sellvana_Sales_Model_Order_Return $return */
        $return = $this->Sellvana_Sales_Model_Order_Return->create($data);
        $return->importFromOrder($order, $qtys);
        $return->state()->overall()->setApproved();
        $return->save();

        $order->calcItemQuantities('returns');
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminUpdatesReturn($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $returnId = $args['return_id'];
        $data = $args['data'];
        $return = $this->Sellvana_Sales_Model_Order_Return->load($returnId);
        if (!$return || $return->get('order_id') != $order->id()) {
            throw new BException('Invalid return to update');
        }
        if (isset($data['state_custom'])) {
            $return->state()->custom()->changeState($data['state_custom']);
        }
        if (isset($data['state_overall'])) {
            foreach ($data['state_overall'] as $state => $_) {
                $method = static::$_overallStates[$state];
                $oldState = $return->state()->overall()->getValue();
                $return->state()->overall()->$method();
            }
        }
        $return->save();

        $order->calcItemQuantities('returns');
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }

    public function action_adminDeletesReturn($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['order'];
        $cancelId = $args['return_id'];
        $cancel = $this->Sellvana_Sales_Model_Order_Return->load($cancelId);
        if (!$cancel || $cancel->get('order_id') != $order->id()) {
            throw new BException('Invalid shipment to delete');
        }
        $cancel->delete();

        $order->calcItemQuantities('returns');
        $order->state()->calcAllStates();
        $order->saveAllDetails();
    }
}
