<?php

class FCom_MarketServer_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_market()
    {
        $category = FCom_Catalog_Model_Category::orm()->where('url_key', 'market')->find_one();
        $modules = $category->products();
        //$customerId = FCom_Customer_Model_Customer::sessionUserId();
        //$options = FCom_MarketServer_Model_Account::i()->getOptions($customerId);

        //get remote modules manifest
        /*
        if ($options && !empty($options->site_url) ) {
            $manifest = BUtil::fromJson(file_get_contents($options->site_url.'/market/modules'));
        }

        //check modules difference
        foreach ($modules as $ind => $mod) {
            $modules[$ind]->need_upgrade = false;
            if (!empty($manifest[$mod->name])) {
                if (version_compare($mod->version, $manifest[$mod->name]) > 0) {
                    $modules[$ind]->need_upgrade = true;
                } else {
                    $modules[$ind]->need_upgrade = false;
                }
            }
        }
         *
         */
        //todo: filter only public modules
        //show modules and description
        $this->view('market/list')->modules = $modules;
        $this->layout('/market/list');
    }

    public function action_modules()
    {
        $category = FCom_Catalog_Model_Category::orm()->where('url_key', 'market')->find_one();
        $modules = $category->products();
        $marketModules = array();
        foreach($modules as $mod) {
            $marketModules[$mod->mod_name] = array(
                'mod_name' => $mod->mod_name,
                'name' => $mod->product_name,
                'version' => $mod->version,
                'description' => $mod->description
                    );
        }
        echo BUtil::toJson($marketModules);
        exit;
    }

    public function action_downlaod()
    {
        $modName = BRequest::i()->get('id');
        $product = FCom_Catalog_Model_Product::orm()->where('mod_name', $modName)->find_one();
        if (!$product) {
            BResponse::i()->status(404, "Module not found");
            echo BUtil::toJson(array('Error' => 'Module '.$modName.' does not exist'));
            exit;
        }

        $storage = BConfig::i()->get('fs/storage_dir');
        $download = $storage . '/downloads/'. $modName.'.zip';

        BResponse::i()->sendFile($download);

        //todo: check that product belong to user

        //$modName = $product->mod_name;
        //$url = '/download/'.$modName.'.zip';
        //BResponse::i()->redirect($url);
    }
}