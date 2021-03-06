<?php
// (c) Copyright 2002-2012 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_font_getfontoptions()
{
	global $prefs;
	
	$names = preg_split('/;/', $prefs['wysiwyg_fonts']);
	$fonts = array();
	$fonts[] = array('text' => '', 'value' => ''); 
	
	foreach ( $names as $n) {
		$fonts[] = array('text' => $n, 'value' => $n);
	}
	return $fonts;
}


/*
 * Note: 
 * 
 * This plugin is needed to save font definitions when the editor is switched:
 * - size unit is 'px' (compatible with the CKE)
 * - fonts are embedded in <span> (compatible with the CKE)
  */
function wikiplugin_font_info()
{
	return array(
		'name' => tra('FONT'),
		'format' => 'wiki',
		'documentation' => 'PluginFont',
		'description' => tra('Experimental: Font family and font size.'),
		'prefs' => array('wikiplugin_font'),
		'params' => array(),
		'body' => tra('Content'),
		'tags' => array( 'experimental' ),		
		'params' => array(
			'family' => array(
				'required' => false,
				'name' => tra('Font Family'),
				'default' => '',
				'description' => tra('Select the font family to display the content.'),
				'options' => wikiplugin_font_getfontoptions(),
			),
			'size' => array(
				'required' => false,
				'name' => tra('Font Size'),
				'default' => '',
				'description' => tra('Define the size of the font in pixels (enter \'12\' to get a font of 12px)'), // 'px' is compatible with the CKE UI
			),
		),
	);	
} // wikiplugin_font_info()


function wikiplugin_font($data, $params)
{
	global $prefs;
	
	$tag = 'span'; // fonts defined in divs are not shown in the CKE UI

	$all_fonts = preg_split('/;/', $prefs['wysiwyg_fonts']);
	foreach ($all_fonts as &$f) {
		$f = strtolower($f);
	}
	
	$family = isset($params['family']) ? strtolower($params['family']) : '';
	$size = isset($params['size']) ? $params['size'] : '';
	
	$style  = '';
	$style .= ($family and in_array($family, $all_fonts)) ? "font-family: $family;" : '';
	$style .= (intval($size) and $size>0)  ? ("font-size: $size". "px;") : '';

	if ($style) {
		return "<$tag style=\"$style\">$data</$tag>";
	} else {
		return $data;
	}

}
