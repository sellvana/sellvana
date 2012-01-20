<?php

class FCom_Catalog_Admin_View_ProductView extends BView
{
    public function addTab($id, $params)
    {
        $tabs = (array)$this->tabs;
        if (empty($params['view'])) {
            $params['view'] = 'catalog/products/tab/'.$id;
        }
        $tabs[$id] = $params;
        $this->tabs = $tabs;
        return $this;
    }
}