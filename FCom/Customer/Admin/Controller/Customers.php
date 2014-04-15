<?php

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
        $config['columns'] = array(
            array('type'=>'row_select'),
            array('name' => 'id', 'label' => 'ID', 'index'=>'c.id'),
            array('name' => 'firstname', 'label'=>'First Name', 'index'=>'c.firstname'),
            array('name' => 'lastname', 'label'=>'Last Name', 'index'=>'c.lastname'),
            array('name' => 'email', 'label'=>'Email', 'index'=>'c.email'),
            array('type' => 'input', 'name' => 'customer_group', 'label'=>'Customer Group', 'index'=>'c.customer_group',
                  'editor' => 'select', 'options' => FCom_CustomerGroups_Model_Group::i()->groupsOptions(),
                  'editable' => true, 'mass-editable' => true, 'validation' => array('required' => true)),
            array('type' => 'input', 'name' => 'status', 'label' => 'Status', 'index' => 'c.status', 'editor' => 'select',
                  'options' => FCom_Customer_Model_Customer::i()->fieldOptions('status'),
                  'editable' => true, 'mass-editable' => true, 'validation' => array('required' => true)),
            array('name' => 'street1', 'label'=>'Address', 'index'=>'a.street1'),
            array('name' => 'city', 'label'=>'City', 'index'=>'a.city'),
            array('name' => 'region', 'label'=>'Region', 'index'=>'a.region'),
            array('name' => 'postcode', 'label'=>'Postal Code', 'index'=>'a.postcode'),
            array('type' => 'input', 'name' => 'country', 'label'=>'Country', 'index'=>'a.country', 'editor'=>'select',
                    'options'=>FCom_Geo_Model_Country::i()->options()),
            array('name' => 'create_at', 'label'=>'Created', 'index'=>'c.create_at'),
            /*array('name' => 'update_at', 'label'=>'Updated', 'index'=>'c.update_at'),*/
            array('name' => 'last_login', 'label'=>'Last Login', 'index'=>'c.last_login'),
            array('type' => 'btn_group', 'buttons' => array(
                array('name'=>'edit'),
                array('name'=>'delete'),
            )),
        );
        $config['actions'] = array(
            'export' => true,
            'edit'   => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'firstname', 'type' => 'text'),
            array('field' => 'lastname', 'type' => 'text'),
            array('field' => 'email', 'type' => 'text'),
            array('field' => 'customer_group', 'type' => 'multiselect'),
            array('field' => 'street1', 'type' => 'text'),
            array('field' => 'city', 'type' => 'text'),
            array('field' => 'region', 'type' => 'text'),
            array('field' => 'postcode', 'type' => 'text'),
            array('field' => 'create_at', 'type'=>'date-range'),
            array('field' => 'last_login', 'type'=>'date-range'),
            array('field' => 'country', 'type' => 'multiselect'),
            array('field' => 'status', 'type' => 'multiselect'),
        );
        //$config['custom']['dblClickHref'] = BApp::href('customers/form/?id=');
        //todo: check this in FCom_Admin_Controller_Abstract_GridForm
        if (!empty($config['orm'])) {
            if (is_string($config['orm'])) {
                $config['orm'] = $config['orm']::i()->orm($this->_mainTableAlias)->select($this->_mainTableAlias.'.*');
            }
            $this->gridOrmConfig($config['orm']);
        }
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->left_outer_join('FCom_Customer_Model_Address', array('a.id','=','c.default_billing_id'), 'a')
            ->left_outer_join('FCom_CustomerGroups_Model_Group', array('cg.id','=','c.customer_group'), 'cg')
            ->select(array('a.street1', 'a.city', 'a.region', 'a.postcode', 'a.country'))
            ->select(array('cg.title'))
        ;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        /** @var $m FCom_Customer_Model_Customer */
        $media = BConfig::i()->get('web/media_dir') ? BConfig::i()->get('web/media_dir') : 'media';
        $resize_url = FCom_Core_Main::i()->resizeUrl();
        $silhouetteImg = $resize_url.'?f='.urlencode(trim($media.'/silhouette.jpg', '/')).'&s=98x98';
        $actions = $args['view']->get('actions');
        if ($m->id) {
            $actions = array_merge($actions, array(
                    'create-order' => '<a class="btn btn-primary" title="'.BLocale::_('Redirect to frontend and create order').'"
                                        href="'.BApp::href('customers/create_order?id='.$m->id).'"><span>' . BLocale::_('Create Order') . '</span></a>'
                ));
        }
        $saleStatistics = $m->saleStatistics();
        $info = $this->_('Lifetime Sales') . ' ' . BLocale::currency($saleStatistics['lifetime']) . ' | ' . $this->_('Avg. Sales') . ' ' . BLocale::currency($saleStatistics['avg']);
        $args['view']->set(array(
            'sidebar_img' => (BConfig::i()->get('modules/FCom_Customer/use_gravatar') ? BUtil::gravatar($m->email) : $silhouetteImg),
            'title' => $m->id ? $this->_('Edit Customer: ').$m->firstname.' '.$m->lastname : $this->_('Create New Customer'),
            'otherInfo' => $m->id ? $info : '',
            'actions' => $actions,
        ));
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        if ($args['do']!=='DELETE') {
            $cust = $args['model'];
            $addrPost = BRequest::i()->post('address');
            if (($newData = BUtil::fromJson($addrPost['data_json']))) {
                $oldModels = FCom_Customer_Model_Address::i()->orm('a')->where('customer_id', $cust->id)->find_many_assoc();
                foreach ($newData as $id=>$data) {
                    if (empty($data['id'])) {
                        continue;
                    }
                    if (!empty($oldModels[$data['id']])) {
                        $addr = $oldModels[$data['id']];
                        $addr->set($data)->save();
                    } elseif ($data['id']<0) {
                        unset($data['id']);
                        $addr = FCom_Customer_Model_Address::i()->newBilling($data, $cust);
                    }
                }
            }
            if (($del = BUtil::fromJson($addrPost['del_json']))) {
                FCom_Customer_Model_Address::i()->delete_many(array('id'=>$del, 'customer_id'=>$cust->id));
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
            ->select(array('c.id', 'c.firstname', 'c.lastname', 'c.email'))
            ->join('FCom_CustomerGroups_Model_Group', array('c.customer_group','=','cg.id'), 'cg')
            ->where('c.customer_group', $group ? $group->id : 0);

        $config = parent::gridConfig();

        // TODO for empty local grid, it throws exception
        unset($config['orm']);
        $config['data'] = $orm->find_many();
        $config['id'] = 'group_customers_grid_'.$group->id;
        $config['columns'] = array(
            array('type'=>'row_select'),
            array('name' => 'id', 'label' => 'ID', 'index' => 'c.id', 'width' => 80, 'hidden' => true),
            array('name' => 'firstname', 'label' => 'Firstname', 'index' => 'c.username', 'width' => 200),
            array('name' => 'lastname', 'label' => 'Lastname', 'index' => 'c.username', 'width' => 200),
            array('name' => 'email', 'label' => 'Email', 'index' => 'c.email', 'width' => 200),
        );
        $config['actions'] = array(
            'add'=>array('caption'=>'Add customer'),
        );
        $config['filters'] = array(
            array('field'=>'firstname', 'type'=>'text'),
            array('field'=>'lastname', 'type'=>'text'),
            array('field'=>'email', 'type'=>'text'),
        );
        $config['data_mode'] = 'local';


        return array('config'=>$config);
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
        $config['columns'] = array(
            array('type'=>'row_select'),
            array('name' => 'id', 'label' => 'ID', 'index' => 'c.id', 'width' => 80, 'hidden' => true),
            array('name' => 'firstname', 'label' => 'Firstname', 'index' => 'c.username', 'width' => 200),
            array('name' => 'lastname', 'label' => 'Lastname', 'index' => 'c.username', 'width' => 200),
            array('name' => 'email', 'label' => 'Email', 'index' => 'c.email', 'width' => 200),
        );
        $config['actions'] = array(
            'add' => array('caption' => 'Add selected customers')
        );
        $config['filters'] = array(
            array('field' => 'firstname', 'type' => 'text'),
            array('field' => 'lastname', 'type' => 'text'),
            array('field' => 'email', 'type' => 'text'),
            '_quick' => array('expr' => 'firstname like ? or lastname like ? or email like ? or c.id=?', 'args' => array('?%', '%?%', '?'))
        );



        return array('config' => $config);
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
                $redirectUrl = BApp::href($this->_formHref).'?id='.$id;
            } else {
                $model->login();
            }
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
            $redirectUrl = BApp::href($this->_formHref).'?id='.$id;
        }

        BResponse::i()->redirect($redirectUrl);
    }

    public function getCustomerRecent()
    {
        $recent = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 7*86400);
        $result = FCom_Customer_Model_Customer::i()->orm()
            ->where_gte('create_at', $recent)
            ->select(array('id' ,'email', 'firstname', 'lastname', 'create_at', 'status'))->find_many();
        return $result;
    }
}
