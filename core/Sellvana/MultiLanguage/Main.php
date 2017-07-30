<?php

/**
 * Class Sellvana_MultiLanguage_Main
 *
 * @property Sellvana_MultiLanguage_Model_Translation $Sellvana_MultiLanguage_Model_Translation
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_MultiLanguage_Main extends BClass
{
    const ENTITY_TYPE_CATEGORY = 'category';
    const ENTITY_TYPE_PRODUCT = 'product';

    const LANG_FIELD_SUFFIX = '_lang_fields';

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_MultiLanguage' => (('Multi Language Settings')),
            'translations' => (('Translations')),
        ]);

    }

    public function getAllowedLocales()
    {
        $localesConf = $this->BConfig->get('modules/Sellvana_MultiLanguage/allowed_locales', []);
        return array_combine($localesConf, $localesConf);
    }

    /**
     * @return null|string
     */
    protected function _getLanguage()
    {
        $lang = $this->BRequest->request("lang");
        if (!$lang) {
            $lang = $this->BLocale->getCurrentLocale();
        }
        return $lang;
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
     */
    public function modelLoadLocale($args, $entityType)
    {
        return $this->_replaceLangData([$args['result']]);
    }

    /**
     * @param $args
     * @param $entityType
     * @return bool
     * @throws BException
     */
    public function modelCollectionLoadLocale($args, $entityType) {
        return $this->_replaceLangData($args['result']);
    }

    /**
     * Get translations for entity type
     *
     * @param FCom_Core_Model_Abstract $model model needed to translate
     * @param string                   $lang string representing current language (de, nl etc.)
     * @param array                    $fields
     * @return array
     */
    protected function _getTranslations($model, $lang, $fields = [])
    {
        $localized = [];
        foreach ($model->as_array() as $key => $data) {
            if (strpos($key, self::LANG_FIELD_SUFFIX) !== false) {
                $key = substr($key, 0, -strlen(self::LANG_FIELD_SUFFIX));
                $data = json_decode($data, true);
                if (!is_array($data)) {
                    continue;
                }
                foreach ($data as $langData) {
                    if (array_key_exists('lang_code', $langData) && $langData['lang_code'] == $lang) {
                        $localized[$key] = $langData['value'];
                        break;
                    }
                }
            }
        }

        return $localized;
    }

    /**
     * @param array $models
     * @return bool
     */
    protected function _replaceLangData(array $models)
    {
        $lang = $this->_getLanguage();
        if (!$lang) {
            return false;
        }

        /* @var $model FCom_Core_Model_Abstract */
        foreach ($models as $model) {
            if ($model instanceof FCom_Core_Model_Abstract) {
                $localized = $this->_getTranslations($model, $lang);
                foreach ($localized as $field => $value) {
                    $model->set($field, $value);
                }
            }
        }

        return true;
    }

    public function getModelTranslations($type, $id)
    {
        $translations = [];
        $raw = $this->Sellvana_MultiLanguage_Model_Translation->orm()
            ->where('entity_type', $type)->where('entity_id', $id)->find_many();
        foreach ((array)$raw as $t) {
            $translations[$t->get('field')][$t->get('locale')] = $t->get('value');
        }
        return $translations;
    }

    public function loadTranslations($type, $collection, $idField = 'id')
    {
        $modelsById = [];
        $translations = [];
        foreach ($collection as $model) {
            $modelsById[$model->get($idField)] = $model;
        }
        $this->Sellvana_MultiLanguage_Model_Translation->orm()
            ->where('entity_type', $type)->where_in('entity_id', array_keys($modelsById))
            ->iterate(function ($t) use ($translations) {
                $translations[$t->get('entity_id')][$t->get('field')][$t->get('locale')] = $t->get('value');
            });

        foreach ($translations as $id => $t) {
            $modelsById[$id]->set('i18n', $t);
        }

        return $this;
    }
}
