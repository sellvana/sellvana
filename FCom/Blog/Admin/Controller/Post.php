<?php

class FCom_Blog_Admin_Controller_Post extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'blog/post';
    protected $_modelClass = 'FCom_Blog_Model_Post';
    protected $_gridTitle = 'Blog Posts';
    protected $_recordName = 'Blog Post';
    protected $_permission = 'blog';
    protected $_mainTableAlias = 'p';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label' => 'ID'),
            array('name' => 'author', 'label'=>'Author'),
            array('name' => 'status', 'label' => 'Status', 'editable' => true, 'mass-editable' => true, 'editor' => 'select',
                  'options' => FCom_Blog_Model_Post::i()->fieldOptions('status')),
            array('name' => 'title', 'label'=>'Title', 'href' => BApp::href('blog/post/form/?id=:id')),
            array('name' => 'url_key', 'label'=>'Url Key', 'hidden' => true),
            array('name' => 'meta_title', 'label'=>'Meta Title', 'hidden' => true),
            array('name' => 'meta_description', 'label'=>'Meta Description', 'hidden' => true),
            array('name' => 'meta_keywords', 'label'=>'Meta Keywords', 'hidden' => true),
            array('name' => 'create_ym', 'label'=>'Create ym'),
            array('name' => 'create_at', 'label'=>'Created', 'cell'=>'date'),
            array('name' => 'update_at', 'label'=>'Updated', 'cell'=>'date'),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false,
                  'data' => array('edit' => array('href' => BApp::href('blog/post/form/?id='), 'col'=>'id'),'delete' => true)),
        );
        $config['orm'] = FCom_Blog_Model_Post::i()->orm('p')
            ->select('p.*')
            ->join('FCom_Admin_Model_User', array('p.author_user_id', '=', 'u.id'), 'u')
            ->select_expr('CONCAT_WS(" ", u.firstname,u.lastname)', 'author');
        $config['actions'] = array(
            'edit' => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'title', 'type' => 'text'),
            array('field' => 'status', 'type' => 'select'),
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
