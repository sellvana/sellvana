<?php

class FCom_AdminSPA_AdminSPA_View_App extends FCom_Core_View_Abstract
{
    protected $_routes = [];

    protected $_navs = [];

    protected $_modules = [];

    protected $_formTabs = [];

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

    public function removeNav($path)
    {
        foreach ($this->_navs as $i => $nav) {
            if ($nav['path'] === $path) {
                unset($this->_navs[$i]);
                break;
            }
        }
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
            if (empty($nav['require'])) {
                if (!empty($nav['module'])) {
                    $mod     = $modRegHlp->module($nav['module']);
                    $relPath = '/AdminSPA/vue/page/settings' . $nav['path'];
                    $fsPath  = $mod->root_dir . $relPath;
                    $webPath = /*$this->BRequest->scheme() . ':' . */$mod->baseSrc(false) . $relPath;
                    $nav['require'] = [
                        file_exists($fsPath . '.js') ? $webPath . '.js' : 'sv-page-settings-default-section',
                        file_exists($fsPath . '.html') ? 'text!' . $webPath . '.html' : 'text!sv-page-settings-default-section-tpl',
                    ];
                } else {
                    $nav['require'] = [
                        'sv-page-settings-default-section',
                        'text!sv-page-settings-default-section-tpl',
                    ];
                }
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

    public function addFormTab($tab)
    {
        $this->_formTabs[] = $tab;
        return $this;
    }

    public function getFormTabs($path)
    {
        $tabs = [];
        foreach ($this->_formTabs as $t) {
            if (!$t['path'] === $path) {
                continue;
            }
            unset($t['path']);
            if (empty($t['component'])) {
                $t['component'] = 'sv-page' . str_replace('/', '-', $path) . '-' . $t['name'];
            } elseif ($t['component'] === 'default') {
                $t['component'] = 'sv-page-default-form-tab';
            }
            $tabs[] = $t;
        }
        usort($tabs, function($a, $b) {
            $p1 = !empty($a['pos']) ? $a['pos'] : 9999;
            $p2 = !empty($b['pos']) ? $b['pos'] : 9999;
            return $p1 < $p2 ? -1 : ($p1 > $p2 ? 1 : 0);
        });
        return $tabs;
    }
}