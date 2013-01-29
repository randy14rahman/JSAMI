<?php
/**
 *   Copyright 2012 Mehran Ziadloo & Siamak Sobhany
 *   Pomegranate Framework Project
 *   (http://www.sourceforge.net/p/pome-framework)
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 **/

namespace Pomegranate\framework\utilities;

class String
{
	protected $string;

	public function __construct($string = null)
	{
		$this->string = ($string == null ? "" : $string);
	}

	public function setString($string)
	{
		$this->string = $string;
	}

	public static function setFromRequest($string)
	{
		if (isset($this))
			return $this->string = trim(stripslashes($string));
		else
			return trim(stripslashes($string));
	}

	public static function setFromDB($string)
	{
		if (isset($this))
			return $this->string = $string;
		else
			return $string;
	}

	public static function setFromXML($string)
	{
		if (isset($this))
			return $this->string = str_replace(array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;', '&apos;'), array('&', '"', "'", '<', '>', '’'), $string);
		else
			return str_replace(array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;', '&apos;'), array('&', '"', "'", '<', '>', '’'), $string);
	}

	public static function getForURL()
	{
		if (func_num_args() == 0)
			return urlencode($this->string);
		else
			return urlencode(func_get_arg(0));
	}

	public static function getForDB()
	{
		if (isset($this))
			$s = $this->string;
		else if (func_num_args() > 0)
			$s = func_get_arg(0);
		else
			$s = null;

		if (!isset($this) && func_num_args() > 1)
			$type = strtolower(func_get_arg(1));
		else if (isset($this) && func_num_args() > 0)
			$type = strtolower(func_get_arg(0));
		else
			$type = 'numeric';

		$quote = true;
		if (!isset($this) && func_num_args() > 2)
			$quote = (bool)(func_get_arg(2));
		else if (isset($this) && func_num_args() > 1)
			$quote = (bool)(func_get_arg(1));

		$cast = $quote;

		$quote = $quote ? "'" : '';

		switch ($type) {
			case 'string':
				if ($s === null)
					return '_utf8\'\'';
				else if ($cast)
					return '_utf8'.$quote. addslashes($s) .$quote;
				else
					return $quote. addslashes($s) .$quote;
				break;

			case 'binary':
				if ($s === null)
					return '_binary\'\'';
				else if ($cast)
					return '_binary'.$quote. addslashes($s) .$quote;
				else
					return $quote. addslashes($s) .$quote;
				break;

			case 'date':
				if ($s === null || $s == '')
					return 'NULL';
				else
					return $quote. addslashes($s) .$quote;
				break;

			case 'numeric':
			default:
				if ($s === null || $s === '' || !is_numeric($s))
					return 'NULL';
				else
					return $quote. addslashes($s) .$quote;
				break;
				
			case 'field_name':
				return '`' . $s . '`';
				break;
		}
	}

	public static function getForJS()
	{
		if (func_num_args() == 0)
			return strtr(addslashes($this->string), array("\r\n" => "\\n", "\n" => "\\n", "\r" => "\\n"));
		else
			return strtr(addslashes(func_get_arg(0)), array("\r\n" => "\\n", "\n" => "\\n", "\r" => "\\n"));
	}

	public static function getForTA()
	{
		if (func_num_args() == 0)
			return htmlspecialchars($this->string);
		else
			return htmlspecialchars(func_get_arg(0));
	}

	public static function getForFV()
	{
		if (func_num_args() == 0)
			return htmlspecialchars($this->string);
		else
			return htmlspecialchars(func_get_arg(0));
	}

	public static function getDateTimestamp($date)
	{
		if (strpos($date, ':') !== FALSE) {
			$parts = explode(' ', $date);
			$date = explode('-', $parts[0]);
			$time = explode(':', $parts[1]);
			$t = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
		}
		else {
			$date = explode('-', $date);
			$t = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
		}
		return $t;
	}

	public static function getFormattedDate($format, $date)
	{
		return date($format, self::getDateTimestamp($date));
	}

	public static function getFirstNSentences($string, $n)
	{
		$sentences = explode('.', $string);
		$str = '';
		for ($i=0; $i<$n && $i < count($sentences); $i++) {
			$str .= $sentences[$i] . '.';
		}
		return $str;
	}

	public static function getFormattedFileSize($size)
	{
		if ($size < 1024)
			$formatted = $size . ' bytes';
		else if ($size < 1024*1024)
			$formatted = ceil($size / 1024) . 'KB';
		else
			$formatted = round($size / (1024*1024), 1) . 'MB';
		return $formatted;
	}

	public static function getLengthFitted($string, $numChar)
	{
		$words = explode(' ', $string);
		$result = '';
		foreach ($words as $word)
			if (strlen($result)<$numChar)
				$result .= $word . ' ';
		if (strlen($result)-1 < strlen($string))
			$result .= '...';
		return trim($result);
	}

	public static function getImagePathByExt($path, $extension)
	{
		return substr($path, 0, strrpos($path, '.')) . $extension;
	}

	public static function getWordLengthFitted($string, $numWords)
	{
		$words = explode(' ', $string);
		$result = '';
		$n = count($words);
		for ($i=0; $i<$numWords && $i<$n; $i++)
			$result .= $words[$i] . ' ';
		if (strlen($result)-1 < strlen($string))
			$result .= '...';
		return trim($result);
	}

	public static function getSeqNumeral($number)
	{
		$str = "$number";
		$ch = $str[strlen($str)-1];
		switch ($ch) {
			case '1':
				$str .= 'st';
				break;
			case '2':
				$str .= 'nd';
				break;
			case '3':
				$str .= 'rd';
				break;
			default:
				$str .= 'th';
			break;
		}
		return $str;
	}

	public static function getHTMLStrip()
	{
		if (func_num_args() == 0)
			return strip_tags($this->string);
		else
			return strip_tags(func_get_arg(0));
	}

	public static function getForHTML()
	{
		$temp = htmlentities('d', ENT_QUOTES,'utf-8');
		if (func_num_args() == 0)
			$temp = htmlentities($this->string, ENT_QUOTES,'utf-8');
		else
			$temp = htmlentities(func_get_arg(0), ENT_QUOTES,'utf-8');
		$temp = strtr($temp, array("\n" => "<br>\n", "\t" => "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"));
		$temp = strtr($temp, array("  " => " &nbsp;"));
		$temp = strtr($temp, array("&nbsp;  " => "&nbsp;&nbsp;"));
		//$temp = ereg_replace("  ", " &nbsp;", $temp);
		//$temp = ereg_replace("&nbsp; ", "&nbsp;&nbsp;", $temp);
		return $temp;
	}

	public static function getForXML()
	{
		if (func_num_args() == 0)
			return str_replace(array('&', '"', "'", '<', '>', '’'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;', '&apos;'), $this->string);
		else {
			$s = func_get_arg(0);
			return str_replace(array('&', '"', "'", '<', '>', '’'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;', '&apos;'), $s);
		}
	}

	public function getString()
	{
		return $this->string;
	}

	public function __toString()
	{
		return $this->string;
	}
}
