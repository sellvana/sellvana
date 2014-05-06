<?php

class FCom_Admin_Controller_Roles extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'system/roles';
    protected $_modelClass = 'FCom_Admin_Model_Role';
    protected $_gridHref = 'roles';
    protected $_gridTitle = 'Roles and Permissions';
    protected $_recordName = 'Role';


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

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set([
            'title' => $m->id ? 'Edit Role: ' . $m->role_name : 'Create New Role',
        ]);
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
            $user_ids = explode(",", $data['user_ids_remove']);
            foreach ($user_ids as $user_id) {
                $user = FCom_Admin_Model_User::i()->load($user_id);
                if ($user) {
                    $user->role_id = null;
                    $user->save();
                }
            }
        }
        //todo: check if can use sql executes to faster, update role_id where user_id in (user_ids_add)?
        if (!empty($data['user_ids_add'])) {
            $user_ids = explode(",", $data['user_ids_add']);
            foreach ($user_ids as $user_id) {
                $user = FCom_Admin_Model_User::i()->load($user_id);
                if ($user) {
                    $user->role_id = $model->id;
                    $user->save();
                }
            }
        }
    }

}
