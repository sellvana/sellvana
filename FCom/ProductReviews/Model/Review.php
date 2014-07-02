<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ProductReviews_Model_Review extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_review';
    protected static $_origClass = __CLASS__;

    protected static $_config = [
       'max' => 5,
       'min' => 0,
       'step' => 1,
    ];

    protected static $_validationRules = [
        ['product_id', '@required'],
        ['customer_id', '@required'],
        ['rating', '@required'],
        ['title', '@required'],

        ['title', '@string', null, ['max' => 255]],

        /*array('helpful_voices', '@string', null, array('max' => 11)),
        array('rating', '@integer'),
        array('rating1', '@integer'),
        array('rating2', '@integer'),
        array('rating3', '@integer'),
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
        $rating = $this->orm()->where('product_id', $pId)
            ->where('approved', 1)
            ->select('(avg(rating))', 'avg')
            #->select('(avg(rating1))', 'avg1')
            #->select('(avg(rating2))', 'avg2')
            #->select('(avg(rating3))', 'avg3')
            ->select('(count(1))', 'num')
            ->find_one();
        $this->FCom_Catalog_Model_Product->load($pId)
            ->set('avg_rating', $rating->get('avg'))
            ->set('num_reviews', $rating->get('num'))
            ->save();

        $pCustomerId = $this->get('customer_id');
        $customer = $this->FCom_Customer_Model_Customer->load($pCustomerId);
        if ($this->BApp->m('FCom_PushServer')->run_status === BModule::LOADED
            && $this->BConfig->get('modules/FCom_PushServer/newreview_realtime_notification')
        ) {
            $this->FCom_PushServer_Model_Channel->getChannel('reviews_feed', true)->send([
                    'signal' => 'new_review',
                    'review' => [
                        'href' =>  'prodreviews/form/?id=' . $this->id(),
                        'text' => $this->BLocale->_('%s has review the product %s', [ $customer->firstname . ' ' . $customer->lastname, '#' .$this->id()])
                    ],
                ]);
        }
        return $this;
    }

    public function helpful($mark)
    {
        $this->add('helpful', $mark);
        $this->add('helpful_voices');
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
