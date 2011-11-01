<?php

class FCom_Catalog_Model_Product extends BModel
{
    protected static $_table = 'fcom_product';

    public static function stockStatusOptions($onlyAvailable=false)
    {
        $options = array(
            'in_stock' => 'In Stock',
            'backorder' => 'On Backorder',
            'special_order' => 'Special Order',
        );
        if (!$onlyAvailable) {
            $options += array(
                'do_not_carry' => 'Do Not Carry',
                'temp_unavail' => 'Temporarily Unavailable',
                'vendor_disc' => 'Supplier Discontinued',
                'manuf_disc' => 'MFR Discontinued',
            );
        }
        return $options;
    }

    public function url($category=null)
    {
        $url = BApp::baseUrl().'/p/';
        if ($category) {
            $url .= $category->url_path.'/';
        }
        $url .= $this->url_key;
        return $url;
    }

    public function imageUrl($full=false)
    {
        return ($full ? DMain::baseUrl().'/' : '').'media/'.($this->image_url ? $this->image_url : 'DC642702.jpg');
    }

    public function thumbUrl($w, $h=null)
    {
        return DMain::baseUrl().'/image_resize.php?f='.urlencode($this->imageUrl()).'&s='.$w.'x'.$h;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;

        if (!$this->get('url_key')) $this->generateUrlKey();

        return true;
    }

    public function generateUrlKey()
    {
        //$key = $this->manuf()->manuf_name.'-'.$this->manuf_sku.'-'.$this->product_name;
        $key = $this->product_name;
        $this->set('url_key', FCom_Catalog::i()->getUrlKey($key));
        return $this;
    }

    public function onAssociateCategory($args)
    {
        $catId = $args['id'];
        $prodIds = $args['ref'];
        if (!$copy) {

        }
    }
}

