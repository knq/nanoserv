<?php

namespace Nanoserv;

/**
 * Timer class
 *
 * Do not instanciate Timer but use the Core::New_Timer() method instead
 *
 * @package nanoserv
 * @subpackage Core
 * @since 0.9
 */
class Timer {
    /**
     * System time for timer activation
     * @var float
     */
    public $microtime;

    /**
     * Timer callback
     * @var mixed
     */
    public $callback;

    /**
     * Timer status
     * @var bool
     */
    public $active = true;

    /**
     * Timer constructor
     *
     * @param float $time
     * @param mixed $callback
     * @since 0.9
     * @see Core::New_Timer()
     */
    public function __construct($time, $callback) {
        $this->microtime = $time;
        $this->callback = $callback;
    }

    /**
     * Activate timer
     *
     * Timers are activated by default, and Activate should only be used after a call do Deactivate()
     *
     * @see Timer::Deactivate()
     */
    public function Activate() {
        $this->active = true;
    }

    /**
     * Deactivate timer
     */
    public function Deactivate() {
        $this->active = false;
    }
}
