<?php

/**
 * Class Sellvana_Seo_Frontend_Controller_Sitemaps
 *
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_Seo_Frontend_Controller_Sitemaps extends FCom_Frontend_Controller_Abstract
{
    public function action_sitemap()
    {
        $categoriesOrm = $this->Sellvana_Catalog_Model_Category->orm()->where_gte('level', 1);
        $productsOrm = $this->Sellvana_Catalog_Model_Product->orm();

        $pageSize = $this->BConfig->get('modules/Sellvana_Seo/page_size');
        $page = $this->BRequest->get('page') ?: 1;

        if ($page) {
            $categoriesOrm->offset($pageSize * ($page - 1));
            $productsOrm->offset($pageSize * ($page - 1));
        }

        $categories = $categoriesOrm->limit($pageSize)->find_many();
        $products = $productsOrm->limit($pageSize)->find_many();

        $this->view('seo/sitemap')->set([
            'categories' => $categories,
            'products' => $products,
            'searches' => [],
            'next_page' => (sizeof($categories) === $pageSize || sizeof($products) === $pageSize) ? ($page + 1) : false,
        ]);

        $this->layout('/sitemap');
    }

    public function action_index_xml()
    {
        $output = '<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $sitemaps = [//TODO: fetch real paginated sitemaps
            //['loc' => $this->BApp->href('sitemap.xml.gz')],
        ];

        $this->BEvents->fire(__METHOD__ . ':before', ['sitemaps' => &$sitemaps]);

        $now = date('c');
        foreach ($sitemaps as $sitemap) {
            $ts = (!empty($sitemap['lastmod']) ? date('c', strtotime($sitemap['lastmod'])) : $now);
            $output .= '<sitemap>'
                . '<loc>' . $sitemap['loc'] . '</loc>'
                . '<lastmod>' . $ts . '</lastmod>' //TODO: figure out how to get lastmod
                . '</sitemap>';
        }
        $output .= '</sitemapindex>';
        echo $output;
        exit;
    }

    public function action_sitemap_data()
    {
        $params = $this->BRequest->param();
        $page = $params[2];
        $type = $params[3];

        $items = [];
        $this->BEvents->fire(__METHOD__ . ':before',
            ['items' => &$items, 'page' => $page, 'filetype' => $type]);

        switch ($type) {
            case 'txt':
                $output = '';
                break;
            case 'xml':
                $output = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
';
        }
        foreach ($items as $item) {
            if (!is_array($item)) {
                $item = ['loc' => $item];
            }
            switch ($type) {
                case 'txt':
                    $output .= $item['loc'] . "\n";
                    break;
                case 'xml':
                    $output .= '<url><loc>' . htmlspecialchars($item['loc']) . '</loc>';
                    if (!empty($item['lastmod'])) {
                        $lastmod = $item['lastmod'];
                        if (!is_numeric($lastmod)) {
                            $lastmod = strtotime($lastmod);
                        }
                        $output .= '<lastmod>' . date('c', $lastmod) . '</lastmod>';
                    }
                    if (!empty($item['changefreq'])) {
                        $output .= '<changefreq>' . $item['changefreq'] . '</changefreq>';
                    }
                    if (!empty($item['priority'])) {
                        $output .= '<priority>' . $item['priority'] . '</priority>';
                    }
                    if (!empty($item['images'])) {
                        foreach ($item['images'] as $img) {
                            $output .= '<image:image>' . htmlspecialchars($img) . '</image:image>';
                        }
                    }
                    $output .= '</url>';
                    break;
            }
        }

        switch ($type) {
            case 'xml':
                $output .= '</urlset>';
                break;
        }

        if (!empty($params[4]) && $params[4] === '.gz') {
            $output = gzcompress($output);
        }
        echo $output;
        exit;
    }
}
