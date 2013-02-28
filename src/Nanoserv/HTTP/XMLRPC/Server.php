<?php

/**
 *
 * nanoserv handlers - XML-RPC server
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
 * XML-RPC Service handler class
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

	/**
	 * Convert a PHP variable to XML string representation
	 *
	 * @param string $var
	 * @return string
	 */
	static protected function Variable_To_XML_String($var) {

		$ret = "<value>";

		if (is_int($var)) {

			$ret .= "<i4>".$var."</i4>";

		} else if (is_bool($var)) {

			$ret .= "<boolean>".(int)$var."</boolean>";

		} else if (is_string($var)) {

			if (htmlentities($var) != $var) {

				$ret .= "<base64>".base64_encode($var)."</base64>";

			} else {

				$ret .= "<string>".$var."</string>";

			}

		} else if (is_float($var)) {

			$ret .= "<double>".$var."</double>";

		} else if (is_array($var)) {

			if (self::Is_Assoc($var)) {

				$ret .= "<struct>";

				foreach ($var as $k=>$v) {

					$ret .= "<member>";
					$ret .= "<name>".$k."</name>";
					$ret .= self::Variable_To_XML_String($v);
					$ret .= "</member>";

				}

				$ret .= "</struct>";

			} else {

				$ret .= "<array><data>";

				foreach ($var as $v) $ret .= self::Variable_To_XML_String($v);

				$ret .= "</data></array>";

			}

		}

		$ret .= "</value>";

		return $ret;

	}

	/**
	 * Checks if given array is associative
	 *
	 * @param array $arr
	 * @return bool
	 */
	static private function Is_Assoc($arr) {

		return is_array($arr) && array_keys($arr) !== range(0, sizeof($arr) - 1);

	}

	/**
	 * Convert a XMLRPC value stored in a \SimpleXMLElement object to php variable
	 *
	 * @param \SimpleXMLElement $xml
	 * @return mixed
	 */
	static protected function XML_Value_To_Variable(\SimpleXMLElement $xml) {

		foreach ($xml as $type => $xvalue) break;

		if (isset($type)) {

			$value = (string)$xvalue;

		} else {

			$type = "string";
			$value = (string)$xml;

		}

		switch (strtoupper($type)) {

			case "I4":
			case "INT":
			$value = (int)$value;
			break;

			case "BOOLEAN":
			$value = (bool)$value;
			break;

			case "DOUBLE":
			$value = (float)$value;
			break;

			case "BASE64":
			$value = base64_decode($value);
			break;

			case "DATETIME.ISO8601":
			$value = strtotime($value);
			break;

			case "STRUCT":
			case "ARRAY":
			$value = self::XML_Struct_To_Array($xvalue);
			break;

			case "STRING":
			default:

		}

		return $value;

	}

	/**
	 * Convert a XMLRPC struct or array stored in a \SimpleXMLElement object to php array
	 *
	 * @param \SimpleXMLElement $xml
	 * @return array
	 */
	static protected function XML_Struct_To_Array(\SimpleXMLElement $xml) {

		$ret = array();

		foreach ($xml as $xtype=>$xelem) {

			switch (strtoupper($xtype)) {

				case "MEMBER":

				$mname = $mval = false;

				foreach ($xelem as $mprop=>$xval) {

					switch (strtoupper($mprop)) {

						case "NAME":
						$mname = (string)$xval;
						break;

						case "VALUE":
						$mval = self::XML_Value_To_Variable($xval);
						break;

					}

				}

				$ret[$mname] = $mval;

				break;

				case "DATA":
				foreach ($xelem as $xval) $ret[] = self::XML_Value_To_Variable($xval);
				break;

			}

		}

		return $ret;

	}

	/**
	 * Convert XMLRPC method call params stored in a \SimpleXMLElement object to a php array
	 *
	 * @param \SimpleXMLElement $xml
	 * @return array
	 */
	static protected function XML_Params_To_Array(\SimpleXMLElement $xml) {

		$ret = array();

		foreach ($xml as $topname=>$xparam) {

			if (strtoupper($topname) != "PARAM") continue;

			foreach ($xparam as $xvalue) $ret[] = self::XML_Value_To_Variable($xvalue);

		}

		return $ret;

	}

	/**
	 * Add XMLRPC response envelope
	 *
	 * @param string $xml_result
	 * @return string
	 */
	static protected function XML_Add_MethodResponse_Envelope($xml_result) {

		return "<methodResponse><params><param>{$xml_result}</param></params></methodResponse>";

	}

	static protected function XML_Add_Fault_Envelope(\Exception $e) {

		return "<methodResponse><fault><value><struct><member><name>faultCode</name><value><int>" . $e->getCode() . "</int></value></member><member><name>faultString</name><value><string>" . $e->getMessage() . "</string></value></member></struct></value></fault></methodResponse>";

	}

	final public function on_Request($url) {

		$this->request_url = $url;

		$xreq = @simplexml_load_string($this->request_content);

		if ($xreq === false) {

			$this->Set_Response_Status(400);
			return "";

		}

		foreach ($xreq as $name => $xtopelem) {

			switch (strtoupper($name)) {

				case "METHODNAME":
				$method = (string)$xtopelem;
				break;

				case "PARAMS":
				$params = $xtopelem;
				break;

			}


		}

		$this->Set_Content_Type("text/xml");

		try {

			return self::XML_Add_MethodResponse_Envelope(self::Variable_To_XML_String($this->on_Call($method, isset($params) ? self::XML_Params_To_Array($params) : NULL)));

		} catch (\Exception $e) {

			return self::XML_Add_Fault_Envelope($e);

		}

	}

	/**
	 * Event called on XML-RPC method call
	 *
	 * The value returned by on_Call() will be sent back as the XMLRPC method call response
	 *
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	abstract public function on_Call($method, $args);

}
