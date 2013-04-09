<?php

namespace Nanoserv;

/**
 * Listener class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 0.9
 */
class Listener {
    /**
     * Attached socket
     * @var ServerSocket
     */
    public $socket;

    /**
     * Name of the handler class
     * @var string
     * @see NS_Connetion_Handler
     */
    public $handler_classname;

    /**
     * Handler options
     *
     * this is passed as the first constructor parameter of each spawned connection handlers
     *
     * @var mixed
     */
    public $handler_options;

    /**
     * Is the listener active ?
     * @var bool
     */
    public $active = false;

    /**
     * If set the listener will fork() a new process for each accepted connection
     * @var bool
     */
    public $forking = false;

    /**
     * Listener constructor
     *
     * @param string $addr
     * @param string $handler_classname
     * @param mixed  $handler_options
     */
    public function __construct($addr, $handler_classname, $handler_options=false, $forking=false) {
        $this->socket = new ServerSocket($addr);
        $this->handler_classname = $handler_classname;
        $this->handler_options = $handler_options;
        $this->forking = ($forking && is_callable("pcntl_fork"));

    }

    /**
     * Set a stream context option
     *
     * @param  string $wrapper
     * @param  string $opt
     * @param  mixed  $val
     * @return bool
     * @since 0.9
     */
    public function Set_Option($wrapper, $opt, $val) {
        return $this->socket->Set_Option($wrapper, $opt, $val);

    }

    /**
     * Sets wether the listener should fork() a new process for each accepted connection
     *
     * @param  bool $forking
     * @return bool
     * @since 0.9
     */
    public function Set_Forking($forking=true) {
        if ($forking && !is_callable("pcntl_fork")) return false;

        $this->forking = $forking;

        return true;

    }

    /**
     * Activate the listener
     *
     * @return bool
     * @since 0.9
     */
    public function Activate() {
        try {
            if ($ret = $this->socket->Listen()) $this->active = true;
            return $ret;

        } catch (ServerException $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e->addr, $this);

        }

    }

    /**
     * Deactivate the listener
     * @since 0.9
     */
    public function Deactivate() {
        $this->socket->Close();
        $this->active = false;

    }

    /**
     * Listener destructor
     */
    public function __destruct() {
        $this->Deactivate();

    }

}
