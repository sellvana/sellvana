<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks API ElementAction Class
 *
 *
 * @package    FreshBooks

 * @copyright  Milan Rukavina, rukavinamilan@gmail.com
 * @version    1.0
 */

include 'Element.php';

/**
 * An abstract class representing a an XML data block, with create/update/delete/get/list actions
 */
abstract class FreshBooks_ElementAction extends FreshBooks_Element
{
	
/**
 * create remote element
 */   	 	
	public function create(){
		$this->_internalPrepareCreate($content);
		$responseXML = $this->_sendRequest($content,"create");
		$responseStatus = $this->_processResponse($responseXML);
		$this->_internalCreate($responseStatus,$responseXML);
		return $responseStatus;
	}
/**
 * internal hook to be implemented in child classes with particular logic how to generate xml request for create request
 */ 	
	abstract protected function _internalPrepareCreate(&$content);
/**
 * internal hook to be implemented in child classes with particular logic how to process xml response object for create
 */
	abstract protected function _internalCreate($responseStatus,&$XMLObject);
/**
 * update remote element
 */   	 	
	public function update(){
		$this->_internalPrepareCreate($content);
		$responseXML = $this->_sendRequest($content,"update");
		$responseStatus = $this->_processResponse($responseXML);
		$this->_internalCreate($responseStatus,$responseXML);
		return $responseStatus;
	}
/**
 * internal hook to be implemented in child classes with particular logic how to generate xml request for update request
 */ 
	abstract protected function _internalPrepareUpdate(&$content);
/**
 * internal hook to be implemented in child classes with particular logic how to process xml response object for update
 */
	abstract protected function _internalUpdate($responseStatus,&$XMLObject);
	
/**
 * get remote element
 */   	 	
	public function get($id){
		$this->_internalPrepareGet($id,$content);
		$responseXML = $this->_sendRequest($content,"get");
		$responseStatus = $this->_processResponse($responseXML);
		$this->_internalGet($responseStatus,$responseXML);
		return $responseStatus;
	}
/**
 * internal hook to be implemented in child classes with particular logic how to generate xml request for get request
 */ 
	abstract protected function _internalPrepareGet($id,&$content);
/**
 * internal hook to be implemented in child classes with particular logic how to process xml response object for get
 */
	abstract protected function _internalGet($responseStatus,&$XMLObject);
	
/**
 * delete remote element
 */   	 	
	public function delete(){
		$this->_internalPrepareDelete($content);
		$responseXML = $this->_sendRequest($content,"delete");
		$responseStatus = $this->_processResponse($responseXML);
		$this->_internalDelete($responseStatus,$responseXML);
		return $responseStatus;
	}
/**
 * internal hook to be implemented in child classes with particular logic how to generate xml request for delete request
 */ 
	abstract protected function _internalPrepareDelete(&$content);
/**
 * internal hook to be implemented in child classes with particular logic how to process xml response object for delete
 */
	abstract protected function _internalDelete($responseStatus,&$XMLObject);
	
/**
 * list/search remote elements
 */   	 	
	public function listing(&$rows,&$resultInfo,$page = 1,$perPage = 25, $filters = array()){
		$content = $this->_getTagXML("page",$page) . $this->_getTagXML("per_page",$perPage);
		$this->_internalPrepareListing($filters,$content);
		$responseXML = $this->_sendRequest($content,"list");
		$responseStatus = $this->_processResponse($responseXML);
		$this->_internalListing($responseStatus,$responseXML,$rows,$resultInfo);
		return $responseStatus;
	}
/**
 * internal hook to be implemented in child classes with particular logic how to process xml response object for list
 */	
	abstract protected function _internalPrepareListing($filters,&$content);
/**
 * internal hook to be implemented in child classes with particular logic how to process xml response object for list
 */
	abstract protected function _internalListing($responseStatus,&$XMLObject,&$rows,&$resultInfo);		
}
