<?php

namespace Nanoserv;

/**
 * Server socket class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 0.9
 */
class ServerSocket extends namespace\Socket {
    /**
     * Listen address (format is 'proto://addr:port')
     * @var string
     */
    public $address;

    /**
     * Real listen address (format is 'proto://addr:port')
     * @var string
     */
    private $real_address;

    /**
     * ServerSocket constructor
     */
    public function __construct($addr) {
        parent::__construct();

        $this->address = $addr;

        $proto = strtolower(strtok($addr, ":"));

        if (($proto === "udp") || ($proto === "unix")) {
            $this->real_address = $addr;
        } else {
            $this->real_address = "tcp:" . strtok("");

            if ($proto !== "tcp") switch ($proto) {
                case "ssl":		$this->crypto_type = STREAM_CRYPTO_METHOD_SSLv23_SERVER;	break;
                case "tls":		$this->crypto_type = STREAM_CRYPTO_METHOD_TLS_SERVER;		break;
                case "sslv2":	$this->crypto_type = STREAM_CRYPTO_METHOD_SSLv2_SERVER;		break;
                case "sslv3":	$this->crypto_type = STREAM_CRYPTO_METHOD_SSLv3_SERVER;		break;

                default:

                if (defined($cname = "STREAM_CRYPTO_METHOD_".strtoupper($proto)."_SERVER")) {
                    $this->crypto_type = constant($cname);
                } else {
                    throw new ServerException("unknown transport/crypto type '{$proto}'");
                }
            }
        }
    }

    /**
     * Start listening and accepting connetions
     *
     * @return bool
     * @since 0.9
     */
    public function Listen($bind_only = false) {
        $errno = $errstr = false;

        $this->fd = @stream_socket_server($this->real_address, $errno, $errstr, STREAM_SERVER_BIND | ($bind_only ? 0 : STREAM_SERVER_LISTEN), $this->context);

        if ($this->fd === false) {
            throw new ServerException("cannot listen to {$this->real_address}: {$errstr}", $errno, $this->real_address);
        }

        $this->Set_Blocking(false);
        $this->Set_Timeout(0);

        return true;
    }

    /**
     * Accept connection
     *
     * @return resource
     * @since 0.9
     */
    public function Accept() {
        return @stream_socket_accept($this->fd, 0);
    }
}
