<?php

namespace Clicalmani\XPower;

use Clicalmani\XPower\XDTNodeList;
use Clicalmani\XPower\XDTIterator;
use Clicalmani\XPower\XDTNamedNodeMap;
use \DOMElement as DOMElement;

/**
 * When set it detroys the last selection result set and starts over a new selection.
 * 
 * @var integer
 */
define('XDT_SELECT_DESTROY', 0);

/**
 * When set it Filters the last selection result set.
 * 
 * @see XDTNodeList
 * @var integer
 */
define('XDT_SELECT_FILTER', 1);

/** ==========================================================================================================
 * ----------------- XDT Class
 * ===========================================================================================================
 * 
 * ===========================================================================================================
 * @author Abdoul-Madjid
 * Developper & Designer
 * ===========================================================================================================
 * Code website
 * @link
 * ===========================================================================================================
 * @copyright Abdoul-Madjid All Right Reserved
 * @license 
 * ===========================================================================================================
 * @since 29/07/2015
 * @version 2.3.0
 * ===========================================================================================================
 *
 */

/**
 * XDT traverses XML DOM; It implements various methods to facilidate elements selection; such as getElementById, 
 * getElementsByClass, getElementsByAttr, getElementsByPseudo; the selector used for elements selection respects 
 * CSS structure, it also defines jQuery special selectors, such as nth, first, last, eq, and not
 * 
 * @author Abdoul-Madjid
 * @version 2.3.0
 *
 */
class XDT 
{
	/**
	 * Holds the loaded file directory.
	 * 
	 * @var \Directory|false
	 */
	protected $xml_dir;

	/**
	 * Holds XML document element.
	 * 
	 * @var mixed
	 */
	private $document;  

	/**
	 * Holds the document root element.
	 * 
	 * @var mixed
	 */
	protected $root;           
	
	/**
	 * A set of the current matched elements.
	 * 
	 * @var XDTNodeList
	 */
	protected $query_result = null; 
	
	private $file_name = null; 
	
	/**
	 * Create a new instance of XDT.
	 * 
	 * @param string $xml_dir [optional] <p>
	 *     Directory containing the XML file. When omitted and connect method is called instead, 
	 *     the file is search within the current directory.</p>
	 * @return void
	 */
	public function __construct() 
	{
		if ( !file_exists(database_path('xml')) ) {
			mkdir( database_path('xml') );
		}
		
		$this->document = new \DOMDocument('1.0', 'utf-8');
		$this->xml_dir = dir(database_path('xml'));
	}
	
