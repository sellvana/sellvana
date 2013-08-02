<?php

class FCom_Blog_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'blog';
    protected $_modelClass = 'FCom_Blog_Model_Article';
    protected $_gridTitle = 'Blog Articles';
    protected $_recordName = 'Blog Article';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['grid']['columns'] += array(
            'id' => array('label' => 'ID'),
            'title' => array('label'=>'Title', 'href' => BApp::href('blog/form/?id=<%=id%>')),
            'author_user_id' => array('label'=>'Author'),
            'version' => array('label'=>'Version'),
            'create_at' => array('label'=>'Created', 'cell'=>'date'),
            'update_at' => array('label'=>'Updated', 'cell'=>'date'),
            '_actions' => array('label' => 'Actions', 'sortable' => false),
        );
        return $config;
    }
}
