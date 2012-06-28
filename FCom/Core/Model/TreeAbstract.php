<?php

class FCom_Core_Model_TreeAbstract extends FCom_Core_Model_Abstract
{
    protected static $_separator = '|';

    protected static $_cacheAuto = array('id', 'full_name', 'url_path');
    protected static $_cacheFlags = array('full_name'=>array('key_lower'));

    public static function load($id, $field=null, $cache=false)
    {
        $cat = parent::load($id, $field, $cache);
        if ($cat) return $cat;
        if ($id===1) {
            return static::i()->create(array(
                'id' => 1,
                'id_path' => 1,
                'sort_order' => 1,
                'url_key' => '',
                'url_path' => '',
            ))->save();
        }
        return false;
    }

    public function createChild($name, $params=array())
    {
        $sep = static::$_separator;

        if (is_string($name)) {
            $name = preg_split('#\s*'.preg_quote($sep).'\s*#', $name, 0, PREG_SPLIT_NO_EMPTY);
        }

        $childName = ($this->full_name ? $this->full_name.$sep : '').$name[0];
        $child = $this->load($childName, 'full_name');
        if (!$child) {
            //$class = get_class($this);
            $child = static::i()->create(array(
                'parent_id' => $this->id,
                'node_name' => $name[0],
                'full_name' => $childName,
                'num_children' => 0,
                'num_descendants' => 0,
            ))->set($params)->save();
            $child->set('id_path', $this->id_path.'/'.$child->id)->save();

            $this->num_children++;
            $this->num_descendants++;
            $saveObjects[$this->id] = $this;
            foreach ($this->ascendants() as $c) {
                $c->num_descendants++;
                $saveObjects[$c->id] = $c;
            }
        }

        if (!empty($name[1])) {
            return $child->createChild(array_slice($name, 1), $params, $saveObjects);
        }
        return $child;
    }

    public function rename($newName, $resetUrl=false)
    {
        $pName = $this->parent()->full_name;
        $this->set(array(
            'node_name' => $newName,
            'full_name' => ($pName?$pName.'|':'').$newName,
        ));
        if ($resetUrl) {
            $this->set(array('url_key'=>null, 'url_path'=>null));
        }
        $this->refreshDescendants(false, $resetUrl);
        return $this;
    }

    public function move($parentId)
    {
        if ($parentId!=$this->parent_id) {
            $p = $this->load($parentId);
            $this->unregister();
            $this->set(array(
                'parent_id' => $p->id,
                'id_path' => $p->id_path.'/'.$this->id,
                'full_name' => trim($p->full_name.static::$_separator.$this->node_name, static::$_separator),
                'url_path' => null,
            ));
            $this->register();

            $this->refreshDescendants(false, true);
            $this->cacheSaveDirty();
            //TODO: improve performance, figure out why can't calculate correct nums
            $this->cacheClear();
            $root = $this->load(1);
            $root->descendants();
            $root->recalculateNumDescendants();
            $this->cacheSaveDirty();
        }
        return $this;
    }

    public function reorder($sortOrder)
    {
        $conflict = false;
        foreach ($this->siblings() as $c) {
            if ($c->sort_order==$sortOrder) {
                $conflict = true;
                break;
            }
        }
        if ($conflict) {
            foreach ($this->siblings() as $c) {
                if ($c->sort_order>=$sortOrder) {
                    $c->sort_order++;
                }
            }
        }
        $this->sort_order = $sortOrder;
        return $this;
    }

    public function reorderChildrenAZ($recursive=false)
    {
        $children = $this->children();
        uasort($children, function($a, $b) { return strcmp($a->node_name, $b->node_name); });
        $i = 0;
        foreach ($children as $c) {
            $c->set('sort_order', ++$i);
            if ($recursive) $c->reorderChildrenAZ(true);
        }
        return $this;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;

        if (!$this->id) $this->_new = true;
        if (!$this->sort_order) $this->generateSortOrder();
        if (!$this->url_key) $this->generateUrlKey();
        if (!$this->url_path || $this->is_dirty('url_key')) $this->generateUrlPath();
        if (!$this->full_name || $this->is_dirty('node_name')) $this->generateFullName();

        return true;
    }

    public function refreshDescendants($save=false, $resetUrl=false)
    {
        foreach ($this->descendants() as $c) {
            $c->set(array(
                'id_path' => $this->id_path.'/'.$c->id,
                'full_name' => $this->full_name.static::$_separator.$c->node_name,
            ));
            if ($resetUrl) $c->set('url_path', null);
            $c->refreshDescendants($save);
            if ($save) $c->save();
        }
        return $this;
    }

