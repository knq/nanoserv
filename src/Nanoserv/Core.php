<?php

/**
 *
 * nanoserv - a sockets daemon toolkit for PHP 5.1+
 *
 * Copyright (C) 2004-2010 Vincent Negrier aka. sIX <six at aegis-corp.org>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package nanoserv
 * @subpackage Core
 */

namespace Nanoserv;

use Nanoserv\ClientException,
    Nanoserv\ClientSocket,
    Nanoserv\ConnectionHandler,
    Nanoserv\DatagramHandler,
    Nanoserv\Exception,
    Nanoserv\Handler,
    Nanoserv\IPCSocket,
    Nanoserv\IWriteButter,
    Nanoserv\LineInputConnection,
    Nanoserv\Listener,
    Nanoserv\ServerException,
    Nanoserv\ServerSocket,
    Nanoserv\SharedObject,
    Nanoserv\Socket,
    Nanoserv\StaticWriteBuffer,
    Nanoserv\StreamWriteBuffer,
    Nanoserv\Timer,
    Nanoserv\WriteBuffer;

/**
 * Server / multiplexer class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 0.9
 */
final class Core {
    /**
     * nanoserv current version number
     * @var string
     */
    const VERSION = "2.3.0";

    /**
     * Registered listeners
     * @var array
     */
    private static $listeners = array();

    /**
     * Write buffers
     * @var array
     */
    private static $write_buffers = array();

    /**
     * Active connections
     * @var array
     */
    private static $connections = array();

    /**
     * Active datagram handlers
     * @var array
     */
    private static $dgram_handlers = array();

    /**
     * Shared objects
     * @var array
     */
    private static $shared_objects = array();

    /**
     * Forked process pipes
     * @var array
     */
    private static $forked_pipes = array();

    /**
     * Timers
     * @var array
     */
    private static $timers = array();

    /**
     * Timers updated
     * @var bool
     */
    private static $timers_updated = false;

    /**
     * Number of active connection handler processes
     * @var int
     */
    public static $nb_forked_processes = 0;

    /**
     * Maximum number of active children before incoming connections get delayed
     * @var int
     */
    public static $max_forked_processes = 64;

    /**
     * Are we master or child process ?
     * @var bool
     */
    public static $child_process = false;

    /**
     * Forked server handled connection
     * @var ConnectionHandler
     */
    private static $forked_connection;

    /**
     * Forked server pipe to the master process
     * @var Socket
     */
    public static $master_pipe;

    /**
     * Class Nanoserv should not be instanciated but used statically
     */
    private function __construct() {
    }

    /**
     * Register a new listener
     *
     * For consistency New_Listener() will also wrap Core::New_Datagram_Handler() if the given addr is of type "udp"
     *
     * @param  string   $addr
     * @param  string   $handler_classname
     * @param  mixed    $handler_options
     * @return Listener
     * @see Listener
     * @see DatagramHandler
     * @since 0.9
     */
    public static function New_Listener($addr, $handler_classname, $handler_options=false) {
        if (strtolower(strtok($addr, ":")) == "udp") {
            $l = self::New_Datagram_Handler($addr, $handler_classname);

        } else {
            $l = new Listener($addr, $handler_classname, $handler_options);
            self::$listeners[] = $l;

        }

        return $l;

    }

    /**
     * Deactivate and free a previously registered listener
     *
     * For consistency Free_Listener() will also wrap Core::Free_Datagram_Handler() if the given object is an instance of DatagramHandler
     *
     * @param  Listener $l
     * @return bool
     * @see Listener
     * @see DatagramHandler
     * @since 0.9
     */
    public static function Free_Listener($l) {
        if ($l instanceof Listener) {
            foreach (self::$listeners as $k => $v) if ($v === $l) {
                unset(self::$listeners[$k]);

                return true;

            }

        } elseif ($l instanceof DatagramHandler) {
            return self::Free_Datagram_Handler($l);

        }

        return false;

    }

    /**
     * Register a new static write buffer
     *
     * This method is used by ConnectionHandler::Write() and should not be
     * called unless you really know what you are doing
     *
     * @param  Socket            $socket
     * @param  string            $data
     * @param  mixed             $callback
     * @return StaticWriteBuffer
     * @see ConnectionHandler::Write()
     * @since 0.9
     */
    public static function New_Static_Write_Buffer(Socket $socket, $data, $callback=false) {
        $wb = new StaticWriteBuffer($socket, $data, $callback);

        $wb->Write();

        if ($wb->Waiting_Data()) {
            self::$write_buffers[$socket->id][] = $wb;

        }

        return $wb;

    }

