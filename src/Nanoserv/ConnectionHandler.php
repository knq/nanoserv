<?php

namespace Nanoserv;

/**
 * Connection handler class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 0.9
 */
abstract class ConnectionHandler extends namespace\Handler {
    /**#@+
     * Cause of connection failure
     * @var int
     */
    const FAIL_CONNREFUSED = 1;
    const FAIL_TIMEOUT = 2;
    const FAIL_CRYPTO = 3;
    /**#@-*/

    /**
     * Send data over the connection
     *
     * @param  string            $data
     * @param  mixed             $callback
     * @return StaticWriteBuffer
     * @since 0.9
     */
    public function Write($data, $callback=false) {
        return Core::New_Static_Write_Buffer($this->socket, $data, $callback);

    }

    /**
     * Send open stream over the connection
     *
     * @param  resource          $stream
     * @param  mixed             $callback
     * @return StreamWriteBuffer
     * @since 2.1
     */
    public function Write_Stream($stream, $callback=false) {
        return Core::New_Stream_Write_Buffer($this->socket, $stream, $callback);

    }

    /**
     * Connect
     *
     * @param int $timeout timeout in seconds
     * @since 0.9
     */
    public function Connect($timeout=false) {
        try {
            $this->socket->Connect($timeout);

        } catch (ClientException $e) {
            Core::Free_Connection($this);

            throw new ClientException($e->getMessage(), $e->getCode(), $e->addr, $this);

        }

    }

    /**
     * Disconnect
     */
    public function Disconnect() {
        $this->socket->Close();

        Core::Free_Connection($this);

    }

    /**
     * Event called on received connection
     * @since 0.9
     */
    public function on_Accept() {
    }

    /**
     * Event called on established connection
     * @since 0.9
     */
    public function on_Connect() {
    }

    /**
     * Event called on failed connection
     *
     * @param int $failcode see ConnectionHandler::FAIL_* constants
     * @since 0.9
     */
    public function on_Connect_Fail($failcode) {
    }

    /**
     * Event called on disconnection
     * @since 0.9
     */
    public function on_Disconnect() {
    }

    /**
     * Event called on data reception
     *
     * @param string $data
     * @since 0.9
     */
    public function on_Read($data) {
    }

    /**
     * Event called before forking
     *
     * @since 2.0
     */
    public function on_Fork_Prepare() {
    }

    /**
     * Event called after forking, both on master and child processes
     *
     * @since 2.0
     */
    public function on_Fork_Done() {
    }

}
