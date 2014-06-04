<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Blog_Model_Tag extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_blog_tag';
    static protected $_origClass = __CLASS__;

    public function getUrl()
    {
        return $this->BApp->href('blog/tag/' . $this->get('tag_key'));
    }

    public function getTagCounts()
    {
        return $this->FCom_Blog_Model_Tag->orm('t')
            ->join('FCom_Blog_Model_PostTag', ['pt.tag_id', '=', 't.id'], 'pt')
            ->join('FCom_Blog_Model_Post', ['p.id', '=', 'pt.post_id'], 'p')
            ->where_in('p.status', ['published'])
            ->group_by('t.id')
            ->select('t.id')->select('tag_name')->select('tag_key')->select('(count(*))', 'cnt')
            ->find_many_assoc('id');
    }

}