    /**
     * Register a new static write buffer
     *
     * This method is used by ConnectionHandler::Write_Stream() and should not be
     * called unless you really know what you are doing
     *
     * @param  Socket            $socket
     * @param  resource          $stream
     * @param  mixed             $callback
     * @return StreamWriteBuffer
     * @see ConnectionHandler::Write_Stream()
     * @since 0.9
     */
    public static function New_Stream_Write_Buffer(Socket $socket, $data, $callback=false) {
        $wb = new StreamWriteBuffer($socket, $data, $callback);

        $wb->Write();

        if ($wb->Waiting_Data()) {
            self::$write_buffers[$socket->id][] = $wb;

        }

        return $wb;

    }

    /**
     * Free a registered write buffer
     *
     * @param int $sid socket id
     * @since 0.9
     */
    public static function Free_Write_Buffers($sid) {
        unset(self::$write_buffers[$sid]);

    }

    /**
     * Register a new outgoing connection
     *
     * @param  string            $addr
     * @param  string            $handler_classname
     * @param  mixed             $handler_options
     * @return ConnectionHandler
     * @see ConnectionHandler
     * @since 0.9
     */
    public static function New_Connection($addr, $handler_classname, $handler_options=false) {
        $sck = new ClientSocket($addr);
        $h = new $handler_classname($handler_options);

        $h->socket = $sck;

        self::$connections[$sck->id] = $h;

        return $h;

    }

    /**
     * Free an allocated connection
     *
     * @param  ConnectionHandler $h
     * @return bool
     * @since 0.9
     */
    public static function Free_Connection(ConnectionHandler $h) {
        $so = $h->socket;

        unset(self::$connections[$so->id]);
        self::Free_Write_Buffers($so->id);

        $so->pending_connect = $so->pending_crypto = $so->connected = false;

        if (self::$child_process && (self::$forked_connection === $h)) exit();
        return true;

    }

    /**
     * Register a new datagram (udp) handler
     *
     * @param  string          $addr
     * @param  string          $handler_classname
     * @return DatagramHandler
     * @see DatagramHandler
     * @since 0.9.61
     */
    public static function New_Datagram_Handler($addr, $handler_classname) {
        $h = new $handler_classname($addr);
        self::$dgram_handlers[$h->socket->id] = $h;

        return $h;

    }

    /**
     * Deactivate and free a datagram handler
     *
     * @param  DatagramHandler $h
     * @return bool
     * @since 0.9.61
     */
    public static function Free_Datagram_Handler(DatagramHandler $h) {
        unset(self::$dgram_handlers[$h->socket->id]);

        return true;

    }

    /**
     * Register a new shared object
     *
     * shared objects allow forked processes to use objects stored on the master process
     * if $o is ommited, a new StdClass empty object is created
     *
     * @param  object       $o
     * @return SharedObject
     * @since 0.9
     */
    public static function New_Shared_Object($o = false) {
        $shr = new SharedObject($o);

        self::$shared_objects[$shr->_oid] = $shr;

        return $shr;

    }

    /**
     * Free a shared object
     *
     * @param SharedObject $o
     * @since 0.9
     */
    public static function Free_Shared_Object(SharedObject $o) {
        unset(self::$shared_objects[$o->_oid]);

    }

    /**
     * Register a new timer callback
     *
     * @param  float $delay    specified in seconds
     * @param  mixed $callback may be "function" or array($obj, "method")
     * @return Timer
     * @since 0.9
     */
    public static function New_Timer($delay, $callback) {
        $t = new Timer(microtime(true) + $delay, $callback);

        self::$timers[] = $t;
        self::$timers_updated = true;

        return $t;

    }

    /**
     * Clear all existing timers
     *
     * @return int number of timers cleared
     * @since 2.0
     */
    public static function Clear_Timers() {
        $ret = count(self::$timers);

        self::$timers = array();

        return $ret;

    }

