<?php

namespace Nanoserv;

/**
 * Stream write buffer class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 2.1
 */
class StreamWriteBuffer extends namespace\WriteBuffer implements namespace\IWriteBuffer {
    /**
     * Get availability of data from stream
     *
     * @return bool
     * @since 0.9
     */
    public function Waiting_Data() {
        return !@feof($this->data);

    }

    /**
     * Read data from stream and write it to socket
     *
     * @param int $length
     * @since 1.1
     */
    public function Write($length = 16384) {
        return $this->socket->Write_From_Stream($this->data, $length);

    }

}
