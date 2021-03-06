<?php
// (c) Copyright 2002-2012 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for Image
 * 
 * Letter key: ~i~
 *
 */
class Tracker_field_Image extends Tracker_Field_File
{
	private $imgMimeTypes;
	private $imgMaxSize;

	public static function getTypes()
	{
		return array(
			'i' => array(
				'name' => tr('Image'),
				'description' => tr('Allow users to upload images on the tracker item.'),
				'help' => 'Image Tracker Field',
				'prefs' => array('trackerfield_image'),
				'tags' => array('basic'),
				'default' => 'y',
				'params' => array(
					'xListSize' => array(
						'name' => tr('List image width'),
						'description' => tr('Display size in pixels'),
						'filter' => 'int',
						'default' => 30,
					),
					'yListSize' => array(
						'name' => tr('List image height'),
						'description' => tr('Display size in pixels'),
						'filter' => 'int',
						'default' => 30,
					),
					'xDetailSize' => array(
						'name' => tr('Detail image width'),
						'description' => tr('Display size in pixels'),
						'filter' => 'int',
						'default' => 300,
					),
					'yDefailSize' => array(
						'name' => tr('Detail image height'),
						'description' => tr('Display size in pixels'),
						'filter' => 'int',
						'default' => 300,
					),
					'uploadLimitScale' => array(
						'name' => tr('Maximum image size'),
						'description' => tr('Maximum image width or height in pixels.'),
						'filter' => 'int',
						'default' => '1000',
					),
					'shadowbox' => array(
						'name' => tr('Shadowbox'),
						'description' => tr('Shadowbox usage on this field'),
						'filter' => 'alpha',
						'options' => array(
							'' => tr('Do not use'),
							'individual' => tr('One box per item'),
							'group' => tr('Use the same box for all images'),
						),
					),
					'imageMissingIcon' => array(
						'name' => tr('Missing Icon'),
						'description' => tr('Icon to use when no images have been uploaded.'),
						'filter' => 'url',
					),
				),
			),
		);
	}

	function __construct($fieldInfo, $itemData, $trackerDefinition)
	{
		parent::__construct($fieldInfo, $itemData, $trackerDefinition);
		$this->imgMimeTypes = array('image/jpeg', 'image/gif', 'image/png', 'image/pjpeg', 'image/bmp');
		$this->imgMaxSize = (1048576 * 4); // 4Mo
	}

	function getFieldData(array $requestData = array())
	{
		global $prefs, $smarty;
		
		$ins_id = $this->getInsertId();

		if (!empty($prefs['fgal_match_regex']) && !empty($_FILES[$ins_id]['name'])) {
			if (!preg_match('/' . $prefs['fgal_match_regex'] . '/', $_FILES[$ins_id]['name'], $reqs)) {
				$smarty->assign('msg', tra('Invalid imagename (using filters for filenames)'));
				$smarty->display("error.tpl");
				die;
			}
		}
		if (!empty($prefs['fgal_nmatch_regex']) && !empty($_FILES[$ins_id]['name'])) {
			if (preg_match('/' . $prefs['fgal_nmatch_regex'] . '/', $_FILES[$ins_id]['name'], $reqs)) {
				$smarty->assign('msg', tra('Invalid imagename (using filters for filenames)'));
				$smarty->display("error.tpl");
				die;
			}
		}
		if (!empty($requestData)) {
			return parent::getFieldData($requestData);
		} else {
			return array( 'value' => $this->getValue() );
		}
	}

