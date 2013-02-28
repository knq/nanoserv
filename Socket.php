<?php

namespace Nanoserv;

/**
 * Base socket class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 0.9
 */
class Socket {

	/**
	 * Maximum number of bytes read by Read()
	 * @var int
	 */
	const DEFAULT_READ_LENGTH = 16384;

	/**
	 * Internal Socket unique ID
	 * @var int
	 */
	public $id;

	/**
	 * Socket stream descriptor
	 * @var resource
	 */
	public $fd;

	/**
	 * Is the socket connected ?
	 * @var bool
	 */
	public $connected = false;

	/**
	 * Is the socket waiting to be connected ?
	 * @var bool
	 */
	public $pending_connect = false;

	/**
	 * Is the socket waiting for ssl/tls handshake ?
	 * @var bool
	 */
	public $pending_crypto = false;

	/**
	 * Is the socket blocked ?
	 * @var bool
	 */
	public $blocked = false;

	/**
	 * Should we block reading from this socket ?
	 * @var bool
	 */
	public $block_reads = false;

	/**
	 * Stream context
	 * @var resource
	 */
	protected $context;

	/**
	 * Crypto type
	 * @var int
	 */
	public $crypto_type;

	/**
	 * Attached handler
	 * @var ConnectionHandler
	 */
	public $handler;

	/**
	 * Static instance counter
	 * @var int
	 */
	private static $sck_cnt;

	/**
	 * Socket contructor
	 *
	 * @param resource $fd
	 */
	public function __construct($fd = false, $crypto_type = false) {

		if ($fd === false) {

			$this->context = stream_context_create();

		} else {

			$this->fd = $fd;
			$this->connected = true;
			$this->Set_Blocking(false);
			$this->Set_Timeout(0);

			if ($crypto_type) $this->crypto_type = $crypto_type;

		}

		$this->id = ++Socket::$sck_cnt;

	}

	/**
	 * Get stream options
	 *
	 * @return array
	 * @since 0.9
	 */
	public function Get_Options() {

		if ($this->fd) {

			return stream_context_get_options($this->fd);

		} else {

			return stream_context_get_options($this->context);

		}

	}

	/**
	 * Set a stream context option
	 *
	 * @param string $wrapper
	 * @param string $opt
	 * @param mixed $val
	 * @return bool
	 * @since 0.9
	 */
	public function Set_Option($wrapper, $opt, $val) {

		if ($this->fd) {

			return stream_context_set_option($this->fd, $wrapper, $opt, $val);

		} else {

			return stream_context_set_option($this->context, $wrapper, $opt, $val);

		}

	}

	/**
	 * Set timeout
	 *
	 * @param int $timeout
	 * @return bool
	 * @since 0.9
	 */
	protected function Set_Timeout($timeout) {

		return stream_set_timeout($this->fd, $timeout);

	}

	/**
	 * Sets wether the socket is blocking or not
	 *
	 * @param bool $block
	 * @return bool
	 * @since 0.9
	 */
	protected function Set_Blocking($block) {

		return stream_set_blocking($this->fd, $block);

	}

	/**
	 * Flag the socket so that the main loop won't read from it even if data is available.
	 *
	 * This can be used to implement flow control when proxying data between two asymetric connections for example.
	 *
	 * @param bool $block
	 * @return bool the previous status
	 * @since 2.0.3
	 */
	public function Block_Reads($block) {

		$ret = $this->block_reads;

		$this->block_reads = $block;

		return $ret;

	}

	/**
	 * Set the stream write buffer (PHP defaults to 8192 bytes)
	 *
	 * @param int $buffer_size
	 * @return int
	 * @since 2.0
	 */
	public function Set_Write_Buffer($buffer_size) {

		return stream_set_write_buffer($this->fd, $buffer_size);

	}

	/**
	 * Enable or disable ssl/tls crypto on the socket
	 *
	 * @param bool $enable
	 * @param int $type
	 * @return mixed
	 * @since 0.9
	 */
	public function Enable_Crypto($enable = true, $type = false) {

		if ($type) $this->crypto_type = $type;

		$ret = @stream_socket_enable_crypto($this->fd, $enable, $this->crypto_type);

		$this->pending_crypto = $ret === 0;

		return $ret;

	}

	/**
	 * Setup crypto if needed
	 *
	 * @return bool
	 * @since 0.9
	 */
	public function Setup() {

		if (isset($this->crypto_type)) return $this->Enable_Crypto();

		return true;

	}

	/**
	 * Get local socket name
	 *
	 * @return string
	 * @since 0.9
	 */
	public function Get_Name() {

		return stream_socket_get_name($this->fd, false);

	}

	/**
	 * Get remote socket name
	 *
	 * @return string
	 * @since 0.9
	 */
	public function Get_Peer_Name() {

		return stream_socket_get_name($this->fd, true);

	}

	/**
	 * Read data from the socket and return it
	 *
	 * @param int $length maximum read length
	 * @return string
	 * @since 0.9
	 */
	public function Read() {

		return fread($this->fd, self::DEFAULT_READ_LENGTH);

	}

	/**
	 * Read data from a non connected socket and return it
	 *
	 * @param string &$addr contains the message sender address upon return
	 * @param int $len maximum read length
	 * @return string
	 * @since 0.9.61
	 */
	public function Read_From(&$addr, $len = 16384) {

		return stream_socket_recvfrom($this->fd, $len, NULL, $addr);

	}

	/**
	 * Write data to the socket
	 *
	 * write returns the number of bytes written to the socket
	 *
	 * @param string $data
	 * @return int
	 * @since 0.9
	 */
	public function Write($data) {

		$nb = fwrite($this->fd, $data);

		if (isset($data[$nb])) $this->blocked = true;

		return $nb;

	}

	/**
	 * Write data to a non connected socket
	 *
	 * @param string $to in the form of "<ip_address>:<port>"
	 * @param string $data
	 * @return int
	 * @since 0.9.61
	 */
	public function Write_To($to, $data) {

		return stream_socket_sendto($this->fd, $data, NULL, $to);

	}

	/**
	 * Write data from stream to socket
	 *
	 * returns the number of bytes read from the stream and written to the socket
	 *
	 * @param resource $stream
	 * @param int $len maximum length (bytes) to read/write
	 * @return int
	 * @since 2.1
	 */
	public function Write_From_Stream($stream, $len = 16384) {

		return stream_copy_to_stream($stream, $this->fd, $len);

	}

	/**
	 * Query end of stream status
	 *
	 * @return bool
	 * @since 0.9
	 */
	public function Eof() {

		$fd = $this->fd;

		if (!is_resource($fd)) return true;

		stream_socket_recvfrom($fd, 1, STREAM_PEEK);

		return feof($fd);

	}

	/**
	 * Close the socket
	 * @since 0.9
	 */
	public function Close() {

		@fclose($this->fd);

		$this->connected = $this->pending_connect = false;

	}

	/**
	 * Socket destructor
	 */
	public function __destruct() {

		Core::Free_Write_Buffers($this->id);

		$this->Close();

	}

}


