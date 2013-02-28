<?php

namespace Nanoserv;

/**
 * Datagram listener / handler class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 0.9.61
 */
abstract class DatagramHandler extends namespace\Handler {

	/**
	 * Is the listener active ?
	 * @var bool
	 */
	public $active = false;

	/**
	 * DatagramHandler constructor
	 *
	 * @param string $addr
	 * @param string $handler_classname
	 * @param mixed $handler_options
	 */
	public function __construct($addr) {

		$this->socket = new ServerSocket($addr);

	}

	/**
	 * Activate the listener
	 *
	 * @return bool
	 * @since 0.9.61
	 */
	public function Activate() {

		try {

			if ($ret = $this->socket->Listen(true)) $this->active = true;

			return $ret;

		} catch (ServerException $e) {

			throw new ServerException($e->getMessage(), $e->getCode(), $e->addr, $this);

		}

	}

	/**
	 * Deactivate the listener
	 * @since 0.9.61
	 */
	public function Deactivate($close_socket = true) {

		if ($close_socket) {

			$this->socket->Close();

		}

		$this->active = false;

	}

	/**
	 * Send data over the connection
	 *
	 * @param string $to in the form of "<ip_address>:<port>"
	 * @param string $data
	 * @return int
	 * @since 0.9.61
	 */
	public function Write($to, $data) {

		return $this->socket->Write_To($to, $data);

	}

	/**
	 * Event called on data reception
	 *
	 * @param string $from
	 * @param string $data
	 * @since 0.9.61
	 */
	public function on_Read($from, $data) {

	}

	/**
	 * DatagramHandler destructor
	 */
	public function __destruct() {

		$this->Deactivate();

	}
}
