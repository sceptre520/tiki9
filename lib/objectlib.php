<?php
// (c) Copyright 2002-2012 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

class ObjectLib extends TikiLib
{
	/**
	 *	Create an object record for the given Tiki object if one doesn't already exist.
	 * Returns the object record OID. If the designated object does not exist, may return NULL.
	 * If the object type is not handled and $checkHandled is TRUE, fail and return FALSE.
	 * $checkHandled A boolean indicating whether only handled object types should be accepted when the object has no object record (legacy).
	 * When creating, if $description is given, use the description, name and URL given as information.
	 * Otherwise retrieve it from the object (if $checkHandled is FALSE, fill with empty strings if the object type is not handled).
	 * Handled object types: "article", "blog", "calendar", "directory", "faq",
	 * "file", "file gallery", "forum", "image gallery", "poll", "quiz", "tracker", "trackeritem" and "wiki page".
	 *
	 */
	function add_object($type, $itemId, $checkHandled = TRUE, $description = NULL, $name = NULL, $href = NULL)
	{
		$objectId = $this->get_object_id($type, $itemId);

		if ($objectId) {
			if (!empty($description) || !empty($name) || !empty($href)) {
				$query = "update `tiki_objects` set `description`=?,`name`=?,`href`=? where `objectId`=?";
				$this->query($query, array($description, $name, $href, $objectId));
			}
		} else {
			if (is_null($description)) {
				switch ($type) {
					case 'article':
						global $artlib; require_once 'lib/articles/artlib.php';
						$info = $artlib->get_article($itemId);

						$description = $info['heading'];
						$name = $info['title'];
						$href = 'tiki-read_article.php?articleId=' . $itemId;
									break;

					case 'blog':
						global $bloglib; require_once('lib/blogs/bloglib.php');
						$info = $bloglib->get_blog($itemId);

						$description = $info['description'];
						$name = $info['title'];
						$href = 'tiki-view_blog.php?blogId=' . $itemId;
									break;

					case 'calendar':
						global $calendarlib; require_once('lib/calendar/calendarlib.php');
						$info = $calendarlib->get_calendar($itemId);

						$description = $info['description'];
						$name = $info['name'];
						$href = 'tiki-calendar.php?calId=' . $itemId;
									break;

					case 'directory':
						$info = $this->get_directory($itemId);

						$description = $info['description'];
						$name = $info['name'];
						$href = 'tiki-directory_browse.php?parent=' . $itemId;
									break;

					case 'faq':
						{
							require_once ('lib/faqs/faqlib.php');
							$info = $faqlib->get_faq($itemId);

							$description = $info['description'];
							$name = $info['title'];
							$href = 'tiki-view_faq.php?faqId=' . $itemId;
						}
									break;

					case 'file':
						$filegallib = TikiLib::lib('filegal');
						$info = $filegallib->get_file_info($itemId, false, false, false);

						$description = $info['description'];
						$name = $info['name'];
						$href = 'tiki-upload_file.php?fileId=' . $itemId;
									break;

					case 'file gallery':
						$filegallib = TikiLib::lib('filegal');
						$info = $filegallib->get_file_gallery($itemId);

						$description = $info['description'];
						$name = $info['name'];
						$href = 'tiki-list_file_gallery.php?galleryId=' . $itemId;
									break;

					case 'forum':
						$commentslib = TikiLib::lib('comments');
						$info = $commentslib->get_forum($itemId);

						$description = $info['description'];
						$name = $info['name'];
						$href = 'tiki-view_forum.php?forumId=' . $itemId;
									break;

					case 'image gallery':
						$info = $this->get_gallery($itemId);

						$description = $info['description'];
						$name = $info['name'];
						$href = 'tiki-browse_gallery.php?galleryId=' . $itemId;
									break;

					case 'poll':
						global $polllib; require_once('lib/polls/polllib_shared.php');
						$info = $polllib->get_poll($itemId);

						$description = $info['title'];
						$name = $info['title'];
						$href = 'tiki-poll_form.php?pollId=' . $itemId;
									break;

					case 'quiz':
						$info = TikiLib::lib('quiz')->get_quiz($itemId);

						$description = $info['description'];
						$name = $info['name'];
						$href = 'tiki-take_quiz.php?quizId=' . $itemId;
									break;

					case 'tracker':
						global $trklib; include_once('lib/trackers/trackerlib.php');
						$info = $trklib->get_tracker($itemId);

						$description = $info['description'];
						$name = $info['name'];
						$href = 'tiki-view_tracker.php?trackerId=' . $itemId;
									break;

					case 'trackeritem':
						global $trklib; include_once('lib/trackers/trackerlib.php');
						$info = $trklib->get_tracker_item($itemId);

						$description = '';
						$name = $trklib->get_isMain_value($info['trackerId'], $itemId);
						$href = "tiki-view_tracker_item.php?itemId=$itemId&trackerId=" . $info['trackerId'];
									break;

					case 'wiki page':
						if (!($info = $this->get_page_info($itemId))) {
							return;
						}
						$description = $info["description"];
						$name = $itemId;
						$href = 'tiki-index.php?page=' . urlencode($itemId);
									break;

					default:
						if ($checkHandled) {
							return FALSE;
						} else {
							$description = '';
							$name = '';
							$href = '';
						}
				}
			}
			$objectId = $this->insert_object($type, $itemId, $description, $name, $href);
		}

		return $objectId;
	}

