<?php

class FCom_Admin_View_Root extends BView
{
    protected $_tree = array('ul' => array('id'=>'nav'));
    protected $_current;

    public function navAdd($path, $node)
    {
        $root =& $this->_tree;
        $pathArr = explode('/', $path);
        foreach ($pathArr as $k) {
            $root =& $root['/'][$k];
        }
        $root = $node;
        return $this;
    }

    public function navCur($path)
    {
        $this->set('current_nav', $path);
        return $this;
    }

    public function tag($tag, $params=array())
    {
        $hmtl = '';
        foreach ($params as $k=>$v) {
            if (''!==$v && !is_null($v) && false!==$v) {
                $hmtl .= ' '.$k.'="'.htmlspecialchars($v).'"';
            }
        }
        return "<{$tag}{$hmtl}>";
    }

    public function renderNodes($root=null, $level=0)
    {
        if (is_null($root)) {
            $root = $this->_tree;
            $this->_curArr = explode('/', $this->get('current_nav'));
        }
        if (empty($root['/'])) {
            return '';
        }
        $html = $this->tag('ul', !empty($root['ul']) ? $root['ul'] : array());
        foreach ($root['/'] as $k=>$node) {
            $label = !empty($node['label']) ? $node['label'] : $k;
            if (!empty($node['href'])) {
                $label = $this->tag('a', array('href'=>$node['href'])).$label.'</a>';
            }
            if (!isset($node['li']['class'])) {
                $node['li']['class'] = '';
            }
            if (!empty($node['/'])) {
                $node['li']['class'] .= ' nav-group';
                $hdrParams = array('class'=>'nav-group-'.$k);
                $label = $this->tag('header', $hdrParams).'<span>'.$label.'</span></header>';
            }
            $key = !empty($node['key']) ? $node['key'] : $k;
            if (isset($this->_curArr[$level]) && $this->_curArr[$level]===$key) {
                $node['li']['class'] .= ' active';
            }
            $children = $this->renderNodes($node, $level+1);
            $html .= $this->tag('li', !empty($node['li']) ? $node['li'] : array())
                . $label . $children . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
}