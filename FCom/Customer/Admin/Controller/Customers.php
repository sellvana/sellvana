<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Customer_Admin_Controller_Customers extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'customers';
    protected $_modelClass = 'FCom_Customer_Model_Customer';
    protected $_gridTitle = 'Customers';
    protected $_recordName = 'Customer';
    protected $_mainTableAlias = 'c';
    protected $_permission = 'customers/manage';
    protected $_navPath = 'customer/customers';
    protected $_formViewPrefix = 'customer/customers-form/';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'c.id'],
            ['name' => 'firstname', 'label' => 'First Name', 'index' => 'c.firstname'],
            ['name' => 'lastname', 'label' => 'Last Name', 'index' => 'c.lastname'],
            ['name' => 'email', 'label' => 'Email', 'index' => 'c.email'],
            ['type' => 'input', 'name' => 'customer_group', 'label' => 'Customer Group', 'index' => 'c.customer_group',
                  'editor' => 'select', 'options' => FCom_CustomerGroups_Model_Group::i()->groupsOptions(),
                  'editable' => true, 'mass-editable' => true, 'validation' => ['required' => true]],
            ['type' => 'input', 'name' => 'status', 'label' => 'Status', 'index' => 'c.status', 'editor' => 'select',
                  'options' => FCom_Customer_Model_Customer::i()->fieldOptions('status'),
                  'editable' => true, 'mass-editable' => true, 'validation' => ['required' => true]],
            ['name' => 'street1', 'label' => 'Address', 'index' => 'a.street1'],
            ['name' => 'city', 'label' => 'City', 'index' => 'a.city'],
            ['name' => 'region', 'label' => 'Region', 'index' => 'a.region'],
            ['name' => 'postcode', 'label' => 'Postal Code', 'index' => 'a.postcode'],
            ['type' => 'input', 'name' => 'country', 'label' => 'Country', 'index' => 'a.country', 'editor' => 'select',
                    'options' => FCom_Geo_Model_Country::i()->options()],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 'c.create_at'],
            /*array('name' => 'update_at', 'label'=>'Updated', 'index'=>'c.update_at'),*/
            ['name' => 'last_login', 'label' => 'Last Login', 'index' => 'c.last_login'],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
                ['name' => 'delete'],
            ]],
        ];
        $config['actions'] = [
            'export' => true,
            'edit'   => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'firstname', 'type' => 'text'],
            ['field' => 'lastname', 'type' => 'text'],
            ['field' => 'email', 'type' => 'text'],
            ['field' => 'customer_group', 'type' => 'multiselect'],
            ['field' => 'street1', 'type' => 'text'],
            ['field' => 'city', 'type' => 'text'],
            ['field' => 'region', 'type' => 'text'],
            ['field' => 'postcode', 'type' => 'text'],
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'last_login', 'type' => 'date-range'],
            ['field' => 'country', 'type' => 'multiselect'],
            ['field' => 'status', 'type' => 'multiselect'],
        ];
        //$config['custom']['dblClickHref'] = BApp::href('customers/form/?id=');
        //todo: check this in FCom_Admin_Controller_Abstract_GridForm
        if (!empty($config['orm'])) {
            if (is_string($config['orm'])) {
                $config['orm'] = $config['orm']::i()->orm($this->_mainTableAlias)->select($this->_mainTableAlias . '.*');
            }
            $this->gridOrmConfig($config['orm']);
        }
        $config['grid_before_create'] = 'customerGridRegister';
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->_useDefaultLayout = false;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->left_outer_join('FCom_Customer_Model_Address', ['a.id', '=', 'c.default_billing_id'], 'a')
            ->left_outer_join('FCom_CustomerGroups_Model_Group', ['cg.id', '=', 'c.customer_group'], 'cg')
            ->select(['a.street1', 'a.city', 'a.region', 'a.postcode', 'a.country'])
            ->select(['cg.title'])
        ;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        /** @var $m FCom_Customer_Model_Customer */
        $media = BConfig::i()->get('web/media_dir') ? BConfig::i()->get('web/media_dir') : 'media';
        $silhouetteImg = FCom_Core_Main::i()->resizeUrl($media . '/silhouette.jpg', ['s' => 98]);
        $actions = $args['view']->get('actions');
        if ($m->id) {
            $actions = array_merge($actions, [
                    'create-order' => '<a class="btn btn-primary" title="' . BLocale::_('Redirect to frontend and create order')
                        . '" href="' . BApp::href('customers/create_order?id=' . $m->id) . '"><span>' . BLocale::_('Create Order') . '</span></a>'
                ]);
        }
        $saleStatistics = $m->saleStatistics();
        $info = $this->_('Lifetime Sales') . ' ' . BLocale::currency($saleStatistics['lifetime'])
            . ' | ' . $this->_('Avg. Sales') . ' ' . BLocale::currency($saleStatistics['avg']);
        $args['view']->set([
            'sidebar_img' => (BConfig::i()->get('modules/FCom_Customer/use_gravatar') ? BUtil::gravatar($m->email) : $silhouetteImg),
            'title' => $m->id ? $this->_('Edit Customer: ') . $m->firstname . ' ' . $m->lastname : $this->_('Create New Customer'),
            'otherInfo' => $m->id ? $info : '',
            'actions' => $actions,
        ]);
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        if ($args['do'] !== 'DELETE') {
            $cust = $args['model'];
            $addrPost = BRequest::i()->post('address');
            if (($newData = BUtil::fromJson($addrPost['data_json']))) {
                $oldModels = FCom_Customer_Model_Address::i()->orm('a')->where('customer_id', $cust->id)->find_many_assoc();
                foreach ($newData as $id => $data) {
                    if (empty($data['id'])) {
                        continue;
                    }
                    if (!empty($oldModels[$data['id']])) {
                        $addr = $oldModels[$data['id']];
                        $addr->set($data)->save();
                    } elseif ($data['id'] < 0) {
                        unset($data['id']);
                        $addr = FCom_Customer_Model_Address::i()->newBilling($data, $cust);
                    }
                }
            }
            if (($del = BUtil::fromJson($addrPost['del_json']))) {
                FCom_Customer_Model_Address::i()->delete_many(['id' => $del, 'customer_id' => $cust->id]);
            }
        }
    }

    /**
     * get config for grid: customers of group
     * @param $group FCom_CustomerGroups_Model_Group
     * @return array
     */
    public function getGroupCustomersConfig($group)
    {
        $class = $this->_modelClass;
        $orm = $class::i()->orm()->table_alias('c')
            ->select(['c.id', 'c.firstname', 'c.lastname', 'c.email'])
            ->join('FCom_CustomerGroups_Model_Group', ['c.customer_group', '=', 'cg.id'], 'cg')
            ->where('c.customer_group', $group ? $group->id : 0);

        $config = parent::gridConfig();

        // TODO for empty local grid, it throws exception
        unset($config['orm']);
        $config['data'] = $orm->find_many();
        $config['id'] = 'group_customers_grid_' . $group->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'c.id', 'width' => 80, 'hidden' => true],
            ['name' => 'firstname', 'label' => 'Firstname', 'index' => 'c.username', 'width' => 200],
            ['name' => 'lastname', 'label' => 'Lastname', 'index' => 'c.username', 'width' => 200],
            ['name' => 'email', 'label' => 'Email', 'index' => 'c.email', 'width' => 200],
        ];
        $config['actions'] = [
            'add' => ['caption' => 'Add customer'],
        ];
        $config['filters'] = [
            ['field' => 'firstname', 'type' => 'text'],
            ['field' => 'lastname', 'type' => 'text'],
            ['field' => 'email', 'type' => 'text'],
        ];
        $config['data_mode'] = 'local';


        return ['config' => $config];
    }

    /**
     * get config for grid: all customer
     * @param $group FCom_CustomerGroups_Model_Group
     * @return array
     */
    public function getAllCustomersConfig($group)
    {
        $config            = parent::gridConfig();
        $config['id']      = 'group_all_customers_grid_' . $group->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'c.id', 'width' => 80, 'hidden' => true],
            ['name' => 'firstname', 'label' => 'Firstname', 'index' => 'c.username', 'width' => 200],
            ['name' => 'lastname', 'label' => 'Lastname', 'index' => 'c.username', 'width' => 200],
            ['name' => 'email', 'label' => 'Email', 'index' => 'c.email', 'width' => 200],
        ];
        $config['actions'] = [
            'add' => ['caption' => 'Add selected customers']
        ];
        $config['filters'] = [
            ['field' => 'firstname', 'type' => 'text'],
            ['field' => 'lastname', 'type' => 'text'],
            ['field' => 'email', 'type' => 'text'],
            '_quick' => ['expr' => 'firstname like ? or lastname like ? or email like ? or c.id=?', 'args' => ['?%', '%?%', '?']]
        ];



        return ['config' => $config];
    }

    public function action_create_order()
    {
        $id = BRequest::i()->param('id', true);
        $r = BRequest::i();
        $baseSrc = BConfig::i()->get('web/base_src');
        $redirectUrl = $r->scheme() . '://' . $r->httpHost() . $baseSrc;
        try {
            $model = FCom_Customer_Model_Customer::i()->load($id);
            if (!$model) {
                $this->message('Cannot load this customer model', 'error');
                $redirectUrl = BApp::href($this->_formHref) . '?id=' . $id;
            } else {
                $model->login();
            }
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
            $redirectUrl = BApp::href($this->_formHref) . '?id=' . $id;
        }

        BResponse::i()->redirect($redirectUrl);
    }

    public function getCustomerRecent()
    {
        $recent = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 7 * 86400);
        $result = FCom_Customer_Model_Customer::i()->orm()
            ->where_gte('create_at', $recent)
            ->select(['id' , 'email', 'firstname', 'lastname', 'create_at', 'status'])->find_many();
        return $result;
    }

    public function onHeaderSearch($args)
    {
        $r = BRequest::i()->get();
        if (isset($r['q']) && $r['q'] != '') {
            $value = '%' . $r['q'] . '%';
            $result = FCom_Customer_Model_Customer::i()->orm()
                ->where(['OR' => [
                    ['id like ?', $value],
                    ['firstname like ?', $value],
                    ['lastname like ?', $value],
                    ['email like ?', $value],
                ]])->find_one();
            $args['result']['customer'] = null;
            if ($result) {
                $args['result']['customer'] = [
                    'priority' => 10,
                    'url' => BApp::href($this->_formHref) . '?id=' . $result->id()
                ];
            }

        }

    }
}
