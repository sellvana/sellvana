<?php

class FCom_Catalog extends BClass
{
    static protected $_transliterateMap = array(
        '&amp;' => 'and',   '@' => 'at',    '©' => 'c', '®' => 'r', 'À' => 'a',
        'Á' => 'a', 'Â' => 'a', 'Ä' => 'a', 'Å' => 'a', 'Æ' => 'ae','Ç' => 'c',
        'È' => 'e', 'É' => 'e', 'Ë' => 'e', 'Ì' => 'i', 'Í' => 'i', 'Î' => 'i',
        'Ï' => 'i', 'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
        'Ø' => 'o', 'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ý' => 'y',
        'ß' => 'ss','à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'å' => 'a',
        'æ' => 'ae','ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ò' => 'o', 'ó' => 'o',
        'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u',
        'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'p', 'ÿ' => 'y', 'Ā' => 'a',
        'ā' => 'a', 'Ă' => 'a', 'ă' => 'a', 'Ą' => 'a', 'ą' => 'a', 'Ć' => 'c',
        'ć' => 'c', 'Ĉ' => 'c', 'ĉ' => 'c', 'Ċ' => 'c', 'ċ' => 'c', 'Č' => 'c',
        'č' => 'c', 'Ď' => 'd', 'ď' => 'd', 'Đ' => 'd', 'đ' => 'd', 'Ē' => 'e',
        'ē' => 'e', 'Ĕ' => 'e', 'ĕ' => 'e', 'Ė' => 'e', 'ė' => 'e', 'Ę' => 'e',
        'ę' => 'e', 'Ě' => 'e', 'ě' => 'e', 'Ĝ' => 'g', 'ĝ' => 'g', 'Ğ' => 'g',
        'ğ' => 'g', 'Ġ' => 'g', 'ġ' => 'g', 'Ģ' => 'g', 'ģ' => 'g', 'Ĥ' => 'h',
        'ĥ' => 'h', 'Ħ' => 'h', 'ħ' => 'h', 'Ĩ' => 'i', 'ĩ' => 'i', 'Ī' => 'i',
        'ī' => 'i', 'Ĭ' => 'i', 'ĭ' => 'i', 'Į' => 'i', 'į' => 'i', 'İ' => 'i',
        'ı' => 'i', 'Ĳ' => 'ij','ĳ' => 'ij','Ĵ' => 'j', 'ĵ' => 'j', 'Ķ' => 'k',
        'ķ' => 'k', 'ĸ' => 'k', 'Ĺ' => 'l', 'ĺ' => 'l', 'Ļ' => 'l', 'ļ' => 'l',
        'Ľ' => 'l', 'ľ' => 'l', 'Ŀ' => 'l', 'ŀ' => 'l', 'Ł' => 'l', 'ł' => 'l',
        'Ń' => 'n', 'ń' => 'n', 'Ņ' => 'n', 'ņ' => 'n', 'Ň' => 'n', 'ň' => 'n',
        'ŉ' => 'n', 'Ŋ' => 'n', 'ŋ' => 'n', 'Ō' => 'o', 'ō' => 'o', 'Ŏ' => 'o',
        'ŏ' => 'o', 'Ő' => 'o', 'ő' => 'o', 'Œ' => 'oe','œ' => 'oe','Ŕ' => 'r',
        'ŕ' => 'r', 'Ŗ' => 'r', 'ŗ' => 'r', 'Ř' => 'r', 'ř' => 'r', 'Ś' => 's',
        'ś' => 's', 'Ŝ' => 's', 'ŝ' => 's', 'Ş' => 's', 'ş' => 's', 'Š' => 's',
        'š' => 's', 'Ţ' => 't', 'ţ' => 't', 'Ť' => 't', 'ť' => 't', 'Ŧ' => 't',
        'ŧ' => 't', 'Ũ' => 'u', 'ũ' => 'u', 'Ū' => 'u', 'ū' => 'u', 'Ŭ' => 'u',
        'ŭ' => 'u', 'Ů' => 'u', 'ů' => 'u', 'Ű' => 'u', 'ű' => 'u', 'Ų' => 'u',
        'ų' => 'u', 'Ŵ' => 'w', 'ŵ' => 'w', 'Ŷ' => 'y', 'ŷ' => 'y', 'Ÿ' => 'y',
        'Ź' => 'z', 'ź' => 'z', 'Ż' => 'z', 'ż' => 'z', 'Ž' => 'z', 'ž' => 'z',
        'ſ' => 'z', 'Ə' => 'e', 'ƒ' => 'f', 'Ơ' => 'o', 'ơ' => 'o', 'Ư' => 'u',
        'ư' => 'u', 'Ǎ' => 'a', 'ǎ' => 'a', 'Ǐ' => 'i', 'ǐ' => 'i', 'Ǒ' => 'o',
        'ǒ' => 'o', 'Ǔ' => 'u', 'ǔ' => 'u', 'Ǖ' => 'u', 'ǖ' => 'u', 'Ǘ' => 'u',
        'ǘ' => 'u', 'Ǚ' => 'u', 'ǚ' => 'u', 'Ǜ' => 'u', 'ǜ' => 'u', 'Ǻ' => 'a',
        'ǻ' => 'a', 'Ǽ' => 'ae','ǽ' => 'ae','Ǿ' => 'o', 'ǿ' => 'o', 'ə' => 'e',
        'Ё' => 'jo','Є' => 'e', 'І' => 'i', 'Ї' => 'i', 'А' => 'a', 'Б' => 'b',
        'В' => 'v', 'Г' => 'g', 'Д' => 'd', 'Е' => 'e', 'Ж' => 'zh','З' => 'z',
        'И' => 'i', 'Й' => 'j', 'К' => 'k', 'Л' => 'l', 'М' => 'm', 'Н' => 'n',
        'О' => 'o', 'П' => 'p', 'Р' => 'r', 'С' => 's', 'Т' => 't', 'У' => 'u',
        'Ф' => 'f', 'Х' => 'h', 'Ц' => 'c', 'Ч' => 'ch','Ш' => 'sh','Щ' => 'sch',
        'Ъ' => '-', 'Ы' => 'y', 'Ь' => '-', 'Э' => 'je','Ю' => 'ju','Я' => 'ja',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ж' => 'zh','з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l',
        'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
        'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
        'ш' => 'sh','щ' => 'sch','ъ' => '-','ы' => 'y', 'ь' => '-', 'э' => 'je',
        'ю' => 'ju','я' => 'ja','ё' => 'jo','є' => 'e', 'і' => 'i', 'ї' => 'i',
        'Ґ' => 'g', 'ґ' => 'g', 'א' => 'a', 'ב' => 'b', 'ג' => 'g', 'ד' => 'd',
        'ה' => 'h', 'ו' => 'v', 'ז' => 'z', 'ח' => 'h', 'ט' => 't', 'י' => 'i',
        'ך' => 'k', 'כ' => 'k', 'ל' => 'l', 'ם' => 'm', 'מ' => 'm', 'ן' => 'n',
        'נ' => 'n', 'ס' => 's', 'ע' => 'e', 'ף' => 'p', 'פ' => 'p', 'ץ' => 'C',
        'צ' => 'c', 'ק' => 'q', 'ר' => 'r', 'ש' => 'w', 'ת' => 't', '™' => 'tm',
    );

