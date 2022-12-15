<?php
namespace Clicalmani\XPower;

/**
 * XDTIterator Class
 * 
 * @author Abdoul-Madjid
 * @package XDT
 *
 */
class XDTIterator implements \Iterator {
	private $obj;
	
	/** Iterator index **/
	private $key;
	
	public function __construct ($obj) { $this->obj = $obj; }
	
	public function rewind () { $this->key = 0; }
	
	public function key () { return $this->key; }
	
	public function next () { $this->key++; }
	
	public function valid () { return $this->key < $this->obj->length ? true: false; }
	
	public function current () { return $this->obj->item($this->key); }
}