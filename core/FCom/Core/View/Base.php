<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Core_View_Base extends FCom_Core_View_Abstract
{
    public function url($path = null, $full = true, $method = 2)
    {
        return $this->BApp->href($path, $full, $method);
    }
}
