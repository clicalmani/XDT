<?php
namespace Clicalmani\XPower;

use Clicalmani\XPower\XDT;
use Clicalmani\XPower\XDTIterator;
use Clicalmani\XPower\Exceptions\OutOfRangeException;
use \DOMElement as DOMElement;

/**
 * Build a list of DOM elements maintaining the parental relationship.
 * And defines appropriate methods to work on the list elements.
 * 
 * This class contains jQuery like methods for document traversing and document altering.
 * It implements IteratorAggregate and ArrayAccess interfaces, which allow class objects to behave like an array.
 * 
 * @tutorial Changes made by a method of this class are not automaticaly saved. Explicitly call the XDT close method to save change.
 * 
 * @author @clicalmani
 * @package clicalmani/xpower
 * @version 2.3.0
 *
 */
class XDTNodeList extends XDT implements \IteratorAggregate, \ArrayAccess 
{
	public $list = []; 
	
	/**
	 * Holds the number of matched elements from the current operation<br><br> The property is 
	 * supposed to be a readonly property but is not declared as so; setting its value will 
	 * truncate the internal result set.
	 * 
	 * @var integer
	 */
	public $length = 0;      
	
	/**
	 * Create a new instance of XDTNodeList.
	 * 
	 * @param \DOMElement|array<DOMElement> $element
	 *     DOM element or array of DOM elements can be provided to instanciate the object with.
	 */
	public function __construct(mixed $element = null) 
	{
	    if (NULL !== $element) {
			if ($element instanceof DOMElement) {
				$this->add($element);
			} elseif (is_array($element)) {
				foreach ($element as $node) {
					if (is_object($node) AND $node instanceof DOMElement) {
						$this->add($node);
					}
				}
			}
		}
	}
	
	/**
	 * Get an item at index n from the set of matched elements.
	 * 
	 * @param integer $index <p>
	 *     Index of the element in the set of matched elements.</p>
	 * @return \DOMElement <p>
	 * 		Returns a DOM element or throws an OutOfRangeException if the element was not found.</p>
	 * @throws \Clicalmani\XPower\Exceptions\OutOfRangeException
	 */
	public function item(int $index) 
	{
		if ($index > ($this->length-1) OR $index < 0) throw new OutOfRangeException('Undefined offset: ' . $index);
		
		return $this->list[$index];
    }
	
    /**
     * Add a DOM element to the current set of matched elements.
     * 
     * @param \DOMElement $node <p>
     *     DOM element to add to the set.</p>
     * @return bool <p>
     *     Returns TRUE on success, or FALSE on error or failure.</p>
     */
	public function add (\DOMElement $node) : bool
	{ 
	    if (array_push($this->list, $node)) {
			$this->length++;
			return true;
		} else return false;
	}
	
	/**
	 * Merge the provided set of elements into the matched set of elements.
	 * 
	 * @param static $list <p>
	 *     Set of elements to be merged into the current set.</p>
	 */
	public function merge (XDTNodeList $list) 
	{ 
		$this->list = array_merge($this->list, $list->getNodeList()); 
		$this->length += $list->length; 
	}
	
	private function removeFromList (int $index) : mixed
	{ 
		if ($arr = array_splice($this->list, $index, 1)) {
			$this->length--;
			return $arr[0];
		}
		    
		return null;
	}

	private function getNodeList () : array
	{ 
		return $this->list; 
	}
	
	/**
	 * Remove the set of matched elements from the DOM.
	 * 
	 * @return void
	 */
	public function remove () : void
	{
		foreach ($this as $node) 
		    $node->parentNode->removeChild($node);
	}
	
	/**
	 * Removes attribute
	 * 
	 * @param string $attr
	 * 	The name of the attribute 
	 * @return static <p>
	 * 	The set of matched elements</p>
	 */
	public function removeAttr(string $attr) : static
	{
		foreach ($this as $node) 
			if ($node->hasAttribute($attr)) $node->removeAttribute($attr);
			
		return $this;
	}
	
	/**
	 * Remove all child nodes of the set of matched elements from the DOM.
	 * 
	 * @return static <p>
	 * 		Returns the set of matched elements whose children were removed.</p>
	 */
	public function emptyNode() : static
	{
		foreach ($this as $node) $node->nodeValue = '';
		
		return $this;
	}
	
