<?php

namespace Nanoserv;

/**
 * Write buffer base class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 0.9
 */
abstract class WriteBuffer {
    /**
     * Attached socket
     * @var Socket
     */
    public $socket;

    /**
     * Buffered data
     * @var string
     */
    protected $data;

    /**
     * End-of-write Callback
     * @var mixed
     */
    protected $callback = false;

    /**
     * WriteBuffer constructor
     *
     * @param Socket $socket
     * @param mixed  $data
     * @param mixed  $callback
     */
    public function __construct(Socket $socket, $data, $callback = false) {
        $this->socket = $socket;
        $this->data = $data;
        $this->callback = $callback;

    }

    /**
     * WriteBuffer destructor
     */
    public function __destruct() {
        if ($this->callback) call_user_func($this->callback, $this->Waiting_Data());

    }

}
