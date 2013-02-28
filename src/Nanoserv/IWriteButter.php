<?php

namespace Nanoserv;

/**
 * Write buffer interface
 */
interface IWriteBuffer {

	/**
	 * Setup a new write buffer
	 *
	 * @param Socket $socket
	 * @param mixed $data
	 * @param mixed $callback
	 */
	public function __construct(Socket $socket, $data, $callback = false);

	/**
	 * Get availability of data
	 *
	 * @return bool
	 * @since 0.9
	 */
	public function Waiting_Data();

	/**
	 * Write data to socket and advance buffer pointer
	 *
	 * @param int $length
	 */
	public function Write($length = NULL);

}

