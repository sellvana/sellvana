<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Admin_Controller_Roles
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */

class FCom_Admin_Controller_Roles extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'system/roles';
    protected $_modelClass = 'FCom_Admin_Model_Role';
    protected $_gridHref = 'roles';
    protected $_gridTitle = 'Roles and Permissions';
    protected $_recordName = 'Role';
    protected $_formTitleField = 'role_name';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'role_name', 'label' => 'Role Name', 'width' => 100],
            ['type' => 'btn_group', 'width' => 85,
                'buttons' => [['name' => 'edit']],
             ],
        ];
        $config['actions'] = [
            'delete' => true,
        ];
        $config['filters'] = [
            ['field' => 'role_name', 'type' => 'text'],
        ];
        return $config;
    }

    public function formPostBefore($args)
    {
        parent::formPostBefore($args);

        if (empty($args['data']['model']['permissions'])) {
            $args['data']['model']['permissions'] = [];
        }
        if (!empty($args['data']['ie_perm_ids_add'])) {
            $iePerms = $args['data']['ie_perm_ids_add'];
            foreach ((array)$iePerms as $type => $permissions) {
                if (empty($permissions)) {
                    continue;
                }
                foreach (explode(',', $permissions) as $p) {
                    $args['data']['permissions'][$p . '/' . $type] = 1;
                }
            }
        }
    }

    public function formPostAfter($args)
    {
        $data = $args['data'];
        $model = $args['model'];
        if (!empty($data['user_ids_remove'])) {
            $userIds = explode(",", $data['user_ids_remove']);
            $this->FCom_Admin_Model_User->update_many(['role_id' => null], ['id' => $userIds]);
        }
        if (!empty($data['user_ids_add'])) {
            $userIds = explode(",", $data['user_ids_add']);
            $this->FCom_Admin_Model_User->update_many(['role_id' => $model->id()], ['id' => $userIds]);
        }
    }

}