	function renderInnerOutput( $context = array() )
	{
		global $prefs;
		$smarty = TikiLib::lib('smarty');

		$val = $this->getConfiguration('value');
		$list_mode = !empty($context['list_mode']) ? $context['list_mode'] : 'n';
		if ($list_mode == 'csv') {
			return $val; // return the filename
		}
		$pre = '';
		if ( !empty($val) && file_exists($val) ) {
			$params['file'] = $val;
			$shadowtype = $this->getOption(5);
			if ($prefs['feature_shadowbox'] == 'y' && !empty($shadowtype)) {
				switch ($shadowtype) {
				case 'item':
					$rel = '['.$this->getItemId().']';
    				break;
				case 'individual':
					$rel = '';
    				break;
				default:
					$rel = '['.$this->getConfiguration('fieldId').']';
    				break;
				}
				$pre = "<a href=\"$val\" rel=\"shadowbox$rel;type=img\">";
			}
			if ( $this->getOption(0) || $this->getOption(1) || $this->getOption(2) || $this->getOption(3)) {
				$image_size_info = getimagesize($val);
			}
			if ($list_mode != 'n') {
				if ($this->getOption(0) || $this->getOption(1)) {
					list( $params['width'], $params['height']) = $this->get_resize_dimensions(
									$image_size_info[0],
									$image_size_info[1],
									$this->getOption(0),
									$this->getOption(1)
					);
				}
			} else {
				if ($this->getOption(2) || $this->getOption(3)) {
					list( $params['width'], $params['height']) = $this->get_resize_dimensions(
									$image_size_info[0],
									$image_size_info[1],
									$this->getOption(2),
									$this->getOption(3)
					);
				}
			}
		} else {
			if ($this->getOption(6)) {
				$params['file'] = $this->getOption(6);
				$params['alt'] = 'n/a';
			} else {
				return '';
			}
		}
		$smarty->loadPlugin('smarty_function_html_image');
		$ret = smarty_function_html_image($params, $smarty);
		if (!empty($pre))
			$ret = $pre.$ret.'</a>';
		return $ret;
	}

	function renderInput($context = array())
	{
		return $this->renderTemplate(
						'trackerinput/image.tpl',
						$context,
						array(
							'image_tag' => $this->renderInnerOutput($context),
						)
		);
	}

	function handleSave($value, $oldValue)
	{
		if (! empty($value)) {
			$old_file = $oldValue;

			if ($value == 'blank') {
				if (file_exists($old_file)) {
					unlink($old_file);
				}

				return array(
					'value' => '',
				);
			}

			$type = $this->getConfiguration('file_type');

			if ($this->isImageType($type)) {
				if ($maxSize = $this->getOption(4)) {
					$imagegallib = TikiLib::lib('imagegal');	// TODO: refactor to use Image class directly and remove dependency on imagegals
					$imagegallib->image = $value;
					$imagegallib->readimagefromstring();
					$imagegallib->getimageinfo();
					if ($imagegallib->xsize > $maxSize || $imagegallib->ysize > $maxSize) {
						$imagegallib->rescaleImage($maxSize, $maxSize);
						$value = $imagegallib->image;
					}
				}
				$filesize = $this->getConfiguration('file_size');
				if ($filesize <= $this->imgMaxSize) {
					$itemId = $this->getItemId();
					$file_name = $this->getImageFilename($this->getConfiguration('file_name'), $itemId, $this->getConfiguration('fieldId'));

					file_put_contents($file_name, $value);
					chmod($file_name, 0644); // seems necessary on some system (see move_uploaded_file doc on php.net

					if (file_exists($old_file) && $old_file != $file_name) {
						unlink($old_file);
					}

					return array(
						'value' => $file_name,
					);
				}
			}
		}

		return array(
			'value' => false,
		);
	}

	/**
	 * Calculate the size of a resized image
	 * 
	 * TODO move to a lib (Images depends on Imagick or GD which this doesn't need)
	 * 
	 * @param int $image_width (existing image width)
	 * @param int $image_height	(existing image height)
	 * @param int $max_width (max width to scale to)
	 * @param int $max_height (optional max height)
	 * @param bool $upscale (whether to make images larger - default = false)
	 * 
	 * @return array(int $resized_width, int $resized_height)
	 */
	private function get_resize_dimensions( $image_width, $image_height, $max_width = null, $max_height = null, $upscale = false)
	{
		if (!$upscale && $image_width <= $max_width && $image_height <= $max_height) {
			return array($image_width, $image_height);
		}
		if ( !$max_height || ($max_width && $image_width > $image_height && $image_height < $max_height)) {
			$ratio = $max_width / $image_width;
		} else {
			$ratio = $max_height / $image_height;
			if ($max_width && round($image_width * $ratio) > $max_width) {
				$ratio = $max_width / $image_width;
			}
		}
		return array(round($image_width * $ratio), round($image_height * $ratio));
	}

	function getImageFilename($name, $itemId, $fieldId)
	{
		do {
			$name = md5(uniqid("$name.$itemId.$fieldId"));
		} while (file_exists("img/trackers/$name"));

		return "img/trackers/$name";
	}

	function isImageType($mimeType)
	{
		return in_array($mimeType, $this->imgMimeTypes);
	}

}

