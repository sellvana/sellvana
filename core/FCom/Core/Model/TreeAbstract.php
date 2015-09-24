<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Core_Model_TreeAbstract
 *
 * @property int $id
 * @property int $parent_id
 * @property string $id_path
 * @property int $level
 * @property int $sort_order
 * @property string $node_name
 * @property string $full_name
 * @property string $url_key
 * @property string $url_path
 * @property string $url_href //todo: check this property
 * @property int $num_children
 * @property int $num_descendants
 * @property int $num_products
 * @property int $is_enabled
 * @property int $is_virtual
 * @property mixed $data_serialized
 */
class FCom_Core_Model_TreeAbstract extends FCom_Core_Model_Abstract
{
    /**
     * @var string
     */
    protected static $_separator = '|';

    /**
     * @var array
     */
    protected static $_cacheAuto = ['id', 'full_name', 'url_path'];
    /**
     * @var array
     */
    protected static $_cacheFlags = ['full_name' => ['key_lower']];

    /**
     * @var array
     */
    protected static $_validationRules = [
        /*array('parent_id', '@required'),
        array('id_path', '@required'),
        array('sort_order', '@required'),*/
        ['node_name', '@required'],
        ['url_key', '@required'],
        /*array('full_name', '@required'),
        array('url_path', '@required'),*/
    ];

    /**
     * @param array|int|string $id
     * @param null $field
     * @param bool $cache
     * @return static|bool
     * @throws BException
     */
    public function load($id, $field = null, $cache = false)
    {
        $cat = parent::load($id, $field, $cache);
        if ($cat) {
            return $cat;
        }
        if ($id === 1) {
            return $this->create([
                    'id' => 1,
                    'id_path' => 1,
                    'sort_order' => 1,
                    'url_key' => '',
                    'url_path' => '',
                ])->save();
        }
        return false;
    }

    /**
     * @param $name
     * @param array $params
     * @param array $saveObjects
     * @return static
     * @throws BException
     */
    public function createChild($name, $params = [], $saveObjects = [])
    {
        $sep = static::$_separator;

        if (is_string($name)) {
            $name = preg_split('#\s*' . preg_quote($sep, '#') . '\s*#', $name, 0, PREG_SPLIT_NO_EMPTY);
        }

        $childName = ($this->get('full_name') ? $this->get('full_name') . $sep : '') . $name[0];
//        $child = $this->load($childName, 'full_name');
//        if (!$child) {
        //$class = get_class($this);
        $child = $this->create([
                'parent_id' => $this->id(),
                'node_name' => $name[0],
                'full_name' => $childName,
                'num_children' => 0,
                'num_descendants' => 0,
            ])->set($params)->save();
        $child->set('id_path', $this->get('id_path') . '/' . $child->id())->save();

        $this->add('num_children');
        $this->add('num_descendants');
        $saveObjects[$this->id()] = $this;
        foreach ($this->ascendants() as $c) {
            $c->add('num_descendants');
            $saveObjects[$c->id()] = $c;
        }
//        }
        if ($saveObjects) {
            foreach ($saveObjects as $saveObj) {
                $saveObj->save();
            }
        }

        if (!empty($name[1])) {
            return $child->createChild(array_slice($name, 1), $params, $saveObjects);
        }
        return $child;
    }

    /**
     * @param string $newName
     * @param bool $resetUrl
     * @return static
     * @throws BException
     */
    public function rename($newName, $resetUrl = false)
    {
        $pName = $this->parent()->get('full_name');
        $this->set([
                'node_name' => $newName,
                'full_name' => ($pName ? $pName . '|' : '') . $newName,
            ]);
        if ($resetUrl) {
            $this->set(['url_key' => null, 'url_path' => null]);
        }
        $this->refreshDescendants(false, $resetUrl);
        return $this;
    }

    /**
     * move node
     * @param int $parentId
     * @return static
     * @throws BException
     * @throws Exception
     */
    public function move($parentId)
    {
        if ($parentId != $this->get('parent_id')) {
            $p = $this->load($parentId);
            $this->unregister();
            $this->set([
                    'parent_id' => $p->id(),
                    'id_path' => $p->get('id_path') . '/' . $this->id(),
                    'full_name' => trim($p->get('full_name') . static::$_separator . $this->get('node_name'), static::$_separator),
                    'url_path' => null,
                ]);

            $this->register();

            $this->refreshDescendants(false, true);
            try {
                $this->cacheSaveDirty();
            } catch (Exception $e) {
                throw new Exception("Duplicate category name");
            }
            //TODO: improve performance, figure out why can't calculate correct nums
            $this->cacheClear();
            $root = $this->load(1);
            $root->descendants();
            $root->recalculateNumDescendants();
            $this->cacheSaveDirty();
        }
        return $this;
    }

