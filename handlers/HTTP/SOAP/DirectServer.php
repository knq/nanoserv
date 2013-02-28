<?php

/**
 *
 * nanoserv handlers - Direct SOAP over HTTP service handler
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

namespace Nanoserv\HTTP\SOAP;

/**
 * Require the SOAP server
 */
require_once "nanoserv/handlers/HTTP/SOAP/Server.php";

/**
 * Direct SOAP over HTTP service handler class
 *
 * If you extend this handler, your methods will be publicly callable by the name they have in PHP
 *
 * @package nanoserv
 * @subpackage Handlers
 * @since 1.0.2
 */
abstract class Direct_Server extends \Nanoserv\HTTP\SOAP\Server {

	final public function on_Call($method, $args) {

		if (is_callable(array($this, $method))) return call_user_func_array(array($this, $method), $args);

	}

	public function Get_Exports() {

		$ret = array();
		
		$rc = new \ReflectionClass(get_class($this));

		foreach ($rc->getMethods() as $rm) {

			if ((!$rm->isPublic()) || ($rm->getDeclaringClass() != $rc)) continue;
			
			$params = array();

			foreach ($rm->getParameters() as $rp) {

				$params[] = array("name" => $rp->getName());
			
			}

			$ret[$rm->getName()] = $params;
		
		}
	
		return $ret;
	
	}

}

?>