	private function replaceNodeByIndex (\DOMElement $new_node, int $index) : mixed
	{
	    if ($arr = array_splice($this->list, $index, 1, $new_node))
	    	return $arr[0]->parentNode->replaceChild($new_node, $arr[0]);
	    
	    return null;
	}
	
	/**
	 * Replace each element in the set of matched elements with the provided new content and 
	 * return the set of elements that was removed.
	 * 
	 * @param XDTNodeList|\DOMElement|string $content <p>
	 *     The content to insert may be an XML string, DOM element, or XDTNodeList object. 
	 *     When you pass a XDTNodeList collection containing more than one element, or a selector 
	 *     matching more than one element, the first element will be used.</p>
	 * @return static <p>
	 * 	   Returns the set of elements that was removed.</p>
	 */
	public function replaceWith (mixed $content) : static
	{
		if ($this->length === 0) return null;
		
		$content = $this->processData($content);
		
		if (!isset($content)) return null;
		
		if (get_class($content) === 'XDTNodeList') {
			$clone = $content[0]->cloneNode(true);
			$content->remove();
		} else {
			$clone = $content->cloneNode(true);
			if (get_class($content) === 'DOMElement') $content->parentNode->removeChild($content);
		}
		
		foreach ($this as $node) 
			$node->parentNode->replaceChild($clone->cloneNode(true), $node);
		
		return $this;
	}
	
	/**
	 * Insert the first element in the selection before the element passed in argument.
	 * 
	 * @see XDTNodeList::insertAfter
	 * @param XDTNodeList|\DOMElement|string $target <p>
	 *     A selector, DOM element, XML string, or XDTNodeList object; the 
	 *     matched set of elements will be inserted before the element specified by this parameter.
	 * @return static <p>
	 *     Returns the inserted elements on success, for chaining purpose, or &null on failure.</p>
	 */
	public function insertBefore (mixed $target) : static
	{
		if ($target = $this->processData($target)) 
			foreach ($this as $node) 
				if ($target instanceof self) $target[0]->parentNode->insertBefore($node, $target[0]);
				else $target->parentNode->insertBefore($node, $target);
				
		return $this;
	}
	
	/**
	 * Insert the first element in the selection after the element passed in argument.
	 * 
	 * @see XDTNodeList::insertBefore
	 * @param XDTNodeList|\DOMElement|string $target <p>
	 *     A selector, DOM element, XML string, or XDTNodeList object; the 
	 *     matched set of elements will be inserted after the element specified by this parameter. 
	 *     When you pass a XDTNodeList collection containing more than one element, or a selector 
	 *     matching more than one element, the first element will be used.</p>
	 * @return static <p>
	 *     Returns the inserted elements on success, for chaining purpose, or &null on failure.</p>
	 */
	public function insertAfter (mixed $target) : static
	{
		if ($target = $this->processData($target)) 
			foreach ($this as $node) 
				if (get_class($target) === 'XDTNodeList') $target[0]->parentNode->insertBefore($node, $target[0]->nextSibling);
				else $target->parentNode->insertBefore($node, $target->nextSibling);  
		
		return $this;
	}
	
	/**
	 * Insert content, specified by the parameter, to the end of each element 
	 * in the set of matched elements.
	 * 
	 * @see XDTNodeList::prepend
	 * @param mixed ...$args <p>
	 *     DOM element, text node, array of elements and text nodes, XML string, or XDTNodeList object 
	 * 	   to insert at the end of each element in the set of matched elements. You can provide any number of contents
	 *     by separating them by a comma (,).</p>
	 * @return static <p>
	 * 	   Returns the set of inserted elements on success, for chaining purpose, or &null on failure.</p>
	 */
	public function append (mixed ...$args) : static
	{
		if ($this->length < 1) return null;
		
		foreach ($args as $offset => $data) {
			if ($data = $this->processData($data)) {
				foreach ($this as $node) {
					if ($data instanceof self) {
						if ($data->length) $node->appendChild($data[0]);
					} else $node->appendChild($data);
				}
			}
		}
				
		return $this;
	}
	
