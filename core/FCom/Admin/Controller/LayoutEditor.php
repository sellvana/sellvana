<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Core_Controller_LayoutEditor
 *
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 */
class FCom_Admin_Controller_LayoutEditor extends FCom_Admin_Controller_Abstract
{
    public function action_export__POST()
    {
        $layoutData = $this->BRequest->post('layout');

        $layout = $this->FCom_Core_LayoutEditor->compileLayout($layoutData);

        $this->BResponse->sendContent($layout);
    }
}