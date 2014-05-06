<?php

class FCom_Admin_View_Nav extends FCom_Core_View_Abstract
{
    protected static $_allPermissions;

    protected $_tree = [];
    protected $_curNav;

    public function addNav($path, $node)
    {
        $root =& $this->_tree;
        $pathArr = explode('/', $path);
        $l = sizeof($pathArr)-1;
        foreach ($pathArr as $i => $k) {
            $parent = $root;
            if ($i < $l && empty($root['/'][$k])) {
                $part = join('/', array_slice($pathArr, 0, $i + 1));
                BDebug::warning('addNav(' . $path . '): Invalid parent path: ' . $part);
            }
            $root =& $root['/'][$k];
        }
        if (empty($node['pos'])) {
            $pos = 0;
            if (!empty($parent['/'])) {
                foreach ($parent['/'] as $k => $n) {
                    if (!empty($n['pos'])) {
                        $pos = max($pos, $n['pos']);
                    }
                }
            }
            $node['pos'] = $pos + 10;
        }
        if (!empty($node['href']) && !preg_match('/^https?:/', $node['href'])) {
            $node['href'] = BApp::href($node['href']);
        }
        $root = $node;
        return $this;
    }

    public function setNav($path)
    {
        $this->set('current_nav', $path);
        return $this;
    }

    public function getNodes($root = null, $path = '')
    {

        if (is_null($root)) {
            $root = $this->_tree;
            $this->_curNav = $this->get('current_nav');
        }
        if (empty($root['/'])) {
            return [];
        }

        uasort($root['/'], function($a, $b) {
            $p1 = !empty($a['pos']) ? $a['pos'] : 9999;
            $p2 = !empty($b['pos']) ? $b['pos'] : 9999;
            return $p1 < $p2 ? -1 : ($p1 > $p2 ? 1 : 0);
        });

        if (!static::$_allPermissions) {
            static::$_allPermissions = FCom_Admin_Model_Role::i()->getAllPermissions();
        }
        $user = FCom_Admin_Model_User::i()->sessionUser();

        $result = [];
        foreach ($root['/'] as $k => $node) {
            $key = !empty($node['key']) ? $node['key'] : $k;
            $nextPath = $path . ($path ? '/' : '') . $key;

            $node['active'] = $this->_curNav === $nextPath || strpos($this->_curNav, $nextPath . '/') === 0;

            $node['children'] = $this->getNodes($node, $nextPath);

            if (empty($node['permission']) && !empty(static::$_allPermissions[$nextPath])) {
                $node['permission'] = $nextPath;
            }
            if (!empty($node['permission']) && !$node['children'] && !$user->getPermission($node['permission'])) {
                continue;
            }
            if (!isset($node['class'])) {
                $node['class'] = '';
            }
            if (empty($node['label'])) {
                $node['label'] = $k;
            }
            if (empty($node['href'])) {
                $node['href'] = '#';
            }

            $result[$k] = $node;
        }

        return $result;
    }
}
