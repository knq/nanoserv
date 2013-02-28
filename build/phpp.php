#!/usr/local/bin/php
<?php

/**
 *
 * Six0r's PHP Preprocessor
 * 
 * Copyright (C) 2008-2010 Vincent Negrier aka. sIX <six at aegis-corp.org>
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

require "inc/tokenizer/tokenizer.php";

$help_txt = <<<EOT
usage: $argv[0] [options] template_filename.phpp

	-o filename         output to filename
	-v                  set verbose mode
	-l                  check syntax of final source file
	-w                  strip whitespaces from source code
	-D NAME[=VALUE]     set defines
	-I filename         include files


EOT;

class PHPP_Tokenizer extends Tokenizer {

	const T_INCLUDE = "/#include[ \t]+([^\r\n]*)[ \r\n]*/";
	const T_DEFINE = "/#define[ \t]+([^ \t]+)[ \t]+([^\r\n]*)[\r\n]*/";
	const T_IFDEF = "/#ifdef[ \t]+([^\r\n]*)[ \r\n]*/";
	const T_IFNDEF = "/#ifndef[ \t]+([^\r\n]*)[ \r\n]*/";
	const T_ELSE = "/#else[ \r\n]*/";
	const T_ENDIF = "/#endif[ \r\n]*/";
	const T_PHP_OPEN = "/#php_tag_open/";
	const T_PHP_CLOSE = "/#php_tag_close/";
	const T_CONST = "/#const[ \t]+([^\r\n]+)[ \r\n]*/";
	const T_MACRO = "/{{{([^}\t\r\n ]+)}}}/";

	const T_HASH = "/#/";
	const T_BRACKET = "/[{}]/";
	const T_ANYTHING = "/([^#{}]+)/s";
	
	public $tokens = array(	"T_INCLUDE"		=> self::T_INCLUDE,
							"T_DEFINE"		=> self::T_DEFINE,
							"T_IFDEF"		=> self::T_IFDEF,
							"T_IFNDEF"		=> self::T_IFNDEF,
							"T_ELSE"		=> self::T_ELSE,
							"T_ENDIF"		=> self::T_ENDIF,
							"T_PHP_OPEN"	=> self::T_PHP_OPEN,
							"T_PHP_CLOSE"	=> self::T_PHP_CLOSE,
							"T_CONST"		=> self::T_CONST,
							"T_MACRO"		=> self::T_MACRO,
							"T_BRACKET"		=> self::T_BRACKET,
							"T_HASH"		=> self::T_HASH,
							"T_ANYTHING"	=> self::T_ANYTHING);

	static public function Create($txt) {

		return new self($txt);
	
	}

}

$defines = array();

if ($argc < 2) die($help_txt);

$options = getopt("o:vlwD:I:");

if ((isset($options["v"])) && (!isset($options["o"]))) unset($options["v"]);

if (isset($options["D"])) {

	foreach ((array)$options["D"] as $cmdline_def) {
		
		if (strpos($cmdline_def, "=")) {

			$name = strtok($cmdline_def, "=");
			$defines[$name] = strtok("");
		
		} else {
		
			$defines[$cmdline_def] = true;

		}

	}

}

function verbose_notice($s) {

	if (isset($GLOBALS["options"]["v"])) echo "{$s}\n";

}

if (!is_callable("php_check_syntax")) {

	function php_check_syntax($file, &$error) {

		exec("php -l {$file}", $arr, $code);
	  
		$error = trim(implode("\n", $arr));
		
		return $code === 0;

	}

}

function read_to_conditional(PHPP_Tokenizer $pt, Token $tok, &$iflvl) {

	$lvl = 1;

	while ($tmp = $pt->Get_Token()) {

		if (($tmp->token === "T_IFDEF") || ($tmp->token === "T_IFNDEF")) {

			$iflvl++;
			$lvl++;

		} else if ($tmp->token === "T_ENDIF") {

			$iflvl--;
			$lvl--;

		} else if ($tmp->token === "T_ELSE") {

			return;
		
		}

		if ($lvl === 0) return;
	
	}

	throw new Exception("{$tok->token} without T_ENDIF line {$tok->line}");

}

