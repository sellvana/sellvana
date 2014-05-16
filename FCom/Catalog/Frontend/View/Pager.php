<?php

class FCom_Catalog_Frontend_View_Pager extends FCom_Core_View_Abstract
{
    public function getViewAs()
    {
        $viewAs = BRequest::i()->get('view');
        return $viewAs && in_array($viewAs, $this->view_as_options) ? $viewAs : $this->default_view_as;
    }
}