	/**
	 * Insert content, specified by the parameter, to the begining of each element 
	 * in the set of matched elements.
	 * 
	 * @see XDTNodeList::append
	 * @param mixed ...$args<p>
	 * 		DOM element, text node, array of elements and text nodes, XML string, or XDTNodeList object 
	 * 		to insert at the begining of each element in the set of matched elements. You can provide any 
	 *      number of content by separating them by a comma (,).</p>
	 * @return static <p>
	 *     Returns the set of inserted elements on success, for chaining purpose, or &null on failure.</p>
	 */
	public function prepend (mixed ...$args) : static
	{
		if ($this->length < 1) return null;
		
		foreach ($args as $offset => $value) 
			if ($value = $this->processData($value)) 
				foreach ($this as $node) 
					if (get_class($value) === 'XDTNodeList') $node->insertBefore($value[0], $node->firstChild);
					else $node->insertBefore($value, $node->firstChild);
				
		return $this;
	}
	
	/**
	 * Insert every element in the set of matched elements to the end of the target.
	 * 
	 * @see XDTNodeList::preprendTo
	 * @param XDTNodeList|\DOMElement|string|array $target <p>
	 *     A selector, element, XML string, array of elements, or XDTNodeList object; the 
	 *     matched set of elements will be inserted at the end of the element(s) specified by this parameter.
	 * @return static <p>
	 * 	   Returns the set of inserted elements, for chaining purpose, on success or &null on failure.</p>
	 */
	public function appendTo (mixed $target) : static
	{
		if ($target = $this->processData($target))
			foreach ($this as $node) 
				if ($target instanceof self OR is_array($target)) 
					foreach ($target as $t) $t->appendChild($node);
				else $target->appendChild($node);
				
		return $this;
	}
	
	/**
	 * Insert every element in the set of matched elements to the begining of the target.
	 * 
	 * @see XDTNodeList::appendTo
	 * @param XDTNodeList|\DOMElement|string|array $target <p>
	 *     A selector, element, XML string, array of elements, or XDTNodeList object; the 
	 *     matched set of elements will be inserted at the begining of the element(s) specified by this parameter.
	 * @return static <p>
	 *     Returns the set of inserted elements, for chaining purpose, on success or &null on failure.</p>
	 */
	public function prependTo (mixed $target) : static
	{
		if ($target = $this->processData($target))
			foreach ($this as $node) 
				if (get_class($target) === 'XDTNodeList' OR is_array($target)) 
					foreach ($target as $t) $t->insertBefore($node, $t->firstChild);
				else $target->insertBefore($node, $target->firstChild);
				
		return $this;
	}
	
	/**
	 * Wrap an XML structure around each element in the set of matched elements.
	 * 
	 * @param mixed $selector <p>
	 *     A selector, element, XML string, or XDTNodeList object specifying the structure 
	 *     to wrap around the matched elements. When you pass a XDTNodeList collection containing 
	 *     more than one element, or a selector matching more than one element, the first element will be used.</p>
	 * @return static <p>
	 * 		Returns the set of matched elements for chaining purpose.</p>
	 */
	public function wrap(mixed $selector) : static
	{
		if ($selector = $this->processData($selector))
			foreach ($this as $node) {
				if ($selector instanceof self) {
					$n = $node->parentNode->insertBefore($selector[0], $node);
					$n->appendChild($node);
				} else {
					$n = $node->parentNode->insertBefore($selector, $node);
					$n->appendChild($node);
				}
			}
		
		return $this;
	}
	
	/**
	 * Get the text content of the first matched element.
	 * 
	 * @return mixed The text content of the matched element or NULL.
	 */
	public function text() : mixed 
	{ 
		return $this[0]?->textContent; 
	}
	
	/**
	 * Get the children of each element in the set of matched elements, 
	 * optionally filtered by a selector.
	 * 
	 * @param string $selector [optional] <p>
	 * 		A string containing the selector expression to match against.</p>
	 * @return static
	 */
	public function children(?string $selector = null) : static
	{
		$l = new XDTNodeList();
		foreach ($this as $node) 
			foreach ($node->childNodes as $child) 
				if ($child instanceof \DOMElement) $l->add($child);
		
		if (!isset($selector)) return $l;
		
		$this->query_result = $l;
		return $this->select($selector, null, XDT_SELECT_FILTER);
	}
	
