<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Admin_Model_Role
 *
 * @property int $id
 * @property string $role_name
 * @property string $permissions_data
 */
class FCom_Admin_Model_Role extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_admin_role';

    protected static $_validationRules = [
        ['role_name', '@required'],
        //array('permissions_data', '@required'),
    ];

    protected static $_allPermissions = [
        '/' => ['title' => 'All Permissions', 'level' => 0],
        'admin' => ['title' => 'Admin Tasks', 'level' => 1],
    ];

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['role_name'],
    ];

    protected static $_fieldOptions = [
        'status' => ['all' => 'All', 'none' => "None"],
    ];

    /**
     * @return array
     */
    public function options()
    {
        /** @var FCom_Admin_Model_Role[] $roles */
        $roles = $this->orm()
            ->select('id')->select('role_name')
            ->find_many();
        $options = [];
        foreach ($roles as $r) {
            $options[$r->id] = $r->role_name;
        }
        return $options;
    }

    /**
     * @param $path
     * @param null $params
     * @return FCom_Admin_Model_Role
     */
    public function createPermission($path, $params = null)
    {
        if (is_array($path)) {
            foreach ($path as $p => $prm) {
                $this->createPermission($p, $prm);
            }
            return $this;
        }
        if (is_string($params)) {
            $params = ['title' => $params];
        }
        if (empty($params['module_name'])) {
            $params['module_name'] = $this->BModuleRegistry->currentModuleName();
        }
        static::$_allPermissions[$path] = $params;
        return $this;
    }

    /**
     * @return array
     */
    public function getAllPermissions()
    {
        return static::$_allPermissions;
    }

    /**
     * @param null $rootPath
     * @param null $level
     * @return array|null
     */
    public function getAllPermissionsTree($rootPath = null, $level = null)
    {
        if (is_null($rootPath)) {
            return [[
                'data' => static::$_allPermissions['/']['title'],
                'attr' => ['id' => 'perm___', 'path' => '/'],
                'icon' => 'folder',
                'state' => 'open',
                'children' => $this->getAllPermissionsTree('', 1),
            ]];
        }

        $nodes = [];
        foreach (static::$_allPermissions as $path => $params) {
            if (!isset($params['level'])) {
                $params['level'] = sizeof(explode('/', $path));
                static::$_allPermissions[$path]['level'] = $params['level'];
            }
            if ($level !== $params['level'] || $rootPath && strpos($path, $rootPath) !== 0) {
                continue;
            }
            $children = $this->getAllPermissionsTree($path . '/', $level + 1);
            $nodes[] = [
                'data' => $params['title'],
                'attr' => ['id' => 'perm_' . str_replace('/', '__', $path), 'path' => $path],
                'icon' => $children ? 'folder' : 'leaf',
                'state' => $this->orm && !empty($this->permissions[$path]) && $children ? 'open' : null,
                'children' => $children,
            ];
        }
        unset($params);
        if ($nodes) {
            if (sizeof($nodes) > 1) {
                usort($nodes, function($a, $b) { return strcmp($a['data'], $b['data']); });
            }
            return $nodes;
        } else {
            return null;
        }
    }

    /**
     * @return array
     */
    public function getPermissionIds()
    {
        $perms = [];
        foreach ((array)$this->permissions as $p => $_) {
            $perms['perm_' . str_replace('/', '__', $p)] = $_;
        }
        return $perms;
    }

    public function onAfterLoad()
    {
        parent::onAfterLoad();
        $perms = explode("\n", trim($this->permissions_data));
        $this->permissions = array_combine($perms, array_fill(0, sizeof($perms), 1));
        return $this;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;
        if (empty($this->create_at)) $this->create_at = $this->BDb->now();
        $this->update_at = $this->BDb->now();
        $this->permissions_data = $this->permissions ? trim(join("\n", array_keys($this->permissions))) : '';
        return true;
    }
}
