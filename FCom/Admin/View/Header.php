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
        $this->BEvents->fire(__METHOD__, ['notifications' => &$notifications]);
        $conf      = $this->BConfig;
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
            $item['html'] = $this->BUtil->tagHtml('a', [
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

    public function getLocales()
    {
        $conf = $this->BConfig->get('modules/FCom_Admin');
        if (empty($conf['enable_locales']) || empty($conf['allowed_locales'])) {
            return false;
        }
        $locales = [];
        $urlTpl = $this->BUtil->setUrlQuery($this->BApp->href('switch_locale'), ['locale' => '-LOCALE-']);
        sort($conf['allowed_locales']);
        foreach ($conf['allowed_locales'] as $locale) {
            list($flag) = explode('_', $locale);
            $locales[] = [
                'code' => $locale,
                'title' => $locale,
                'flag' => $flag,
                'href' => str_replace('-LOCALE-', $locale, $urlTpl),
            ];
        }
        return $locales;
    }

    public function getCurrentLocale()
    {
        $locale = $this->BSession->get('_locale');
#echo "<pre>"; var_dump($locale); exit;
        if (!$locale) {
            $locale = $this->BConfig->get('modules/FCom_Admin/default_locale');
        }
        list($flag) = explode('_', $locale);
        return [
            'title' => $locale,
            'flag' => $flag,
        ];
    }
}
