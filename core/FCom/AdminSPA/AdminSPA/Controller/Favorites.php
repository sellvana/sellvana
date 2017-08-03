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
                static::LABEL => $post['label'],
                static::LINK => $post['link'],
            ];
            $custData = $this->BUtil->arrayMask($post, ['label', 'link'], true);
            $this->FCom_Admin_Model_Favorite->create($data)->setData($custData)->save();
            $this->ok();
        } catch (Exception $e) {
            $this->addMessage($e);

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
            $this->ok();
        } catch (Exception $e) {
            $this->addMessage($e);

        }
        $this->respond($result);
    }
}