    /**
     * @param $sortOrder
     * @return static
     * @throws BException
     */
    public function reorder($sortOrder)
    {
        $conflict = false;
        foreach ($this->siblings() as $c) {
            if ($c->get('sort_order') == $sortOrder) {
                $conflict = true;
                break;
            }
        }
        if ($conflict) {
            foreach ($this->siblings() as $c) {
                if ($c->get('sort_order') >= $sortOrder) {
                    $c->add('sort_order');
                }
            }
        }
        $this->set('sort_order', $sortOrder);
        return $this;
    }

    /**
     * @param bool $recursive
     * @return static
     * @throws BException
     */
    public function reorderChildrenAZ($recursive = false)
    {
        $children = $this->children();
        uasort(
            $children,
            function ($a, $b) {
                return strcmp($a->get('node_name'), $b->get('node_name'));
            }
        );
        $i = 0;
        foreach ($children as $c) {
            $c->set('sort_order', ++$i);
            $c->cacheSaveDirty();
            if ($recursive) {
                $c->reorderChildrenAZ(true);
            }
        }
        return $this;
    }

    /**
     * @return bool
     * @throws BException
     */
    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) {
            return false;
        }

        if (!$this->id()) {
            $this->_new = true;
        }
        if (!$this->get('sort_order')) {
            $this->generateSortOrder();
        }
        if (!$this->get('url_key')) {
            $this->generateUrlKey();
        }
        if (!$this->get('url_path') || ($this->is_dirty('url_key') && !$this->_new)) {
            $this->generateUrlPath();
        }
        if (!$this->get('full_name') || ($this->is_dirty('node_name') && !$this->_new)) {
            $this->generateFullName();
        }
        if ($this->is_dirty('id_path')) {
            $this->set('level', sizeof(explode('/', $this->get('id_path'))) - 1);
        }

        return true;
    }

    /**
     * @param bool $save
     * @param bool $resetUrl
     * @return static
     * @throws BException
     */
    public function refreshDescendants($save = false, $resetUrl = false)
    {
        $children = $this->children();
        foreach ($children as $c) {
            $c->set([
                    'id_path' => $this->get('id_path') . '/' . $c->id(),
                    'full_name' => $this->get('full_name') . static::$_separator . $c->get('node_name'),
                ]);
            if ($resetUrl) {
                $c->set('url_path', null);
            }
            $c->refreshDescendants($save);
            if ($save) {
                $c->save();
            }
        }
        return $this;
    }

    /**
     * @param bool $save
     * @return static
     * @throws BException
     */
    public function recalculateNumDescendants($save = false)
    {
        $children = $this->children();
        $this->set('num_children', 0);
        $this->set('num_descendants', 0);
        if ($children) {
            foreach ($children as $c) {
                $c->recalculateNumDescendants($save);
                $this->add('num_children', 1);
                $this->add('num_descendants', 1 + $c->get('num_descendants'));
            }
        }
        if ($save) $this->save();
        return $this;
    }

    /**
     * @param bool $save
     * @return static
     */
    public function unregister($save = false)
    {
        if ($this->parent()) {
            $this->parent()->add('num_children', -1);
        }
        $numDesc = 1 + $this->get('num_descendants');
        foreach ($this->ascendants() as $c) {
            $c->add('num_descendants', - $numDesc);
            if ($save) {
                $c->save();
            }
        }
        $this->saveInstanceCache('parent', null);
        if ($save) {
            $this->save();
        }
        return $this;
    }

    /**
     * @param bool $save
     * @return static
     */
    public function register($save = false)
    {
        $this->parent()->add('num_children');
        $numDesc = 1 + $this->get('num_descendants');
        foreach ($this->ascendants() as $c) {
            // TODO: fix updating when re-registering existing node
            $c->add('num_descendants', $numDesc);
            if ($save) {
                $c->save();
            }
        }
        return $this;
    }

    public function onAfterSave()
    {
        if ($this->_new) {
            $this->register(true);
            $this->_new = null;
        }
        $this->cacheStore();

        parent::onAfterSave();
    }

    public function onBeforeDelete()
    {
        if (!parent::onBeforeDelete()) return false;
        if (($d = $this->descendants())) {
            $this->delete_many(['id' => array_keys($d)]);
        }
        $this->unregister(true);
        return true;
    }

    /**
     * @return FCom_Core_Model_TreeAbstract
     */
    public function parent()
    {
        return $this->relatedModel(get_class($this), $this->get('parent_id'), false, 'parent');
    }

    /**
     * @param string $sort
     * @return FCom_Core_Model_TreeAbstract[]
     */
    public function children($sort = 'sort_order')
    {
        $children = [];
        $id = $this->id();

        if (($cache = $this->cacheFetch())) {
            foreach ($cache as $c) {
                if ($c->get('parent_id') == $id) {
                    $children[$c->id()] = $c;
                }
            }
        }
        $numChildren = $this->get('num_children');
        if (null === $numChildren || sizeof($children) != $numChildren) {
            $class = get_class($this);
            $orm = $this->orm('t')->where('t.parent_id', $id);
            if ($children) {
                $orm->where_not_in('t.id', array_keys($children));
            }
            if ($sort) {
                $orm->order_by_asc($sort);
            }
            $rows = $orm->find_many();
            foreach ($rows as $c) {
                $c->cacheStore();
                $children[$c->id()] = $c;
            }
        }
        return $children;
    }

    /**
     * @param string $sort
     * @return FCom_Core_Model_TreeAbstract[]
     */
    public function descendants($sort = 'sort_order')
    {
        $desc = [];

        if ($this->is_dirty('id_path')) {
            $path = $this->old_values('id_path') . '/';
        } else {
            $path = $this->get('id_path') . '/';
        }
        if (($cache = $this->cacheFetch())) {
            foreach ($cache as $c) {
                if (strpos($c->get('id_path'), $path) === 0) $desc[$c->id()] = $c;
            }
        }
#echo "<pre>"; print_r($this->BDb->many_as_array($desc)); exit;
        $numDescendants = $this->get('num_descendants');
        if (null === $numDescendants || sizeof($desc) != $numDescendants) {
            $orm = $this->orm('t')->where_like('t.id_path', $path . '%');
            if ($desc) {
                $orm->where_not_in('t.id', array_keys($desc));
            }
            if ($sort) {
                $orm->order_by_asc($sort);
            }
            $rows = $orm->find_many();
            foreach ($rows as $c) {
                $c->cacheStore();
                $desc[$c->id()] = $c;
            }
        }
        return $desc;
    }

    /**
     * get all descendants nodes
     * @return FCom_Core_Model_TreeAbstract[]
     */
    public function ascendants()
    {
        $asc = [];
        foreach (explode('/', $this->get('id_path')) as $id) {
            if ($id && $this->id() != $id) {
                $asc[$id] = $this->load($id);
            }
        }
        return $asc;
    }

    /**
     * get all siblings nodes
     * @return FCom_Core_Model_TreeAbstract[]
     */
    public function siblings()
    {
        $siblings = [];
        foreach ($this->parent()->children() as $c) {
            if ($c->id() != $this->id()) {
                $siblings[$c->id()] = $c;
            }
        }
        return $siblings;
    }

    /**
     * @return static
     * @throws BException
     */
    public function generateSortOrder()
    {
        $sortOrder = 0;
        $parent = $this->parent();
        if ($parent) {
            $siblings = $parent->children();
        }
        foreach (static::$_cache[$this->_origClass()]['id'] as $c) {
            if ($c->get('sort_order') && $c->get('parent_id') == $this->get('parent_id')) {
                $sortOrder = max($sortOrder, $c->get('sort_order'));
            }
        }
        $this->set('sort_order', $sortOrder + 1);
        return $this;
    }

    /**
     * generate url node url_key base on node_name value
     * @return static
     * @throws BException
     */
    public function generateUrlKey()
    {
        $this->set('url_key', $this->BLocale->transliterate($this->get('node_name')));
        return $this;
    }

    /**
     * @return static
     * @throws BException
     */
    public function generateUrlPath()
    {
        $urlKey = $this->get('url_key');
        $parent = $this->parent();
        //todo: confirm with Boris system don't save url_path of root node
        $urlPath = ($parent && $parent->get('parent_id')) ? $parent->get('url_path') : null;
        if ($urlPath) {
            $urlKey = trim($urlPath . '/' . $urlKey, '/');
        }
        $this->set('url_path', $urlKey);
        return $this;
    }

    /**
     * @return static
     * @throws BException
     */
    public function generateIdPath()
    {
        $idPath = $this->id();
        $parentIdPath = '';
        /** @var FCom_Core_Model_TreeAbstract $parent */
        $parent = $this->parent();
        while ($parent) {
            $parentIdPath = $parent->id() . '/' . $parentIdPath;
            $parent = $parent->parent();
        }
        if ($parentIdPath) {
            $idPath = trim($parentIdPath, '/') . '/' . $idPath;
        }
        $this->set('id_path', $idPath);
        return $this;
    }

    /**
     * @return static
     * @throws BException
     */
    public function generateFullName()
    {
        $parent = $this->parent();
        $fullName = ($parent ? $parent->get('full_name') : '') . static::$_separator . $this->get('node_name');
        $this->set('full_name', trim($fullName, '|'));
        return $this;
    }

    /**
     * validate node name before create/rename/move, make sure that name is unique in same level tree
     * @param string $newName
     * @param bool   $create
     * @return bool
     */
    public function validateNodeName($newName, $create = false)
    {
        $fullName = ($this->get('full_name') ? $this->get('full_name') : '');
        $childName = rtrim($fullName, $this->get('node_name')) . $newName;
        if ($create) {
            $sep = static::$_separator;
            if (is_string($newName)) {
                $newName = preg_split('#\s*' . preg_quote($sep, '#') . '\s*#', $newName, 0, PREG_SPLIT_NO_EMPTY);
            }
            $childName = ($this->get('full_name') ? $this->get('full_name') . $sep : '') . $newName[0];
        }
        if ($newName == $this->get('node_name')) {
            return true;
        }
        $child = $this->load($childName, 'full_name');
        if (!$child) {
            return true;
        }
        return false;
    }

    /**
     * clone current node, also move cloned-node to another node
     * @param bool $move2node
     * @param string $newParentNodeId
     * @return bool|FCom_Core_Model_TreeAbstract
     * @throws BException
     */
    public function cloneMe($move2node = false, $newParentNodeId = '')
    {
        if (!$this->id()) {
            return false;
        }
        $data = $this->as_array();
        unset($data['id']);
        //unset data and generate this data in function onBeforeSave
        unset($data['full_name']);
        unset($data['sort_order']);
        unset($data['url_key']);
        unset($data['url_path']);
        unset($data['full_name']);
        if ($move2node) {
            $newParent = $this->load($newParentNodeId);
            if ($newParent) {
                $data['parent_id'] = (int)$newParentNodeId;
            } else {
                throw new BException('Cannot load target node');
            }
        }
        //get number suffix
//        $numberSuffix = 1;
//        while($numberSuffix <= 20) {
//            $result = $this->orm()->where(array('node_name' => $this->get('node_name').'-'.$numberSuffix, 'parent_id' => $data['parent_id']))->find_one();
//            if (!$result) {
//                break;
//            }
//            $numberSuffix++;
//        }
        $tmpName = $this->get('node_name') . '-';
        $result = $this->orm()->select('node_name')->where([
            ['node_name REGEXP ?', preg_quote((string)$tmpName) . '[0-9]$'],
            ['parent_id=?', (int)$data['parent_id']]
        ])->order_by_desc('id')->find_many();
        $numberSuffix = 1;
        if ($result) {
            $max = 0;
            foreach ($result as $arr) {
                $tmp = explode($tmpName, $arr->get('node_name'));
                if ($max < $tmp[1]) {
                    $max = $tmp[1];
                }
            }
            $numberSuffix = $max + 1;
        }
        $data['id_path'] = '';
        $data['node_name'] = $tmpName . $numberSuffix;
        $cloneNode = $this->create($data)->save(true); /** @var FCom_Core_Model_TreeAbstract $cloneNode */
        $cloneNode->set(
            [
                'id_path' => $cloneNode->parent()->get('id_path') . '/' . $cloneNode->id(),
                'num_children' => 0,
                'num_descendants' => 0,
                'is_enabled' => 0
            ])->save();

        //update num_children and num_descendant of ascendant node
        $saveObjects = [];
        $saveObjects[$cloneNode->id()] = $cloneNode;
        foreach ($cloneNode->ascendants() as $c) {
            $c->add('num_descendants');
            $saveObjects[$c->id()] = $c;
        }
        if ($saveObjects) {
            foreach ($saveObjects as $saveObj) {
                $saveObj->save();
            }
        }

        $this->onAfterClone($cloneNode);
        return $cloneNode;
    }

    /**
     * @param $cloneNode
     * @return static
     */
    public function onAfterClone(&$cloneNode)
    {
        $this->BEvents->fire($this->_origClass() . '::onAfterClone', ['node' => $this, 'cloneNode' => $cloneNode]);
        return $this;
    }

    /**
     * clone children of current node to cloned node
     * @param $cloneNode FCom_Core_Model_TreeAbstract
     * @param $recursive bool
     * @return bool
     */
    public function cloneChildren($cloneNode, $recursive = false)
    {
        $children = $this->children();
        if ($children) {
            foreach ($children as $child) {
                $node = $child->cloneMe(true, $cloneNode->id);
                if ($recursive && $child->get('num_children') > 0) {
                    $child->cloneChildren($node);
                }
            }
        }
        return true;
    }
}
