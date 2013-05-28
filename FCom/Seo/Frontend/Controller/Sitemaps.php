<?php

class FCom_Seo_Frontend_Controller_Sitemaps extends FCom_Frontend_Controller_Abstract
{
    public function action_sitemap()
    {
        $params = BRequest::i()->param();
        $page = $params[2];
        $type = $params[3];

        $urls = array();
        BPubSub::i()->fire('FCom_Seo_Frontend_Controller_Sitemaps.sitemap',
            array('urls'=>&$urls, 'page'=>$page, 'filetype'=>$type));

        switch ($type) {
        case '.txt':
            $output = '';
            break;
        case '.xml':
            $output = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
';
        }
        foreach ($urls as $url) {
            if (!is_array($url)) {
                $url = array('loc'=>$url);
            }
            switch ($type) {
            case '.txt':
                $output .= $url['loc']."\n";
                break;
            case '.xml':
                $output .= '<url><loc>'.$url['loc'].'</loc>';
                if (!empty($url['lastmod'])) {
                    $lastmod = $url['lastmod'];
                    if (!is_numeric($lastmod)) $lastmod = strtotime($lastmod);
                    $output .= '<lastmod>'.date('c', $lastmod).'</lastmod>';
                }
                if (!empty($url['changefreq'])) {
                    $output .= '<changefreq>'.$url['changefreq'].'</changefreq>';
                }
                if (!empty($url['priority'])) {
                    $output .= '<priority>'.$url['priority'].'</priority>';
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

        if (!empty($params[4]) && $params[4]==='.gz') {
            $output = gzcompress($output);
        }
        echo $output;
        exit;
    }

    public function action_index()
    {
        $output = '<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $sitemaps = array( //TODO: fetch real paginated sitemaps
            array('loc' => BApp::href('sitemap.xml.gz')),
        );
        foreach ($sitemaps as $sitemap) {
            $output .= '<sitemap>'
                .'<loc>'.$sitemap['loc'].'</loc>'
                .'<lastmod>'.date('c').'</lastmod>' //TODO: figure out how to get lastmod
                .'</sitemap>';
        }
        $output .= '</sitemapindex>';
        echo $output;
        exit;
    }
}