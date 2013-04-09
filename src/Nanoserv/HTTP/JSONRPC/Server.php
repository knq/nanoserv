<?php

/**
 *
 * nanoserv handlers - JSON-RPC server
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

namespace Nanoserv\HTTP\JSONRPC;

use Nanoserv;

/**
 * JSON-RPC Service handler class
 *
 * @package nanoserv
 * @subpackage Handlers
 */
abstract class Server extends Nanoserv\HTTP\Server {
    /**
     * Request URL
     * @var string
     */
    protected $request_url = "";

    final public function on_Request($url) {
        $this->request_url = $url;

        $req = json_decode($this->request_content);

        $ret = array("id" => $req->id);

        if ($req === NULL) {
            $this->Set_Response_Status(400);

            switch (json_last_error()) {
                case JSON_ERROR_DEPTH:      $ret["error"] = "The maximum stack depth has been exceeded";                break;
                case JSON_ERROR_CTRL_CHAR:  $ret["error"] = "Control character error, possibly incorrectly encoded";    break;
                case JSON_ERROR_SYNTAX:     $ret["error"] = "Syntax error";                                             break;
                default:                    $ret["error"] = "Unknown error";                                            break;
            }

            return json_encode($ret);
        }

        try {
            $ret["result"] = $this->on_Call($req->method, $req->params);
        } catch (\Exception $e) {
            $ret["error"] = $e->getMessage();
        }

        if (isset($req->id)) {
            return json_encode($ret);
        } else {
            return "";
        }
    }

    /**
     * Event called on JSON-RPC method call
     *
     * The value returned by on_Call() will be sent back as the JSON-RPC method call response
     *
     * @param  string $method
     * @param  array  $args
     * @return mixed
     */
    abstract public function on_Call($method, $args);
}
