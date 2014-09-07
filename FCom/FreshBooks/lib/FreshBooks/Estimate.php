<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks Estimate Class
 *
 *
 * @package    FreshBooks

 * @copyright  Milan Rukavina, rukavinamilan@gmail.com
 * @version    1.0
 */

include_once 'BaseInvoice.php';
/**
 * Class representing invoice API 
 */
class FreshBooks_Estimate extends FreshBooks_BaseInvoice 
{
	protected $_elementName = "estimate";
	
	public $estimateId = "";
	
/**
 * return XML content
 */	
	protected function _internalXMLContent()
	{
		$content =
							$this->_getTagXML("estimate_id",$this->estimateId) .							
							parent::_internalXMLContent();
							
		return $content;
		
	}
	
/**
 * load obect properties from SimpleXML object
 */	
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->estimateId = (string)$XMLObject->estimate_id;		
		parent::_internalLoadXML($XMLObject);
	}
		
/**
 * prepare XML string request for CREATE server method
 */		
	protected function _internalCreate($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			$this->estimateId = (string)$XMLObject->estimate_id;
		}
	}	
	
/**
 * prepare XML string request for GET server method
 */ 	
	protected function _internalPrepareGet($id,&$content)
	{
		$content = $this->_getTagXML("estimate_id",$id);
	}
	
/**
 * process XML string response from GET server method
 */		
	protected function _internalGet($responseStatus,&$XMLObject)
	{
		if($responseStatus)
			$this->_internalLoadXML($XMLObject->estimate);
	}

/**
 * prepare XML string request for DELETE server method
 */			
	protected function _internalPrepareDelete(&$content)
	{
		$content = $this->_getTagXML("estimate_id",$this->estimateId);
	}
	
/**
 * process XML string response from DELETE server method
 */	
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		parent::_internalDelete($responseStatus,$XMLObject);
		if($responseStatus){
			unset($this->estimateId);
		}
	}
	
/**
 * process XML string response from LIST server method
 */	 	
	protected function _internalListing($responseStatus,&$XMLObject,&$rows,&$resultInfo)
	{
		$rows = array();
		$resultInfo = array();
		
		$estimates = $XMLObject->estimates;
		$resultInfo['page'] = (string)$estimates['page'];
		$resultInfo['perPage'] = (string)$estimates['per_page'];
		$resultInfo['pages'] = (string)$estimates['pages'];
		$resultInfo['total'] = (string)$estimates['total'];

		foreach ($estimates->children() as $key=>$currXML){
			$thisEstimates = new FreshBooks_Estimate();
			$thisEstimates->_internalLoadXML($currXML);
			$rows[] = $thisEstimates;
		}
	}
	
/**
 * prepare XML string request for SENDBYEMAIL server method
 */		
	protected function _internalPrepareSendByEmail(&$content)
	{
		$content = $this->_getTagXML("estimate_id",$this->estimateId);
	}

/**
 * process XML string response from SENDBYEMAIL server method
 */		
	protected function _internalSendByEmail($responseStatus,&$XMLObject)
	{
		//
	}
}
