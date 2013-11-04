<?php

class FCom_Blog_Admin_Controller_Post extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'blog/post';
    protected $_modelClass = 'FCom_Blog_Model_Post';
    protected $_gridTitle = 'Blog Posts';
    protected $_recordName = 'Blog Post';
    protected $_permission = 'blog';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('name' => 'id', 'label' => 'ID'),
            array('name' => 'title', 'label'=>'Title', 'href' => BApp::href('blog/post/form/?id=:id')),
            array('name' => 'author_user_id', 'label'=>'Author'),
            array('name' => 'version', 'label'=>'Version'),
            array('name' => 'create_at', 'label'=>'Created', 'cell'=>'date'),
            array('name' => 'update_at', 'label'=>'Updated', 'cell'=>'date'),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'data' => array('edit' => array('href' => BApp::href('blog/post/form/?id='), 'col'=>'id'),'delete' => true)),
        );
        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'title' => $m->id ? 'Edit Blog Post: '.$m->title : 'Create New Blog Post',
        ));
        $tagOptions = FCom_Blog_Model_Tag::i()->orm()->order_by_asc('tag_name')
            ->select('tag_key', 'id')->select('tag_name', 'name')->find_many();
        $tagOptionsJson = BUtil::toJson(BDb::many_as_array($tagOptions));
        $this->view('blog/post-form/main')->set('tag_options_json', $tagOptionsJson);
    }

}
