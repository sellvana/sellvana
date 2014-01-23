<?php

class FCom_Blog_Model_Category extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_blog_category';
    protected $_validationRules = array(
        array('url_key', 'FCom_Blog_Model_Category::validateDupUrlKey', 'Duplicate URL Key'),

    );

    public static function validateDupUrlKey($data, $args)
    {
        if (empty($data[$args['field']])) {
            return true;
        }
        $url_key = $data[$args['field']];
        $orm = static::orm('c')->where('url_key', $data[$args['field']]);
        if (!empty($data['id'])) {
            $orm->where_not_equal('c.id', $data['id']);
        }
        return !$orm->find_one();
    }

    public function getUrl()
    {
        return BApp::href('blog/category/' . $this->get('url_key'));
    }

    static public function getCategoryCounts()
    {
        return FCom_Blog_Model_Category::i()->orm('c')
            ->join('FCom_Blog_Model_PostCategory', array('pc.category_id','=','c.id'), 'pc')
            ->join('FCom_Blog_Model_Post', array('p.id','=','pc.post_id'), 'p')
            ->where_in('p.status', array('published'))
            ->group_by('c.id')
            ->select('c.id')->select('c.name')->select('c.url_key')->select('(count(*))', 'cnt')
            ->find_many_assoc('id');
    }

}
