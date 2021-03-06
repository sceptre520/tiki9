<?php
// (c) Copyright 2002-2012 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Update language.php files
 * 
 * Examples:
 * 		- http://localhost/pathToTiki/get_strings.php -> update all language.php files
 * 		- http://localhost/pathToTiki/get_strings.php?lang=fr -> update just lang/fr/language.php file
 * 		- http://localhost/pathToTiki/get_strings.php?lang[]=fr&lang[]=pt-br&outputFiles -> update both French
 * 		  and Brazilian Portuguese language.php files and for each string add a line with
 * 		  the file where it was found.
 * 
 * Command line examples:
 * 		- php get_strings.php
 * 		- php get_strings.php lang=pt-br outputFiles=true
 * 		- php get_strings.php baseDir=lib/ excludeDirs=lib/core/Zend,lib/captcha includeFiles=captchalib.php,index.php fileName=language_r.php
 * 
 * Note: baseDir and fileName parameters are available in command line mode only 
 *
 *
 * If you want to know the translation progression for your language, just visit : http://i18n.tiki.org/status
 *
 */

if (php_sapi_name() != 'cli') {
	require_once('tiki-setup.php');
	$access->check_permission('tiki_p_admin');
}

require_once('lib/core/Request.php');
require_once('lib/language/CollectFiles.php');
require_once('lib/language/FileType.php');
require_once('lib/language/FileType/Php.php');
require_once('lib/language/FileType/Tpl.php');
require_once('lib/language/GetStrings.php');
require_once('lib/language/WriteFile/Factory.php');

require_once('lib/setup/timer.class.php');

$timer = new timer();
$timer->start();

$options = array();

$request = new Request();

if ($request->hasProperty('lang')) {
	$options['lang'] = $request->getProperty('lang');
}

if ($request->hasProperty('outputFiles')) {
	$options['outputFiles'] = $request->getProperty('outputFiles');
}

$excludeDirs = array(
	'dump' , 'img', 'lang', 'lib/adodb', 'lib/ckeditor',
	'lib/codemirror', 'lib/core/Zend', 'lib/ezcomponents', 'lib/html5shim', 
	'lib/htmlpurifier', 'lib/jquery', 'lib/jquery.s5', 'lib/jquery.sheet', 'lib/jscalendar', 'lib/mobileesp', 'lib/pclzip',
	'lib/pear', 'lib/phpcas', 'lib/smarty', 'lib/svg-edit', 'lib/test',	'temp',
	'temp/cache',	'templates_c'
);

$includeFiles = array(
	'./lang/langmapping.php', './img/flags/flagnames.php'
);

// command-line only options
if (php_sapi_name() == 'cli') {
	if ($request->hasProperty('baseDir')) {
		$options['baseDir'] = $request->getProperty('baseDir');
		
		// when a custom base dir is set, default $includeFiles and $excludeDirs are not used
		$includeFiles = array();
		$excludeDirs = array();
	}
	
	if ($request->hasProperty('excludeDirs')) {
		$excludeDirs = explode(',', $request->getProperty('excludeDirs'));
	}
	
	if ($request->hasProperty('includeFiles')) {
		$includeFiles = explode(',', $request->getProperty('includeFiles'));
	}
	
	if ($request->hasProperty('fileName')) {
		$options['fileName'] = $request->getProperty('fileName');
	}
}

$getStrings = new Language_GetStrings(new Language_CollectFiles, new Language_WriteFile_Factory, $options);

$getStrings->addFileType(new Language_FileType_Php);
$getStrings->addFileType(new Language_FileType_Tpl);

// skip the following directories 
$getStrings->collectFiles->setExcludeDirs($excludeDirs);

// manually add the following files from skipped directories
$getStrings->collectFiles->setIncludeFiles($includeFiles);

echo formatOutput("Languages: " . implode(' ', $getStrings->getLanguages()) . "\n");

$getStrings->run();

echo formatOutput("\nTotal time spent: " . $timer->stop() . " seconds\n");

function formatOutput($string)
{
	if (php_sapi_name() == 'cli') {
		return $string;
	} else {
		return nl2br($string);
	}
}
