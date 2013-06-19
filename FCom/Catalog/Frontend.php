<?php

class FCom_Catalog_Frontend extends BClass
{
    static public function bootstrap()
    {
        if (class_exists('FCom_FrontendCP_Main')) {
            FCom_FrontendCP_Main::i()
                ->addEntityHandler('product', 'FCom_Catalog_Frontend::cpProductEntityHandler')
                ->addEntityHandler('category', 'FCom_Catalog_Frontend::cpCategoryEntityHandler')
            ;
        }
    }

    static public function cpProductEntityHandler($params)
    {
        $model = FCom_Catalog_Model_Product::i()->load($params['data']['model_id']);
        if (!$model) {
            return array('error' => 'Product not found');
        }
        $field = $params['data']['field'];
        $value = isset($params['value']) ? $params['value'] : null;
        if ($params['type']==='image') {
            if (preg_match('/resize\.php\?f=media%2F([^&]+)/', $params['attributes']['src'], $m)) {
                $src = urldecode($m[1]);
                if ($src!=='image-not-found.jpg') { // TODO: make configurable
                    $value = $src;
                }
            } else {
                return array('error' => 'Invalid image source');
            }
        }
        if ($model->get($field) != $value) {
            $model->set($field, $value)->save();
        }

        return array('success' => true);
    }

    static public function cpCategoryEntityHandler($params)
    {
        $model = FCom_Catalog_Model_Category::i()->load($params['data']['model_id']);
        if (!$model) {
            return array('error' => 'Category not found');
        }
        $field = $params['data']['field'];
        $value = $params['value'];
        if ($model->get($field) != $value) {
            $model->set($field, $value)->save();
        }
        return array('success' => true);
    }
}