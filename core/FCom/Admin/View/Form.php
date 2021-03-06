<?php

/**
 * Class FCom_Admin_View_Form
 */
class FCom_Admin_View_Form extends FCom_Admin_View_Abstract
{
    public function getActionsHtml()
    {
        if (!$this->get('actions')) {
            return '';
        }

        $actions = [];
        $defaultOrder = 100;
        foreach ($this->get('actions') as $action) {
            $order = isset($action[3]) ? $action[3] : $defaultOrder;
            $actions[$order] = $action;
            $defaultOrder += 10;
        }
        ksort($actions);

        $htmlArr = [];
        foreach ($actions as $action) {
            if (is_string($action)) {
                $htmlArr[] = $action;
            } elseif (is_array($action)) {
                $htmlArr[] = $this->BUtil->tagHtml($action);
            } elseif ($action instanceof BView) {
                $htmlArr[] = (string)$action;
            }
        }
        return join(' ', $htmlArr);
    }
}