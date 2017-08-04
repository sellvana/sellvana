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
            static::ID => 'users',
            static::TITLE => (('Users')),
            static::DATA_URL => 'users/grid_data',
            static::COLUMNS => [
                [static::TYPE => static::ROW_SELECT, static::WIDTH => 80],
                [static::NAME => 'id', static::LABEL => (('ID'))],
                [static::NAME => 'thumb_path', static::LABEL => (('Thumbnail')), static::WIDTH => 48, 'sortable' => false,
                    static::DATACELL_TEMPLATE => '<td><a :href="\'#/users/form?id=\'+row.id"><img :src="row.thumb_url" :alt="row.username"></a></td>'],
                [static::NAME => 'username', static::LABEL => (('User Name')), static::WIDTH => 250,
                    static::DATACELL_TEMPLATE => '<td><a :href="\'#/users/form?id=\'+row.id">{{row.username}}</a></td>'],
                [static::NAME => 'email', static::LABEL => (('Email'))],
                [static::NAME => 'firstname', static::LABEL => (('First Name'))],
                [static::NAME => 'lastname', static::LABEL => (('Last Name'))],
                [static::NAME => 'create_at', static::LABEL => (('Created At'))],
                [static::NAME => 'update_at', static::LABEL => (('Updated At'))],
            ],
            static::FILTERS => true,
            static::EXPORT => true,
            static::PAGER => true,
            static::BULK_ACTIONS => [
                [static::NAME => 'delete', static::LABEL => (('Delete'))],
            ],
            static::PAGE_ACTIONS => [
                [static::NAME => 'new', static::LABEL => (('Add New User')), static::BUTTON_CLASS => 'button1', static::LINK => '/users/form', static::GROUP => 'new'],
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
        $bool = [0 => (('no')), 1 => (('Yes'))];

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
        $result[static::FORM]['user'] = $user->as_array();
        $result[static::FORM][static::CONFIG][static::TITLE] = $userId ? $user->get('username') : (('New User'));
        $result[static::FORM][static::CONFIG][static::TABS] = '/users/form';
        $result[static::FORM]['avatar'] = ['thumb_url' => $user->thumb(100)];
        $result[static::FORM][static::CONFIG][static::FIELDS] = [
            static::DEFAULT_FIELD => [static::MODEL => 'user', static::TAB => 'main'],
            [static::NAME => 'status', static::LABEL => (('Status')), static::OPTIONS => $statusOptions],
            [static::NAME => 'username', static::LABEL => (('Username')), static::REQUIRED => true],
            [static::NAME => 'email', static::LABEL => (('Email')), static::REQUIRED => true],
            [static::NAME => 'firstname', static::LABEL => (('First Name')), static::REQUIRED => true],
            [static::NAME => 'lastname', static::LABEL => (('Last Name')), static::REQUIRED => true],
            [static::NAME => 'is_superadmin', static::LABEL => (('Superadmin?')), static::TYPE => 'checkbox'],
            [static::NAME => 'role_id', static::LABEL => (('Role')), static::REQUIRED => true, static::TYPE => 'select2', static::OPTIONS => $roleOptions],
            [static::NAME => 'phone', static::LABEL => (('Phone'))],
            [static::NAME => 'fax', static::LABEL => (('Fax'))],
            [static::NAME => 'tz', static::LABEL => (('Timezone')), static::OPTIONS => $timezones],
            [static::NAME => 'locale', static::LABEL => (('Locale')), static::OPTIONS => $locales],
        ];

        $result[static::FORM][static::CONFIG][static::PAGE_ACTIONS] = $this->getDefaultFormPageActions();

        return $result;
    }
}