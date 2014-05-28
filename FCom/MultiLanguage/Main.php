<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class FCom_MultiLanguage_Main extends BClass
{
    const ENTITY_TYPE_CATEGORY = 'category';

    const ENTITY_TYPE_PRODUCT = 'product';

    public static function bootstrap()
    {
        $lang = static::getLanguage();
        if (!empty($lang)) {
            BSession::i()->set('_language', $lang);
        }
        FCom_Admin_Model_Role::i()->createPermission([
            'translations' => 'Translations',
        ]);

    }

    /**
     * @return null|string
     */
    protected static function getLanguage()
    {
        return BRequest::i()->request("lang");
    }

    public function productCollectionLoadLocale($args)
    {
        return $this->modelCollectionLoadLocale($args, static::ENTITY_TYPE_PRODUCT);
    }

    public function productLoadLocale($args)
    {
        return $this->modelLoadLocale($args, static::ENTITY_TYPE_PRODUCT);
    }

    public function categoryLoadLocale($args)
    {
        return $this->modelLoadLocale($args, static::ENTITY_TYPE_CATEGORY);
    }

    public function categoryCollectionLoadLocale($args) {
        return $this->modelCollectionLoadLocale($args, static::ENTITY_TYPE_CATEGORY);
    }

    public function modelLoadLocale($args, $entityType)
    {
        $lang = static::getLanguage();
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

    public function modelCollectionLoadLocale($args, $entityType) {
        $lang = static::getLanguage();
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
        $orm = FCom_MultiLanguage_Model_Translation::i()
            ->orm('ml')
            ->select(['entity_id', 'field', 'value', 'data_serialized'], 'ml')
            ->where(['entity_id' => $id, 'entity_type' => $entityId, 'locale' => $lang]);
        if (!empty($fields)) {
            $orm->where(['field' => $fields]);
        }
        $localized = $orm->find_many();
        return $localized;
    }
}