function template_transform($txt, $basedir = ".") {

	$ret = "";
	$iflvl = 0;
	
	$pt = PHPP_Tokenizer::Create($txt);
	
	while ($tok = $pt->Get_Token()) {
		
		switch ($tok->token) {

			case "T_PHP_OPEN":
			$ret .= "<" . "?php";
			break;

			case "T_PHP_CLOSE":
			$ret .= "?" . ">";
			break;
			
			case "T_CONST":
			$ret .= get_constants_code($tok->data[0]);
			break;
			
			case "T_DEFINE":
			$GLOBALS["defines"][$tok->data[0]] = $tok->data[1];
			break;
			
			case "T_INCLUDE":

			$fns = $tok->data[0];

			if ($fns[0] !== DIRECTORY_SEPARATOR) {

				$fns = $basedir . DIRECTORY_SEPARATOR . $fns;
			
			}
			
			$repl_data = array();
			
			$all_fns = glob($fns);

			if (!$all_fns) {

				throw new Exception("FATAL: cannot find file matching include pattern '{$fns}'");
			
			}
			
			foreach ($all_fns as $fn) {
			
				$bd = dirname($fn);
				
				if ($data = trim(@file_get_contents($fn))) {

					verbose_notice("including file: '{$fn}'");
				
				} else {

					throw new Exception("FATAL: cannot read file '{$fn}'");
				
				}

				$repl2 = array(	"<" . "?"		=> "",
								"<" . "?php"	=> "",
								"?" . ">"		=> "");

				$data = trim(strtr($data, $repl2));

				$repl_data[] = template_transform($data, $bd);

			}

			$ret .= implode("\n\n", $repl_data);
			
			break;
			

			case "T_IFDEF":

			$iflvl++;
			
			if (!isset($GLOBALS["defines"][$tok->data[0]])) {

				read_to_conditional($pt, $tok, $iflvl);
			
			}
			
			break;
			

			case "T_IFNDEF":

			$iflvl++;
			
			if (isset($GLOBALS["defines"][$tok->data[0]])) {

				read_to_conditional($pt, $tok, $iflvl);
				
			}
			
			break;
			
			
			case "T_ELSE":

			$lvl = 1;

			while ($tmp = $pt->Get_Token()) {

				if (($tmp->token === "T_IFDEF") || ($tmp->token === "T_IFNDEF")) {

					$iflvl++;
					$lvl++;

				} else if ($tmp->token === "T_ENDIF") {

					$iflvl--;
					$lvl--;

				} else if (($tmp->token === "T_ELSE") && ($lvl === 1)) {

					throw new Exception("unexpected T_ELSE line {$tok->line}");
				
				}

				if ($lvl === 0) break 2;
			
			}

			throw new Exception("T_ELSE without T_ENDIF line {$tok->line}");

			break;

			
			case "T_ENDIF":

			if (--$iflvl < 0) {

				throw new Exception("T_ENDIF without T_IFDEF or T_IFNDEF line {$tok->line}");

			}

			break;

			
			case "T_MACRO":

			if (isset($GLOBALS["defines"][$tok->data[0]])) {
				
				$ret .= $GLOBALS["defines"][$tok->data[0]];
			
			} else {

				throw new Exception("undefined macro '{$tok->data[0]}' line {$tok->line}");

			}
			
			break;

			
			case "T_ANYTHING":
			case "T_BRACKET":
			case "T_HASH":
			$ret .= $tok->string;
			break;
			
			default:
			throw new Exception("internal parser error (unknown token type {$tok->token})");
		
		}
	
	}

	if ($iflvl > 0) {

		throw new Exception("unexpected end of file (missing T_ENDIF)");
	
	}
	
	return $ret;

}

function get_constants($mask, $warn = true) {

	$ret = array();
	
	foreach (get_defined_constants() as $k => $v) {
		
		if (($k === $mask) || ((substr($mask, -1) === "*") && (strpos($k, substr($mask, 0, -1)) === 0))) {

			$ret[$k] = $v;
	
		}

	}

	if (!$ret && $warn) {

		verbose_notice("warning: no constants matching pattern '{$mask}'");
	
	}
	
	return $ret;

}

function get_constants_code($mask) {

	$ret = array();
	
	foreach (get_constants($mask) as $k => $v) {

		$ret[] = "define(\"{$k}\", \"" . addslashes($v) . "\");";
	
	}

	return implode("\n", $ret);

}

$fn = $argv[$argc - 1];

if (isset($options["I"])) {

	foreach ((array)$options["I"] as $in) {

		verbose_notice("including source file: '{$in}'");

		include $in;
	
	}

}

verbose_notice("reading source template: '{$fn}'");

$txt = @file_get_contents($fn) or die($help_txt);

verbose_notice("template loaded (" . number_format(strlen($txt)) . " bytes), applying transform");

try {

	$src = template_transform($txt, dirname($fn));

	verbose_notice("transform done");

	if (isset($options["o"])) {

		$out_fn = $options["o"];
		
		verbose_notice("writing destination file: '{$out_fn}'");
		
		file_put_contents($out_fn, $src);

		verbose_notice("done (" . number_format(strlen($src)) . " bytes)");

		if (isset($options["w"])) {

			verbose_notice("removing whitespaces");

			$bl = strlen($src);
			
			$src = php_strip_whitespace($out_fn);

			file_put_contents($out_fn, $src);
			
			verbose_notice("done (" . number_format($bl - strlen($src)) . " bytes saved)");

		}
		
		if (isset($options["l"])) {

			verbose_notice("checking syntax");

			$errstr = "";
			
			if (php_check_syntax($out_fn, $errstr)) {

				verbose_notice("syntax ok");

			} else {

				throw new Exception("syntax check failed: {$errstr}");

			}
		
		}

	} else {

		echo $src;

	}

} catch (Exception $e) {

	echo "error: {$e->getMessage()}\n";
	exit(-1);

}

?>
