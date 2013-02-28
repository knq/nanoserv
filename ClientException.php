<?php

namespace Nanoserv;

/**
 * Client exception class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 2.0
 */
class ClientException extends namespace\Exception {

	public $handler;

	public function __construct($errmsg, $errno, $addr, Handler $handler = NULL) {

		parent::__construct($errmsg, $errno, $addr);

		$this->handler = $handler;

	}

}

