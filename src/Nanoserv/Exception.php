<?php

namespace Nanoserv;

/**
 * Base exception class
 *
 * @package nanoserv
 * @subpackage Core
 * @since 2.0
 */
abstract class Exception extends \Exception {

    public $addr;

    public function __construct($errmsg, $errno, $addr) {

        parent::__construct($errmsg, $errno);

        $this->addr = $addr;

    }

}
