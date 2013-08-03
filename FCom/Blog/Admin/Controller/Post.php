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
        $config['grid']['columns'] += array(
            'id' => array('label' => 'ID'),
            'title' => array('label'=>'Title', 'href' => BApp::href('blog/post/form/?id=<%=id%>')),
            'author_user_id' => array('label'=>'Author'),
            'version' => array('label'=>'Version'),
            'create_at' => array('label'=>'Created', 'cell'=>'date'),
            'update_at' => array('label'=>'Updated', 'cell'=>'date'),
            '_actions' => array('label' => 'Actions', 'sortable' => false),
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
