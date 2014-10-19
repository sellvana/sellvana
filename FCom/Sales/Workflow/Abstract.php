<?php

abstract class FCom_Sales_Workflow_Abstract extends BClass
{
    protected $_flagRegistered = false;
    protected $_localHooks = [];

    public function registerWorkflow()
    {
        if ($this->_flagRegistered) {
            return $this;
        }
        $this->_flagRegistered = true;

        if ($this->_localHooks) {
            $class = $this->origClass();
            $events = $this->BEvents;
            foreach ($this->_localHooks as $key => $method) {
                $hook = !is_numeric($key) ? $key : $method;
                $events->on('FCom_Sales_Workflow::' . $hook, [$this, $method]);
            }
        }

        return $this;
    }
}
