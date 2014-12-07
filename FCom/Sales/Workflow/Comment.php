<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Workflow_Comment
 *
 * @property FCom_Sales_Model_Order_Comment $FCom_Sales_Model_Order_Comment
 */

class FCom_Sales_Workflow_Comment extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerPostsOrderComment',

        'adminAcknowledgesOrderComment',
        'adminDelegatesOrderComment',
        'adminPostsOrderComment',
        'adminClosesOrderComment',

        'timedAutoCloseOrderComment',
    ];

    public function customerPostsOrderComment($args)
    {
        $this->FCom_Sales_Model_Order_Comment->create([
            'order_id' => $args['order']->id(),
            'comment_text' => $args['comment_text'],
            'from_admin' => 0,
            'is_internal' => 0,
        ])->save();
        $args['order']->state()->comment()->setReceived();
        $args['order']->save();
    }

    public function adminAcknowledgesOrderComment($args)
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

    public function adminDelegatesOrderComment($args)
    {
        $this->FCom_Sales_Model_Order_Comment->create([
            'order_id' => $args['order']->id(),
            'comment_text' => $args['comment_text'],
            'from_admin' => 1,
            'is_internal' => 1,
            'user_id' => $args['user']->id(),
        ])->save();
        $args['order']->state()->comment()->setDelegated();
        $args['order']->save();
    }

    public function adminPostsOrderComment($args)
    {
        $this->FCom_Sales_Model_Order_Comment->create([
            'order_id' => $args['order']->id(),
            'comment_text' => $args['comment_text'],
            'from_admin' => 1,
            'is_internal' => $args['is_internal'],
            'user_id' => $args['user']->id(),
        ])->save();
        $args['order']->state()->comment()->setSent();
        $args['order']->save();
    }

    public function adminClosesOrderComment($args)
    {
        $args['order']->state()->commment()->setClosed();
        $args['order']->save();
    }

    public function timedAutoCloseOrderComment($args)
    {
        $args['order']->state()->comment()->setAutoClosed();
        $args['order']->save();
    }

}
