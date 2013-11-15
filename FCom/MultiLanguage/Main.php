<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_MultiLanguage_Main extends BClass
{
    public static function bootstrap()
    {
        $lang = self::getLanguage();
        if(!empty($lang)){
            BSession::i()->set('_language', $lang);
        }
    }

    /**
     * @return null|string
     */
    protected static function getLanguage()
    {
        return BRequest::i()->request("lang");
    }

    public function productLoadLocale($args)
    {
        $lang = self::getLanguage();
        if (!$lang || count($args['result']) == 0) {
            return;
        }
        $result = $args['result'];
        $prIds = array();
        $prIdIdx = array(); // product id index, we want to update data in $result, and this should help us
        foreach ($result as $idx => $product) {
            /* @var FCom_Catalog_Model_Product $product */
            $id      = $product->get('id');
            if(!$id){
                continue;
            }
            $prIds[] = $id;
            $prIdIdx[$id] = $idx;
        }
        if(empty($prIds)){
            return;
        }
        // todo, filter by actual fields selected in product
        $localized = FCom_MultiLanguage_Model_Translation::i()->orm('ml')
            ->select(array('entity_id', 'field', 'value', 'data_serialized'), 'ml')
            ->where(array('entity_id' => $prIds, 'entity_type' => 'product', 'locale' => $lang))
            ->find_many(); // localized fields for current product ids and language

        foreach ($localized as $locale) {
            /* @var FCom_MultiLanguage_Model_Translation $locale */
            $id = $locale->get('entity_id');
            $product = &$result[$prIdIdx[$id]];
            $field = $locale->get('field');
            $product->set($field, $locale->get('value'));
        }

    }
}