<?php

class FCom_Admin_View_Form extends BView
{
    public function addTab($id, $params)
    {
        $tabs = (array)$this->tabs;
        if (empty($params['view'])) {
            $params['view'] = $this->tab_view_prefix.$id;
        }
        if (empty($params['pos'])) {
            $params['pos'] = null;
        }
        $tabs[$id] = $params;
        $this->tabs = $tabs;
        return $this;
    }

    public function sortedTabs()
    {
        $tabs = (array)$this->tabs;
        uasort($tabs, function($a, $b) {
            return $a['pos']<$b['pos'] ? -1 : ($a['pos']>$b['pos'] ? 1 : 0);
        });
        #$this->tabs = $tabs;
        return $tabs;
    }
}