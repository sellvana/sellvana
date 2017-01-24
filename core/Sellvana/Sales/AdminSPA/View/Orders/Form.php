<?php

class Sellvana_Sales_AdminSPA_View_Orders_Form extends FCom_Core_View_Abstract
{
    protected $_detailsSections = [];

    public function addDetailsSection($section)
    {
        $this->_detailsSections[] = $section;
    }

    public function getDetailsSections()
    {
        return $this->_detailsSections;
    }
}