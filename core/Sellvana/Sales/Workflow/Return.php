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

    public function action_customerRequestsRMA($args)
    {
    }

    public function action_adminCreatesRMA($args)
    {
    }

    public function action_adminApprovesRMA($args)
    {
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

            $item->add('qty_returned', $qtyToReturn);

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
}
