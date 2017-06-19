<?php

/**
 * Class FCom_Admin_View_Dashboard
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
class FCom_AdminSPA_AdminSPA_View_Dashboard extends FCom_Admin_View_Abstract
{
    /**
     * @param string $widgetKey
     * @param array $widget
     * @return $this
     * @throws BException
     */
    public function addWidget($widgetKey, $widget)
    {
        if (array_key_exists('permission', $widget)) {
            $user = $this->FCom_Admin_Model_User->sessionUser();

            if (!$user || !$user->getPermission($widget['permission'])) {
                return $this;
            }
        }
        if (empty($widget['component']) && empty($widget['template'])) {
            throw new BException('Invalid widget configuration');
        }

        $widgets = (array)$this->get('widgets');
        $widget['key'] = $widgetKey;
        if (empty($widget['container_class'])) {
            $widget['container_class'] = 'col-md-6';
        }
        $widgets[$widgetKey] = $widget;
        $this->set('widgets', $widgets);
        return $this;
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        $this->BEvents->fire(__METHOD__, ['view' => $this]);

        $widgets = (array)$this->get('widgets');

        $pers = $this->FCom_Admin_Model_User->personalize();
        if (!empty($pers['dashboard']['widgets'])) {
            foreach ($pers['dashboard']['widgets'] as $wKey => $wState) {
                if (!empty($widgets[$wKey])) {
                    $widgets[$wKey]['state'] = $wState;
                }
            }

            $pos = 0;
            foreach ($widgets as $wKey => $widget) {
                $pos++;
                if (empty($widget['state']['pos'])) {
                    $widgets[$wKey]['state']['pos'] = $pos;
                }
            }
            uasort($widgets, function($a, $b) {
                return $a['state']['pos'] < $b['state']['pos'] ? -1
                    : ($a['state']['pos'] > $b['state']['pos'] ? 1 : 0);
            });
        }
        return $widgets;
    }

    public function widgetVisitorsTotals($filter)
    {

    }
}
