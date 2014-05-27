<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * FreshBooks API Element Interface
 *
 *
 * @package    FreshBooks

 * @copyright  Milan Rukavina, rukavinamilan@gmail.com
 * @version    1.0
 */

interface FreshBooks_Element_Interface
{
/**
 * return XML data
 */ 
	public function asXML();
/**
 * load XML string
 */ 
	public function loadXML($xml);
}
