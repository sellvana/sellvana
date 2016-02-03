<?php

/**
 * Class FCom_Admin_View_Header
 *
 * @property FCom_Admin_Model_Activity $FCom_Admin_Model_Activity
 */
class FCom_Admin_View_Header extends FCom_Core_View_Abstract
{
    /**
     * @var array
     */
    protected $_quickSearches = [];
    /**
     * @var array
     */
    protected $_shortcuts = [];

    /**
     * @param string $name
     * @param $config
     * @return $this
     */
    public function addQuickSearch($name, $config)
    {
        $this->_quickSearches[$name] = $config;
        return $this;
    }

    /**
     * @param string $name
     * @param $config
     * @return $this
     */
    public function addShortcut($name, $config)
    {
        $this->_shortcuts[$name] = $config;
        return $this;
    }

    /**
     * @param string $feed (local, remote, realtime)
     * @return array
     */
    public function getAllNotifications()
    {
        $items = [];
        $this->BEvents->fire(__METHOD__, ['items' => &$items]);

        $activity = $this->FCom_Admin_Model_Activity->addActivityItems($items)->getUserVisibleItems();

        $conf      = $this->BConfig;
        $dismissed = $conf->get('modules/FCom_Core/dismissed/notifications');
        $result = [
            'local' => [
                'title' => 'Local Alerts',
                'icon_class' => 'icon-bell',
                'count' => 0,
            ],
            'remote' => [
                'title' => 'Remote Notifications',
                'icon_class' => 'icon-rss',
                'count' => 0,
            ],
            'realtime' => [
                'title' => 'Real-Time Activity',
                'icon_class' => 'icon-bolt',
                'count' => 0,
            ],
        ];
        foreach ($activity as $a) {
            /** @var FCom_Admin_Model_Activity $a */
            $item = $a->getData() + $a->as_array();
            if ($dismissed && in_array($item['code'], $dismissed)) {
                continue;
            }
            if (empty($item['group'])) {
                $item['group'] = 'other';
            }
            if (empty($item['href'])) {
                $item['href'] = '#';
            }
            if (empty($item['title'])) {
                $item['title'] = $item['content'];
            }
            $item['ts'] = $this->BLocale->datetimeDbToLocal($item['ts'], true);
            if (empty($item['icon_class'])) {
                switch ($item['type']) {
                    case 'error':
                        $item['icon_class'] = 'icon_error';
                        break;
                    case 'warning':
                        $item['icon_class'] = 'icon_warning';
                        break;
                    case 'progress':
                        $item['icon_class'] = 'fa fa-tasks';
                        break;
                    default:
                        $item['icon_class'] = 'icon_info';
                }

            }
            /*
            $item['html'] = $this->BUtil->tagHtml('a', [
                'href' => $item['href'],
                'title' => $item['title'],
            ], $this->BResponse->safeHtml($item['message']));
            */
            $feed = $item['feed'];
            if (empty($result[$feed])) {
                $result[$feed] = [
                    'title' => !empty($titles[$feed]) ? $titles[$feed] : null,
                    'icon_class' => !empty($iconClasses[$feed]) ? $iconClasses[$feed] : null,
                    'count' => 0,
                    'groups' => [],
                ];
            }
            $result[$feed]['count']++;
            $result[$feed]['groups'][$item['group']][] = $item;
        }
        return $result;
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    public function getCurrentLocale()
    {
        $locale = $this->BSession->get('current_locale');
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
