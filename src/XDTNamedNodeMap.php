<?php
namespace Clicalmani\XPower;

/**
 * @package clicalmani/xpower
 * @author @clicalmani
 */
class XDTDOMNamedNodeMap 
{
	private $attributes;
	public $length;
	
	public function __construct(\DOMNamedNodeMap $attributes) {
		
		$this->attributes = $attributes;
		$this->length = $attributes->length;
	}
	
	public function __set($name, $value) {
		
		if (NULL !== $node = $this->attributes->getNamedItem($name)) $node->nodeValue = $value;
	}
	
	public function __get($name) {
		
		return $this->attributes->getNamedItem($name)?->nodeValue;
	}
	
	public function item(int $index) { return $this->attributes->item($index); }
}