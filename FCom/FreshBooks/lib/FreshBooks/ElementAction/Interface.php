<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks API ElementAction Interface
 *
 *
 * @package    FreshBooks

 * @copyright  Milan Rukavina, rukavinamilan@gmail.com
 * @version    1.0
 */

interface FreshBooks_ElementAction_Interface
{
/**
 * create/save object to the server
 */ 
	public function create();
/**
 * update/save object on the server
 */ 
	public function update();
/**
 * get object from the server having particular id
 */ 
	public function get($id);
/**
 * delete object from the server having the same id
 */ 
	public function delete();
/**
 * list/search object from the server
 */ 
	public function listing(&$rows,&$resultInfo,$page = 1,$perPage = 25, $filters = array());
}
