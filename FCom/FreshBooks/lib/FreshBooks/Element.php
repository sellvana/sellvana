<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks API Element Class
 *
 *
 * @package    FreshBooks

 * @copyright  Milan Rukavina, rukavinamilan@gmail.com
 * @version    1.0
 */

include_once 'HttpClient.php';
/**
 * An abstract class representing a an XML data block
 */
abstract class FreshBooks_Element
{
/**
 * main xml tag name for particular instance
 */ 
	protected $_elementName = "";
	
/**
 * holds last error text
 */
	public $lastError = ""; 
	
/**
 * loads XML string
 */ 	
	public function loadXML($xml){
		$this->_internalLoadXML(simplexml_load_string($xml));
	}
	
/**
 * internal hook to be implemented in child classes with particular logic how to populate object properties from xml
 */ 	
	abstract protected function _internalLoadXML(&$XMLObject);	
		
/**
 * construct simple xml element
 */ 	
	protected function _getTagXML($tag,$value,$excludeIfEmpty = true){
		if($value == "" && $excludeIfEmpty)
			return "";
			
		$result = "<$tag>$value</$tag>";
		return $result;
	}
	
/**
 * add common tags to a request 
 */  	
	protected function _requestEnvelope($content,$methodName){
		$result = '<?xml version="1.0" encoding="utf-8"?><request method="' . $this->_elementName . '.' . $methodName . '">' . $content . '</request>';

		return $result;
	}

/**
 * send request to the server
 */ 	
	protected function _sendRequest($content,$methodName){
		//reset error
		$this->lastError = "";
		
		$requestXML = $this->_requestEnvelope($content,$methodName);
		$resultXML = FreshBooks_HttpClient::getInstance()->send($requestXML);
		if($resultXML === false){
			return false;
		}
		else
			return simplexml_load_string($resultXML);
	}
	
/**
 * processes response xml
 */ 	
	protected function _processResponse(&$XMLObject){
		if($XMLObject === false){
			$this->lastError = FreshBooks_HttpClient::getInstance()->getLastError();
			return false;
		}
				
		$result = (string)$XMLObject["status"] == "ok";
		if(!$result)
			$this->lastError = (string)$XMLObject->error;
		return $result;
	}
}
