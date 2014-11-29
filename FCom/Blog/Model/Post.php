<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Blog_Model_Post
 *
 * @property int $id
 * @property int $author_user_id
 * @property string $status
 * @property string $title
 * @property string $url_key
 * @property string $preview
 * @property string $content
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $create_ym
 * @property string $create_at
 * @property string $update_at
 *
 * DI
 * @property FCom_Blog_Model_Post $FCom_Blog_Model_Post
 */
class FCom_Blog_Model_Post extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_blog_post';
    static protected $_origClass = __CLASS__;
    static protected $_fieldOptions = [
        'status' => [
            'pending'  => 'Pending',
            'published' => 'Published',
        ],
    ];

    protected static $_validationRules = [
        /*array('author_user_id', '@required'),*/
        ['title', '@required'],
        ['url_key', 'FCom_Blog_Model_Post::validateDupUrlKey']
        /*array('url_key', '@required'),*/
    ];


    public function getPostsOrm()
    {
        return $this->FCom_Blog_Model_Post->orm('p')
            ->select('p.*')
            ->join('FCom_Admin_Model_User', ['p.author_user_id', '=', 'u.id'], 'u')
            ->select('u.firstname')->select('u.lastname')
            ->where_in('p.status', ['published'])
            ->order_by_desc('create_at');
    }

    public function getArchiveTree()
    {

    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        if (!$this->create_at) {
            $this->set(['create_at' => $this->BDb->now(), 'create_ym' => date('Ym')]);
        }
        $this->set('update_at', $this->BDb->now());


        if (!$this->url_key) {
            $this->url_key = $this->BLocale->transliterate($this->title);
        }

        if (!$this->author_user_id) {
            $this->author_user_id = $this->FCom_Admin_Model_User->sessionUserId();
        }

        return true;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();
        $this->FCom_Blog_Model_PostTag->delete_many(['post_id' => $this->id]);
        if ($this->tags) {
            $tagNames = preg_split('#[ ,;]+#', $this->tags);
            $exists = $this->FCom_Blog_Model_Tag->orm()->where_in('tag_name', $tagNames)->find_many_assoc('tag_name');
            foreach ($tagNames as $t) {
                if (isset($exists[$t])) {
                    $tagId = $exists[$t]->id;
                } else {
                    $tag = $this->FCom_Blog_Model_Tag->create(['tag_key' => $t, 'tag_name' => $t])->save();
                    $tagId = $tag->id;
                }
                $this->FCom_Blog_Model_PostTag->create(['post_id' => $this->id, 'tag_id' => $tagId])->save();
            }
        }
    }

    public function getUrl()
    {
        return $this->BApp->href('blog/' . $this->get('url_key'));
    }

    public function getAuthor()
    {
        if (!$this->author) {
            $this->author = $this->FCom_Admin_Model_User->load($this->get('author_user_id'));
        }
        return $this->author;
    }

    public function getAuthorName()
    {
        $user = $this->getAuthor();
        return $user->get('firstname') . ' ' . $user->get('lastname');
    }

    public function getTags()
    {
        if (!$this->tag_models) {
            $this->tag_models = $this->FCom_Blog_Model_Tag->orm('t')
                ->join('FCom_Blog_Model_PostTag', ['pt.tag_id', '=', 't.id'], 'pt')
                ->where('pt.post_id', $this->id())
                ->find_many();
        }
        return $this->tag_models;
    }

    public function getCategories()
    {
        if (!$this->category_models) {
            $this->category_models = $this->FCom_Blog_Model_Category->orm('c')
                ->join('FCom_Blog_Model_PostCategory', ['pc.category_id', '=', 'c.id'], 'pc')
                ->where('pc.post_id', $this->id())
                ->find_many();
        }
        return $this->category_models;
    }

    public function getTagsString()
    {
        return join(' ', $this->BUtil->arrayToOptions($this->BDb->many_as_array($this->getTags()), 'tag_name'));
    }

    public function getRelatedPosts()
    {
        return [];
    }

    public function validateDupUrlKey($data, $args)
    {
        if (!empty(static::$_flags['skip_duplicate_checks'])) {
            return true;
        }
        if (empty($data[$args['field']])) {
            return true;
        }
        $orm = $this->orm('p')->where('url_key', $data[$args['field']]);
        if (!empty($data['id'])) {
            $orm->where_not_equal('p.id', $data['id']);
        }
        if ($orm->find_one()) {
            return $this->BLocale->_('The URL Key entered is already in use. Please enter a valid URL Key.');
        }
        return true;
    }
}