    static public function bootstrap()
    {
        BDb::migrate('FCom_Catalog_Migrate');

        switch (FCom::area()) {
            case 'FCom_Frontend': self::frontend(); break;
            case 'FCom_Admin': self::admin(); break;
        }
    }

    static public function frontend()
    {
        BFrontController::i()
            ->route( 'GET /c/*category', 'FCom_Catalog_Controller.category')
            ->route( 'GET /m/*manuf', 'FCom_Catalog_Controller.manuf')
            ->route( 'GET /p/*product', 'FCom_Catalog_Controller.product')
            ->route( 'GET /search', 'FCom_Catalog_Controller.search')
            ->route( 'GET /compare', 'FCom_Catalog_Controller.compare')
        ;

        BLayout::i()->allViews('views_frontend');
    }

    static public function admin()
    {
        BFrontController::i()
            ->route('GET /api/products', 'AdminApi.products')
            ->route('GET /api/category_tree', 'AdminApi.category_tree_get')
            ->route('POST /api/category_tree', 'AdminApi.category_tree_post')
        ;

        BLayout::i()->allViews('views_admin');

        BPubSub::i()
            ->on('category_tree_post.associate.products', 'AProduct.onAssociateCategory')
            ->on('category_tree_post.associate.category-aliases', 'CategoryAlias.onAssociateCategory')
            ->on('category_tree_post.reorderAZ', 'ACategory.onReorderAZ')
        ;
    }

    static public function getUrlKey($str)
    {
        return strtolower(trim(preg_replace('#[^0-9a-z]+#i', '-',
            strtr($str, static::$_transliterateMap)), '-'));
    }

