<?php

class FCom_Seo_Frontend extends BClass
{
    static public function bootstrap()
    {
        BRouting::i()
            ->get('/sitemap', 'FCom_Seo_Frontend_Controller_Sitemaps.sitemap')

            /** @see https://support.google.com/webmasters/bin/answer.py?hl=en&answer=71453 */
            ->get('/sitemap-index.xml', 'FCom_Seo_Frontend_Controller_Sitemaps.index_xml')

            /** @see https://support.google.com/webmasters/bin/answer.py?hl=en&answer=183668 */
            ->get('^/sitemap(-([a-z0-9-]+))?\.(xml|txt)(\.gz)?$', 'FCom_Seo_Frontend_Controller_Sitemaps.sitemap_data')

            ->get('/*url', 'FCom_Seo_Frontend_Controller_UrlAliases.index')
        ;
    }
}