	/**
	 * Verify whether any element in the set of matched elements is a single parent 
	 * or optionally verify whether the given element that match the selector is a child 
	 * of any element in the set of matched elements.
	 * 
	 * @param \DOMElement|string|null $selector <p>
	 *     A string containing the selector expression or a DOMElement.
	 * @return bool <p>
	 *     Returns TRUE on success, or FALSE on error or failure.
	 */
	public function hasChildren(mixed $selector = null) : bool
	{
		$ret = $this->children();
		
		if (!isset($selector)) return $this->children()->length? true: false;
		
		if (is_object($selector) AND $selector instanceof \DOMElement) {
			
			foreach ($this as $node) {
					
				$is_parent = false;
				
				foreach ($node->childNodes as $child) 
					if ($child->isSameNode($selector)) $is_parent = true;
				
				if ($is_parent === false) return false;
			}
			
		} elseif (is_string($selector)) {
			
			foreach ($this as $node) {
				$node = $this->toXDTObject($node);
				$this->query_result = $node->children();
				if ($this->select($selector, null, XDT_SELECT_FILTER)->length === 0) return false;
			}
		} else return false;
		
		return true;
	}
	
	/**
	 * Get the ancestors of each element in the current set of matched elements, optionally 
	 * filtered by a selector.
	 * 
	 * @see XDTNodeList::parent
	 * @param ?string $selector <p>
	 *     A string containing the selector expression.</p>
	 * @return static
	 */
	public function parents(?string $selector = NULL) : static
	{
		$list = new self;
		
		foreach ($this as $node) 
			while (!$node->isSameNode($node->ownerDocument)) {
				$node = $node->parentNode;
				
				if (!($node instanceof DOMElement)) continue;
				
				$list->add($node);
			}
			
		if (NULL === $selector) return $list;
		
		$this->query_result = $list;
		return $this->select($selector, null, XDT_SELECT_FILTER);
	}
	
	/**
	 * Get the parent of each element in the current set of matched elements, optionally filtered by a selector.
	 * 
	 * @param ?string $selector <p>
	 * 		A string containing the selector expression.</p>
	 * @return static
	 */
	public function parent(?string $selector = NULL) : static
	{
		$list = new XDTNodeList();
		
		foreach ($this as $node) $list->add($node->parentNode);
		
		if (NULL === $selector) return $list;
		
		$this->query_result = $list;
		return $this->select($selector, null, XDT_SELECT_FILTER);
	}
	
	/**
	 * Get the descendences of each element in the current set of matched elements, filtered by a selector
	 * 
	 * @param ?string $selector <p>
	 *     A string containing the selector expression.
	 *     If omitted the current set of matched elements is returned.</p>
	 * @return static  
	 */
	public function find (?string $selector = NULL) : static
	{
		if (!isset($selector) OR $this->length === 0) return $this;
		
		$l = new self;
		
		foreach ($this as $node) {
			
			foreach ($this->select($selector, $node) as $n) $l->add($n);
		}
		
		return $l;
	}
	
	/**
	 * Reduce the set of matched elements to those that match the selector
	 * 
	 * @param ?string $selector <p>
	 *     A string containing the selector expression.
	 *     If omitted the current set of matched elements is returned.</p>
	 * @return static
	 */
	public function filter(?string $selector = NULL) : static
	{
		if (!isset($selector) OR $this->length === 0) return $this;
		
		$this->query_result = $this;
		return $this->select($selector, null, XDT_SELECT_FILTER);
	}
	
	/**
	 * Verify whethe any of the matched elements are assigned the given attribute;
	 * 
	 * @see XDTNodeList::hasAttr
	 * @param string $attr <p>
	 * 		A string representing the attribute to search for.
	 * @return bool <p>
	 * 		Returns boolean true on success, or false on failure.</p>
	 */
	public function hasAttribute(string $attr) : bool
	{
		foreach ($this as $node) 
			if (!$node->attributes->getNamedItem($attr)) return false;
		
		return true;
	}
	
	/**
	 * Verify whethe any of the matched elements are assigned the given attribute;
	 * 
	 * @see XDTNodeList::hasAttribute
	 * @param string $attr <p>
	 * 		A string representing the attribute to search for.
	 * @return bool <p>
	 * 		Returns boolean true on success, or false on failure.</p>
	 */
	public function hasAttr(string $attr) : bool { return $this->hasAttribute($attr); }
	
