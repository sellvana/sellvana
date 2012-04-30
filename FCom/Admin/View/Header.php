<?php

class FCom_Admin_View_Header extends FCom_Core_View_Abstract
{
    protected static $_allPermissions;

    protected $_tree = array();
    protected $_curNav;
    protected $_quickSearches = array();
    protected $_shortcuts = array();

    public function addNav($path, $node)
    {
        $root =& $this->_tree;
        $pathArr = explode('/', $path);
        $l = sizeof($pathArr)-1;
        foreach ($pathArr as $i=>$k) {
            $parent = $root;
            if ($i<$l && empty($root['/'][$k])) {
                $part = join('/', array_slice($pathArr, 0, $i+1));
                BDebug::warning('addNav('.$path.'): Invalid parent path: '.$part);
            }
            $root =& $root['/'][$k];
        }
        if (empty($node['pos'])) {
            $pos = 0;
            if (!empty($parent['/'])) {
                foreach ($parent['/'] as $k=>$n) {
                    $pos = max($pos, $n['pos']);
                }
            }
            $node['pos'] = $pos+10;
        }
        $root = $node;
        return $this;
    }

    public function setNav($path)
    {
        $this->set('current_nav', $path);
        return $this;
    }

    public function addQuickSearch($name, $config)
    {
        $this->_quickSearches[$name] = $config;
        return $this;
    }

    public function addShortcut($name, $config)
    {
        $this->_shortcuts[$name] = $config;
        return $this;
    }

    public function tag($tag, $params=array())
    {
        $html = '';
        foreach ($params as $k=>$v) {
            if (''!==$v && !is_null($v) && false!==$v) {
                $html .= ' '.$k.'="'.htmlspecialchars($v).'"';
            }
        }
        return "<{$tag}{$html}>";
    }

    public function renderNodes($root=null, $path='')
    {
        if (is_null($root)) {
            $root = $this->_tree;
            $this->_curNav = $this->get('current_nav');
        }
        if (empty($root['/'])) {
            return '';
        }

        uasort($root['/'], function($a, $b) {
            return $a['pos']<$b['pos'] ? -1 : ($a['pos']>$b['pos'] ? 1 : 0);
        });

        if (!static::$_allPermissions) {
            static::$_allPermissions = FCom_Admin_Model_Role::i()->getAllPermissions();
        }
        $user = FCom_Admin_Model_User::i()->sessionUser();

        $html = '';
        foreach ($root['/'] as $k=>$node) {
            if (!isset($node['li']['class'])) {
                $node['li']['class'] = '';
            }

            $key = !empty($node['key']) ? $node['key'] : $k;
            $nextPath = $path.($path?'/':'').$key;
            if ($this->_curNav===$nextPath || strpos($this->_curNav, $nextPath.'/')===0) {
                $node['li']['class'] .= ' active';
            }
            $children = $this->renderNodes($node, $nextPath);

            if (empty($node['permission']) && !empty(static::$_allPermissions[$nextPath])) {
                $node['permission'] = $nextPath;
            }
            if (!empty($node['permission']) && !$children && !$user->getPermission($node['permission'])) {
                continue;
            }

            $label = !empty($node['label']) ? $node['label'] : $k;
            if (!empty($node['href'])) {
                $label = $this->tag('a', array('href'=>$node['href'])).$label.'</a>';
            }
            if (!empty($node['/'])) {
                $node['li']['class'] .= ' nav-group';
                $hdrParams = array('class'=>'nav-group-'.$k);
                $label = $this->tag('a', $hdrParams).'<span class="icon"></span><span class="title">'.$label.'</span></a>';
            }
            $html .= $this->tag('li', !empty($node['li']) ? $node['li'] : array())
                . $label . $children . '</li>';
        }

        if ($html) {
            return $this->tag('ul', !empty($root['ul']) ? $root['ul'] : array()).$html.'</ul>';
        } else {
            return '';
        }
    }
}