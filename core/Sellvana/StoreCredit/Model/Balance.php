<?php

/**
 * Class Sellvana_StoreCredit_Model_Balance
 *
 * @property Sellvana_StoreCredit_Model_Transaction $Sellvana_StoreCredit_Model_Transaction
 */
class Sellvana_StoreCredit_Model_Balance extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_storecredit_balance';
    protected static $_origClass = __CLASS__;

    public function deposit($amount, $orderId = null)
    {
        $trans = $this->_transact($amount, 'deposit', $orderId);
        $this->BEvents->fire(__METHOD__, ['balance' => $this, 'transaction' => $trans]);
        return $this;
    }

    public function withdraw($amount, $orderId = null)
    {
        $trans = $this->_transact(-$amount, 'withdraw', $orderId);
        $this->BEvents->fire(__METHOD__, ['balance' => $this, 'transaction' => $trans]);
        return $this;
    }

    public function adjust($amount)
    {
        $trans = $this->_transact($amount, 'adjust');
        $this->BEvents->fire(__METHOD__, ['balance' => $this, 'transaction' => $trans]);
        return $this;
    }

    protected function _transact($amount, $event, $orderId = null)
    {
        $this->add('amount', $amount)->save();
        $trans = $this->Sellvana_StoreCredit_Model_Transaction->create([
            'balance_id' => $this->id(),
            'event' => $event,
            'amount' => $amount,
            'order_id' => $orderId,
        ])->save();
        return $trans;
    }
}