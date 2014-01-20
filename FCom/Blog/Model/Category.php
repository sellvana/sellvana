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
}
