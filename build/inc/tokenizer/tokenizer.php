<?php

/**
 *
 * six0r's generic extensible tokenizer
 * 
 * Copyright (C) 2008-2009 Vincent Negrier aka. sIX <six at aegis-corp.org>
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
 */

class Tokenizer_Exception extends Exception {

	public function __construct($message) {

		parent::__construct($message);

	}

}

class Token {

	public $token;
	public $string;
	public $offset;
	public $data;
	public $line;

	public function __construct($token, $string, $offset, $data, $line) {

		$this->token = $token;
		$this->string = $string;
		$this->offset = $offset;
		$this->data = $data;
		$this->line = $line;
	
	}

}

class Tokenizer {

	const PEEK = 1;
	
	public $tokens = array();

	public $token_class;

	private $pointer = 0;
	private $parsed = array();

	public function __construct($txt = NULL, $token_class = NULL) {

		if (isset($token_class)) {

			$this->token_class = $token_class;

		} else {

			$this->token_class = "Token";

		}
		
		if (isset($txt)) {

			$this->Tokenize($txt);
		
		}
	
	}
	
	public function Register_Token($id, $regexp) {

		$this->tokens[$id] = $regexp;
	
	}
	
	public function Tokenize($s) {

		$ret = array();
	
		$ln = 1;

		$sl = strlen($s);
		$pp = 0;

		while ($pp < $sl) {

			$ml = 0;
			$ms = $md = "";

			foreach ($this->tokens as $t => $reg) {

				if (preg_match($reg, $s, $matches, PREG_OFFSET_CAPTURE, $pp)) {

					$match_str = $matches[0][0];
					$match_ofs = $matches[0][1];

					$match_data = array();
					
					array_shift($matches);

					foreach ($matches as $match) $match_data[] = $match[0];

					$tl = strlen($match_str);

					if (($tl > $ml) && ($match_ofs === $pp)) {

						$ml = $tl;
						$mt = $t;
						$ms = $match_str;
						$md = $match_data;
					
					}
				
				}
			
			}

			if ($ml === 0) {

				$err_tok = $s[$pp];
				$err_ofs = $pp;

				while (($err_ofs < $sl) && (strlen($err_tok) < 30)) {

					switch ($s[++$err_ofs]) {

						case " ":
						case "\n":
						case "\r":
						case "\t":
						break 2;

						default:
						$err_tok .= $s[$err_ofs];

					}
				
				}
				
				throw new Exception("unexpected '{$err_tok}' line {$ln}");
			
			}
			
			$ret[] = new $this->token_class($mt, $ms, $pp, $md, $ln);
			
			$ln += substr_count($ms, "\n");
			$pp += $ml;
		
		}
	
		$this->parsed = $ret;
		$this->Reset();
		
		return $ret;
	
	}

	public function Get_Token($flags = 0) {

		if ($this->pointer < count($this->parsed)) {

			$ret = $this->parsed[$this->pointer];

			if (!($flags & self::PEEK)) {

				$this->pointer++;

			}
			
			return $ret;

		} else {

			return false;

		}
	
	}

	public function Reset() {

		$this->pointer = 0;
	
	}

	public function Set_Token_Class($s) {

		$this->token_class = $s;

	}

	protected function Get_Pointer() {

		return $this->pointer;

	}

	protected function Set_Pointer($value) {

		$this->pointer = $value;

	}

}

?>
