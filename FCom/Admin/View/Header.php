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

    public function getNotifications()
    {
        $notifications = array();
        BEvents::i()->fire(__METHOD__, array('notifications' => &$notifications));
        $result = array();
        foreach ($notifications as &$item) {
            if (empty($item['group'])) {
                $item['group'] = 'other';
            }
            if (empty($item['href'])) {
                $item['href'] = '#';
            }
            if (empty($item['title'])) {
                $item['title'] = $item['message'];
            }
            $item['html'] = BUtil::tagHtml('a', array(
                'href'=>$item['href'],
                'title'=>$item['title'],

            ), $item['message']);
            $result[$item['group']][] = $item;
        }
        unset($item);
        return array('count' => sizeof($notifications), 'groups' => $result);
    }
}
