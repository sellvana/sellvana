<?php

class FCom_Customer_Admin_Controller_Customers extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'customers';
    protected $_modelClass = 'FCom_Customer_Model_Customer';
    protected $_gridTitle = 'Customers';
    protected $_recordName = 'Customer';
    protected $_mainTableAlias = 'c';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label' => 'ID', 'index'=>'c.id'),
            array('name' => 'firstname', 'label'=>'First Name', 'index'=>'c.firstname'),
            array('name' => 'lastname', 'label'=>'Last Name', 'index'=>'c.lastname'),
            array('name' => 'email', 'label'=>'Email', 'index'=>'c.email'),
            array('name' => 'street1', 'label'=>'Address', 'index'=>'a.street1'),
            array('name' => 'city', 'label'=>'City', 'index'=>'a.city'),
            array('name' => 'region', 'label'=>'Region', 'index'=>'a.region'),
            array('name' => 'postcode', 'label'=>'Postal Code', 'index'=>'a.postcode'),
            array('name' => 'country', 'label'=>'Country', 'index'=>'a.country', 'options'=>FCom_Geo_Model_Country::i()->options()),
            array('name' => 'create_at', 'label'=>'Created', 'index'=>'c.create_at'),
            array('name' => 'update_at', 'label'=>'Updated', 'index'=>'c.update_at'),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'width' => 85,
                  'data'=> array('edit' => array('href' => BApp::href($this->_formHref.'?id='), 'col' => 'id'), 'delete' => true)),
        );
        $config['actions'] = array(
            'export' => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'firstname', 'type' => 'text'),
            array('field' => 'email', 'type' => 'text'),
            array('field' => 'country', 'type' => 'select'),
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
            ->select(array('a.street1', 'a.city', 'a.region', 'a.postcode', 'a.country'))
        ;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'sidebar_img' => BUtil::gravatar($m->email),
            'title' => $m->id ? 'Edit Customer: '.$m->firstname.' '.$m->lastname : 'Create New Customer',
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
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
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
        $config['events'] = array('init', 'add','mass-delete');

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
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
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

        $config['events'] = array('add');

        return array('config' => $config);
    }
}
