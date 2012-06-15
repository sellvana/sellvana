<?php

class FCom_Wishlist_Model_Wishlist extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_wishlist';
    protected static $_origClass = __CLASS__;

    protected $items = null;
    protected $wishlist = null;

    public function wishlist()
    {
        $user = FCom_Customer_Model_Customer::sessionUser();
        if(!$user){
            return;
        }
        if (!$this->wishlist) {
            $wishlist = static::i()->load(array("user_id", $user->id));
            if (!$wishlist) {
                $this->orm()->create()->set("user_id", $user->id())->save();
                $wishlist = static::i()->load(array("user_id", $user->id));
            }

            $this->wishlist = $wishlist;
        }
        return $this->wishlist;
    }

    public function items($refresh=false)
    {
        if (is_null($this->items) || $refresh) {
            $this->items = FCom_Wishlist_Model_WishlistItem::factory()->where('wishlist_id', $this->id)->find_many_assoc();
        }
        return $this->items;
    }

    public function addItem($productId)
    {
        $item = FCom_Wishlist_Model_WishlistItem::i()->load(array('wishlist_id'=>$this->id, 'product_id'=>$productId));
        if (!$item) {
            $item = FCom_Wishlist_Model_WishlistItem::i()->orm()->create();
            $item->set('wishlist_id', $this->id())
                    ->set('product_id', $productId);
            $item->save();
        }

        return $this;
    }

    public function removeItem($itemId)
    {
        if (is_numeric($itemId)) {
            $this->items();
            $item = $this->childById('items', $itemId);
        }
        if ($item) {
            unset($this->items[$item->id()]);
            $item->delete();
        }
        return $this;
    }

    public static function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}