<?php
namespace Clicalmani\XPower;

class XDTDOMNamedNodeMap {
	private $attributes;
	public $length;
	
	function __construct(DOMNamedNodeMap $attributes) {
		
		$this->attributes = $attributes;
		$this->length = $attributes->length;
	}
	
	function __set($name, $value) {
		
		$this->attributes->getNamedItem($name)->value = $value;
	}
	
	function __get($name) {
		
		return $this->attributes->getNamedItem($name)->value;
	}
	
	function item($index) { return $this->attributes->item($index); }
}