    /**
     * Get all registered ConnectionHandler objects
     *
     * Note: connections created by fork()ing listeners can not be retreived this way
     *
     * @param  bool  $include_pending_connect
     * @return array
     * @since 0.9
     */
    public static function Get_Connections($include_pending_connect=false) {
        $ret = array();

        foreach (self::$connections as $c) if ($c->socket->connected || $include_pending_connect) $ret[] = $c;

        return $ret;

    }

    /**
     * Get all registered Listener objects
     *
     * @param  bool  $include_inactive
     * @return array
     * @since 0.9
     */
    public static function Get_Listeners($include_inactive=false) {
        $ret = array();

        foreach (self::$listeners as $l) if ($l->active || $include_inactive) $ret[] = $l;

        return $ret;

    }

    /**
     * Get all registered Timer objects
     *
     * @param  bool  $include_inactive
     * @return array
     * @since 2.0.1
     */
    public static function Get_Timers($include_inactive=false) {
        $ret = array();

        foreach (self::$timers as $t) if ($t->active || $include_inactive) $ret[] = $t;

        return $ret;

    }

    /**
     * Set the maximum number of allowed children processes before delaying incoming connections
     *
     * Note: this setting only affect and applies to forking listeners
     *
     * @param int $i
     * @since 2.0
     */
    public static function Set_Max_Children($i) {
        self::$max_forked_processes = $i;

    }

    /**
     * Flush all write buffers
     *
     * @since 2.0
     */
    public static function Flush_Write_Buffers() {
        while (self::$write_buffers) {
            self::Run(1);

        }

    }

    /**
     * Fork and setup IPC sockets
     *
     * @return int the pid of the created process, 0 if child process
     * @since 0.9.63
     */
    public static function Fork() {
        if ($has_shared = (SharedObject::$shared_count > 0)) {
            list($s1, $s2) = IPCSocket::Pair();

        }

        $pid = pcntl_fork();

        if ($pid === 0) {
            self::$child_process = true;

            if ($has_shared) {
                self::$master_pipe = $s2;

            }

        } elseif ($pid > 0) {
            ++self::$nb_forked_processes;

            if ($has_shared) {
                $s1->pid = $pid;
                self::$forked_pipes[$pid] = $s1;

            }

        }

        return $pid;

    }

