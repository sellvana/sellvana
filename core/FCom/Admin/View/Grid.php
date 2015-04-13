<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Admin_View_Grid extends FCom_Core_View_Abstract
{
    public function getActionsHtml()
    {
        if (!$this->get('actions')) {
            return '';
        }
        $htmlArr = [];
        foreach ($this->get('actions') as $action) {
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
