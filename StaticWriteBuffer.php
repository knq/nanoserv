<?php

namespace Nanoserv;

/**
 * Static write buffer class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 0.9
 */
class StaticWriteBuffer extends namespace\WriteBuffer implements namespace\IWriteBuffer {

	/**
	 * Buffered data pointer
	 * @var int
	 */
	private $pointer = 0;

	/**
	 * Get availability of data
	 *
	 * @return bool
	 * @since 0.9
	 */
	public function Waiting_Data() {

		return isset($this->data[$this->pointer]);

	}

	/**
	 * Write data to socket and advance buffer pointer
	 *
	 * @param int $length
	 * @since 1.1
	 */
	public function Write($length = 16384) {

		$this->pointer += $this->socket->Write(substr($this->data, $this->pointer, $length));

	}

}


