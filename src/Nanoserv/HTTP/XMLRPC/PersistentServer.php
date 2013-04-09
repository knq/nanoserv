<?php

/**
 *
 * nanoserv handlers - Persistent XML-RPC server
 *
 * Copyright (C) 2004-2010 Vincent Negrier aka. sIX <six@aegis-corp.org>
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA *
 *
 * @package nanoserv
 * @subpackage Handlers
 */

namespace Nanoserv\HTTP\XMLRPC;

use Nanoserv;

/**
 * Persistent XML-RPC server class
 *
 * @package nanoserv
 * @subpackage Handlers
 */
class PersistentServer extends Nanoserv\HTTP\XMLRPC\Server {
    /**
     * Persistent object
     * @var object
     */
    private $wrapped;

    /**
     * Persistent XML-RPC server constructor
     *
     * @param object $o
     */
    public function __construct($o) {
        $this->wrapped = $o;
    }

    final public function on_Call($method, $args) {
        return call_user_func_array(array($this->wrapped, $method), $args);
    }
}
