<?php
namespace Clicalmani\XPower\Exceptions;

/**
 * @package clicalmani/xpower 
 * @author @clicalmani
 */
class OutOfRangeException extends \Exception {
	function __construct($message){
		parent::__construct($message);
	}
}
?>