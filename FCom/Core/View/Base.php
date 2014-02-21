<?php

class FCom_Core_View_Base extends FCom_Core_View_Abstract
{
    public function url($path = null, $full = true, $method = 2)
    {
        return BApp::href($path, $full, $method);
    }
}