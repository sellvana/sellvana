<?php

class FCom_Admin_View_Header extends FCom_Core_View_Abstract
{
    protected $_quickSearches = array();
    protected $_shortcuts = array();

    public function addQuickSearch($name, $config)
    {
        $this->_quickSearches[$name] = $config;
        return $this;
    }

    public function addShortcut($name, $config)
    {
        $this->_shortcuts[$name] = $config;
        return $this;
    }
}