<?php

class FCom_AdminSPA_AdminSPA_View_App extends FCom_Core_View_Abstract
{
    protected $_routes = [];

    protected $_navs = [];

    protected $_modules = [];

    public function addRoute($route)
    {
        $this->_routes[] = $route;
        return $this;
    }

    public function getRoutes()
    {
        $routes = $this->_routes;
        $routes[] = ['path' => '*', 'require' => ['sv-page-not-found', 'text!sv-page-not-found-tpl']];
        return $routes;
    }

    public function addNav($nav)
    {
        $this->_navs[] = $nav;
        return $this;
    }

    public function getNavs()
    {
        return $this->_navs;
    }

    public function sortNavCallback($a, $b) {
        $p1 = !empty($a['pos']) ? $a['pos'] : 9999;
        $p2 = !empty($b['pos']) ? $b['pos'] : 9999;
        return $p1 < $p2 ? -1 : ($p1 > $p2 ? 1 : 0);
    }

    public function sortNavRecursively($tree)
    {
        usort($tree, [$this, 'sortNavCallback']);
        foreach ($tree as $i => $node) {
            if (!empty($node['children'])) {
                $tree[$i]['children'] = $this->sortNavRecursively($node['children']);
            }
        }
        return $tree;
    }

    public function normalizeSettingsNav()
    {
        $modRegHlp = $this->BModuleRegistry;
        foreach ($this->_navs as &$nav) {
            if (empty($nav['require']) && !empty($nav['module'])) {
                $mod = $modRegHlp->module($nav['module']);
                $relPath = '/AdminSPA/vue/page/settings' . $nav['path'];
                $fsPath = $mod->root_dir . $relPath;
                $webPath = /*$this->BRequest->scheme() . ':' . */$mod->baseSrc(false) . $relPath;
                $nav['require'] = [
                    file_exists($fsPath . '.js') ? $webPath . '.js' : '',
                    file_exists($fsPath . '.html') ? 'text!' . $webPath . '.html' : '',
                ];
            }
        }
        unset($nav);
        return $this;
    }

    public function getNavTree()
    {
        $tree = [];
        foreach ($this->_navs as $nav) {
            $a = explode('/', trim($nav['path'], '/'));
            switch (count($a)) {
                case 1: $tree[$a[0]] = $nav; break;
                case 2: $tree[$a[0]]['children'][$a[1]] = $nav; break;
                case 3: $tree[$a[0]]['children'][$a[1]]['children'][$a[2]] = $nav; break;
            }
        }
        $tree = $this->sortNavRecursively($tree);
        return $tree;
//        return [
//            ['label' => 'Dashboard', 'path' => '/dashboard', 'icon_class' => 'fa fa-tachometer', 'link' => '/'],
//            ['label' => 'Sales', 'path' => '/sales', 'icon_class' => 'fa fa-line-chart', 'children' => [
//                ['label' => 'Orders', 'path' => '/sales/orders', 'link' => '/sales/orders'],
//                ['label' => 'Payments', 'path' => '/sales/payments', 'link' => '/sales/payments'],
//                ['label' => 'Custom Order States', 'path' => '/sales/custom-order-states', 'link' => '/sales/custom-order-states'],
//                ['label' => 'Tax', 'path' => '/sales/tax', 'children' => [
//                    ['label' => 'Customer Classes', 'path' => '/sales/tax/customer-classes', 'link' => '/sales/tax/customer-classes'],
//                    ['label' => 'Product Classes', 'path' => '/sales/tax/product-classes', 'link' => '/sales/tax/product-classes'],
//                    ['label' => 'Zones', 'path' => '/sales/tax/zones', 'link' => '/sales/tax/zones'],
//                    ['label' => 'Rules', 'path' => '/sales/tax/rules', 'link' => '/sales/tax/rules'],
//                ]],
//            ]],
//            ['label' => 'Catalog', 'path' => '/catalog', 'icon_class' => 'fa fa-book', 'children' => [
//                ['label' => 'Navigation', 'path' => '/catalog/categories', 'link' => '/catalog/categories'],
//                ['label' => 'Products', 'path' => '/catalog/products', 'link' => '/catalog/products'],
//            ]],
//            ['label' => 'Customers', 'path' => '/customers', 'icon_class' => 'fa fa-user', 'children' => [
//                ['label' => 'Customers', 'path' => '/customers/customers', 'link' => '/customers/customers'],
//            ]],
//            ['label' => 'CMS', 'path' => '/cms', 'icon_class' => 'fa fa-folder-open', 'children' => [
//                ['label' => 'Blocks', 'path' => '/cms/blocks', 'link' => '/cms/blocks'],
//            ]],
//            ['label' => 'SEO', 'path' => '/seo', 'icon_class' => 'fa fa-window-maximize', 'children' => [
//                ['label' => 'URL Aliases', 'path' => '/seo/url-aliases', 'link' => '/seo/url-aliases'],
//            ]],
//            ['label' => 'Reports', 'path' => '/reports', 'icon_class' => 'fa fa-filter', 'children' => [
//                ['label' => 'Sales', 'path' => '/reports/sales', 'children' => [
//                    ['label' => 'Product Performance', 'path' => '/reports/sales/product-performance', 'link' => '/reports/sales/product-performance'],
//                ]],
//            ]],
//            ['label' => 'Modules', 'path' => '/modules', 'icon_class' => 'fa fa-puzzle-piece', 'children' => [
//                ['label' => 'Manage Modules', 'path' => '/modules/manage', 'link' => '/modules/manage'],
//            ]],
//            ['label' => 'System', 'path' => '/system', 'icon_class' => 'fa fa-cog', 'children' => [
//                ['label' => 'Users', 'path' => '/system/users', 'link' => '/system/users'],
//                ['label' => 'Settings', 'path' => '/system/settings', 'link' => '/system/settings'],
//            ]],
//        ];
    }

    public function addModule($modName)
    {
        $this->_modules[$modName] = $modName;
        return $this;
    }

    public function getModules()
    {
        $modules = [];
        $modRegHlp = $this->BModuleRegistry;
        foreach ($this->_modules as $modName => $mod) {
            $mod = $modRegHlp->module($modName);
            $modules[$modName] = ['src_root' => $mod->baseSrc(false)];
        }
        return $modules;
    }

    public function getEnv()
    {
        return [
            'root_href' => $this->BApp->href(),
        ];
    }
}