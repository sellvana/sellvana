<?php

/**
 * Class Sellvana_ProductReviews_Model_Review
 *
 * @property int $id
 * @property int $product_id
 * @property int $customer_id
 * @property int $rating
 * @property int $approved
 * @property int $helpful
 * @property int $helpful_voices
 * @property int $offensive
 * @property string $title
 * @property string $create_at
 * @property string $text
 *
 * DI
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_ProductReviews_Model_Review extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_review';
    protected static $_origClass = __CLASS__;

    protected static $_config = [
       'max' => 5,
       'min' => 0,
       'step' => 1,
    ];

    protected static $_fieldDefaults = [
        'verified_purchase' => 0,
    ];

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['product_id', 'customer_id', 'create_at'],
        'related'    => [
            'product_id'  => 'Sellvana_Catalog_Model_Product.id',
            'customer_id' => 'Sellvana_Customer_Model_Customer.id'
        ],
    ];
    protected static $_validationRules = [
        ['product_id', '@required'],
        ['customer_id', '@required'],
        ['rating', '@required'],
        ['title', '@required'],

        ['title', '@string', null, ['max' => 255]],

        /*array('helpful_voices', '@string', null, array('max' => 11)),
        array('rating', '@integer'),
        array('approved', '@integer'),
        array('helpful', '@integer'),
        array('offensive', '@integer'),
        array('offensive', '@integer'),*/
    ];

    public function notify()
    {
        $this->BLayout->view('email/prodreview-new-admin')->email();
        $this->BLayout->view('email/prodreview-new-customer')->email();
        return $this;
    }

    public function confirm()
    {
        $this->set('approved', 1)->save();
        $this->BLayout->view('email/prodreview-confirm-customer')->email();
        return $this;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        //TODO: condition on relevant changes only (approved, rating)
        $pId = $this->get('product_id');
        if ($pId) { //make sure we have related product id
            $rating = $this->orm()->where('product_id', $pId)
                           ->where('approved', 1)
                           ->select('(avg(rating))', 'avg')
                           ->select('(count(1))', 'num')
                           ->find_one();

            $this->Sellvana_Catalog_Model_Product->load($pId)
                                             ->set('avg_rating', $rating->get('avg'))
                                             ->set('num_reviews', $rating->get('num'))
                                             ->save();
        }

        return $this;
    }

    public function helpful($mark, $add = false)
    {
        if ($mark === 0) {
            $mark = -1;
        }
        $this->add('helpful', $mark);

        if ($add) {
            $this->add('helpful_voices');
        }

        $this->save();
        return $this;
    }

    public function config()
    {
        return static::$_config;
    }

    public function indexAvgRating($products, $field)
    {
        $data = [];
        foreach ($products as $p) {
            $m = $p->avg_rating;
            if     ($m >= 4) $v = '4 ==> 4 Stars & Up';
            elseif ($m >= 3) $v = '3 ==> 3 Stars & Up';
            elseif ($m >= 2) $v = '2 ==> 2 Stars & Up';
            elseif ($m >= 1) $v = '1 ==> 1 Star & Up';
            else $v = '';
            $data[$p->id()] = $v;
        }
        return $data;
    }
}