	function insert_object($type, $itemId, $description = '', $name = '', $href = '')
	{
		if (! $itemId) {
			// When called with a blank page name or any other empty value, no insertion should be made
			return false;
		}

		$tikilib = TikiLib::lib('tiki');
		$table = $this->table('tiki_objects');
		return $table->insert(array(
			'type' => $type,
			'itemId' => (string) $itemId,
			'description' => $description,
			'name' => $name,
			'href' => $href,
			'created' => (int) $tikilib->now,
			'hits' => 0,
			'comments_locked' => 'n',
		));
	}

	function get_object_id($type, $itemId)
	{
		$query = "select `objectId` from `tiki_objects` where `type`=? and `itemId`=?";
		return $this->getOne($query, array($type, $itemId));
	}

	// Returns an array containing the object ids of objects of the same type.
	// Each entry uses the item id as key and the object id as key. Items with no object id are ignored.
	function get_object_ids($type, $itemIds)
	{
		$query = 'select `objectId`, `itemId` from `tiki_objects` where `type`=? and `itemId` IN (' .
						implode(',', array_fill(0, count($itemIds), '?')) . ')';

		$result = $this->query($query, array_merge(array($type), $itemIds));
		$objectIds = array();

		while ($res = $result->fetchRow()) {
			$objectIds[$res['itemId']] = $res['objectId'];
		}
		return $objectIds;
	}

	function get_needed_perm($objectType, $action)
	{
		switch ($objectType) {
			case 'wiki page': case 'wiki':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_view';

					case 'edit':
						return 'tiki_p_edit';
				}
			case 'article':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_read_article';

					case 'edit':
						return 'tiki_p_edit_article';
				}
			case 'post':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_read_blog';