    static public function url($type, $args)
    {
        if (is_string($args)) {
            return BApp::m('FCom_Catalog')->baseUrl().'/'.$type.'/'.$args;
        }
        return false;
    }

    static public function lastNav($save=false)
    {
        $s = BSession::i();
        $r = BRequest::i();
        if ($save) {
            $s->data('lastNav', array($r->rawPath(), $r->get()));
        } else {
            $d = $s->data('lastNav');
            return BApp::baseUrl().($d ? $d[0].'?'.http_build_query((array)$d[1]) : '');
        }
    }
}


class FCom_Catalog_Controller extends BActionController
{
    public function action_category()
    {
        $layout = BLayout::i();
        $category = ACategory::load(BRequest::i()->params('category'), 'url_path');
        if (!$category) $this->forward('noroute');

        $layout->view('category')->category = $category;
        $layout->view('product/rows')->category = $category;

        $crumbs = array('home');
        foreach ($category->ascendants() as $c) if ($c->node_name) $crumbs[] = array('label'=>$c->node_name, 'href'=>$c->url());
        $crumbs[] = array('label'=>$category->node_name, 'active'=>true);
        $layout->view('breadcrumbs')->crumbs = $crumbs;

        DCatalogMain::lastNav(true);
        $layout->view('main')->body_class = 'catalog-category-view';
        $layout->view('body')->append('category');
        BResponse::i()->render();
    }

    public function action_search()
    {
        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $qs = preg_split('#\s+#', $q, 0, PREG_SPLIT_NO_EMPTY);
        if (!$qs) {
            BResponse::i()->redirect(BApp::baseUrl());
        }
        $and = array();
        foreach ($qs as $k) $and[] = array('product_name like ?', '%'.$k.'%');
        $productsORM = AProduct::i()->factory()->where_complex(array('OR'=>array('manuf_sku'=>$q, 'AND'=>$and)));

        DCatalogMain::lastNav(true);
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Search: '.$q, 'active'=>true));
        $layout->view('search')->query = $q;
        $layout->view('product/list')->productsORM = $productsORM;
        $layout->view('main')->body_class = 'catalog-search';
        $layout->view('body')->append('search');
        BResponse::i()->render();
    }

    public function action_manuf()
    {
        BLayout::i()->view('body')->append('manuf');
        BResponse::i()->render();
    }

    public function action_product()
    {
        $layout = BLayout::i();
        $crumbs = array('home');
        $r = explode('/', BRequest::i()->params('product'));
        $p = array_pop($r);
        $product = AProduct::i()->load($p, 'url_key');
        if (!$product) {
            $this->forward('noroute');
            return $this;
        }
        BLayout::i()->view('product')->product = $product;
        if ($r) {
            $category = ACategory::load(join('/', $r), 'url_path');
            if (!$category) {
                $this->forward('noroute');
                return $this;
            }
            $layout->view('product')->category = $category;
            $layout->view('main')->canonical_url = $product->url();
            foreach ($category->ascendants() as $c) if ($c->node_name) $crumbs[] = array('label'=>$c->node_name, 'href'=>$c->url());
            $crumbs[] = array('label'=>$category->node_name, 'href'=>$category->url());
        }
        $crumbs[] = array('label'=>$product->product_name, 'active'=>true);

        $layout->view('breadcrumbs')->crumbs = $crumbs;

        $layout->view('main')->body_class = 'catalog-product-view';
        $layout->view('body')->append('product');
        BResponse::i()->render();
    }

    public function action_compare()
    {
        $layout = BLayout::i();
        $cookie = BRequest::i()->cookie('dentevaCompare');
        $xhr = BRequest::i()->xhr();
        if (!empty($cookie)) $arr = BUtil::fromJson($cookie);
        if (!empty($arr)) {
            AProduct::i()->cachePreloadFrom($arr);
            $products = AProduct::i()->cacheFetch();
        }
        if (empty($products)) {
            if ($xhr) {
                return;
            } else {
                BSession::i()->addMessage('No products to compare');
                BResponse::i()->redirect(DCatalogMain::lastNav());
            }
        }
        $layout->view('compare')->products = array_values($products);
        if ($xhr) {
            $layout->rootView('compare');
        } else {
            $layout->view('breadcrumbs')->crumbs = array('home',
                array('label'=>'Compare '.sizeof($products).' products', 'active'=>true)
            );
            $layout->view('body')->append('compare');
        }
        BResponse::i()->render();
    }
}
