<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks Item Class
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
 * Class representing item API 
 */
class FreshBooks_Item extends FreshBooks_ElementAction implements FreshBooks_Element_Interface,FreshBooks_ElementAction_Interface
{
	protected $_elementName = "item";
	
	public $itemId = "";
	public $name = "";
	public $description = "";
	public $unitCost = "";
	public $quantity = "";
	public $inventory = "";
	
/**
 * return XML string
 */	
	public function asXML()
	{
		$content =
							$this->_getTagXML("item_id",$this->itemId) .
							$this->_getTagXML("name",$this->name) .
							$this->_getTagXML("description",$this->description) .
							$this->_getTagXML("unit_cost",$this->unitCost) .
							$this->_getTagXML("quantity",$this->quantity) .
							$this->_getTagXML("inventory",$this->inventory);
							
		return $this->_getTagXML("item",$content);
		
	}
	
/**
 * load obect properties from SimpleXML object
 */ 	
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->itemId = (string)$XMLObject->item_id;
		$this->name = (string)$XMLObject->name;
		$this->description = (string)$XMLObject->description;
		$this->unitCost = (string)$XMLObject->unit_cost;
		$this->quantity = (string)$XMLObject->quantity;		
		$this->inventory = (string)$XMLObject->inventory;
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
			$this->itemId = (string)$XMLObject->item_id;
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
		$content = $this->_getTagXML("item_id",$id);
	}
	
/**
 * process XML string response from GET server method
 */	
	protected function _internalGet($responseStatus,&$XMLObject)
	{
		if($responseStatus)
			$this->_internalLoadXML($XMLObject->item);
	}
	
/**
 * prepare XML string request for DELETE server method
 */		
	protected function _internalPrepareDelete(&$content)
	{
		$content = $this->_getTagXML("item_id",$this->itemId);
	}
	
/**
 * process XML string response from DELETE server method
 */	
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			unset($this->itemId);
			unset($this->name);
			unset($this->description);
			unset($this->unitCost);
			unset($this->quantity);		
			unset($this->inventory);
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
		$items = $XMLObject->items;
		$resultInfo['page'] = (string)$items['page'];
		$resultInfo['perPage'] = (string)$items['per_page'];
		$resultInfo['pages'] = (string)$items['pages'];
		$resultInfo['total'] = (string)$items['total'];

		foreach ($items->children() as $key=>$currXML){
			$thisItem = new FreshBooks_Item();
			$thisItem->_internalLoadXML($currXML);
			$rows[] = $thisItem;
		}
	}
}
