<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Workflow_Comment
 *
 * @property Sellvana_Sales_Model_Order_Comment $Sellvana_Sales_Model_Order_Comment
 */

class Sellvana_Sales_Workflow_Comment extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_customerPostsOrderComment($args)
    {
        $this->Sellvana_Sales_Model_Order_Comment->create([
            'order_id' => $args['order']->id(),
            'comment_text' => $args['comment_text'],
            'from_admin' => 0,
            'is_internal' => 0,
        ])->save();
        $args['order']->state()->comment()->setReceived();
        $args['order']->save();
    }

    public function action_adminAcknowledgesOrderComment($args)
    {
        $args['order']->state()->comment()->setProcessing();
        $args['order']->save();

        if (!empty($args['comment'])) {
            $comment = $args['comment'];
        } else {
            $comment = $args['order']->getLastCustomerComment();
        }
        $comment->set('user_id', $args['user']->id())->save();
    }

    public function action_adminDelegatesOrderComment($args)
    {
        $this->Sellvana_Sales_Model_Order_Comment->create([
            'order_id' => $args['order']->id(),
            'comment_text' => $args['comment_text'],
            'from_admin' => 1,
            'is_internal' => 1,
            'user_id' => $args['user']->id(),
        ])->save();
        $args['order']->state()->comment()->setDelegated();
        $args['order']->save();
    }

    public function action_adminPostsOrderComment($args)
    {
        $this->Sellvana_Sales_Model_Order_Comment->create([
            'order_id' => $args['order']->id(),
            'comment_text' => $args['comment_text'],
            'from_admin' => 1,
            'is_internal' => $args['is_internal'],
            'user_id' => $args['user']->id(),
        ])->save();
        $args['order']->state()->comment()->setSent();
        $args['order']->save();
    }

    public function action_adminClosesOrderComment($args)
    {
        $args['order']->state()->commment()->setClosed();
        $args['order']->save();
    }

    public function action_timedAutoCloseOrderComment($args)
    {
        $args['order']->state()->comment()->setAutoClosed();
        $args['order']->save();
    }

}