	/**
	 * Verify whether any of the matched elements are assigned the given class; 
	 * elements can have more than one class assigned to them, in html this is represented 
	 * by separating the class names with space
	 * 
	 * @param string $class <p>
	 * 		The class name to search for.
	 * @return bool <p>
	 * 		Returns boolean true on success, or false on failure.</p>
	 */
	public function hasClass(string $class) : bool
	{
		foreach ($this as $node) {
			$node = $this->toXDTObject($node);
			if ($node->hasAttr('class') === false) return false;
		}
			
		foreach ($this as $node) {
			$node = $this->toXDTObject($node);
			
			if (in_array($class, explode(' ', $node->data()->class)) == false) return false;
		}
		
		return true;
	}
	
	/**
	 * Get attributes for the first matched element or set one or more attributes for every matched element.
	 * 
	 * @see XDTNodeList::attr
	 * @param array $data <br><br>
	 *     When set it contains attributes representing the key and value pairs of the array. 
	 *     If omitted the first matched element attributes are returned as a DOMNamedNodeMap object.
	 * @return object with the selected matched element attributes as properties otherwise the current selection matched elements.
	 */
	public function data(?array $data = null) : object
	{
		if (!isset($data)) {
			
			$data = $this[0]->attributes;
			$obj = new XDTNamedNodeMap($data);
			
			for ($i=0; $i<$data->length; $i++) {
				$obj->{$data->item($i)->name} = $data->item($i)->nodeValue;
			}
			
			return $obj;
		}
		
		/** @var \DOMNode */
		foreach ($this as $node) {
			foreach ($data as $name => $value) {
				$attrNode = $node->ownerDocument->createAttribute($name);
				$attrNode->nodeValue = $value;
				$node->appendChild($attrNode);
			}
		}
		
		return $this;
	}
	
	/**
	 * Get the value of an attribute for the first element in the set of matched elements or 
	 * set one or more attributes for every matched element.
	 * 
	 * @see XDTNodeList::data
	 * @param string|string[] $name <p>
	 *     A string representing the attribute name which value is to be set or an array 
	 *     of attributes which values are to be set.</p>
	 * @param string|string[] $value [Optional] <p>
	 *     A string representing the new value of the first matched element or an array of values 
	 *     in the same order as the names array giving as the first argument to the method.</p>
	 *     
	 *     <p>If omitted, the named attribute value is returned.</p>
	 * @return mixed <p>
	 * 		The current value of the named attribute of the first matched element when 
	 *      the method is used to get a value; otherwise the current selection matched elements.</p>
	 */
	public function attr(string|array $name, mixed $value = null) : mixed
	{
		if (!isset($value) AND is_string($name)) {
			/** @var \DOMNode */
			$node = $this[0];

			if ($n = $node->attributes->getNamedItem($name)) return $n->nodeValue;
			
			throw new \Exception(
				sprintf("Attribute %s not found on node %s, in %s at %d", $name, $node->nodeName, __FILE__, __LINE__)
			);
		}  
		    
		if ((is_array($value) AND is_array($name)) AND (count($value) === count($name))) {
			
			/** @var \DOMNode */
			foreach ($this as $node) {
				foreach ($name as $i => $attr) {
					
					$attrNode = $node->ownerDocument->createAttribute($attr);
					$attrNode->nodeValue = (string)$value[$i];
					$node->appendChild($attrNode);
				}
			}
				
		} else {
			
			foreach ($this as $node) {
				
				$attrNode = $node->ownerDocument->createAttribute($name);
				$attrNode->nodeValue = (string)$value;
				$node->appendChild($attrNode);
			}
		}
		
		return $this;
	}
	
	/**
	 * Get the matched element index from among its siblings, or get a giving DOM element's 
	 * index from the set of matched elements, or get a filtered element's index from among 
	 * the set of matched elements
	 * 
	 * @param \DOMElement|string|null $selector <p>
	 *     A string representing a selector expression to filter the set of matched elements against, 
	 *     or a DOMElement representing the element which index is search for.
	 * @return int <p>
	 *     Returns the matched element's index on success, or -1 on failure.
	 */
	public function index (mixed $selector = null) : int
	{
		if (!isset($selector)) {
			
			foreach ($this[0]->parentNode->childNodes as $index => $child) {
				
				if ($child->isSameNode($this[0])) return $index;
			}
		} elseif (is_object($selector) AND get_class($selector) === 'DOMElement') {
				
			foreach ($this->list as $index => $elt) {
				if ($selector->isSameNode($elt)) return $index;
			}
		} elseif (is_string($selector)) {
			
			$old_list = $this->list;
			
			foreach ($old_list as $index => $node) {
				
				if ($node->isSameNode($this->filter($selector)->first()->get(0))) return $index;
			}
		}
		
		return -1;
	}
	
