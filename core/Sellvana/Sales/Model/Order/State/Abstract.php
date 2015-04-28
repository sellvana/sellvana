<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_State_Abstract extends FCom_Core_Model_Abstract_State_Concrete
{
    public function changeState($value, $updateModelField = true)
    {
        $newState = parent::changeState($value, $updateModelField);

        if ($this->getValue() == $newState->getValue()) {
            return $newState;
        }

        $context = $this->getContext();
        $model = $context->getModel();

        if ($this->getValue()) {
            $comment = $this->BLocale->_('%s state was changed from %s to %s', [
                $context->getStateLabel($this->_type),
                $this->getValueLabel(),
                $newState->getValueLabel(),
            ]);
        } else {
            $comment = $this->BLocale->_('%s state was set to %s', [
                $context->getStateLabel($this->_type),
                $newState->getValueLabel(),
            ]);
        }
        $model->addHistoryEvent('state:' . $this->_type, $comment);

        return $newState;
    }

    public function getItemStateStatistics($stateType)
    {
        /** @var Sellvana_Sales_Model_Order $model */
        $model = $this->getContext()->getModel();

        $itemStates = [];
        foreach ($model->items() as $item) {
            $itemStateValue = $item->state()->{$stateType}()->calcState()->getValue();
            $itemStates[$itemStateValue] = !empty($itemStates[$itemStateValue]) ? $itemStates[$itemStateValue]+1 : 1;
        }

        return $itemStates;
    }
}