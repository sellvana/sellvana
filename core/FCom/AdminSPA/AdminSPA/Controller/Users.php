<?php

/**
 * Class FCom_AdminSPA_AdminSPA_Controller_Users
 *
 * @property FCom_Admin_Model_Role FCom_Admin_Model_Role
 */
class FCom_AdminSPA_AdminSPA_Controller_Users extends FCom_AdminSPA_AdminSPA_Controller_Abstract_GridForm
{
    static protected $_modelClass = 'FCom_Admin_Model_User';
    static protected $_modelName = 'user';
    static protected $_recordName = (('User'));

    public function getGridConfig()
    {
        return [
            'id' => 'users',
            'title' => (('Users')),
            'data_url' => 'users/grid_data',
            'columns' => [
                ['type' => 'row-select', 'width' => 80],
                ['name' => 'id', 'label' => (('ID'))],
                ['name' => 'thumb_path', 'label' => (('Thumbnail')), 'width' => 48, 'sortable' => false,
                    'datacell_template' => '<td><a :href="\'#/users/form?id=\'+row.id"><img :src="row.thumb_url" :alt="row.username"></a></td>'],
                ['name' => 'username', 'label' => (('User Name')), 'width' => 250,
                    'datacell_template' => '<td><a :href="\'#/users/form?id=\'+row.id">{{row.username}}</a></td>'],
                ['name' => 'email', 'label' => (('Email'))],
                ['name' => 'firstname', 'label' => (('First Name'))],
                ['name' => 'lastname', 'label' => (('Last Name'))],
                ['name' => 'create_at', 'label' => (('Created At'))],
                ['name' => 'update_at', 'label' => (('Updated At'))],
            ],
            'filters' => true,
            'export' => true,
            'pager' => true,
            'bulk_actions' => [
                ['name' => 'delete', 'label' => (('Delete'))],
            ],
            'page_actions' => [
                ['name' => 'new', 'label' => (('Add New User')), 'button_class' => 'button1', 'link' => '/users/form', 'group' => 'new'],
            ],
            'state' => [
                'sc' => 'username asc'
            ]
        ];
    }

    public function processGridPageData($data)
    {
        foreach ($data['rows'] as $row) {
            $row->set('thumb_url', $row->thumb(48));
        }
        return parent::processGridPageData($data);
    }

    public function getFormData()
    {
        $userId = $this->BRequest->get('id');
        $bool = [0 => 'no', 1 => (('Yes'))];

        if ($userId) {
            $user = $this->FCom_Admin_Model_User->load($userId);
            if (!$user) {
                throw new BException('User not found');
            }
        } else {
            $user = $this->FCom_Admin_Model_User->create();
        }

        $statusOptions = $this->FCom_Admin_Model_User->fieldOptions('status');
        $roleOptions = $this->FCom_Admin_Model_Role->options();
        $timezones = $this->BLocale->tzOptions();
        $locales = $this->BLocale->getAvailableLocaleCodes();

        $result = [];
        $result['form']['user'] = $user->as_array();
        $result['form']['config']['title'] = $userId ? $user->get('username') : (('New User'));
        $result['form']['config']['tabs'] = '/users/form';
        $result['form']['avatar'] = ['thumb_url' => $user->thumb(100)];
        $result['form']['config']['fields'] = [
            'default' => ['model' => 'user', 'tab' => 'main'],
            ['name' => 'status', 'label' => (('Status')), 'options' => $statusOptions],
            ['name' => 'username', 'label' => (('Username')), 'required' => true],
            ['name' => 'email', 'label' => (('Email')), 'required' => true],
            ['name' => 'firstname', 'label' => (('First Name')), 'required' => true],
            ['name' => 'lastname', 'label' => (('Last Name')), 'required' => true],
            ['name' => 'is_superadmin', 'label' => (('Superadmin?')), 'type' => 'checkbox'],
            ['name' => 'role_id', 'label' => (('Role')), 'required' => true, 'type' => 'select2', 'options' => $roleOptions],
            ['name' => 'phone', 'label' => (('Phone'))],
            ['name' => 'fax', 'label' => (('Fax'))],
            ['name' => 'tz', 'label' => (('Timezone')), 'options' => $timezones],
            ['name' => 'locale', 'label' => (('Locale')), 'options' => $locales],
        ];

        $result['form']['config']['page_actions'] = $this->getDefaultFormPageActions();

        return $result;
    }
}