<?php

namespace Nanoserv;

/**
 * Server exception class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 2.0
 */
class ServerException extends namespace\Exception {

	public $listener;

	public function __construct($errmsg, $errno, $addr, Listener $listener = NULL) {

		parent::__construct($errmsg, $errno, $addr);

		$this->listener = $listener;

	}

}

