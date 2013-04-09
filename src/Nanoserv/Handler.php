<?php

namespace Nanoserv;

/**
 * Base handler class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 0.9
 */
abstract class Handler {
    /**
     * Attached socket
     * @var Socket
     */
    public $socket;

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

}
