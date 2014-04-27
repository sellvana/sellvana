<?php

class FCom_Admin_Controller_Users extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'system/users';
    protected $_modelClass = 'FCom_Admin_Model_User';
    protected $_gridHref = 'users';
    protected $_gridTitle = 'Admin Users';
    protected $_recordName = 'User';
    protected $_mainTableAlias = 'au';
    protected $_navPath = 'system/users';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config[ 'columns' ] = [
            [ 'type' => 'row_select' ],
            [ 'name' => 'id', 'label' => 'ID', 'index' => 'id', 'width' => 55, 'cell' => 'integer' ],
            [ 'name' => 'username', 'label' => 'User Name', 'width' => 100 ],
            [ 'name' => 'email', 'label' => 'Email', 'width' => 150 ],
            [ 'name' => 'firstname', 'label' => 'First Name', 'width' => 150 ],
            [ 'name' => 'lastname', 'label' => 'Last Name', 'width' => 150 ],
            [ 'type' => 'input', 'name' => 'is_superadmin', 'label' => 'SuperAdmin', 'width' => 100, 'editable' => true,
                'editor' => 'select', 'options' => FCom_Admin_Model_User::i()->fieldOptions( 'is_superadmin' ) ],
            [ 'type' => 'input', 'name' => 'status', 'label' => 'Status', 'width' => 100, 'editor' => 'select',
                'editable' => true, 'mass-editable' => true,
                'options' => FCom_Admin_Model_User::i()->fieldOptions( 'status' ) ],
            [ 'name' => 'create_at', 'label' => 'Created', 'width' => 100 ],
            [ 'name' => 'update_at', 'label' => 'Updated', 'width' => 100 ],
            [ 'type' => 'btn_group', 'width' => 85, 'buttons' => [
                [ 'name' => 'edit' ],
                [ 'name' => 'delete' ],
            ] ],
        ];
        $config[ 'actions' ] = [
            'edit' => [ 'caption' => 'status' ],
            'delete' => true,
        ];
        $config[ 'filters' ] = [
            [ 'field' => 'username', 'type' => 'text' ],
            [ 'field' => 'email', 'type' => 'text' ],
            [ 'field' => 'is_superadmin', 'type' => 'multiselect' ],
            [ 'field' => 'status', 'type' => 'multiselect' ],
            [ 'field' => 'create_at', 'type' => 'date-range' ],
            [ 'field' => 'update_at', 'type' => 'date-range' ],
        ];

        return $config;
    }

    public function formViewBefore( $args )
    {
        parent::formViewBefore( $args );
        $m = $args[ 'model' ];
        $args[ 'view' ]->set( [
            'sidebar_img' => BUtil::gravatar( $m->email ),
            'title' => $m->id ? 'Edit User: ' . $m->username : 'Create New User',
        ] );
    }

    public function formPostBefore( $args )
    {
        parent::formPostBefore( $args );

        unset( $args[ 'data' ][ 'model' ][ 'password_hash' ] );
        if ( !empty( $args[ 'data' ][ 'model' ][ 'password' ] ) ) {
            $args[ 'model' ]->setPassword( $args[ 'data' ][ 'model' ][ 'password' ] );
        }
    }

    /**
     * get config for grid: users of role
     * @param $model FCom_Admin_Model_Role
     * @return array
     */
    public function getRoleUsersConfig( $model )
    {
        $class = $this->_modelClass;
        $orm = $class::i()->orm()->table_alias( 'au' )
            ->select( [ 'au.id', 'au.username', 'au.email', 'au.status' ] )
            ->join( 'FCom_Admin_Model_Role', [ 'au.role_id', '=', 'ar.id' ], 'ar' )
            ->where( 'au.role_id', $model ? $model->id : 0 );

        $config = parent::gridConfig();

        // TODO for empty local grid, it throws exception
        unset( $config[ 'orm' ] );
        $config[ 'data' ] = $orm->find_many();
        $config[ 'id' ] = 'role_users_grid_' . $model->id;
        $config[ 'columns' ] = [
            [ 'type' => 'row_select' ],
            [ 'name' => 'id', 'label' => 'ID', 'index' => 'au.id', 'width' => 80, 'hidden' => true ],
            [ 'name' => 'username', 'label' => 'Username', 'index' => 'au.username', 'width' => 200 ],
            [ 'name' => 'email', 'label' => 'Email', 'index' => 'au.email', 'width' => 200 ],
            [ 'name' => 'status', 'label' => 'Status', 'index' => 'au.status', 'width' => 200, 'editable' => true,
                'mass-editable' => true, 'editor' => 'select',
                'options' => FCom_Admin_Model_User::i()->fieldOptions( 'status' ) ]
        ];
        $config[ 'actions' ] = [
            'add' => [ 'caption' => 'Add user' ],
            'delete' => [ 'caption' => 'Remove' ]
        ];
        $config[ 'filters' ] = [
            [ 'field' => 'username', 'type' => 'text' ],
            [ 'field' => 'email', 'type' => 'text' ],
            [ 'field' => 'status', 'type' => 'multiselect' ]
        ];
        $config[ 'data_mode' ] = 'local';
        $config[ 'grid_before_create' ] = 'rolesGridRegister';
        $config[ 'events' ] = [ 'init', 'add', 'mass-delete' ];

        return [ 'config' => $config ];
    }

    /**
     * get config for grid: all users
     * @param $model FCom_Admin_Model_Role
     * @param $filterAdmin bool
     * @return array
     */
    public function getAllUsersConfig( $model, $filterAdmin = true )
    {
        $config            = parent::gridConfig();
        $config[ 'id' ]      = 'role_all_users_grid_' . $model->id;
        $config[ 'columns' ] = [
            [ 'type' => 'row_select' ],
            [ 'name' => 'id', 'label' => 'ID', 'index' => 'au.id', 'width' => 55, 'hidden' => true ],
            [ 'name' => 'username', 'label' => 'Name', 'index' => 'au.username', 'width' => 250 ],
            [ 'name' => 'email', 'label' => 'Email', 'index' => 'au.email', 'width' => 100 ],
            [ 'type' => 'input', 'name' => 'status', 'label' => 'Status', 'index' => 'au.status', 'width' => 100,
                'editable' => true, 'mass-editable' => true, 'editor' => 'select',
                'options' => FCom_Admin_Model_User::i()->fieldOptions( 'status' ) ]
        ];
        $config[ 'actions' ] = [
            'add' => [ 'caption' => 'Add selected users' ]
        ];
        $config[ 'filters' ] = [
            [ 'field' => 'username', 'type' => 'text' ],
            [ 'field' => 'email', 'type' => 'text' ],
            [ 'field' => 'status', 'type' => 'multiselect' ],
            '_quick' => [ 'expr' => 'username like ? or email like ? or au.id=?', 'args' => [ '?%', '%?%', '?' ] ]
        ];
        if ( $filterAdmin ) {
            $config[ 'orm' ] = FCom_Admin_Model_User::i()->orm()->where( 'is_superadmin', 0 );
        }
        $config[ 'grid_before_create' ] = 'userGridRegister';
        $config[ 'events' ] = [ 'add' ];

        return [ 'config' => $config ];
    }
}
