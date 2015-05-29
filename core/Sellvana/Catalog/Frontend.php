<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Frontend
 * @property Sellvana_FrontendCP_Main $Sellvana_FrontendCP_Main
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_Catalog_Frontend extends BClass
{
    public function bootstrap()
    {
        if (class_exists('Sellvana_FrontendCP_Main')) {
            $this->Sellvana_FrontendCP_Main
                ->addEntityHandler('product', 'Sellvana_Catalog_Frontend_ControlPanel::productEntityHandler')
                ->addEntityHandler('category', 'Sellvana_Catalog_Frontend_ControlPanel::categoryEntityHandler')
            ;
        }
    }

    public function getFeaturedProducts($cnt = null)
    {
        if (!$cnt) {
            $cnt = 6;
        }
        return $this->Sellvana_Catalog_Model_Product->orm()->where('is_featured', 1)->limit($cnt)->find_many();
    }

    public function getPopularProducts($cnt = null)
    {
        if (!$cnt) {
            $cnt = 6;
        }
        return $this->Sellvana_Catalog_Model_Product->orm()->where('is_popular', 1)->limit($cnt)->find_many();
    }

    public function getRecentlyViewedProducts()
    {
        return [];
    }

    public function onSitemapsIndexXmlBefore($args)
    {
        $pageSize = $this->BConfig->get('modules/Sellvana_Seo/page_size', 100);

        $categoryCount = $this->Sellvana_Catalog_Model_Category->orm()->where('is_enabled', 1)->count();
        $pages = ceil($categoryCount / $pageSize);
        for ($i = 0; $i < $pages; $i++) {
            $args['sitemaps'][] = ['loc' => $this->BApp->href('sitemap-categories-' . $i . '.xml.gz')];
        }

        $productCount = $this->Sellvana_Catalog_Model_Product->orm()->where('is_hidden', 0)->count();
        $pages = ceil($productCount / $pageSize);
        for ($i = 0; $i < $pages; $i++) {
            $args['sitemaps'][] = ['loc' => $this->BApp->href('sitemap-products-' . $i . '.xml.gz')];
        }
    }

    public function onSitemapsDataBefore($args)
    {
        $urlParam = $this->BRequest->param(2);
        if (!preg_match('#^(categories|products)-([0-9]+)$#', $urlParam, $m)) {
            return;
        }
        $dataType = $m[1];
        $page = $m[2];
        $pageSize = $this->BConfig->get('modules/Sellvana_Seo/page_size', 100);
        switch ($dataType) {
            case 'categories':
                $categories = $this->Sellvana_Catalog_Model_Category->orm()->where('is_enabled', 1)
                    ->order_by_asc('id')->offset($page * $pageSize)->limit($pageSize)->find_many();
                foreach ($categories as $c) {
                    $args['urls'][] = ['loc' => $c->url()];
                }
                break;

            case 'products':
                $products = $this->Sellvana_Catalog_Model_Product->orm()->where('is_hidden', 0)
                    ->order_by_asc('id')->offset($page * $pageSize)->limit($pageSize)->find_many();
                foreach ($products as $p) {
                    $args['urls'][] = ['loc' => $p->url()];
                }
                break;
        }
    }
}
