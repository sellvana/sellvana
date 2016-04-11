<?php

class Sellvana_Sales_Admin_Controller_Abstract extends FCom_Admin_Controller_Abstract_GridForm
{
    protected function _resetOrderTabs(Sellvana_Sales_Model_Order $order)
    {
        $result = [
            'tabs' => [
                'main' => (string)$this->view('order/orders-form/main')->set('model', $order),
            ]
        ];
        foreach (['shipments', 'payments', 'cancellations', 'returns', 'refunds'] as $tab) {
            $result['tabs'][$tab] = false;
        }
        return $result;
    }
}