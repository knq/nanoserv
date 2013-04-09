<?php

namespace Nanoserv;

/**
 * IPC Socket class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 0.9
 */
class IPCSocket extends namespace\Socket {
    /**
     * Maximum size of inter process communication packets
     * @var int
     */
    const IPC_MAX_PACKET_SIZE = 1048576;

    /**
     * pid number of the remote forked process
     * @var int
     */
    public $pid;

    /**
     * IPC Socket constructor
     *
     * @param resource $fd
     * @param int      $pid
     */
    public function __construct($fd, $pid=false) {
        parent::__construct($fd);

        $this->Set_Write_Buffer(self::IPC_MAX_PACKET_SIZE);

        $this->pid = $pid;
    }

    /**
     * Read data from IPC socket
     *
     * @return string
     * @since 0.9
     */
    public function Read() {
        return fread($this->fd, self::IPC_MAX_PACKET_SIZE);
    }

    /**
     * Creates a pair of connected, indistinguishable pipes
     *
     * Returns an array of two IPCSocket objects
     *
     * @param  int   $domain
     * @param  int   $type
     * @param  int   $proto
     * @return array
     * @since 0.9
     */
    public static function Pair($domain = STREAM_PF_UNIX, $type = STREAM_SOCK_DGRAM, $proto = 0) {
        list($s1, $s2) = stream_socket_pair($domain, $type, $proto);

        return array(new IPCSocket($s1), new IPCSocket($s2));
    }

    /**
     * Ask the master process for object data
     *
     * @param  array $request
     * @param  bool  $need_response
     * @return mixed
     * @since 0.9
     */
    public function Ask_Master($request, $need_response = true) {
        $this->Write(serialize($request));

        if (!$need_response) return;

        $rfd = array($this->fd);
        $dfd = array();

        if (@stream_select($rfd, $dfd, $dfd, 600)) return unserialize($this->Read());
    }
}
