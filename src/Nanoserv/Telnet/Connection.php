<?php

/**
 *
 * nanoserv handlers - Telnet protocol handler
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

namespace Nanoserv\Telnet;

use Nanoserv\ConnectionHandler;

/**
 * Telnet protocol handler handler class
 *
 * @package nanoserv
 * @subpackage Handlers
 */
abstract class Connection extends ConnectionHandler {

	const IAC = "\xff";

	const TYPE_WILL = "\xfb";
	const TYPE_WONT = "\xfc";
	const TYPE_DO = "\xfd";
	const TYPE_DONT = "\xfe";

	const OPT_8BIT = "\0";
	const OPT_ECHO = "\1";
	const OPT_SUPPRESS_GO_AHEAD = "\3";
	const OPT_LINE_MODE = "\x22";

	/**
	 * Protocol options set by the remote party
	 */
	public $remote_options = array();

	private function Set_Remote_Option($type, $option) {

		$this->remote_options[] = array($type, $option);

	}

	public function Send_Option($type, $option) {

		$this->Raw_Write(self::IAC . $type . $option);

	}

	protected function Raw_Write($data, $callback=false) {

		return parent::Write($data, $callback);

	}

	public function Write($data, $callback=false) {

		$data = str_replace(self::IAC, self::IAC . self::IAC, $data);

		return parent::Write($data, $callback);

	}

	public function on_Read($data) {

		if (strpos($data, self::IAC) !== false) {

			$tmp = "";
			$len = strlen($data);

			for ($a = 0; $a < $len; $a++) {

				if ($data[$a] === self::IAC) {

					switch ($data[$a + 1]) {

						case self::TYPE_WILL:
						case self::TYPE_WONT:
						case self::TYPE_DO:
						case self::TYPE_DONT:
						$this->Set_Remote_Option($data[$a + 1], $data[$a + 2]);
						$a += 2;
						break;

						case self::IAC:
						$tmp .= self::IAC;
						$a++;
						break;


					}

				} else {

					$tmp .= $data[$a];

				}

			}

			if (strlen($tmp)) {

				$data = $tmp;

			} else {

				return;

			}

		}

		$this->on_Telnet_Read($data);

	}

	/**
	 * Telnet read event
	 *
	 * This event is called every time there is data received
	 *
	 * @param string $s
	 */
	abstract public function on_Telnet_Read($s);

}
