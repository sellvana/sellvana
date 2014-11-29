<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_MultiLanguage_Main
 *
 * @property FCom_MultiLanguage_Model_Translation $FCom_MultiLanguage_Model_Translation
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class FCom_MultiLanguage_Main extends BClass
{
    const ENTITY_TYPE_CATEGORY = 'category';

    const ENTITY_TYPE_PRODUCT = 'product';

    public function bootstrap()
    {
        $lang = $this->getLanguage();
        if (!empty($lang)) {
            $this->BSession->set('_language', $lang);
        }
        $this->FCom_Admin_Model_Role->createPermission([
            'translations' => 'Translations',
        ]);

    }

    /**
     * @return null|string
     */
    protected function getLanguage()
    {
        return $this->BRequest->request("lang");
    }

    /**
     * @param $args
     * @return bool
     */
    public function productCollectionLoadLocale($args)
    {
        return $this->modelCollectionLoadLocale($args, static::ENTITY_TYPE_PRODUCT);
    }

    /**
     * @param $args
     * @return bool
     */
    public function productLoadLocale($args)
    {
        return $this->modelLoadLocale($args, static::ENTITY_TYPE_PRODUCT);
    }

    /**
     * @param $args
     * @return bool
     */
    public function categoryLoadLocale($args)
    {
        return $this->modelLoadLocale($args, static::ENTITY_TYPE_CATEGORY);
    }

    /**
     * @param $args
     * @return bool
     */
    public function categoryCollectionLoadLocale($args) {
        return $this->modelCollectionLoadLocale($args, static::ENTITY_TYPE_CATEGORY);
    }

    /**
     * @param $args
     * @param $entityType
     * @return bool
     * @throws BException
     */
    public function modelLoadLocale($args, $entityType)
    {
        $lang = $this->getLanguage();
        if (!$lang || !($args['result'] instanceof BModel)) { // should instance check be more strict?
            return false;
        }
        /* @var $model FCom_Core_Model_Abstract */
        $model = $args['result'];
        $id      = $model->id();

        $localized = $this->getTranslations($id, $entityType, $lang);

        foreach ($localized as $locale) {
            /* @var FCom_MultiLanguage_Model_Translation $locale */
            $field = $locale->get('field');
            $model->set($field, $locale->get('value'));
        }
        return true;
    }

    /**
     * @param $args
     * @param $entityType
     * @return bool
     * @throws BException
     */
    public function modelCollectionLoadLocale($args, $entityType) {
        $lang = $this->getLanguage();
        if (!$lang || count($args['result']) == 0) {
            return false;
        }
        $result  = $args['result'];
        $modelIds   = [];
        $modelIdIdx = [];
        foreach ($result as $idx => $model) {
            /* @var FCom_Core_Model_Abstract $model */
            $id = $model->get('id');
            if (!$id) {
                continue;
            }
            $modelIds[]      = $id;
            $modelIdIdx[$id] = $idx;
        }
        if (empty($modelIds)) {
            return false;
        }
        // todo, filter by actual fields selected in model
        $localized = $this->getTranslations($modelIds, $entityType, $lang);
        // localized fields for current product ids and language

        foreach ($localized as $locale) {
            /* @var FCom_MultiLanguage_Model_Translation $locale */
            $id      = $locale->get('entity_id');
            $model = & $result[$modelIdIdx[$id]];
            $field   = $locale->get('field');
            $model->set($field, $locale->get('value'));
        }
        return true;
    }

    /**
     * Get translations for entity type
     *
     * @param string|int|array $id either single id in string or integer form or an array of ids
     * @param string           $entityId string representing entity type (product, category etc.)
     * @param string           $lang string representing current language (de, nl etc.)
     * @param array            $fields
     * @return array
     */
    protected function getTranslations($id, $entityId, $lang, $fields = [])
    {
        /* @var $orm BORM */
        $orm = $this->FCom_MultiLanguage_Model_Translation
            ->orm('ml')
            ->select(['entity_id', 'field', 'value', 'data_serialized'], 'ml')
            ->where(['entity_id' => (int)$id, 'entity_type' => (string)$entityId, 'locale' => (string)$lang]);
        if (!empty($fields)) {
            $orm->where(['field' => $fields]);
        }
        $localized = $orm->find_many();
        return $localized;
    }
}
