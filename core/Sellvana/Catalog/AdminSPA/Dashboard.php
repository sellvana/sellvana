<?php

/**
 * Class Sellvana_Catalog_AdminSPA_Dashboard
 *
 * @property Sellvana_Catalog_Admin_Dashboard Sellvana_Catalog_Admin_Dashboard
 */
class Sellvana_Catalog_AdminSPA_Dashboard extends BClass
{
    public function widgetLowInventory($filter)
    {
        $products = $this->Sellvana_Catalog_Admin_Dashboard->getLowStockProducts();
        return [
            'products' => $this->BDb->many_as_array($products),
        ];
    }

    public function widgetNewProducts($filter)
    {
        $products = $this->Sellvana_Catalog_Admin_Dashboard->getLatestNewProducts();
        return [
            'products' => $this->BDb->many_as_array($products),
        ];
    }

    public function widgetWithoutImages($filter)
    {
        $products = $this->Sellvana_Catalog_Admin_Dashboard->getProductsWithoutImages();
        return [
            'products' => $this->BDb->many_as_array($products),
        ];
    }

    public function widgetSearchRecentTerms($filter)
    {
        $products = $this->Sellvana_Catalog_Admin_Dashboard->getSearchesRecentTerms();
        return [
            'terms' => $this->BDb->many_as_array($products),
        ];
    }

    public function widgetSearchTopTerms($filter)
    {
        $products = $this->Sellvana_Catalog_Admin_Dashboard->getSearchesTopTerms();
        return [
            'terms' => $this->BDb->many_as_array($products),
        ];
    }

}