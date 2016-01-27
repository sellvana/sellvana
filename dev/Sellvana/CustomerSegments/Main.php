<?php

/**
 * Class Sellvana_CustomerSegments_Main
 *
 * @property Sellvana_CustomerSegments_Model_SegmentCustomer $Sellvana_CustomerSegments_Model_SegmentCustomer
 */
class Sellvana_CustomerSegments_Main extends BClass
{
    public function onCustomerBeforeSave($args)
    {
        //$defCustSegment = $this->BConfig->get('modules/Sellvana_CustomerSegments/default_segment_id');

        $newIds = $args['model']->get('segment_ids');
        if (null !== $newIds) {
            $newIds = $this->BUtil->arraycleanInt($newIds);

            $this->Sellvana_CustomerSegments_Model_SegmentCustomer
                ->updateManyToManyIds($args['model'], 'customer_id', 'segment_id', $newIds);
        }
    }
}