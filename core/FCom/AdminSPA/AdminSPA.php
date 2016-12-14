<?php

class FCom_AdminSPA_AdminSPA extends BClass
{
    protected $_routes = [];

    protected $_navs = [];

    protected $_modules = [];

    public function bootstrap()
    {
        $this->addModule('FCom_AdminSPA')
            ->addRoute(['path' => '/login', 'require' => [
            $this->BApp->src('@FCom_AdminSPA/AdminSPA/vue/page/login.js'),
            'text!' . $this->BApp->src('@FCom_AdminSPA/AdminSPA/vue/page/login.html'),
        ]]);
    }

    public function addRoute($route)
    {
        $this->_routes[] = $route;
        return $this;
    }

    public function getRoutes()
    {
        $routes = $this->_routes;
        $routes[] = ['path' => '*', 'require' => [
            '',
            'text!' . $this->BApp->src('@FCom_AdminSPA/AdminSPA/vue/page/not-found.html'),
        ]];
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
}