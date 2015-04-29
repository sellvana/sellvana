<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Blog_Model_Category
 *
 * @property int $id
 * @property string $name
 * @property string $url_key
 * @property string $description
 *
 * DI
 * @property Sellvana_Blog_Model_Category $Sellvana_Blog_Model_Category
 */
class Sellvana_Blog_Model_Category extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_blog_category';
    protected static $_validationRules = [
        ['url_key', 'Sellvana_Blog_Model_Category::validateDupUrlKey', 'Duplicate URL Key'],
    ];
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['url_key'],
    ];

    public function validateDupUrlKey($data, $args)
    {
        if (empty($data[$args['field']])) {
            return true;
        }
        $url_key = $data[$args['field']];
        $orm = $this->orm('c')->where('url_key', $data[$args['field']]);
        if (!empty($data['id'])) {
            $orm->where_not_equal('c.id', $data['id']);
        }
        return !$orm->find_one();
    }

    public function getUrl()
    {
        return $this->BApp->href('blog/category/' . $this->get('url_key'));
    }

    public function getCategoryCounts()
    {
        return $this->Sellvana_Blog_Model_Category->orm('c')
            ->join('Sellvana_Blog_Model_PostCategory', ['pc.category_id', '=', 'c.id'], 'pc')
            ->join('Sellvana_Blog_Model_Post', ['p.id', '=', 'pc.post_id'], 'p')
            ->where_in('p.status', ['published'])
            ->group_by('c.id')
            ->select('c.id')->select('c.name')->select('c.url_key')->select('(count(*))', 'cnt')
            ->find_many_assoc('id');
    }

}
