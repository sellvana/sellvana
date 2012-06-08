<?php

class FCom_Checkout extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /cart', 'FCom_Checkout_Frontend_Controller.cart')
            ->route('POST /cart', 'FCom_Checkout_Frontend_Controller.cart_post')

            //checkout
            ->route( 'GET /checkout', 'FCom_Checkout_Frontend_Controller_Checkout.checkout')
            ->route( 'POST /checkout', 'FCom_Checkout_Frontend_Controller_Checkout.checkout_post')

            //payment
            ->route( 'GET /checkout/payment', 'FCom_Checkout_Frontend_Controller_Checkout.payment')
            ->route( 'POST /checkout/payment', 'FCom_Checkout_Frontend_Controller_Checkout.payment_post')

            //payment
            ->route( 'GET /checkout/shipping', 'FCom_Checkout_Frontend_Controller_Checkout.shipping')
            ->route( 'POST /checkout/shipping', 'FCom_Checkout_Frontend_Controller_Checkout.shipping_post')

            //shipping address
            ->route( 'GET /checkout/address/shipping', 'FCom_Checkout_Frontend_Controller_Address.shipping')
            ->route('POST /checkout/address/shipping', 'FCom_Checkout_Frontend_Controller_Address.shipping_post')
            //billing address
            ->route( 'GET /checkout/address/billing', 'FCom_Checkout_Frontend_Controller_Address.billing')
            ->route('POST /checkout/address/billing', 'FCom_Checkout_Frontend_Controller_Address.billing_post')
        ;

        //merge cart sessions after user login
        BPubSub::i()->on('FCom_Customer_Frontend_Controller::action_login', 'FCom_Checkout_Model_Cart::userLogin');

        BLayout::i()->addAllViews('Frontend/views')
                ->afterTheme('FCom_Checkout::layout');
    }
    static public function layout()
    {
        BLayout::i()->layout(array(
            '/checkout/cart'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/cart'))
            ),
            '/checkout/checkout'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/checkout'))
            ),
            '/checkout/payment'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/payment'))
            ),
            '/checkout/shipping'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/shipping'))
            ),
            '/checkout/address/shipping'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/address/shipping'))
            ),
            '/checkout/address/billing'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/address/billing'))
            ),
        ));
    }

    public function getShippingMethods()
    {
        return array(new stdClass());
    }

    public function install()
    {
        /*
         * CREATE TABLE IF NOT EXISTS `fcom_cart` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned DEFAULT NULL,
  `location_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `item_qty` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `item_num` smallint(6) unsigned NOT NULL DEFAULT '0',
  `subtotal` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `session_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `NewIndex1` (`session_id`),
  UNIQUE KEY `user_id` (`user_id`,`description`,`session_id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `fcom_cart_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `qty` decimal(12,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_id` (`cart_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `fcom_cart_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `can_admin` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `can_edit` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `can_share` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `can_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_id` (`cart_id`,`user_id`),
  KEY `FK_a_cart_user_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `fcom_cart_user`
  ADD CONSTRAINT `FK_fcom_cart_user_cart` FOREIGN KEY (`cart_id`) REFERENCES `fcom_cart` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_fcom_cart_user_customer` FOREIGN KEY (`user_id`) REFERENCES `fcom_customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
         */
    }
}

