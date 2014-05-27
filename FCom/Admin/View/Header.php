<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Admin_View_Header extends FCom_Core_View_Abstract
{
    protected $_quickSearches = [];
    protected $_shortcuts = [];

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
        $notifications = [];
        BEvents::i()->fire(__METHOD__, ['notifications' => &$notifications]);
        $conf      = BConfig::i();
        $dismissed = $conf->get('modules/FCom_Core/dismissed/notifications');
        $result = [];
        foreach ($notifications as $k => &$item) {
            if ($dismissed && in_array($item['code'], $dismissed)) {
                unset($notifications[$k]);
                continue;
            }
            if (empty($item['group'])) {
                $item['group'] = 'other';
            }
            if (empty($item['href'])) {
                $item['href'] = '#';
            }
            if (empty($item['title'])) {
                $item['title'] = $item['message'];
            }
            $item['html'] = BUtil::tagHtml('a', [
                'href' => $item['href'],
                'title' => $item['title'],

            ], $item['message']);
            $result[$item['group']][] = $item;
        }
        unset($item);
        return ['count' => sizeof($notifications), 'groups' => $result];
    }

    public function getRecentActivity()
    {
        return [];
    }
}
