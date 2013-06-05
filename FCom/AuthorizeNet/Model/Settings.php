<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_AuthorizeNet_Model_Settings
{
    protected static $gatewayUrl = "https://secure.authorize.net/gateway/transact.dll";
    public static function paymentActions()
    {
        return array(
            "authorize"         => BLocale::i()->_("Authorize Only"),
            "authorize_capture" => BLocale::i()->_("Authorize and Capture")
        );
    }

    public static function cardTypes()
    {
        return array(
            "AE" => "American Express",
            "VI" => "Visa",
            "MC" => "MasterCard",
            "DI" => "Discover",
            "OT" =>  BLocale::i()->_("Other")
        );
    }

    public static function countries()
    {
        $countries = array();
        foreach (FCom_Geo_Model_Country::options() as $iso => $name) {
            if (empty($iso)) {
                continue;
            }
            $countries[$iso] = $name;
        }

        return $countries;
    }

    public static function currencies()
    {
        // todo - update to be dynamically built
        return array(
            "-"   => "-- Select One --",
            "usd" => "USD"
        );
    }

    public static function orderStatuses()
    {
        // todo - update to be dynamically built
        return array(
            "-"          => "-- Select One --",
            "processing" => "Processing"
        );
    }

    /**
     * @param BConfig $config
     * @return string
     */
    public static function gatewayUrl($config)
    {
        $url = static::$gatewayUrl;
        if ($config->get('modules/FCom_AuthorizeNet/cgi_url')) {
            $url = $config->get('modules/FCom_AuthorizeNet/cgi_url');
        }
        return $url;
    }
}