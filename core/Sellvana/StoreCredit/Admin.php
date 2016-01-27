<?php

/**
 * Class Sellvana_StoreCredit_Admin
 *
 * @property Sellvana_StoreCredit_Model_Balance $Sellvana_StoreCredit_Model_Balance
 * @property Sellvana_StoreCredit_Model_Transaction $Sellvana_StoreCredit_Model_Transaction
 */
class Sellvana_StoreCredit_Admin extends BClass
{
    public function getCustomerHistoryGridConfig(Sellvana_Customer_Model_Customer $customer)
    {
        return [
            'config' => [
                'id' => 'customer_storecredit_history',
                'data_mode' => 'local',
                'orm' => $this->Sellvana_StoreCredit_Model_Transaction->orm('t')
                    ->join('Sellvana_StoreCredit_Model_Balance', ['b.id', '=', 't.balance_id'], 'b')
                    ->left_outer_join('Sellvana_Sales_Model_Order', ['o.id', '=', 't.order_id'], 'o')
                    ->where('b.customer_id', $customer->id())
                    ->select(['t.*', 'o.unique_id']),
                'columns' => [
                    ['name' => 'event', 'label' => 'Event', 'index' => 't.event'],
                    ['name' => 'amount', 'label' => 'Amount', 'index' => 't.amount'],
                    ['name' => 'unique_id', 'label' => 'Order #', 'index' => 'o.unique_id'],
                    ['name' => 'create_at', 'label' => 'Created At', 'index' => 't.create_at'],
                ],
                'state' => ['s' => 'create_at', 'sd' => 'desc'],
            ],
        ];
    }

    public function onCustomersFormPostAfter($args)
    {
        /** @var Sellvana_Customer_Model_Customer $model */
        $model = $args['model'];
        $data = $args['data'];
        $post = $this->BRequest->post('store_credit');
        if (!empty($post['adjust_amount'])) {
            $balHlp = $this->Sellvana_StoreCredit_Model_Balance;
            /** @var Sellvana_StoreCredit_Model_Balance $balance */
            $balance = $balHlp->load($model->id(), 'customer_id');
            if (!$balance) {
                $balance = $balHlp->create(['customer_id' => $model->id(), 'amount' => 0])->save();
            }
            $balance->adjust($post['adjust_amount']);
        }
    }
}