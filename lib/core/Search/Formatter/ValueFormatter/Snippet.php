<?php
// (c) Copyright 2002-2012 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Formatter_ValueFormatter_Snippet extends Search_Formatter_ValueFormatter_Abstract
{
	private $length = 240;
	private $suffix = '...';

	function __construct($arguments)
	{
		if (isset($arguments['length'])) {
			$this->length = (int) $arguments['length'];
		}

		if (isset($arguments['suffix'])) {
			$this->suffix = $arguments['suffix'];
		}
	}

	function render($name, $value, array $entry)
	{
		$snippet = TikiLib::lib('tiki')->get_snippet($value, 'n', '', $this->length + 1);

		if (strlen($snippet) > $this->length) {
			$snippet = substr($snippet, 0, -1) . $this->suffix;
		}

		return $snippet;
	}
}

