<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Catalog_ApiServer_V1_Category extends FCom_ApiServer_Controller_Abstract
{
    //protected $_authorizeActionsWhitelist = array('Put');

    public function action_index()
    {
        $id = $this->BRequest->param('id');
        $len = $this->BRequest->get('len');
        if (!$len) {
            $len = 10;
        }
        $start = $this->BRequest->get('start');
        if (!$start) {
            $start = 0;
        }

        if ($id) {
            $categories[] = $this->FCom_Catalog_Model_Category->load($id);
        } else {
            $categories = $this->FCom_Catalog_Model_Category->orm()->limit($len, $start)->find_many();
        }
        if (empty($categories)) {
            $this->ok();
        }
        $result = $this->FCom_Catalog_Model_Category->prepareApiData($categories);
        $this->ok($result);
    }

    public function action_index__POST()
    {
        $post = $this->BUtil->fromJson($this->BRequest->rawPost());

        if (!empty($post['parent_id'])) {
            $category = $this->FCom_Catalog_Model_Category->load($post['parent_id']);
        } else {
            $category = $this->FCom_Catalog_Model_Category->orm()->where_null('parent_id')->find_one();
        }
        if (!$category) {
            $this->notFound("Parent category id #{$post['parent_id']} do not found");
        }

        $res = $category->createChild($post['name']);
        if (!$res) {
            $this->badRequest("Incorrect data provided");
        }
        $this->created(['id' => $res->id]);
    }

    public function action_index__PUT()
    {
        $id = $this->BRequest->param('id');
        $post = $this->BUtil->fromJson($this->BRequest->rawPost());

        if (empty($id)) {
            $this->notFound("Category id is required");
        }

        if (empty($post['parent_id']) && empty($post['name'])) {
            $this->badRequest("Missing parameters. Use any of the following parameters: parent_id or name to move or rename category");
        }

        $category = $this->FCom_Catalog_Model_Category->load($id);
        if (!$category) {
            $this->notFound("Category id #{$id} not found");
        }

        if (!empty($post['name']) && $category->node_name != $post['name']) {
            $category->rename($post['name']);
        }
        if (!empty($post['parent_id']) && $category->parent_id != $post['parent_id']) {
            try {
                $category->move($post['parent_id']);
            } catch (Exception $e) {
                $this->internalError($e->getMessage());
            }
        }
        $category->cacheSaveDirty();
        $this->ok();
    }

    public function action_index__DELETE()
    {
        $id = $this->BRequest->param('id');

        if (empty($id)) {
            $this->notFound("Category id is required");
        }

        $category = $this->FCom_Catalog_Model_Category->load($id);
        if (!$category) {
            $this->notFound("Category id #{$id} not found");
        }

        if ($id < 2) {
            $this->badRequest("Can't remove root");
        }
        try {
            $category->delete();
        } catch (Exception $e) {
            $this->badRequest($e->getMessage());
        }
        $this->ok();
    }


}
