<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_State_Custom extends FCom_Core_Model_Abstract_State_Concrete
{
    protected function _initialize()
    {
        $statuses = $this->FCom_Sales_Model_CustomStatus->orm()->find_many();

        $this->_valueLabels = [];
        foreach ($statuses as $status) {
            $this->_valueLabels[$status->get('code')] = $status->get('name');
        }
    }

    public function setDefault()
    {
        $defaultState = $this->BConfig->get('modules/FCom_Sales/default_custom_state');
        return $this->changeState($defaultState);
    }
}
