<?php

class FCom_Promo_Model_Promo extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo';
    protected static $_fieldOptions = array(
        'buy_type' => array(
            'qty' => 'Quantity',
            '$' => '$ AMT',
        ),
        'buy_group' => array(
            'one' => 'Single Group',
            'any' => 'ANY Group',
            'all' => 'ALL Groups',
        ),
        'get_type' => array(
            'qty' => 'Quantity',
            '$' => '$ AMT',
            '%' => '% OFF',
        ),
        'get_group' => array(
            'same_prod' => 'Same Product',
            'same_group' => 'Same Group',
            'any_group' => 'Any Group',
            'diff_group' => 'Different Group',
        ),
        'status' => array(
            'template' => 'Template',
            'pending' => 'Pending',
            'active' => 'Active',
            'expired' => 'Expired',
        ),
    );

    public function manuf()
    {
        //todo: load vendors here
    }

    public function groups()
    {
        return FCom_Promo_Model_Group::i()->orm()
            ->where('promo_id', $this->id)
            ->order_by_asc('group_type')
            ->find_many_assoc();
    }

    public function mediaORM()
    {
        return FCom_Promo_Model_Media::i()->orm()->table_alias('pa')
            ->where('pa.promo_id', $this->id)
            ->select(array('pa.manuf_vendor_id', 'pa.promo_status'))
            ->join('FCom_Core_Model_MediaLibrary', array('a.id','=','pa.file_id'), 'a')
            ->select(array('a.id', 'a.file_name', 'a.file_size'));
    }

    public function media()
    {
        return $this->mediaORM()->find_many_assoc();
    }

    public function createClone()
    {
        $grHlp = FCom_Promo_Model_Group::i();
        $prodHlp = FCom_Promo_Model_Product::i();
        $attHlp = FCom_Promo_Model_Media::i();
        $clone = static::i()->create($this->as_array())->set(array(
            'id'=>'null',
            'status'=>'pending',
        ))->save();
        foreach ($this->groups() as $gr) {
            $clGr = $grHlp->create($gr->as_array())->set(array(
                'id' => null,
                'promo_id' => $clone->id,
            ))->save();
            foreach ($gr->products() as $gp) {
                $clProd = $prodHlp->create($gp->as_array())->set(array(
                    'id' => null,
                    'promo_id' => $clone->id,
                    'group_id' => $clGr->id,
                ))->save();
            }
        }
        foreach ($this->media() as $att) {
            $attHlp->create($att->as_array())->set(array(
                'id' => null,
                'promo_id' => $clone->id,
            ))->save();
        }
        return $clone;
    }

    public function afterCreate()
    {
        parent::afterCreate();
        $this->from_date = gmdate('Y-m-d');
        $this->to_date = gmdate('Y-m-d', time()+30*86400);
        $this->status = 'pending';
    }

    public function afterSave()
    {
        parent::afterSave();

        $groups = array();
        if (!$this->_newRecord) {
            $groupsRaw = FCom_Promo_Model_Group::i()->orm()->where('promo_id', $this->id)->find_many();
            foreach ($groupsRaw as $g) {
                $groups[$g->group_type][] = $g;
            }
        }
        $delete = array();
        if (empty($groups['buy'])) {
            FCom_Promo_Model_Group::i()->create(array(
                'promo_id' => $this->id,
                'group_type' => 'buy',
                'group_name' => 'BUY Group',
            ))->save();
        } elseif ($this->buy_group==='one' && sizeof($groups['buy'])>1) {
            foreach ($groups['buy'] as $i=>$g) {
                if ($i) $delete[] = $g->id;
            }
        }
        if (empty($groups['get']) && $this->get_group==='diff_group') {
            FCom_Promo_Model_Group::i()->create(array(
                'promo_id' => $this->id,
                'group_type' => 'get',
                'group_name' => 'GET Group',
            ))->save();
        } elseif (!empty($groups['get']) && $this->get_group!=='diff_group') {
            $delete[] = $groups['get'][0]->id;
        }
        if (!empty($delete)) {
            FCom_Promo_Model_Group::i()->delete_many(array('id'=>$delete));
        }
    }

    public function getActive()
    {
        return self::orm()->where('status', 'active')->find_many();
    }
}