    public function recalculateNumDescendants($save=false)
    {
        $children = $this->children();
        $this->num_children = 0;
        $this->num_descendants = 0;
        if ($children) {
            foreach ($children as $c) {
                $c->recalculateNumDescendants($save);
                $this->add('num_children', 1);
                $this->add('num_descendants', 1+$c->get('num_descendants'));
            }
        }
        if ($save) $this->save();
        return $this;
    }

    public function unregister($save=false)
    {
        $this->parent()->add('num_children', -1);
        $numDesc = 1+$this->num_descendants;
        foreach ($this->ascendants() as $c) {
            $c->add('num_descendants', -$numDesc);
            if ($save) $c->save();
        }
        $this->saveInstanceCache('parent', null);
        if ($save) $this->save();
        return $this;
    }

    public function register($save=false)
    {
        $this->parent()->add('num_children');
        $numDesc = 1+$this->num_descendants;
        foreach ($this->ascendants() as $c) {
            $c->add('num_descendants', $numDesc);
            if ($save) $c->save();
        }
        return $this;
    }

    public function afterSave()
    {
        if ($this->_new) {
            $this->register(true);
            $this->_new = null;
        }
        $this->cacheStore();

        parent::afterSave();
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) return false;
        if (($d = $this->descendants())) {
            $this->delete_many(array('id'=>array_keys($d)));
        }
        $this->unregister(true);
        return true;
    }

    public function parent()
    {
        return $this->relatedModel(get_class($this), $this->parent_id, false, 'parent');
    }

    public function children($sort='sort_order')
    {
        $children = array();
        $id = $this->id;
        /*
        if (($cache = $this->cacheFetch())) {
            foreach ($cache as $c) {
                if ($c->parent_id==$id) $children[$c->id] = $c;
            }
        }
        */
        if (is_null($this->num_children) || sizeof($children)!=$this->num_children) {
            $class = get_class($this);
            $orm = $this->orm('t')->where('t.parent_id', $id);
            if ($children) $orm->where_not_in('t.id', array_keys($children));
            if ($sort) $orm->order_by_asc($sort);
            $rows = $orm->find_many();
            foreach ($rows as $c) {
                $c->cacheStore();
                $children[$c->id] = $c;
            }
        }
        return $children;
    }

    public function descendants($sort='sort_order')
    {
        $desc = array();
        $path = $this->id_path.'/';
        /*
        if (($cache = $this->cacheFetch())) {
            foreach ($cache as $c) {
                if (strpos($c->id_path, $path)===0) $desc[$c->id] = $c;
            }
        }
        */
#echo "<pre>"; print_r(BDb::many_as_array($desc)); exit;
        if (is_null($this->num_descendants) || sizeof($desc)!=$this->num_descendants) {
            $orm = $this->orm('t')->where_like('t.id_path', $path.'%');
            if ($desc) $orm->where_not_in('t.id', array_keys($desc));
            if ($sort) $orm->order_by_asc($sort);
            $rows = $orm->find_many();
            foreach ($rows as $c) {
                $c->cacheStore();
                $desc[$c->id] = $c;
            }
        }
        return $desc;
    }

    public function ascendants()
    {
        $asc = array();
        foreach (explode('/', $this->id_path) as $id) {
            if ($id && $this->id!=$id) $asc[$id] = $this->load($id);
        }
        return $asc;
    }

    public function siblings()
    {
        $siblings = array();
        foreach ($this->parent()->children() as $c) {
            if ($c->id!=$this->id) $siblings[$c->id] = $c;
        }
        return $siblings;
    }

    public function generateSortOrder()
    {
        $sortOrder = 0;
        $parent = $this->parent();
        $siblings = $parent->children();
        foreach (static::$_cache[$this->_origClass()]['id'] as $c) {
            if ($c->sort_order && $c->parent_id==$this->parent_id) {
                $sortOrder = max($sortOrder, $c->sort_order);
            }
        }
        $this->set('sort_order', $sortOrder+1);
        return $this;
    }

    public function generateUrlKey()
    {
        $this->set('url_key', BLocale::transliterate($this->node_name));
        return $this;
    }

    public function generateUrlPath()
    {
        $urlKey = $this->url_key;
        if ($this->parent() && $this->parent()->url_path) {
            $urlKey = trim($this->parent()->url_path.'/'.$this->url_key, '/');
        }
        $this->set('url_path', $urlKey);
        return $this;
    }

    public function generateFullName()
    {
        $parent = $this->parent();
        $fullName = ($parent ? $parent->full_name : '').static::$_separator.$this->node_name;
        $this->set('full_name', trim($fullName, '|'));
        return $this;
    }
}