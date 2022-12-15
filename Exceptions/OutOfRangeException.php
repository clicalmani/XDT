<?php
namespace Clicalmani\XPower\Exceptions;

class OutOfRangeException extends \Exception {
	function __construct($message){
		parent::__construct($message);
	}
}
?>