<?php

class Sellvana_Catalog_AdminSPA_Controller_QuickAdd_Products extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    public function action_config()
    {
        $config = [
            'add_new_options' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20],
            'categories' => [],
            'csrf_token' => $this->BSession->csrfToken(),
            'dropzone_options' => [
                'url' => $this->BApp->href('quickadd/products/upload'),
                'acceptedFileTypes' => 'image/*',
//                'thumbnailHeight' => 32,
//                'thumbnailWidth' => 32,
                'maxFileSizeInMB' => 10,
                'autoProcessQueue' => true,
            ],
        ];
        $this->respond($config);
    }

    public function action_index__POST()
    {
        //create products
        $this->respond($this->BRequest->request());
    }

    public function action_upload__POST()
    {
        $this->respond($this->BRequest->request());
    }
}