	/**
	 * Gets element by index in the set of matched elements.
	 * 
	 * @param int $index Element index
	 * 
	 * @see XDTNodeList::get
	 * @see XDTNodeList::item
	 * 
	 * @return static
	 */
	public function pos(int $index) : static { return new self($this[$index]); }
	
	/**
	 * Verify whether the selection is empty.
	 * 
	 * @return bool
	 */
	public function isEmpty() : bool { return $this->children()->length? false: true; }
	
	/**
	 * Iterate over the set of matched elements executing a function for each matched element
	 * 
	 * @see XDTNodeList::map
	 * @param callable $callback <p>
	 *     A function to execute for each matched element.</p>
	 * @return void
	 */
	public function each(callable $callback) : void
	{
		foreach ($this as $index => $node) $callback($index, $node); 
	}
	
	/**
	 * Translate all items in set of matched elements to a new set of elements by altering their 
	 * value through a call to a callback function passed in argument <br><br>
	 * <b>Notice:</b> This method alter directly the loaded xml file; an explicit call 
	 * to the XDT::close method must follow the call of the method.
	 * @see XDTNodeList::each
	 * @param callable $callback <p>
	 * 		A function to process each item against. The first argument to the function is the element 
	 * 		index and the second argument is a DOMElement object. The returned value of the function is 
	 * 		used as the current value of the element.</p>
	 * @return
	 *     Returns null on failure.
	 */
	public function map(callable $callback) 
	{
		foreach ($this as $index => $node) $node->nodeValue = $callback($index, $node); 
	}
	
	/**
	 * Get the current value of the first element in the set of matched elements 
	 * or optionally set the value of every matched element. <br><br>
	 * @param ?string $value <p>
	 * 		A string representing the current value of the first matched element. Otherwise void.
	 * @return mixed <p>
	 * 		A string representing the current value of the first element in the set of matched elements.</p>
	 */
	public function val(?string $value = null) : mixed
	{
		if (isset($value)) {
			foreach ($this as $node) $node->nodeValue = $value;
			return null;
		} else return $this[0]->nodeValue;
	}
	
	/**
	 * Get the node name of the first element in the set of matched elements.
	 * 
	 * @return string <p>
	 * 		A string representing the matched element's node name.</p>
	 */
	 public function name() : string { return $this[0]->nodeName; }
	
	/**
	 * Return an array of the values of the set of matched elements.
	 * [deprecated]
	 * 
	 * @return array
	 */
	public function values() : array
	{
		$values = array();
		foreach ($this as $node) {
			$values[] = $node->nodeValue;
		}
		
		return $values;
	}
	
	/**
	 * Get the first element in the set of matched elements.
	 * 
	 * @return static
	 */
	public function first() : static { return new self($this[0]); }
	
	/**
	 * Get the last element in the set of matched elements.
	 * 
	 * @return static
	 */
	public function last() : static { return new XDTNodeList($this[$this->length-1]); }
	
	/**
	 * Get the siblings of each element in the set of matched elements, optionally filtered by a selector.
	 * 
	 * @since Version 2.1
	 * @param ?string $selector [optional] <p>
	 * 		A string containing a selector expression to match against.</p>
	 * 		<p>When omitted, the siblings of each element in the set of matched elements are returned.</p>
	 * @return static <p>
	 * 		Returns the sibling of each element in the set of matched elements, optionally filtered by a selector.</p>
	 */
	public function siblings(?string $selector = null) : static
	{
		$list = new XDTNodeList();
		
		foreach ($this as $node) {
			
			$node = $this->toXDTObject($node);
			
			foreach ($node->parent()->children() as $child) {
				
				if ($child->isSameNode($node[0])) continue;
				
				$list->add($child);
			}
		}
		
		if (!isset($selector)) return $list;
		
		return $list->filter($selector);
	}
	
