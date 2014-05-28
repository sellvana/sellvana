<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks Project Class
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
 * Class representing project API 
 */
class FreshBooks_Project extends FreshBooks_ElementAction implements FreshBooks_Element_Interface,FreshBooks_ElementAction_Interface
{
	protected $_elementName = "project";
	
	public $projectId = "";
	public $name = "";
	public $billMethod = "";
	public $clientId = "";
	public $rate = "";
	public $description = "";
	
/**
 * return XML string
 */	
	public function asXML()
	{
		$content =
							$this->_getTagXML("project_id",$this->projectId) .
							$this->_getTagXML("name",$this->name) .
							$this->_getTagXML("bill_method",$this->billMethod) .
							$this->_getTagXML("client_id",$this->clientId) .
							$this->_getTagXML("rate",$this->rate) .
							$this->_getTagXML("description",$this->description);
							
		return $this->_getTagXML("project",$content);
		
	}
	
/**
 * load obect properties from SimpleXML object
 */	
	protected function _internalLoadXML(&$XMLObject)
	{
		$this->projectId = (string)$XMLObject->project_id;
		$this->name = (string)$XMLObject->name;
		$this->billMethod = (string)$XMLObject->bill_method;
		$this->clientId = (string)$XMLObject->client_id;
		$this->rate = (string)$XMLObject->rate;		
		$this->description = (string)$XMLObject->description;
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
			$this->projectId = (string)$XMLObject->project_id;
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
		$content = $this->_getTagXML("project_id",$id);
	}
	
/**
 * process XML string response from GET server method
 */		
	protected function _internalGet($responseStatus,&$XMLObject)
	{
		if($responseStatus)
			$this->_internalLoadXML($XMLObject->project);
	}
	
/**
 * prepare XML string request for DELETE server method
 */	
	protected function _internalPrepareDelete(&$content)
	{
		$content = $this->_getTagXML("project_id",$this->projectId);
	}
	
/**
 * process XML string response from DELETE server method
 */		
	protected function _internalDelete($responseStatus,&$XMLObject)
	{
		if($responseStatus){
			unset($this->projectId);
			unset($this->name);
			unset($this->billMethod);
			unset($this->clientId);
			unset($this->rate);
			unset($this->description);		
		}
	}
	
/**
 * prepare XML string request for LIST server method
 */	
	protected function _internalPrepareListing($filters,&$content)
	{
		if(is_array($filters) && count($filters)){
			$content .= $this->_getTagXML("client_id",$filters['clientId'])
								. $this->_getTagXML("task_id",$filters['taskId']);
		}
	}

/**
 * process XML string response from LIST server method
 */	
	protected function _internalListing($responseStatus,&$XMLObject,&$rows,&$resultInfo)
	{
		$rows = array();
		$resultInfo = array();
		$projects = $XMLObject->projects;
		$resultInfo['page'] = (string)$projects['page'];
		$resultInfo['perPage'] = (string)$projects['per_page'];
		$resultInfo['pages'] = (string)$projects['pages'];
		$resultInfo['total'] = (string)$projects['total'];

		foreach ($projects->children() as $key=>$currXML){
			$thisProject = new FreshBooks_Project();
			$thisProject->_internalLoadXML($currXML);
			$rows[] = $thisProject;
		}
	}
}
