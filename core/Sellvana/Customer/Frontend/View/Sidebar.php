<?php

/**
 * Class Sellvana_Customer_Frontend_View_Sidebar
 */
class Sellvana_Customer_Frontend_View_Sidebar extends FCom_Core_View_Abstract
{
    /**
     * @var array
     */
    protected $_navItems = [];

    /**
     * @var string
     */
    protected $_currentNavItem;

    /**
     * @param $itemKey
     * @param $item
     * @return $this
     */
    public function addNavItem($itemKey, $item)
    {
        if (empty($item['position'])) {
            $item['position'] = 1 + array_reduce($this->_navItems, function($a, $b) {
                return max($a['position'], $b['position']);
            });
        }
        $navItems = $this->get('items');
        $navItems[$itemKey] = $item;
        $this->set('items', $navItems);
        return $this;
    }

    /**
     * @param $itemKey
     * @return $this
     */
    public function removeNavItem($itemKey)
    {
        $navItems = $this->get('items');
        unset($navItems[$itemKey]);
        $this->set('items', $navItems);
        return $this;
    }

    /**
     * @return null
     */
    public function getNavItems()
    {
        return $this->get('items');
    }

    /**
     * @param string $itemKey
     * @return $this
     */
    public function setCurrentNavItem($itemKey)
    {
        #var_dump($itemKey);
        $this->_currentNavItem = $itemKey;
        return $this;
    }

    public function getCurrentNavItem()
    {
        return $this->_currentNavItem;
    }
}
