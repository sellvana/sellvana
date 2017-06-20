<?php

class Sellvana_ProductReviews_AdminSPA_Dashboard extends BClass
{
    public function widgetNewReviews($filter)
    {
        $reviews = $this->Sellvana_ProductReviews_Admin_Dashboard->getLatestProductReviews();
        return [
            'reviews' => $this->BDb->many_as_array($reviews),
        ];
    }
}