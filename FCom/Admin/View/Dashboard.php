<?php

class FCom_Admin_View_Dashboard extends FCom_Admin_View_Abstract
{
    public function addWidget($widgetKey, $widget)
    {
        $widgets = (array)$this->get('widgets');
        $widget['key'] = $widgetKey;
        if (empty($widget['cols'])) {
            $widget['cols'] = 6;
        }
        $widgets[$widgetKey] = $widget;
        $this->set('widgets', $widgets);
        return $this;
    }

    public function getWidgets()
    {
        BEvents::i()->fire(__METHOD__, array('view' => $this));

        $widgets = (array)$this->get('widgets');

        $pers = FCom_Admin_Model_User::i()->personalize();
        if (!empty($pers['dashboard']['widgets'])) {
            foreach ($pers['dashboard']['widgets'] as $wKey => $wState) {
                $widgets[$wKey]['state'] = $wState;
            }
        }
        return $widgets;
    }

}
