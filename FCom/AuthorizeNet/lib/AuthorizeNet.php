<?php
/**
 * The AuthorizeNet PHP SDK. Include this file in your project.
 *
 * @package AuthorizeNet
 */
require __DIR__ . '/shared/AuthorizeNetRequest.php';
require __DIR__ . '/shared/AuthorizeNetTypes.php';
require __DIR__ . '/shared/AuthorizeNetXMLResponse.php';
require __DIR__ . '/shared/AuthorizeNetResponse.php';
require __DIR__ . '/AuthorizeNetAIM.php';
require __DIR__ . '/AuthorizeNetARB.php';
require __DIR__ . '/AuthorizeNetCIM.php';
require __DIR__ . '/AuthorizeNetSIM.php';
require __DIR__ . '/AuthorizeNetDPM.php';
require __DIR__ . '/AuthorizeNetTD.php';
require __DIR__ . '/AuthorizeNetCP.php';

if (class_exists("SoapClient")) {
    require __DIR__ . '/AuthorizeNetSOAP.php';
}
/**
 * Exception class for AuthorizeNet PHP SDK.
 *
 * @package AuthorizeNet
 */
class AuthorizeNetException extends Exception
{
}