	/**
	 * Get the immediately following sibling of each element in the set of matched elements. 
	 * If a selector is provided, it retrieves the next sibling only if it matches that selector.</p>
	 * 
	 * @since Version 2.1
	 * @param ?string $selector [optional] <p>
	 * 		A string containing a selector expression to match elements against.</p>
	 * 		<p>If selector is omitted, all the next siblings of each element in the matched set are returned.</p>
	 * @return static <p>
	 * 		Returns the matched set.</p>
	 */
	public function next(?string $selector = null) : static
	{
		$list = new XDTNodeList();
		
		foreach ($this as $node) {
			
			$node = $this->toXDTObject($node);
			$siblings = $node->siblings();
			
			//$list->add($siblings->get($siblings->index($node[0])+1));
			for($i=$siblings->index($node[0])+1; $i<$siblings->length; $i++) $list->add($siblings->get($i));
		}
		
		if (!isset($selector)) return $list;
		else {
			
			$this->query_result = $list;
			return $this->select($selector, null, XDT_SELECT_FILTER);
		}
	}
	
	/**
	 * Get the immediately preceding sibling of each element in the set of matched elements. 
	 * If a selector is provided, it retrieves the previous sibling only if it matches that selector.
	 * 
	 * @since Version 2.1
	 * @param string $selector [optional] <p>
	 * 		A string containing a selector expression to match elements against.</p>
	 * 		<p>If selector is omitted, all the previous siblings of each element in the matched set are returned.</p>
	 * @return static <p>
	 * 		Returns the matched set.</p>
	 */
	public function prev(?string $selector = null) : static
	{
		$list = new XDTNodeList();
		
		foreach ($this as $node) {
			
			$node = $this->toXDTObject($node);
			$siblings = $node->siblings();
		
			for($i=$siblings->index($node[0])-1; $i>=0; $i--) $list->add($siblings->get($i));
		}
		
		if (!isset($selector)) return $list;
		else {
			
			$this->query_result = $list;
			return $this->select($selector, null, XDT_SELECT_FILTER);
		}
	}
	
	/**
	 * Select the element at index n within the matched set.
	 * 
	 * @see XDTNodeList::eq
	 * @param int $index <p>
	 *     Zero-base index at which to select the element.</p>
	 * @return \DOMElement|null <p>
	 * 		Returns the selected element object.</p>
	 */
	public function get(int $index) : \DOMElement { return @$this[$index]; }
	
	/**
	 * Select the element at index n within the matched set.
	 * 
	 * @see XDTNodeList::get
	 * @since Version 2.3
	 * @param int $index <p>
	 * 		Zero-based index at which to select the element.</p>
	 * 		<p>If negative index is given, the counting starts from the end of the matched set.</p>
	 * @return \DOMElement|null <p>
	 * 		Returns the selected element object, otherwise null on failure.</p>
	 */
	public function eq(int $index) { 
		
		if ($index >= 0) return $this->get($index); 
		elseif ($this->length+$index-1>=0) return $this->get($this->length+$index-1);
		
		return  null;
	}
	
	/**
	 * Select all elements that are nth-child of their parent.
	 * 
	 * @param string $selector <p>
	 * 		A string containing a selector expression to match against.</p>
	 * @return static <p>
	 * 		Returns the set of matched elements.</p>
	 */
	public function nth(string $selector) : static
	{
		$this->query_result = $this;
		return $this->select($this->name() . ':nth(' . $selector . ')', null, XDT_SELECT_FILTER);
	}
	
	/**
	 * Remove elements from the set of matched elements.
	 * 
	 * @param \DOMElement|array|string|null $selector <p>
	 * 		A string containing a selector expression, a DOM element, or an array
	 * 		of elements to match against the set.</p>
	 * 		<p>If selector is omitted, the filtering operation is canceled and the previous set of matched elements is returned.</p>
	 * @return static <p>
	 * 		Returns the filtered elements.</p>
	 */
	public function not(mixed $selector = null) : static
	{
		if (!isset($selector)) return $this;
		
		if (is_string($selector)) {
			
			foreach ($this as $index => $node) 
				foreach ($this->filter($selector) as $n)
					if ($node->isSameNode($n)) $this->removeFromList($index);
		} elseif (is_object($selector) AND get_class($selector) === 'DOMElement') {
			
			if (in_array($selector, $this->list)) $this->removeFromList($this->index($selector));
		} elseif (is_array($selector)) {
			
			foreach ($selector as $node) {
				if (is_object($node) == false) continue;
				
				if (in_array($node, $this->list)) $this->removeFromList($this->index($node));
			}
		}
		
		return $this;
	}
	