    /**
     * Enter main loop
     *
     * The <var>$time</var> parameter can have different meanings:
     * <ul>
     * <li>int or float > 0 : the main loop will run once and will wait for activity for a maximum of <var>$time</var> seconds</li>
     * <li>0 : the main loop will run once and will not wait for activity when polling, only handling waiting packets and timers</li>
     * <li>int or float < 0 : the main loop will run for -<var>$time</var> seconds exactly, whatever may happen</li>
     * <li>NULL : the main loop will run forever</li>
     * </ul>
     *
     * @param  float $time         how much time should we run, if omited nanoserv will enter an endless loop
     * @param  array $user_streams if specified, user streams will be polled along with internal streams
     * @return array the user streams with pending data
     * @since 0.9
     */
    public static function Run($time = NULL, array $user_streams = NULL) {
        $tmp = 0;

        $ret = array();

        if (isset($time)) {
            if ($time < 0) {
                $poll_max_wait = -$time;
                $exit_mt = microtime(true) - $time;

            } else {
                $poll_max_wait = $time;
                $exit = true;

            }

        } else {
            $poll_max_wait = 60;
            $exit = false;

        }

        do {
            $t = microtime(true);

            // Timers

            if (self::$timers_updated) {
                usort(self::$timers, function(Timer $a, Timer $b) { return $a->microtime > $b->microtime; });
                self::$timers_updated = false;

            }

            $next_timer_md = NULL;

            if (self::$timers) foreach (self::$timers as $k => $tmr) {
                if ($tmr->microtime > $t) {
                    $next_timer_md = $tmr->microtime - $t;
                    break;

                } elseif ($tmr->active) {
                    $tmr->Deactivate();
                    call_user_func($tmr->callback);

                }

                unset(self::$timers[$k]);

            }

            if (self::$timers_updated) {
                $t = microtime(true);

                usort(self::$timers, function(Timer $a, Timer $b) { return $a->microtime > $b->microtime; });

                foreach (self::$timers as $tmr) {
                    if ($tmr->microtime > $t) {
                        $next_timer_md = $tmr->microtime - $t;
                        break;

                    }

                }

                self::$timers_updated = false;

            }

            // Write buffers to non blocked sockets

            foreach (self::$write_buffers as $write_buffers) {
                if (!$write_buffers || $write_buffers[0]->socket->blocked || !$write_buffers[0]->socket->connected) continue;

                foreach ($write_buffers as $wb) {
                    while ($wb->Waiting_Data() && !$wb->socket->blocked) {
                        $wb->Write();

                        if (!$wb->Waiting_Data()) {
                            array_shift(self::$write_buffers[$wb->socket->id]);
                            if (!self::$write_buffers[$wb->socket->id]) self::Free_Write_Buffers($wb->socket->id);

                            break;

                        }

                    }

                }

            }

            $handler = $so = $write_buffers = $l = $c = $wbs = $wb = $data = $so = NULL;

            // Prepare socket arrays

            $fd_lookup_r = $fd_lookup_w = $rfd = $wfd = $efd = array();

            foreach (self::$listeners as $l) if (($l->active) && ((!$l->forking) || (self::$nb_forked_processes <= self::$max_forked_processes))) {
                $fd = $l->socket->fd;
                $rfd[] = $fd;
                $fd_lookup_r[(int) $fd] = $l;

            }

            $next_conn_timeout_mt = NULL;

            foreach (self::$connections as $c) {
                $so = $c->socket;

                if ($so->pending_crypto) {
                    $cr = $so->Enable_Crypto();

                    if ($cr === true) {
                        $c->on_Accept();

                    } elseif ($cr === false) {
                        $c->on_Connect_Fail(ConnectionHandler::FAIL_CRYPTO);
                        self::Free_Connection($c);

                    } else {
                        $fd = $so->fd;
                        $rfd[] = $fd;
                        $fd_lookup_r[(int) $fd] = $c;

                    }

                } elseif ($so->connected) {
                    if (!$so->block_reads) {
                        $fd = $so->fd;
                        $rfd[] = $fd;
                        $fd_lookup_r[(int) $fd] = $c;

                    }

                } elseif ($so->connect_timeout < $t) {
                    $c->on_Connect_Fail(ConnectionHandler::FAIL_TIMEOUT);
                    self::Free_Connection($c);

                } elseif ($so->pending_connect) {
                    $fd = $so->fd;
                    $wfd[] = $fd;
                    $fd_lookup_w[(int) $fd] = $c;

                    if (!$next_conn_timeout_mt || ($sc->connect_timeout < $next_conn_timeout_mt)) {
                        $next_conn_timeout_mt = $sc->connect_timeout;

                    }

                }

            }

            if (self::$dgram_handlers) foreach (self::$dgram_handlers as $l) if ($l->active) {
                $fd = $l->socket->fd;
                $rfd[] = $fd;
                $fd_lookup_r[(int) $fd] = $l;

            }

            foreach (self::$write_buffers as $wbs) if ($wbs[0]->socket->blocked) {
                $fd = $wbs[0]->socket->fd;
                $wfd[] = $fd;
                $fd_lookup_w[(int) $fd] = self::$connections[$wbs[0]->socket->id];

            }

            if (self::$forked_pipes) foreach (self::$forked_pipes as $fp) {
                $fd = $fp->fd;
                $rfd[] = $fd;
                $fd_lookup_r[(int) $fd] = $fp;

            }

            if (isset($user_streams)) {
                foreach ((array) $user_streams[0] as $tmp_r) $rfd[] = $tmp_r;
                foreach ((array) $user_streams[1] as $tmp_w) $wfd[] = $tmp_w;

            }

            // Main select

            $wait_mds = array($poll_max_wait);
            if (isset($next_timer_md)) $wait_mds[] = $next_timer_md;
            if (isset($exit_mt)) $wait_mds[] = $exit_mt - $t;
            if (isset($next_conn_timeout_mt)) $wait_mds[] = $next_conn_timeout_mt - $t;

            $wait_md = min($wait_mds);

            $tv_sec = (int) $wait_md;
            $tv_usec = ($wait_md - $tv_sec) * 1000000;

            if (($rfd || $wfd) && (@stream_select($rfd, $wfd, $efd, $tv_sec, $tv_usec))) {
                foreach ($rfd as $act_rfd) {
                    $handler = $fd_lookup_r[(int) $act_rfd];
                    $so = $handler->socket;

                    if ($handler instanceof ConnectionHandler) {
                        if ($so->pending_crypto) {
                            $cr = $so->Enable_Crypto();

                            if ($cr === true) {
                                $handler->on_Accept();

                            } elseif ($cr === false) {
                                $handler->on_Connect_Fail(ConnectionHandler::FAIL_CRYPTO);
                                self::Free_Connection($handler);

                            }

                        } elseif (!$so->connected) {
                            continue;

                        }

                        $data = $so->Read();

                        if (($data === "") || ($data === false)) {
                            if ($so->Eof()) {
                                // Disconnected socket

                                $handler->on_Disconnect();
                                self::Free_Connection($handler);

                            }

                        } else {
                            // Data available

                            $handler->on_Read($data);

                        }

                    } elseif ($handler instanceof DatagramHandler) {
                        $from = "";
                        $data = $so->Read_From($from);

                        $handler->on_Read($from, $data);

                    } elseif ($handler instanceof Listener) {
                        while ($fd = $so->Accept()) {
                            // New connection accepted

                            $sck = new Socket($fd, $so->crypto_type);

                            $hnd = new $handler->handler_classname($handler->handler_options);
                            $hnd->socket = $sck;

                            if ($handler->forking) {
                                $hnd->on_Fork_Prepare();

                                if (self::Fork() === 0) {
                                    $hnd->on_Fork_Done();

                                    self::$write_buffers = self::$listeners = array();
                                    self::$connections = array($sck->id => $hnd);
                                    self::$forked_connection = $hnd;

                                    self::Clear_Timers();

                                    if ($sck->Setup()) {
                                        $hnd->on_Accept();

                                    }

                                    $handler = $hnd = $sck = $l = $c = $wbs = $wb = $fd_lookup_r = $fd_lookup_w = false;

                                    break;

                                }

                                $hnd->on_Fork_Done();

                                if (self::$nb_forked_processes >= self::$max_forked_processes) break;

                            } else {
                                self::$connections[$sck->id] = $hnd;

                                if ($sck->Setup()) {
                                    $hnd->on_Accept();

                                }

                            }

                            $sck = $hnd = NULL;

                        }

                    } elseif ($handler instanceof IPCSocket) {
                        while ($ipcm = $handler->Read()) {
                            if ((!$ipcq = unserialize($ipcm)) || (!is_object($o = self::$shared_objects[$ipcq["oid"]]))) continue;

                            switch ($ipcq["action"]) {
                                case "G":
                                $handler->Write(serialize($o->$ipcq["var"]));
                                break;

                                case "S":
                                $o->$ipcq["var"] = $ipcq["val"];
                                break;

                                case "C":
                                SharedObject::$caller_pid = $handler->pid;
                                $handler->Write(serialize(call_user_func_array(array($o, $ipcq["func"]), $ipcq["args"])));
                                break;

                            }

                        }

                        $o = $ipcq = $ipcm = NULL;

                    } elseif (!isset($handler)) {
                        // User stream

                        $ret[0][] = $act_rfd;

                    }

                }

                foreach ($wfd as $act_wfd) {
                    $handler = $fd_lookup_w[$act_wfd];
                    $so = $handler->socket;

                    if (!isset($handler)) {
                        // User stream

                        $ret[1][] = $act_wfd;

                    } elseif ($so->connected) {
                        // Unblock buffered write

                        if ($so->Eof()) {
                            $handler->on_Disconnect();
                            self::Free_Connection($handler);

                        } else {
                            $so->blocked = false;

                        }

                    } elseif ($so->pending_connect) {
                        // Pending connect

                        if ($so->Eof()) {
                            $handler->on_Connect_Fail(ConnectionHandler::FAIL_CONNREFUSED);
                            self::Free_Connection($handler);

                        } else {
                            $so->Setup();
                            $so->connected = true;
                            $so->pending_connect = false;
                            $handler->on_Connect();

                        }

                    }

                }

            }

            if (self::$nb_forked_processes && !self::$child_process) while ((($pid = pcntl_wait($tmp, WNOHANG)) > 0) && self::$nb_forked_processes--) unset(self::$forked_pipes[$pid]);

            if ($ret) {
                return $ret;

            } elseif (isset($exit_mt)) {
                $exit = $exit_mt <= $t;

            }

        } while (!$exit);
    }
}
