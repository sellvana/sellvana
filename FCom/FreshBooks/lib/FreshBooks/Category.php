<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks Category Class
 *
 *
 * @package    FreshBooks

 * @copyright  Milan Rukavina, rukavinamilan@gmail.com
 * @version    1.0
 */

include_once 'ElementAction.php';
include_once 'Element/Interface.php';
include_once 'ElementAction/Interface.php';

/**
 * Class representing category API 
 */
class FreshBooks_Category extends FreshBooks_ElementAction implements FreshBooks_Element_Interface,FreshBooks_ElementAction_Interface
{
	protected $_elementName = "category";
	
	public $categoryId = "";
	public $name = "";
	public $tax1 = "";
	public $tax2 = "";	
	
/**
 * return XML string
 */ 	
	public function asXML()
	{
		$content =
							$this->_getTagXML("category_id",$this->categoryId) .
							$this->_getTagXML("name",$this->name) .
							$this->_getTagXML("tax1",$this->tax1) .
							$this->_getTagXML("tax2",$this->tax2);
							
		return $this->_getTagXML("category",$content);
		
	}
	
/**
 * load obect properties from SimpleXML object
 */ 	
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->categoryId = (string)$XMLObject->category_id;
		$this->name = (string)$XMLObject->name;
		$this->tax1 = (string)$XMLObject->tax1;		
		$this->tax2 = (string)$XMLObject->tax2;
	}
	
/**
 * prepare XML string request for CREATE server method
 */	
	protected function _internalPrepareCreate(&$content)
	{
		$content = $this->asXML();
	}
	
/**
 * process XML string response from CREATE server method
 */		
	protected function _internalCreate($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			$this->categoryId = (string)$XMLObject->category_id;
		}
	}
	
/**
 * prepare XML string request for UPDATE server method
 */	
	protected function _internalPrepareUpdate(&$content)
	{
		$content = $this->asXML();
	}
	
/**
 * process XML string response from UPDATE server method
 */		
	protected function _internalUpdate($responseStatus,&$XMLObject)
	{
		//
	}
	
/**
 * prepare XML string request for GET server method
 */ 	
	protected function _internalPrepareGet($id,&$content)
	{
		$content = $this->_getTagXML("category_id",$id);
	}
	
/**
 * process XML string response from GET server method
 */		
	protected function _internalGet($responseStatus,&$XMLObject)
	{
		if($responseStatus)
			$this->_internalLoadXML($XMLObject->category);
	}
	
/**
 * prepare XML string request for DELETE server method
 */	
	protected function _internalPrepareDelete(&$content)
	{
		$content = $this->_getTagXML("category_id",$this->categoryId);
	}
	
/**
 * process XML string response from DELETE server method
 */		
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			unset($this->categoryId);
			unset($this->name);
			unset($this->tax1);		
			unset($this->tax2);
		}
	}
	
/**
 * prepare XML string request for LIST server method
 */		
	protected function _internalPrepareListing($filters,&$content)
	{
		//
	}
	
/**
 * process XML string response from LIST server method
 */	
	protected function _internalListing($responseStatus,&$XMLObject,&$rows,&$resultInfo)
	{
		$rows = array();
		$resultInfo = array();
		$categories = $XMLObject->categories;
		$resultInfo['page'] = (string)$categories['page'];
		$resultInfo['perPage'] = (string)$categories['per_page'];
		$resultInfo['pages'] = (string)$categories['pages'];
		$resultInfo['total'] = (string)$categories['total'];

		foreach ($categories->children() as $key=>$currXML){
			$thisCategory = new FreshBooks_Category();
			$thisCategory->_internalLoadXML($currXML);
			$rows[] = $thisCategory;
		}
	}
}
