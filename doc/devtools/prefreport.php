<?php
// (c) Copyright 2002-2012 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

// Usage: From the command line:
// php doc/devtools/prefreport.php > prefreport.csv
//
// also check out  doc/devtools/securitycheck.php to see in which files are 
// used each pref (and permission name too)
//

require_once 'tiki-setup.php';
require_once 'lib/prefslib.php';

$defaultValues = get_default_prefs();

$fields = array(
	'preference' => '',
	'hard_to_search' => false,
	'duplicate_name' => 0,
	'duplicate_description' => 0,
	'word_count' => 0,
	'filter' => '',
	'name' => '',
	'help' => '',
	'default' => '',
	'description' => '',
	'locations' => '',
	'dependencies' => '',
	'type' => '',
	'options' => '',
	'admin' => '',
	'module' => '',
	'view' => '',
	'permission' => '',
	'plugin' => '',
	'extensions' => '',
	'tags' => '',
	'parameters' => '',
	'detail' => '',
	'warning' => '',
	'hint' => '',
	'shorthint' => '',
	'perspective' => '',
	'separator' => '',
);

$stopWords = array('', 'in', 'and', 'a', 'to', 'be', 'of', 'on', 'the', 'for', 'as', 'it', 'or', 'with', 'by', 'is', 'an');

$data = array();

$data = collect_raw_data($fields);
remove_fake_descriptions($data);
set_default_values($data, $defaultValues);
collect_locations($data);
$index = array(
	'name' => index_data($data, 'name'),
	'description' => index_data($data, 'description'),
);
update_search_flag($data, $index, $stopWords);

// Output results
fputcsv(STDOUT, array_keys($fields));

foreach ($data as $values) {
	fputcsv(STDOUT, array_values($values));
}

function collect_raw_data($fields)
{
	$data = array();

	foreach (glob('lib/prefs/*.php') as $file) {
		$name = substr(basename($file), 0, -4);
		$function = "prefs_{$name}_list";

		include $file;
		$list = $function();

		foreach ($list as $name => $raw) {
			$entry = $fields;

			$entry['preference'] = $name;
			$entry['name'] = isset($raw['name']) ? $raw['name'] : '';
			$entry['description'] = isset($raw['description']) ? $raw['description'] : '';
			$entry['filter'] = isset($raw['filter']) ? $raw['filter'] : '';
			$entry['help'] = isset($raw['help']) ? $raw['help'] : '';
			$entry['dependencies'] = isset($raw['dependencies']) ? implode(',', $raw['dependencies']) : '';
			$entry['type'] = isset($raw['type']) ? $raw['type'] : '';
			$entry['options'] = isset($raw['options']) ? implode(',', $raw['options']) : '';
			$entry['admin'] = isset($raw['admin']) ? $raw['admin'] : '';
			$entry['module'] = isset($raw['module']) ? $raw['module'] : '';
			$entry['view'] = isset($raw['view']) ? $raw['view'] : '';
			$entry['permission'] = isset($raw['permission']) ? implode(',', $raw['permission']) : '';
			$entry['plugin'] = isset($raw['plugin']) ? $raw['plugin'] : '';
			$entry['extensions'] = isset($raw['extensions']) ? implode(',', $raw['extensions']) : '';
			$entry['tags'] = isset($raw['tags']) ? implode(',', $raw['tags']) : '';
			$entry['parameters'] = isset($raw['parameters']) ? implode(',', $raw['parameters']) : '';
			$entry['detail'] = isset($raw['detail']) ? $raw['detail'] : '';
			$entry['warning'] = isset($raw['warning']) ? $raw['warning'] : '';
			$entry['hint'] = isset($raw['hint']) ? $raw['hint'] : '';
			$entry['shorthint'] = isset($raw['shorthint']) ? $raw['shorthint'] : '';
			$entry['perspective'] = isset($raw['perspective']) ? $raw['perspective'] ? 'true' : 'false' : '';
                        $entry['separator'] = isset($raw['separator']) ? $raw['separator'] : '';
			$data[] = $entry;
		}
	}

	return $data;
}

function remove_fake_descriptions(& $data)
{
	foreach ($data as & $row) {
		if ($row['name'] == $row['description']) {
			$row['description'] = '';
		}
	}
}

function set_default_values(& $data, $prefs)
{
	foreach ($data as & $row) {
		$row['default'] = $prefs[$row['preference']];
	}
}

function index_data($data, $field)
{
	$index = array();

	foreach ($data as $row) {
		$value = strtolower($row[$field]);

		if (! isset($index[$value])) {
			$index[$value] = 0;
		}

		$index[$value]++;
	}

	return $index;
}

function collect_locations(& $data)
{
	global $prefslib; require_once 'lib/prefslib.php';

	foreach ($data as & $row) {
		$pages = $prefslib->getPreferenceLocations($row['preference']);
		foreach ($pages as & $page) {
			$page = $page[0] . '/' . $page[1];
		}
		$row['locations'] = implode(', ', $pages);
	}
}

function update_search_flag(& $data, $index, $stopWords)
{
	foreach ($data as & $row) {
		$name = strtolower($row['name']);
		$description = strtolower($row['description']);

		$words = array_diff(explode(' ', $name . ' ' . $description), $stopWords);

		$row['duplicate_name'] = $index['name'][$name];
		if (! empty($description)) {
			$row['duplicate_description'] = $index['description'][$description];
		}
		$row['word_count'] = count($words);

		if (count($words) < 5) {
			$row['hard_to_search'] = 'X';
		} elseif ($index['name'][$name] > 2) {
			$row['hard_to_search'] = 'X';
		} elseif ($index['description'][$description] > 2) {
			$row['hard_to_search'] = 'X';
		}
	}
}

