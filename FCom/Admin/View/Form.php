<?php

class FCom_Admin_View_Form extends BView
{
    public function addTab($id, $params)
    {
        $tabs = (array)$this->tabs;
        if (empty($params['view'])) {
            $params['view'] = $this->tab_view_prefix.$id;
        }
        $tabs[$id] = $params;
        $this->tabs = $tabs;
        return $this;
    }
}