<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Cms_Model_Block
 *
 * fields in table
 * @property int $id
 * @property string $handle
 * @property string $description
 * @property string $renderer
 * @property string $content
 * @property string $layout_update
 * @property int $version
 * @property string $create_at
 * @property string $update_at
 * @property int $page_enabled
 * @property string $page_url
 * @property string $page_title
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property int $modified_time
 * @property int $form_enable
 * @property string $form_fields
 * @property string $form_email
 * @property string $form_custom_email
 *
 * other
 * @property string $version_comments
 *
 * DI
 * @property FCom_Cms_Model_BlockHistory $FCom_Cms_Model_BlockHistory
 * @property FCom_Cms_Frontend_View_Block $FCom_Cms_Frontend_View_Block
 */
class FCom_Cms_Model_Block extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_block';
    protected static $_origClass = __CLASS__;
    #protected static $_cacheAuto = ['id', 'name'];

    protected static $_validationRules = [
        ['handle', '@required'],
        /*array('version', '@required'),*/

        ['version', '@integer'],
        ['page_enabled', '@integer'],
        ['page_url', 'FCom_Cms_Model_Block::rulePageUrlUnique', 'Duplicate URL Key'],
    ];

    /**
     * @return bool
     */
    public function validateBlock()
    {
        return true;
    }

    public function onAfterCreate()
    {
        parent::onAfterCreate();
        $this->set('renderer', 'FCom_LibTwig');
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        if (!$this->is_dirty()) {
            return false;
        }

        $this->set('version', $this->version ? $this->version + 1 : '1');
//        $this->set('version_comments', $this->version ? $this->version : '1');

        $this->set('modified_time', time()); // attempt to compare with filemtime() for caching

        if ($this->get('page_url') === '') {
            $this->set('page_url', null);
        }
        return true;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        $user = $this->FCom_Admin_Model_User->sessionUser();
        $hist = $this->FCom_Cms_Model_BlockHistory->create([
            'block_id' => $this->id,
            'user_id' => $user ? $user->id : null,
            'username' => $user ? $user->username : null,
            'version' => $this->version,
            'comments' => $this->version_comments ? $this->version_comments : 'version ' . $this->version,
            'ts' => $this->BDb->now(),
            'data' => $this->BUtil->toJson($this->BUtil->arrayMask($this->as_array(), 'content')), // more fields?
        ])->save();
    }

    /**
     * rule page url unique
     * @param $data
     * @param $args
     * @return bool
     */
    public function rulePageUrlUnique($data, $args)
    {
        if (empty($data[$args['field']])) {
            return true;
        }
        $orm = $this->orm()->where('page_url', $data[$args['field']]);
        if (!empty($data['id'])) {
            $orm->where_not_equal('id', $data['id']);
        }
        if ($orm->find_one()) {
            return false;
        }
        return true;
    }

    /**
     * @param array $params
     * @return FCom_Cms_Frontend_View_Block
     * @throws BException
     */
    public function createView($params = [])
    {
        return $this->FCom_Cms_Frontend_View_Block->createView($this, $params);
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $content = $this->get('content');
        if(empty($content)){
            $content = '';
        }
        return $content;
    }
}
