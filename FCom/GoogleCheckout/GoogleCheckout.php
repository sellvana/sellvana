<?php

class FCom_GoogleCheckout extends BClass
{
    const MODE_SANDBOX = 'sandbox';
    const MODE_PROD    = 'production';
    protected static $_origClass = __CLASS__;

    /**
     * Google Checkout config
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $sizes;

    /**
     * @var GoogleCart
     */
    protected $gCart;

    /**
     * @param bool  $new
     * @param array $args
     * @return FCom_GoogleCheckout
     */
    public static function i($new = false, array $args = array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function getFormUrl()
    {
        $conf = $this->getConfig();
        $mode = $this->getMode($conf);
        $url  = "https://" . $conf[$mode]['url'] . $conf[$mode]['merchant_id'];
        return $url;
    }

    public function getCartValueEncoded()
    {
        $gCart = $this->prepareCart();
        return base64_encode($gCart->GetXML());
    }

    public function getSignatureValueEncrypted()
    {
        $gCart = $this->prepareCart();
        return base64_encode($gCart->CalcHmacSha1($gCart->GetXML()));
    }

    public function getButtonSrc()
    {
        $conf = $this->getConfig();
        $src  = $this->getBaseBtnSrc($conf);
        if (!empty($src)) {
            $w     = $this->getButtonWidth($conf);
            $h     = $this->getButtonHeight($conf);
            $style = $this->getButtonStyle($conf);
            $loc   = $this->getButtonLoc($conf);
            $merchant_id = $conf[$this->getMode($conf)]['merchant_id'];
            $variant = 'text';
            $req = http_build_query(compact('merchant_id', 'w', 'h', 'style', 'variant', 'loc'));
            $src = "http://" . $src . '?' . $req;
        }
        return $src;
    }

    public function getButtonHeight($conf = null)
    {
        if(null == $conf){
            $conf = $this->getConfig();
        }
        $this->parseButtonSize($conf);
        $size = $conf['button']['size'];
        return $this->sizes[$size]['h'];
    }

    public function getButtonWidth($conf = null)
    {
        if(null == $conf){
            $conf = $this->getConfig();
        }
        $this->parseButtonSize($conf);
        $size = $conf['button']['size'];
        return $this->sizes[$size]['w'];
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param $conf
     * @return string
     * @throws DomainException
     */
    protected function getMode($conf)
    {
        $mode = $conf[self::MODE_SANDBOX]['mode'] == 'on' ? self::MODE_SANDBOX : self::MODE_PROD;
        if (empty($conf[$mode]['merchant_id'])) {
            throw new DomainException("Merchant id for '$mode' mode is not setup.");
        }
        return $mode;
    }

    protected function getBaseBtnSrc($conf)
    {
        $mode = $this->getMode($conf);
        $alt  = $mode == self::MODE_PROD ? self::MODE_SANDBOX : self::MODE_PROD;

        if (isset($conf[$mode]['button_url'])) {
            return $conf[$mode]['button_url'];
        } else if (!isset($conf[$mode]['button_url']) && isset($conf[$alt]['button_url'])) {
            return $conf[$alt]['button_url'];
        }
        return '';
    }

    protected function getButtonStyle($conf)
    {
        $style = $conf['button']['style']?:'white';
        return $style;

    }

    protected function getButtonLoc($conf)
    {
        $loc = $conf['button']['loc']?:'en_US';
        return $loc;
    }

    /**
     * @param $conf
     * @return FCom_GoogleCheckout
     */
    protected function parseButtonSize($conf)
    {
        $size = $conf['button']['size'];
        if(isset($this->sizes[$size])){
            return $this;
        }
        list($w, $h) = explode('x', $size, 2);
        $this->sizes[$size] = array(
            'w' => $w,
            'h' => $h
        );
        return $this;
    }

    /**
     * @return GoogleCart|null
     */
    protected function prepareCart()
    {
        if (!$this->gCart) {
            /* @var $cart FCom_Sales_Model_Cart */
            $cart = FCom_Sales_Model_Cart::sessionCart();
            /* @var $salesOrder FCom_Sales_Model_Order */
            $salesOrder = FCom_Sales_Model_Order::i()->load($cart->id(), 'cart_id');
            if (!$salesOrder) {
                return null;
            }

            require_once "lib/googlecart.php";

            $conf = $this->getConfig();
            $mode = $this->getMode($conf);
            $id   = $conf[$mode]['merchant_id'];
            $key  = $conf[$mode]['merchant_key'];
            // todo find used currency!!!
            $gCart    = new GoogleCart($id, $key, $mode);
            $cartHref = BApp::href('cart');
            $gCart->SetEditCartUrl($cartHref);
            $gCart->SetContinueShoppingUrl(BApp::href('/'));

            $items = $salesOrder->items();

            if (!empty($items)) {
                require_once "lib/googleitem.php";
                foreach ($items as $item) {
                    // todo add $item to $gCart
                    // use merchant-private-item-data and merchant_item_id
                    // to add stuff like product sku for example
                }

            }

            $shippingMethods = FCom_Sales_Main::i()->getShippingMethods();

            if (!empty($shippingMethods)) {
                require_once "lib/googleshipping.php";
                foreach ($shippingMethods as $shippingMethod) {
                    // todo add shipping methods to gcart
                    /*
                     * Checkout API requests may not mix a merchant-calculated shipping option with either
                     * flat-rate shipping or pickup shipping options.
                     * However, you may offer more than one merchant-calculated shipping option. You may also offer
                     * flat-rate and pickup shipping options together as long as they are not combined with
                     * merchant-calculated shipping options.
                     */
                }
            }
            $this->gCart = $gCart;
        }

        return $this->gCart;
    }
}