	/**
	 * Load XML from a file.
	 * 
	 * @see XDT::load
	 * @param string $file_name <p>
	 *     XML file to load. The file extension (.xml) is optional.</p>
	 * @param bool $preserve_white_space [optional] <p>
	 * 		Do not remove redundant white space. Default to false.</p>
	 * @param boolean $format_output [optional] <p>
	 * 		Nicely formats output with indentation and extra space. Default to false.</p>
	 * @return bool <p>
	 * 		Returns true on success and false on failure.</p>
	 *     
	 */
	public final function connect(string $file_name, bool $preserve_white_space = false, bool $format_output = false) : bool
	{ 
		
		if (preg_match('/\.xml$/i', $file_name) == false) {
			$file_name .= '.xml';
		}
		
		$this->document->preserveWhiteSpace = $preserve_white_space;
		$this->document->formatOutput = $format_output;
		
		$this->file_name = $file_name;
		$this->query_result = null;
		
		if ($this->document->load($this->xml_dir->path . DIRECTORY_SEPARATOR . $file_name)) {
			$this->root = $this->document->childNodes->item(0);
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Load a string containing XML structure.
	 * 
	 * @param string $xml <p>
	 * 		String containing XML structure.</p>
	 * @param boolean $preserve_white_space [optional] <p>
	 * 		Do not remove redundant white space. Default to false.</p>
	 * @param bool $format_output [optional] <p>
	 * 		Nicely formats output with indentation and extra space. Default to false.</p>
	 * @return bool <p>
	 * 		Returns true on success and false on failure.</p>
	 */
	public final function load (string $xml, bool $preserve_white_space = false, bool $format_output = false) : bool
	{
		
		$this->document->preserveWhiteSpace = $preserve_white_space;
		$this->document->formatOutput = $format_output;
		
		$this->query_result = null;
		
		if ($this->document->loadXML($xml)) {
			$this->root = $this->document->childNodes->item(0);
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Set new directory.
	 * 
	 * @param string $new_dir <p>
	 * 		New directory.</p>
	 * @return void
	 */
	public final function setDirectory (string $new_dir) : void
	{ 
		$this->xml_dir = dir($new_dir); 
	}
	
	/**
	 * Get the current directory.
	 * 
	 * @return string <p>
	 * 		The directory path.</p>
	 */
	public final function getDirectory () : string
	{ 
		return $this->xml_dir->path; 
	}
	
	/** 
	 * Get the document root element.
	 * 
	 * @return XDTNodeList
	 */
	public final function getDocumentRootElement() : XDTNodeList
	{ 
		return new XDTNodeList($this->root); 
	}
	
	/**
	 * Closes the current xml file and saves the changes to the file.
	 * 
	 * @return bool <br><br>
	 *     Returns TRUE on success, or FALSE on error or failure.
	 */
	public final function close() : bool
	{ 
		return $this->document->save($this->xml_dir->path . DIRECTORY_SEPARATOR . $this->file_name, LIBXML_NOEMPTYTAG); 
	}
	
	/**
	 * Save the loaded XML to a string.
	 * 
	 * @return mixed <p>
	 * 		This method returns true on success an false on failure.</p>
	 */
	public final function save() : mixed
	{ 
		return $this->document->saveXML($this->root, LIBXML_NOEMPTYTAG); 
	}
	
	/**
	 * Parse a string containing a CSS selector expression, match the selected elements and 
	 * returns an XDTNodeList object containing the set of matched elements.
	 *     
	 * @param string $selector [optional] <p>
	 *     A string containing the selector expression. You can provide multiples selectors 
	 *     by separating them with a comma (,).</p>
	 * @param DOMElement $context [optional] <p>
	 *     DOM element used as the selection context.</p>
	 * @param int $flag [optional] <p>
	 * 	   Accepted flags are: <br>
	 *     <ul>
	 *       <li>XDT_SELECT_DESTROY end the current operation and start over a new operation.</li>
	 *       <li>XDT_SELECT_FILTER select within the last result set.</li>
	 *     </ul>
	 * @return XDTNodeList
	 */
	public final function select(string $selector = '*', \DOMElement $context = null, int $flag = XDT_SELECT_DESTROY) : XDTNodeList
	{
		if ($flag === XDT_SELECT_DESTROY) $this->query_result = null;
		
	    if (isset($context)) {
	    	
	    	$all = $context->getElementsByTagName('*');
	    	$l = new XDTNodeList();
	    	
	    	foreach ($all as $node) {
	    		$l->add($node);
	    	}
	    	
	    	$this->query_result = $l;
		}
		
		/** ------------------------------------------------------- **/
		/** ================ SELECT MULTIPLE ====================== **/
		/** ------------------------------------------------------- **/
		if (preg_match('/[,]/', $selector)) return $this->multipleSelect($selector);
		
		$selector = $this->parseSelector($selector); // Convert selector to code logic
		
		$chunks = preg_split('/[\s>+]/', $selector, -1, PREG_SPLIT_NO_EMPTY);
		$glues = preg_split('/[^\s>+]/', $selector, -1, PREG_SPLIT_NO_EMPTY);
		
		if (empty($glues)) return $this->querySelector($selector);
		
		$this->query_result = $this->querySelector($chunks[0]);
		
		for ($index = 1; $index < count($chunks); $index++) {
			
			/**
			 * Cast query result to NodeList.
			 */
			if (is_object($this->query_result) AND get_class($this->query_result) === 'DOMElement') {
				$this->query_result = new XDTNodeList($this->query_result);
			}
			
			$list = new XDTNodeList();
			
			switch (trim($glues[$index-1])) {
				case '': // Select descendent elements
					
					foreach ($this->query_result as $node) 
						foreach ($node->getElementsByTagName('*') as $n) $list->add($n);
				break;
				
				case '>': // Select child elements
				    
					foreach ($this->query_result as $node) {
						foreach ($node->childNodes as $child) {
							if ($child->nodeType == 3) continue;
							$list->add($child);
						}
					}
				break;
				
				case '+': // Select adjacent-element
				    
					foreach ($this->query_result as $node) {
						foreach ($node->parentNode->childNodes as $child) {
							if ($child->nodeType == 3) continue;
							$list->add($child);
						}
					}
				break;
			}
			
			$this->query_result = $list;
			$this->query_result = $this->querySelector($chunks[$index]);
		}
		
		if (is_object($this->query_result) AND get_class($this->query_result) === 'DOMElement') {
			$this->query_result = new XDTNodeList($this->query_result);
		}
		
		return $this->query_result;
	}
	
	private function multipleSelect (string $selectors, \DOMElement $context = null, int $flag = XDT_SELECT_DESTROY) : XDTNodeList
	{
		$selectors = preg_split('/[,]/', $selectors, -1, PREG_SPLIT_NO_EMPTY);
		
		$list = new XDTNodeList();
		
		foreach ($selectors as $selector) { 
			
			$list->merge($this->select($selector, $context, $flag));
			$this->query_result = null;
		}
		
		return $list;
	}
    
	private function parseSelector (string $selector) : mixed
	{
		$char = substr($selector, 0, 1);
		
		if ($char === '.') $selector = '!' . substr($selector, 1);
		elseif ($char === '#') $selector = '&' . substr($selector, 1);
		
	    if (strpos($selector, ':') !== false) {
			$selector = str_replace(':', ';', $selector);
		}
		
		$pattern = '/\[([^\[=\^\$<>\|\*]+)([=\^\$<>\|\*]+)?([^\.!&#%\]]+)?\]/';
		if (preg_match($pattern, $selector)) {
			
			$selector = str_replace('[', ':[', $selector);
			
			$matches = array();
			preg_match_all($pattern, $selector, $matches);
			
			foreach ($matches[2] as $match) {
				
				$tmp = $match;
				$match = str_replace('>', '|', $match);
				$selector = str_replace($tmp, $match, $selector);
			}
			
			foreach ($matches[3] as $match) {
				
				$tmp = $match;
				$match = trim($match, '\'"');
				$match = str_replace(' ', '~', $match);
				$selector = str_replace($tmp, $match, $selector);
			}
		}
		
		if (preg_match('/\([n0-9\+]+\)/', $selector)) {
			
			$matches = array();
			preg_match_all('/\([n0-9\+]+\)/', $selector, $matches);
			
			foreach ($matches as $match) {
				$old_match = $match[0];
				$match = str_replace('+', 'p', $match[0]);
				$selector = str_replace($old_match, $match, $selector);
			}
		}
		
		if (preg_match('/([^#]+)(#)/', $selector)) {
			
			$selector = preg_replace('/([^#]+)(#)/', '\\1&', $selector);
		}
		
		if (preg_match('/([^\.]+)(\.)/', $selector)) {
			
			$selector = preg_replace('/([^\.]+)(\.)/', '\\1!', $selector);
		}
		
		return $selector;
	}
	
	private function querySelector (string $selector) : XDTNodeList
	{
		$selector = trim($selector);
		$chunks = array();
		$glues = array();
		
		if(preg_match('/[^\.#!&\s]+[\.#:!&]/', $selector)) {
			$chunks = preg_split('/[\.#:!&]/', $selector, -1, PREG_SPLIT_NO_EMPTY);
		    $glues = preg_split('/[^\.#:!&]/', $selector, -1, PREG_SPLIT_NO_EMPTY);
		}
		
		if(empty($glues)) { 
			$glue = substr($selector, 0, 1);
			$selector = substr($selector, 1);
			
			switch ($glue) {
				case '&':
		        case '#': return $this->getElementById($selector);
		        case '!':
		        case '.': return $this->getElementsByClass($selector);
				default: 
					
					if (preg_match('/;/', $selector)) { 
						
						$chunks = explode(';', $glue . $selector, 2);
						return $this->getElementsByPseudo($chunks[0] . ':' . $chunks[1]);
					}
					
					return $this->getElementsByTagName($glue . $selector);
			}
		} else {
			
			switch ($glues[0]) {
		        case '&': return $this->getElementById($chunks[1], $chunks[0]); 
		        case '!': return $this->getElementsByClass(join('!', array_splice($chunks, 1)), $chunks[0]);
				case ':': return $this->getElementsByAttr($selector);
				default: return $this->query_result;
			}
		}
	}
	
	private function getElementById (string $selector, string $tag_name = null) : XDTNodeList
	{
		$l = new XDTNodeList();
		
		if (isset($tag_name)) {
			
			$this->query_result = $this->getElementsByTagName($tag_name);
			
			foreach ($this->query_result as $node) 
				if (strtolower($node->nodeName) === strtolower($tag_name) AND @$node->attributes->getNamedItem('id')->value === $selector) {
					
					$l->add($node);
					$this->query_result = $l;
					
					return $l;
				}
		}
		
		$this->query_result = $this->getElementsByTagName('*');
		
		foreach ($this->query_result as $node) 
			if (@$node->attributes->getNamedItem('id')->value === $selector) {
				
				$l->add($node);
				$this->query_result = $l;
				
				return $l;
			}
		
		$this->query_result = $l;
		
		return $l;
	}
	
	private function getElementsByClass (string $selector, string $tag_name = null) : XDTNodeList
	{
		$l = new XDTNodeList(); 
		
		if (strpos($selector, ';')) {
				
			$chunks = explode(';', $selector);
		    $pseudo = $chunks[1];
		    
		    if (preg_match('/(nth-child|nth|first|first-child|last|last-child|eq|not)(\(([evnodp0-9]+)\))?/', $pseudo) == false) $pseudo = null;
		    
		    $selector = $chunks[0];
		} else {
			
			$pseudo = null;
		}
		
		if (isset($tag_name)) $this->query_result = $this->getElementsByTagName($tag_name);
		elseif ($this->query_result == null AND get_class($this->root) === 'DOMElement') $this->query_result = $this->getElementsByTagName('*');
		
		foreach ($this->query_result as $node) {
			$node = $this->toXDTObject($node);
			
			if ($node->hasAttr('class') === false) continue;
			
			$classes = preg_split('/\s/', $node->attr('class'), -1, PREG_SPLIT_NO_EMPTY);
			$values = array_unique(explode('!', $selector));
			$count = sizeof($values);
			foreach ($values as $key => $value) {
				if (in_array($value, $classes)) $count = $count-1;
			}
			
			if ($count === 0) $l->add($node[0]);
		}
		
		$this->query_result = $l;
		
		if (isset($pseudo)) return $this->getElementsByPseudo('*:' . $pseudo);
		else return $this->query_result;
	}
	
	private function getElementsByAttr (string $selector) : XDTNodeList
	{ 
		$matches = array();
		preg_match('/^([^\[:]+):\[([^\[=\^\$<>\|\*]+)([=\^\$<>\|\*]+)?([^\.!&#%\]]+)?\]$/', $selector, $matches);
		
		$list = new XDTNodeList();
		
		if (empty($matches)) {
			
			$this->query_result = new XDTNodeList();
			return $this->query_result;
		}
		
		$this->query_result = $this->getElementsByTagName($matches[1]);
		
		$attr_name = $matches[2];
		
		if (isset($matches[3]) AND isset($matches[4])) {
			
			foreach ($this->query_result as $node) {
				
				$operator = $matches[3];
				$attr_value = str_replace('~', ' ', $matches[4]);
				
			    if ($node->attributes->getNamedItem($attr_name)) {
			    	
			    	switch ($operator) {
			    		/** Attr is egal to a specific value **/
			    		case '=': if (strcmp($node->attributes->getNamedItem($attr_name)->value, $attr_value) == 0) $list->add($node); break;
			    		/** Attr contains a specific value **/
			    		case '*=': if (strstr($node->attributes->getNamedItem($attr_name)->value, $attr_value)) $list->add($node); break;
			    		/** Attr value starts from a specific value **/
			    		case '^=': if (preg_match("/^$attr_value/", $node->attributes->getNamedItem($attr_name)->value)) $list->add($node); break;
			    		/** Attr value ends with a specific value **/
			    		case '$=': if (preg_match("/$attr_value$/", $node->attributes->getNamedItem($attr_name)->value)) $list->add($node); break;
			    		/** Attr value is greater than a value **/
			    		case '|': if ($node->attributes->getNamedItem($attr_name)->value > $attr_value) $list->add($node); break;
			    		/** Attr value is greater than or egal to a value **/
			    		case '|=': if ($node->attributes->getNamedItem($attr_name)->value >= $attr_value) $list->add($node); break;
			    		/** Attr value is less than a value **/
			    		case '<': if ($node->attributes->getNamedItem($attr_name)->value < $attr_value) $list->add($node); break;
			    		/** Attr value is less than or egal to a value **/
			    		case '<=': if ($node->attributes->getNamedItem($attr_name)->value <= $attr_value) $list->add($node); break;
			    		/** Operator does not exists **/
			    		//default: $list->add($node); break;
			    	}
			    }
			}
			
			$this->query_result = $list;
			
			return $this->query_result;
		}
		
		foreach ($this->query_result as $node) {
			
		    if ($node->attributes->getNamedItem($attr_name)) $list->add($node);
		}
		
		$this->query_result = $list;
			
		return $this->query_result;
	}
	
	private function getElementsByTagName (string $tag) : XDTNodeList
	{
		if ($this->query_result == null AND $this->root instanceof \DOMElement) $this->query_result = $this->root->getElementsByTagName('*');
		
		if ($tag === '*') return $this->query_result;
		
		$list = new XDTNodeList();
		
		foreach ($this->query_result as $node) {
			if (($node->nodeType === 3)) continue;
			
			if (strtolower($node->nodeName) === strtolower($tag)) { 
				$list->add($node);
			}
		}
		
		$this->query_result = $list;
		
		return $this->query_result;
	}
	
	protected function processData(mixed $data) : mixed
	{
		if (is_string($data)) {
			if (preg_match('/^</', $data)) {
				$this->document = $this[0]->ownerDocument;
				return $this->createDocumentFragmentWithData($data);
			} else {
				$this->query_result = null; 
				$this->root = $this[0]->ownerDocument->firstChild;
				return $this->select($data);
			}
		} elseif (is_object($data)) {
			if ($data instanceof XDTNodeList OR $data instanceof \DOMElement) return $data;
		}
		
		return null;
	}
	
	protected function getElementsByPseudo (string $selector) : mixed
	{
		$chuncks = explode(':', $selector);
		$list = new XDTNodeList();
		
		if ($chuncks[0] == 'root') return $this->getDocumentRootElement();
		
		if ($this->query_result == null AND get_class($this->root) === 'DOMElement') $this->query_result = $this->getElementsByTagName($chuncks[0]);
		
		$this->query_result = $this->select($chuncks[0], null, XDT_SELECT_FILTER);
		
		foreach ($this->query_result as $node) {
			
			$key = 0;
			
			foreach ($node->parentNode->childNodes as $n) {
				
				if ($n->nodeType === 3) continue;
				if ($list->index($n) !== -1 OR !$n->isSameNode($node)) {
					
					$key++;
					continue;
				}
				
				switch ($chuncks[1]) {
	    		case 'first-child':
	    		case 'first': 
	    			
	    			if (($key === 0 AND $n->isSameNode($node))) $list->add($node);
	    			
	    			break;
	    		case 'last-child':
	    		case 'last': 
	    			
	    			if ($n->isSameNode($node)) {
	    				
	    				$count = 1;
	    				while ($node->parentNode->childNodes->item($node->parentNode->childNodes->length-$count)->nodeType === 3) $count++;
	    				
	    				if ($n->isSameNode($node->parentNode->childNodes->item($node->parentNode->childNodes->length-$count))) $list->add($node);
	    			}
	    			
	    			break;
	    		default:
	    			
	    			$matches = array();
	    			preg_match('/(nth-child|nth|eq)\(([evnodp0-9]+)\)/', $chuncks[1], $matches);
	    			
	    			if (in_array(@$matches[1], array('nth', 'nth-child', 'eq'))) {
	    				
	    				if ($matches[2] === 'even') {
	    					if (($key+1)%2==0) $list->add($node);
	    				} elseif ($matches[2] === 'odd') {
	    					if (($key+1)%2!=0) $list->add($node);
	    				} elseif (strpos($matches[2], 'n') === false) {
	    					if ($matches[2] == $key+1) $list->add($node);
	    				} elseif (preg_match('/[np0-9]+/', $matches[2])) {
	    					
	    					if (count(explode('p', $matches[2])) > 1) { 
	    						$ops = array();
	    						preg_match('/([0-9]+)?np([0-9]+)?/', $matches[2], $ops);
	    						 
	    						if (empty($ops[1])) $ops[1] = 1;
	    					} else {
	    						$ops = array();
	    						preg_match('/([0-9]+)?n/', $matches[2], $ops);
	    						 
	    						if (empty($ops[1])) $ops[1] = 1;
	    					}
	    					
	    					if (!isset($ops[2])) $ops[2] = 0;
	    					
	    				    if ((($ops[1]*$key)+$ops[2]) === ($key+1)) $list->add($node);
	    				}
	    			}
	    		}
	    		
	    		$key++;
			} 
		}
		
		$this->query_result = $list;
		
		return $this->query_result;
	}
	
	private function createDocumentFragmentWithData($data) : mixed
	{
		$frag = $this->createDocumentFragment();
		if ($frag->appendXML($data)) {
			return $frag;
		}
		
		return false;
	}
	
	public function createDocumentFragment() { return $this->document->createDocumentFragment(); }
	
	/**
	 * Cast DOMElement to XDTNodeList object.
	 * 
	 * @param DOMElement $node
	 * @return XDTNodeList
	 */
	public function toXDTObject(DOMElement $node) { return new XDTNodeList($node); }
	
	/**
	 * Alias toXDTObject
	 * 
	 * @param DOMElement $node
	 * @return XDTNodeList
	 * @see toXDTObject
	 */
	public function parse(DOMElement $node) { return new XDTNodeList($node); }
	
	/**
	 * @See toXDTObject
	 */
	public function createObject(DOMElement $node) { return new XDTNodeList($node); }
	
	/**
	 * Create a new XML file, if the file already exists, its content will be overwritten.
	 * 
	 * @param string $file_name <p>
	 *     File name</p>
	 * @param string $root [optional] <p>
	 *     New content. Must contain at least the root element.</p>
	 * @param string $charset [optional] <p>
	 *     Charset, default value is UTF-8. </p>
	 * @param string $version [optional] <p>
	 * 	   XML file version, default value is 1.0 </p>
	 * @return Boolean True on success, or False on error.
	 */
	public function createNewXMLFile ($file_name, $root = '<root></root>', $version = '1.0', $charset = 'UTF-8') {
		
		$file_name = $this->xml_dir->path . DIRECTORY_SEPARATOR . $file_name;
		
		$xml = "<?xml version=\"$version\" encoding=\"$charset\"?>\n$root";
		
		$handle = fopen($file_name, "w+");
		fwrite($handle, $xml);
		return fclose($handle);
	}
	
	public function newFile($file_name, $root = null, $version = '1.0', $charset = 'UTF-8') { $this->createNewXMLFile($file_name, $root, $version, $charset); }
}


?>