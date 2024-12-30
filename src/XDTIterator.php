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

	/** Object **/
	private $obj;
	
	/** Iterator index **/
	private $key;
	
	public function __construct ($obj) { $this->obj = $obj; }
	
	public function rewind () : void { $this->key = 0; }
	
	public function key () : mixed { return $this->key; }
	
	public function next () : void { $this->key++; }
	
	public function valid () : bool { return $this->key < $this->obj->length ? true: false; }
	
	public function current () : mixed { return $this->obj->item($this->key); }
}