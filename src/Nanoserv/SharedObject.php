<?php

namespace Nanoserv;

/**
 * Shared object class for inter-process communications
 *
 * @package nanoserv
 * @subpackage namespace\Core
 * @since 0.9
 */
class SharedObject {

    /**
     * caller process pid
     * @var int
     */
    public static $caller_pid;

    /**
     * shared object unique identifier
     * @var int
     */
    public $_oid;

    /**
     * wrapped object
     * @var object
     */
    private $wrapped;

    /**
     * static instance counter
     * @var int
     */
    public static $shared_count = 0;

    /**
     * SharedObject constructor
     *
     * If $o is omited, a new StdClass object will be created and wrapped
     *
     * @param object $o
     */
    public function __construct($o=false) {

        if ($o === false) $o = new StdClass();

        $this->_oid = ++self::$shared_count;
        $this->wrapped = $o;

    }

    public function __get($k) {

        if (namespace\Core::$child_process) {
            return namespace\Core::$master_pipe->Ask_Master(array("oid" => $this->_oid, "action" => "G", "var" => $k));

        } else {
            return $this->wrapped->$k;

        }

    }

    public function __set($k, $v) {

        if (namespace\Core::$child_process) {

            namespace\Core::$master_pipe->Ask_Master(array("oid" => $this->_oid, "action" => "S", "var" => $k, "val" => $v), false);

        } else {

            $this->wrapped->$k = $v;

        }

    }

    public function __call($m, $a) {
        if (namespace\Core::$child_process) {
            return namespace\Core::$master_pipe->Ask_Master(array("oid" => $this->_oid, "action" => "C", "func" => $m, "args" => $a));

        } else {
            return call_user_func_array(array($this->wrapped, $m), $a);

        }
    }
}