					case 'edit':
						return 'tiki_p_create_blog';
				}

			case 'blog':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_read_blog';

				case 'edit':
					return 'tiki_p_create_blog';
				}

			case 'faq':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_view_faqs';

					case 'edit':
						return 'tiki_p_admin_faqs';
				}

			case 'file gallery':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_view_file_gallery';

					case 'edit':
						return 'tiki-admin_file_galleries';
				}

			case 'image gallery':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_view_image_gallery';

					case 'edit':
						return 'tiki_p_admin_galleries';
				}

			case 'poll':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_vote_poll';

					case 'edit':
						return 'tiki_p_admin';
				}

			case 'comment':
			case 'comments':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_read_comments';

					case 'edit':
						return 'tiki_p_edit_comments';
				}

			case 'trackeritem':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_view_trackers';

					case 'edit':
						return 'tiki_p_modify_tracker_items';
				}

			case 'trackeritem_closed':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_view_trackers';

					case 'edit':
						return 'tiki_p_modify_tracker_items_closed';
				}

			case 'trackeritem_pending':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_view_trackers';

					case 'edit':
						return 'tiki_p_modify_tracker_items_pending';
				}

			case 'tracker':
				switch ($action) {
					case 'view':
					case 'read':
						return 'tiki_p_list_trackers';

					case 'edit':
						return 'tiki_p_admin_trackers';
				}
			default : return '';
		}
	}

	function get_info($objectType, $object)
	{
		switch ($objectType) {
			case 'wiki':
			case 'wiki page':
				global $tikilib; include_once('lib/tikilib.php');
				$info = $tikilib->get_page_info($object);
				return (array('title'=>$object, 'data'=>$info['data'], 'is_html'=>$info['is_html']));

			case 'article':
				global $artlib; require_once 'lib/articles/artlib.php';
				$info = $artlib->get_article($object);
				return (array('title'=>$info['title'], 'data'=>$info['body']));

			case 'file gallery':
				$info = TikiLib::lib('filegal')->get_file_gallery_info($object);
				return (array('title' => $info['name']));

			case 'blog':
				$info = TikiLib::lib('blog')->get_blog($object);
				return (array('title' => $info['title']));

			case 'forum':
				$info = TikiLib::lib('comments')->get_forum($object);
				return (array('title' => $info['name']));

			case 'tracker':
				$info = TikiLib::lib('trk')->get_tracker($object);
				return (array('title' => $info['name']));
		}
		return (array('error'=>'true'));
	}

	function set_data($objectType, $object, $data)
	{
		switch ($objectType) {
			case 'wiki':
			case 'wiki page':
				global $tikilib; include_once('lib/tikilib.php');
				global $user;
				$tikilib->update_page($object, $data, tra('section edit'), $user, $tikilib->get_ip_address());
							break;
		}
	}

	function delete_object($type, $itemId)
	{
		$query = 'delete from `tiki_objects` where `itemId`=? and `type`=?';
		$this->query($query, array($itemId, $type));
	}

	function get_object($type, $itemId)
	{
		$query = 'select * from `tiki_objects` where `itemId`=? and `type`=?';
		$result = $this->query($query, array($itemId, $type));
		return $result->fetchRow();
	}

	function get_object_via_objectid($objectId)
	{
		$query = 'select * from `tiki_objects` where `objectId`=?';
		$result = $this->query($query, array((int) $objectId));
		return $result->fetchRow();
	}

	function get_title($type, $id)
	{
		switch ($type) {
			case 'trackeritem':
				return TikiLib::lib('trk')->get_isMain_value(null, $id);
			case 'category':
				return TikiLib::lib('categ')->get_category_name($id);
		}

		$title = $this->table('tiki_objects')->fetchOne(
						'name',
						array(
							'type' => $type,
							'itemId' => $id,
						)
		);

		if ($title) {
			return $title;
		}

		$info = $this->get_info($type, $id);

		if (isset($info['title'])) {
			return $info['title'];
		}
	}

	// Returns a hash indicating which permission is needed for viewing an object of desired type.
	static function map_object_type_to_permission()
	{
		return array(
			'wiki page' => 'tiki_p_view',
			'wiki' => 'tiki_p_view',
			'wiki' => 'tiki_p_view',
			'forum' => 'tiki_p_forum_read',
			'forum post' => 'tiki_p_forum_read',
			'image gallery' => 'tiki_p_view_image_gallery',
			'file gallery' => 'tiki_p_view_file_gallery',
			'tracker' => 'tiki_p_view_trackers',
			'blog' => 'tiki_p_read_blog',
			'blog post' => 'tiki_p_read_blog',
			'quiz' => 'tiki_p_take_quiz',

			// overhead - we are checking individual permission on types below, but they
			// can't have individual permissions, although they can be categorized.
			// should they have permissions too?
			'poll' => 'tiki_p_vote_poll',
			'survey' => 'tiki_p_take_survey',
			'directory' => 'tiki_p_view_directory',
			'faq' => 'tiki_p_view_faqs',
			'sheet' => 'tiki_p_view_sheet',

			// these ones are tricky, because permission type is for container, not object itself.
			// I think we need to refactor permission schemes for them to be wysiwyca - lfagundes
			//
			// by now they're not showing, list_category_objects needs support for ignoring permissions
			// for a type.
			'article' => 'tiki_p_read_article',
			'submission' => 'tiki_p_approve_submission',
			'image' => 'tiki_p_view_image_gallery',
			'calendar' => 'tiki_p_view_calendar',
			'file' => 'tiki_p_download_files',
			'trackeritem' => 'tiki_p_view_trackers',

			// newsletters can't be categorized, although there's some code in tiki-admin_newsletters.php
			// 'newsletter' => ?,
			// 'events' => ?,
		);
	}

	function get_metadata($type, $object, & $classList)
	{
		global $smarty;
		$smarty->loadPlugin('smarty_modifier_escape');

		$escapedType = smarty_modifier_escape($type);
		$escapedObject = smarty_modifier_escape($object);
		$metadata = ' data-type="' . $escapedType . '" data-object="' . $escapedObject . '"';

		if ($coordinates = TikiLib::lib('geo')->get_coordinates($type, $object)) {
			$classList[] = 'geolocated';
			$metadata = " data-geo-lat=\"{$coordinates['lat']}\" data-geo-lon=\"{$coordinates['lon']}\"";

			if (isset($coordinates['zoom'])) {
				$metadata .= " data-geo-zoom=\"{$coordinates['zoom']}\"";
			}
		}

		$attributelib = TikiLib::lib('attribute');
		$attributes = $attributelib->get_attributes($type, $object);

		if (isset($attributes['tiki.icon.src'])) {
			$escapedIcon = smarty_modifier_escape($attributes['tiki.icon.src']);
			$metadata .= " data-icon-src=\"$escapedIcon\"";
		}

		return $metadata;
	}
}
global $objectlib;
$objectlib = new ObjectLib;
