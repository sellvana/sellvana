<?php

/**
 * Class FCom_AdminSPA_AdminSPA_Controller_Favorites
 *
 * @property FCom_Admin_Model_Favorite FCom_Admin_Model_Favorite
 */
class FCom_AdminSPA_AdminSPA_Controller_Favorites extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{

    public function action_add__POST()
    {
        $result = [];
        try {
            $post = $this->BRequest->post();
            if (empty($post['label']) || empty($post['link'])) {
                throw new BException('Invalid request');
            }
            $userId = $this->FCom_Admin_Model_User->sessionUserId();
            $data = [
                'user_id' => $userId,
                'label' => $post['label'],
                'link' => $post['link'],
            ];
            $custData = $this->BUtil->arrayMask($post, ['label', 'link'], true);
            $this->FCom_Admin_Model_Favorite->create($data)->setData($custData)->save();
            $this->addResponses(['_ok' => true]);
        } catch (Exception $e) {
            $this->addResponses(['_messages' => [
                ['type' => 'error', 'message' => $e->getMessage()],
            ]]);
        }
        $this->respond($result);
    }

    public function action_remove__POST()
    {
        $result = [];
        try {
            $post = $this->BRequest->post();
            if (empty($post['link'])) {
                throw new BException('Invalid request');
            }
            $userId = $this->FCom_Admin_Model_User->sessionUserId();
            $fav = $this->FCom_Admin_Model_Favorite->orm()
                ->where('user_id', $userId)->where('link', $post['link'])->find_one();
            if (!$fav) {
                throw new BException('Favorite not found');
            }
            $fav->delete();
            $this->addResponses(['_ok' => true]);
        } catch (Exception $e) {
            $this->addResponses(['_messages' => [
                ['type' => 'error', 'message' => $e->getMessage()],
            ]]);
        }
        $this->respond($result);
    }
}