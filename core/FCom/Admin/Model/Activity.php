<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * class FCom_Admin_Model_Activity
 *
 * @property int $id
 * @property string $status new|recent|archived
 * @property string $type workflow|alert
 * @property string $event_code (order:new:123)
 * @property string $permissions (orders, customers, modules)
 * @property int $action_user_id
 * @property int $customer_id
 * @property int $order_id
 * @property string $create_at
 * @property string data_serialized
 *     - message (?)
 *     - message_html
 *     - href
 *     - item_class
 *     - icon_class
 *
 * DI
 * @property FCom_Admin_Model_ActivityUser $FCom_Admin_Model_ActivityUser
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 */
class FCom_Admin_Model_Activity extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_admin_activity';
    static protected $_origClass = __CLASS__;
    protected static $_usersRestrictionsCache;

    protected static $_fieldOptions = [
        'status' => [
            'new' => 'New',
            'recent' => 'Recent',
            'archived' => 'Archived',
        ],
        'type' => [
            'workflow' => 'Workflow',
            'alert' => 'Alert',
        ],
    ];

    static protected $_importExportProfile = [
        'skip'    => ['id'],
        'related' => [
            'action_user_id' => 'FCom_Admin_Model_User.id',
            'customer_id'    => 'Sellvana_Customer_Model_Customer.id',
            'order_id'       => 'Sellvana_Sales_Model_Order.id',
        ]
    ];

    static protected $_availableFilters = [];

    static protected $_permissionsCache = [];

    static protected $_filtersCache = [];

    public function registerFilter($filter)
    {
        static::$_availableFilters += (array)$filter;
    }

    public function registerAllFilters()
    {
        $this->BEvents->fire(__METHOD__);
    }

    public function fetchAllUsersRestrictions()
    {
        if (!static::$_usersRestrictionsCache) {
            $users = $this->FCom_Admin_Model_User->orm('u')
                ->left_outer_join('FCom_Admin_Model_Role', ['r.id', '=', 'u.role_id'], 'r')
                ->select('u.id')->select('u.is_superadmin')
                ->select('u.data_serialized')->select('r.permissions_data')
                ->find_many_assoc();

            foreach ($users as $uId => $u) {
                static::$_permissionsCache['*'][$uId] = $u->get('is_superadmin');
                if (!static::$_permissionsCache['*'][$uId]) {
                    $perms = $u->get('permissions_data');
                    if ($perms) {
                        foreach (array_flip(explode("\n", $perms)) as $p) {
                            static::$_permissionsCache[$p][$uId] = 1;
                        }
                    }
                }
                static::$_filtersCache[$uId] = '#^(' . join(':|', $u->getData('alert_filters')) . ':)#';
            }
        }
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function addActivity($data)
    {
        $coreFields = 'status,type,code,permissions,action_user_id,customer_id,order_id,create_at';
        $coreData = $this->BUtil->arrayMask($data, $coreFields);
        $customData = $this->BUtil->arrayMask($data, $coreFields, true);
        $model = $this->create($coreData)->setCustomData($customData)->save();
        $permissions = explode(',', $data['permissions']);

        $hlp = $this->FCom_Admin_Model_ActivityUser;

        $this->fetchAllUsersRestrictions();

        foreach (static::$_permissionsCache['*'] as $uId => $isSuperUser) {
            // check alert permissions
            if ($permissions[0] !== '*') { // allow for everybody
                if (!$isSuperUser) { // super user is allowed to receive everything
                    $skip = true; // assume not allowed
                    foreach ($permissions as $perm) { // iterate alert permissions
                        if (!empty(static::$_permissionsCache[$perm][$uId])) { // user has permission
                            $skip = false;
                            break;
                        }
                    }
                    if ($skip) {
                        continue;
                    }
                }
            }
            // check user filters
            $filters = static::$_filtersCache[$uId];
            if ($filters && !preg_match($filters, $data['code'])) {
                continue; // not in user filters
            }
            $hlp->create(['activity_id' => $model->id(), 'user_id' => $uId, 'alert_user_status' => 'new'])->save();
        }

        return $model;
    }

    /**
     * get recent activity by user
     * @param string $type
     * @param int $userId
     * @return FCom_Admin_Model_Activity[]
     */
    public function getRecentActivityByUser($type = null, $userId = null)
    {
        if (!$userId) {
            $userId = $this->FCom_Admin_Model_User->sessionUserId();
        }

        $orm = $this->orm('a')
            ->join('FCom_Admin_Model_ActivityUser', ['au.activity_id', '=', 'a.id'], 'au')
            ->where('au.user_id', $userId);

        if ($type) {
            $orm->where('a.type', $type);
        }

        return $orm->find_many();
    }

    /**
     * @param null $userId
     * @return FCom_Admin_Model_Activity
     * @throws BException
     */
    public function markAsRead($userId = null)
    {
        if (!$userId) {
            $userId = $this->FCom_Admin_Model_User->sessionUserId();
        }

        $hlp = $this->FCom_Admin_Model_ActivityUser;
        $actUser = $hlp->loadWhere(['activity_id' => $this->id(), 'user_id' => $userId]);
        $actUser->set('alert_user_status', 'read')->save();

        return $this;
    }

    /**
     * @param int $userId
     * @return FCom_Admin_Model_Activity
     */
    public function dismiss($userId = null)
    {
        if (!$userId) {
            $userId = $this->FCom_Admin_Model_User->sessionUserId();
        }

        $hlp = $this->FCom_Admin_Model_ActivityUser;

        $hlp->delete_many(['activity_id' => $this->id(), 'user_id' => $userId]);

        /*
        $actUser = $hlp->load(array('activity_id' => $this->id(), 'user_id' => $userId));
        $actUser->set('alert_user_status', 'dismissed')->save();
        $left = $hlp->orm()->where('activity_id', $this->id())->where_in('alert_user_status', array('new','read'))
            ->select('(count(*))', 'cnt')->find_one();
        */

        /*
        if (!$left->get('cnt')) {
            $this->set('status', 'archive')->save();
        }
        */
        return $this;
    }

    public function addActivityItems($items)
    {
        if (!$items) {
            return $this;
        }
        $hashes = [];
        foreach ($items as $i => $item) {
            if (empty($item['hash'])) {
                $hash = $items[$i]['hash'] = sha1(join('|', $this->BUtil->arrayMask($item, 'ts,content,feed,type,code')));
            } else {
                $hash = $item['hash'];
            }
            $hashes[$hash] = $i;
        }
        $existing = $this->orm()
            ->where_in('unique_hash', array_keys($hashes))->find_many_assoc('id', 'unique_hash');
        if ($existing) {
            foreach ($existing as $hash) {
                unset($items[$hashes[$hash]]);
            }
        }
        $result = [];
        if ($items) {
            $now = $this->BDb->now();
            foreach ($items as $item) {
                $data = $item;
                unset($data['hash'], $data['feed'], $data['type'], $data['code'], $data['ts']);
                $result[] = $this->create([
                    'status' => 'new',
                    'feed' => !empty($item['feed']) ? $item['feed'] : 'local',
                    'type' => !empty($item['type']) ? $item['type'] : 'info',
                    'event_code' => !empty($item['code']) ? $item['code'] : null,
                    'unique_hash' => $item['hash'],
                    'ts' => !empty($item['ts']) ? $item['ts'] : $now,
                ])->setData($data)->save();
            }
        }
        return $this;
    }

    public function getUserVisibleItems($userId = null)
    {
        if (!$userId) {
            $userId = $this->FCom_Admin_Model_User->sessionUserId();
        } else {
            $userId = (int)$userId;
        }

        $items = $this->orm('a')->select('a.*')
            ->where('a.status', 'new')
            ->left_outer_join('FCom_Admin_Model_ActivityUser', "au.user_id={$userId} and au.activity_id=a.id
                and au.alert_user_status in ('new', 'read')", 'au')
            ->select('au.alert_user_status')
            ->order_by_desc('ts')
            ->find_many_assoc();
        return $items;
    }
}
