<?php

namespace Nanoserv;

/**
 * Client socket class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 0.9
 */
class ClientSocket extends namespace\Socket {
    /**
     * Connect timeout (seconds)
     * @var int
     */
    const CONNECT_TIMEOUT = 10;

    /**
     * Peer address (format is 'proto://addr:port')
     * @var string
     */
    public $address;

    /**
     * Connect timeout (timestamp)
     * @var int
     */
    public $connect_timeout;

    /**
     * ClientSocket constructor
     */
    public function __construct($addr) {
        parent::__construct();

        $this->address = $addr;

        $proto = strtolower(strtok($addr, ":"));
        $s = strtok("");

        if (($proto === "udp") || ($proto === "unix")) {
            $this->real_address = $addr;

        } else {
            $this->real_address = "tcp:" . $s;

            if ($proto != "tcp") switch ($proto) {
                case "ssl":		$this->crypto_type = STREAM_CRYPTO_METHOD_SSLv23_CLIENT;	break;
                case "tls":		$this->crypto_type = STREAM_CRYPTO_METHOD_TLS_CLIENT;		break;
                case "sslv2":	$this->crypto_type = STREAM_CRYPTO_METHOD_SSLv2_CLIENT;		break;
                case "sslv3":	$this->crypto_type = STREAM_CRYPTO_METHOD_SSLv3_CLIENT;		break;

                default:		if (defined($cname = "STREAM_CRYPTO_METHOD_".strtoupper($proto)."_CLIENT")) $this->crypto_type = constant($cname);

            }

        }

    }

    /**
     * Connect to the peer address
     *
     * @param  int  $timeout connection timeout in seconds
     * @return bool
     * @since 0.9
     */
    public function Connect($timeout = false) {
        $errno = $errstr = false;

        $this->fd = @stream_socket_client($this->real_address, $errno, $errstr, 3, STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT, $this->context);

        if ($this->fd === false) {
            throw new ClientException("cannot connect to {$this->real_address}: {$errstr}", $errno, $this->real_address);

        }

        if ($timeout === false) $timeout = self::CONNECT_TIMEOUT;

        $this->connect_timeout = microtime(true) + $timeout;
        $this->pending_connect = true;
        $this->connected = false;
        $this->Set_Blocking(false);
        $this->Set_Timeout(0);

        return true;

    }

}