	/** 
	 * End the recent filtering operation in the current chain and return the set
	 * of matched element to its initial state.
	 * 
	 * @return static <p>
	 * 		Returns the current matched elements.</p>
	 **/
	public function end() : static
	{
		$this->query_result = null; 
		$this->root = $this[0]->ownerDocument->firstChild; 
		
		return $this; 
	}
	
	/**
	 * Get the html of the first element in the set of matched elements or set 
	 * the html element content of every matched element.
	 * 
	 * @param ?string $html [optional]<p>
	 * 		when set, the html content of every matched element will be set to that value.
	 * 		Otherwise the html content of the first element in the set of matched elements is returned.</p>
	 * @return mixed <p>
	 * 		Returns the html content of the first element in the set of matched elements. 
	 * 		Otherwise the elements in the set of matched elements which html content is set are returned.</p>
	 */
	public function html(?string $html = null) : mixed
	{
		if (!isset($html)) return $this[0]->C14N(); 
		
		foreach ($this as $node) {
			
			$node = $this->toXDTObject($node);
			$node->emptyNode();
			$node->append($html);
		}
		
		return $this;
	}
	
	/**
	 * Adds the specified class(es) to each element in the set of matched elements.
	 * 
	 * @param string $className <p>
	 * 		One or more space seperated classes to be added to the class attribute of each matched element.</p>
	 * @return static <p>
	 * 		Returns the set of matched elements.</p>
	 */
	public function addClass (string $className) : static
	{
		foreach ($this as $node) {
			$node = $this->toXDTObject($node);
			
			if ($node->hasAttr('class') == false) {
				
				$node->attr('class', $className);
				continue;
			}
			
			foreach (explode(' ', $className) as $class) 
				if ($node->hasClass($class) == false) $node->data()->class .= ' ' . $class;
		}
		
		return $this;
	}
	
	/**
	 * Removes the specified class(es) from each element in the set of matched elements.
	 * 
	 * @param string $className <p>
	 * 		One or more space seperated classes to be removed to the class attribute of each matched element.</p>
	 * @return static <p>
	 * 		Returns the set of matched elements.</p>
	 */
	public function removeClass(string $className) : static
	{
		foreach ($this as $node) {
			$node = $this->toXDTObject($node);
			
			if ($node->hasAttr('class') == false) continue;
			
			foreach (explode(' ', $className) as $class)
				if ($node->hasClass($class)) {
						
						$arr = explode(' ', $node->data()->class);
						
						foreach ($arr as $k => $v) {
							
							if ($v == $class) array_splice($arr, $k, 1);
						}
						
						$node->data()->class = join(' ', $arr);
					}
				}
				
		return $this;
	}
	
	/**
	 * Adds or removes one or more classes from each element in the set of matched elements, depending on eather the class's presence.
	 * Enter description here ...
	 * @param string $className
	 * @return void
	 */
	public function toggleClass(string $className) : void
	{
		foreach ($this as $node) {
			$node = $this->toXDTObject($node);
			
			foreach (explode(' ', $className) as $class)
				if ($node->hasClass($class)) $node->removeClass($class);
				else $node->addClass($class);
		}
	}
	
	public function toArray() { return $this->list; }
	
	public function toString() { return $this->values(); }
	
	public function getIterator() : \Traversable
	{ 
		return new XDTIterator($this); 
	}
	
	public function offsetExists ($index) : bool
	{ 
		return is_null($this->item($index)) ? false: true; 
	}
	
	public function offsetGet (mixed $index) : mixed
	{ 
		return $this->item($index); 
	}
	
	public function offsetSet (mixed $index, mixed $new_node) : void
	{ 
		$this->replaceNodeByIndex($new_node, $index); 
	}
	
	public function offsetUnset (mixed $index) : void
	{ 
		$this->removeFromList($index); 
	}
}