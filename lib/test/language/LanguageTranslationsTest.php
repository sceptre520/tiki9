<?php
// (c) Copyright 2002-2012 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once 'lib/tikiaccesslib.php';
require_once 'lib/language/LanguageTranslations.php';

/**
 * Test class for LanguageTranslations.
 * Generated by PHPUnit on 2010-08-05 at 10:04:14.
 */
class LanguageTranslationsTest extends TikiTestCase
{
	/**
	 * @var LanguageTranslations
	 */
	protected $obj;

	protected $lang;

	protected $langDir;

	protected $tikiroot;

	protected function setUp()
	{
		$this->tikiroot = dirname(__FILE__) . '/../../../';
		$this->lang = 'test_language';
		$this->langDir = $this->tikiroot . 'lang/' . $this->lang;

		chdir($this->tikiroot);
		mkdir($this->langDir);

		$this->obj = new LanguageTranslations($this->lang);

		TikiDb::get()->query('INSERT INTO `tiki_language` (`source`, `lang`, `tran`, `changed`) VALUES (?, ?, ?, ?)', array('Contributions by author', $this->lang, 'Contribuições por autor', 1));
		TikiDb::get()->query('INSERT INTO `tiki_language` (`source`, `lang`, `tran`, `changed`) VALUES (?, ?, ?, ?)', array('Remove', $this->lang, 'Novo remover', 1));
		TikiDb::get()->query('INSERT INTO `tiki_language` (`source`, `lang`, `tran`, `changed`) VALUES (?, ?, ?, ?)', array('Approved Status', $this->lang, 'Aprovado', 1));
		TikiDb::get()->query('INSERT INTO `tiki_language` (`source`, `lang`, `tran`, `changed`) VALUES (?, ?, ?, ?)', array('Something', $this->lang, 'Algo', 1));
		TikiDb::get()->query('INSERT INTO `tiki_language` (`source`, `lang`, `tran`, `changed`) VALUES (?, ?, ?, ?)', array('Trying to insert malicious PHP code back to the language.php file', $this->lang, 'asff"); echo \'teste\'; $dois = array(\'\',"', 1));
		TikiDb::get()->query('INSERT INTO `tiki_language` (`source`, `lang`, `tran`, `changed`) VALUES (?, ?, ?, ?)', array('Should escape "double quotes" in the source string', $this->lang, 'Deve escapar "aspas duplas" na string original', 1));
		TikiDb::get()->query('INSERT INTO `tiki_language` (`source`, `lang`, `tran`) VALUES (?, ?, ?)', array('Not changed', $this->lang, 'Translation not changed'));

		TikiDb::get()->query('INSERT INTO `tiki_untranslated` (`source`, `lang`) VALUES (?, ?)', array('Untranslated string 1', $this->lang));
		TikiDb::get()->query('INSERT INTO `tiki_untranslated` (`source`, `lang`) VALUES (?, ?)', array('Untranslated string 2', $this->lang));
		TikiDb::get()->query('INSERT INTO `tiki_untranslated` (`source`, `lang`) VALUES (?, ?)', array('Untranslated string 3', $this->lang));

		global ${"lang_$this->lang"};

		copy(dirname(__FILE__) . '/fixtures/language_orig.php', $this->langDir . '/language.php');

		if (!isset(${"lang_$this->lang"})) {
			require_once('lib/init/tra.php');
			init_language($this->lang);
		}
	}

	protected function tearDown()
	{
		if (file_exists($this->langDir . '/language.php')) {
			unlink($this->langDir . '/language.php');
		}

		if (file_exists($this->langDir . '/custom.php')) {
			unlink($this->langDir . '/custom.php');
		}

		rmdir($this->langDir);

		TikiDb::get()->query('DELETE FROM `tiki_language` WHERE `lang` = ?', array($this->lang));
		TikiDb::get()->query('DELETE FROM `tiki_untranslated` WHERE `lang` = ?', array($this->lang));

		unset($GLOBALS['prefs']['record_untranslated']);
	}

	public function testUpdateTransShouldInsertNewTranslation()
	{
		$this->obj->updateTrans('New string', 'New translation');
		$result = TikiDb::get()->getOne('SELECT `tran` FROM `tiki_language` WHERE `lang` = ? AND `source` = ?', array($this->lang, 'New string'));
		$this->assertEquals('New translation', $result);
		TikiDb::get()->query('DELETE FROM `tiki_language` WHERE `lang` = ? AND `source` = ?', array($this->lang, 'New string'));
	}

	public function testUpdateTransShouldUpdateTranslation()
	{
		TikiDb::get()->query('INSERT INTO `tiki_language` (`source`, `lang`, `tran`) VALUES (?, ?, ?)', array('New string', $this->lang, 'Old translation'));
		$this->obj->updateTrans('New string', 'New translation');
		$result = TikiDb::get()->getOne('SELECT `tran` FROM `tiki_language` WHERE `lang` = ? AND `source` = ?', array($this->lang, 'New string'));
		$this->assertEquals('New translation', $result);
		TikiDb::get()->query('DELETE FROM `tiki_language` WHERE `lang` = ? AND `source` = ?', array($this->lang, 'New string'));
	}

	public function testUpdateTransShouldNotUpdateTranslation()
	{
		global ${"lang_$this->lang"};
		${"lang_$this->lang"}['Not changed'] = 'Translation not changed';

		$this->assertEquals(null, $this->obj->updateTrans('Not changed', 'Translation not changed'));
		$result = TikiDb::get()->getOne('SELECT `changed` FROM `tiki_language` WHERE `lang` = ? AND binary `source` = ?', array($this->lang, 'Not changed'));
		$this->assertEquals(null, $result);
	}

	public function testUpdateTransShouldDeleteTranslation()
	{
		TikiDb::get()->query('INSERT INTO `tiki_language` (`source`, `lang`, `tran`) VALUES (?, ?, ?)', array('New string', $this->lang, 'New translation'));
		$this->obj->updateTrans('New string', '');
		$result = TikiDb::get()->getOne('SELECT `tran` FROM `tiki_language` WHERE `lang` = ? AND `source` = ?', array($this->lang, 'New string'));
		$this->assertFalse($result);
	}

	public function testUpdateTransShouldNotInsertStringsThatWereNotChanged()
	{
		$this->obj->updateTrans('last modification time', 'data da última modificação');
		$this->assertFalse(TikiDb::get()->getOne('SELECT `tran` FROM `tiki_language` WHERE `lang` = ? AND `source` = ?', array($this->lang, 'last modification time')));
	}

	public function testUpdateTransShouldMarkTranslationAsChanged()
	{
		TikiDb::get()->query('INSERT INTO `tiki_language` (`source`, `lang`, `tran`) VALUES (?, ?, ?)', array('New string', $this->lang, 'Old translation'));
		$this->obj->updateTrans('New string', 'New translation');
		$result = TikiDb::get()->getOne('SELECT `changed` FROM `tiki_language` WHERE `lang` = ? AND `source` = ?', array($this->lang, 'New string'));
		$this->assertEquals(1, $result);
		TikiDb::get()->query('DELETE FROM `tiki_language` WHERE `lang` = ? AND `source` = ?', array($this->lang, 'New string'));
	}

	public function testUpdateTransShouldDeleteEntryFromUntranslatedTable()
	{
		TikiDb::get()->query('INSERT INTO `tiki_untranslated` (`source`, `lang`) VALUES (?, ?)', array('New string', $this->lang));
		$this->obj->updateTrans('New string', 'New translation');
		$result = TikiDb::get()->getOne('SELECT `source` FROM `tiki_untranslated` WHERE `lang` = ? AND `source` = ?', array($this->lang, 'New string'));
		$this->assertFalse($result);
	}

	public function testUpdateTransShouldIgnoreWhenSourceAndTranslationAreEqual()
	{
		$this->obj->updateTrans('Source and translation are the same', 'Source and translation are the same');
		$result = TikiDb::get()->getOne('SELECT `source` FROM `tiki_language` WHERE `lang` = ? AND `source` = ?', array($this->lang, 'Source and translation are the same'));
		$this->assertFalse($result);
	}

	public function testWriteLanguageFile()
	{
		copy(dirname(__FILE__) . '/fixtures/language_orig.php', $this->langDir . '/language.php');
		$this->obj->writeLanguageFile();
		$this->assertEquals(file_get_contents(dirname(__FILE__) . '/fixtures/language_modif.php'), file_get_contents($this->langDir . '/language.php'));
	}

	public function testWriteLanguageFileCallingTwoTimes_shouldNotDuplicateStringsInTheFile()
	{
		copy(dirname(__FILE__) . '/fixtures/language_orig.php', $this->langDir . '/language.php');
		$this->obj->writeLanguageFile();
		$this->obj->writeLanguageFile();
		$this->assertEquals(file_get_contents(dirname(__FILE__) . '/fixtures/language_modif.php'), file_get_contents($this->langDir . '/language.php'));
	}

	public function testWriteLanguage_shouldReturnTheNumberOfNewStringsInLanguageFile()
	{
		copy(dirname(__FILE__) . '/fixtures/language_orig.php', $this->langDir . '/language.php');
		$expectedResult = array('modif' => 2, 'new' => 4);
		$return = $this->obj->writeLanguageFile();
		$this->assertEquals($expectedResult, $return);
	}

	public function testWriteLanguage_shouldIgnoreEmptyStrings()
	{
		TikiDb::get()->query('INSERT INTO `tiki_language` (`source`, `lang`, `tran`, `changed`) VALUES (?, ?, ?, ?)', array('', $this->lang, '', 1));
		copy(dirname(__FILE__) . '/fixtures/language_orig.php', $this->langDir . '/language.php');
		$this->obj->writeLanguageFile();
		$this->assertEquals(file_get_contents(dirname(__FILE__) . '/fixtures/language_modif.php'), file_get_contents($this->langDir . '/language.php'));
	}

	public function testWriteLanguage_shouldRaiseExceptionForInvalidLanguagePhp()
	{
		$this->setExpectedException('Language_Exception');
		copy(dirname(__FILE__) . '/fixtures/language_invalid.php', $this->langDir . '/language.php');
		$this->obj->writeLanguageFile();
	}

	public function testDeleteTranslations()
	{
		$this->obj->deleteTranslations();
		$this->assertFalse(TikiDb::get()->getOne('SELECT * FROM `tiki_language` WHERE `lang` = ?', array($this->obj->lang)));
	}

	public function testGetFileUntranslated()
	{
		$cachelib = $this->getMock('Cachelib', array('getSerialized', 'cacheItem'));
		$cachelib->expects($this->once())->method('getSerialized')->with('untranslatedStrings.test_language.1234', 'untranslatedStrings')->will($this->returnValue(null));
		$cachelib->expects($this->once())->method('cacheItem');

		$obj = $this->getMock('LanguageTranslations', array('_getCacheLib', '_getFileHash'), array($this->lang));
		$obj->expects($this->once())->method('_getCacheLib')->will($this->returnValue($cachelib));
		$obj->expects($this->once())->method('_getFileHash')->will($this->returnValue(1234));

		$expectedResult = array(
				"Kalture Video" => array('source' => "Kalture Video", 'tran' => null),
				"Communication error" => array('source' => "Communication error", 'tran' => null),
				"Invalid response provided by the Kaltura server. Please retry" => array('source' => "Invalid response provided by the Kaltura server. Please retry", 'tran' => null),
				"Delete comments" => array('source' => "Delete comments", 'tran' => null),
				"Approved Status" => array('source' => "Approved Status", 'tran' => null),
				"Queued" => array('source' => "Queued", 'tran' => null),
				"The file is already locked by %s" => array('source' => "The file is already locked by %s", 'tran' => null),
				"WARNING: The file is used in" => array('source' => "WARNING: The file is used in", 'tran' => null),
				"You do not have permission to edit this file" => array('source' => "You do not have permission to edit this file", 'tran' => null),
				"Not modified since" => array('source' => "Not modified since", 'tran' => null),
				'Test special "characters" escaping' => array('source' => 'Test special "characters" escaping', 'tran' => null),
				);

		$this->assertEquals($expectedResult, $obj->getFileUntranslated());
	}

	public function testGetFileUntranslated_checkCache()
	{
		$expectedResult = array(
				"Kalture Video" => array('source' => "Kalture Video", 'tran' => null),
				"Communication error" => array('source' => "Communication error", 'tran' => null),
				"Invalid response provided by the Kaltura server. Please retry" => array('source' => "Invalid response provided by the Kaltura server. Please retry", 'tran' => null),
				"Delete comments" => array('source' => "Delete comments", 'tran' => null),
				"Approved Status" => array('source' => "Approved Status", 'tran' => null),
				"Queued" => array('source' => "Queued", 'tran' => null),
				"The file is already locked by %s" => array('source' => "The file is already locked by %s", 'tran' => null),
				"WARNING: The file is used in" => array('source' => "WARNING: The file is used in", 'tran' => null),
				"You do not have permission to edit this file" => array('source' => "You do not have permission to edit this file", 'tran' => null),
				"Not modified since" => array('source' => "Not modified since", 'tran' => null),
				'Test special "characters" escaping' => array('source' => 'Test special "characters" escaping', 'tran' => null),
				);
		$this->assertEquals($expectedResult, $this->obj->getFileUntranslated());

		// change file to check if the cache is ignored when the file changes
		copy(dirname(__FILE__) . '/fixtures/language_untranslated.php', $this->langDir . '/language.php');
		$expectedResult = array(
				"Kalture Video" => array('source' => "Kalture Video", 'tran' => null),
				"Invalid response provided by the Kaltura server. Please retry" => array('source' => "Invalid response provided by the Kaltura server. Please retry", 'tran' => null),
				"Delete comments" => array('source' => "Delete comments", 'tran' => null),
				"Queued" => array('source' => "Queued", 'tran' => null),
				"The file is already locked by %s" => array('source' => "The file is already locked by %s", 'tran' => null),
				"WARNING: The file is used in" => array('source' => "WARNING: The file is used in", 'tran' => null),
				"You do not have permission to edit this file" => array('source' => "You do not have permission to edit this file", 'tran' => null),
				);
		$this->assertEquals($expectedResult, $this->obj->getFileUntranslated());
	}

	public function getAllTranslations_dataProvider()
	{
		$fileTranslations = array(
				"categorize" => array("source" => "categorize", "tran" => "categorizar"),
				"Set prefs" => array("source" => "Set prefs", "tran" => "Definir preferências"),
				"creation date" => array("source" => "creation date", "tran" => "data de criação"),
				"Delete comments" => array("source" => "Delete comments", "tran" => "Deletar comentários"),
				);

		$dbTranslations = array(
				"Approved Status" => array("id" => "16131", "source" => "Approved Status", "lang" => "test_language", "tran" => "Aprovado", "changed" => "1"),
				"creation date" => array("id" => "16132", "source" => "creation date", "lang" => "test_language", "tran" => "data de criação nova", "changed" => "1"),
				"Post" => array("id" => "16133", "source" => "Post", "lang" => "test_language", "tran" => "Enviar", "changed" => "1"),
				);

		return array(
				array($fileTranslations, $dbTranslations)
				);
	}

	/**
	 * @dataProvider getAllTranslations_dataProvider
	 */
	public function testGetAllTranslations($fileTranslations, $dbTranslations)
	{
		$expectedResult = array(
				'translations' => array(
					"Approved Status" => array("id" => "16131", "source" => "Approved Status", "lang" => "test_language", "tran" => "Aprovado", "changed" => "1"),
					"categorize" => array("source" => "categorize", "tran" => "categorizar"),
					"creation date" => array("id" => "16132", "source" => "creation date", "lang" => "test_language", "tran" => "data de criação nova", "changed" => "1"),
					"Delete comments" => array("source" => "Delete comments", "tran" => "Deletar comentários"),
					"Post" => array("id" => "16133", "source" => "Post", "lang" => "test_language", "tran" => "Enviar", "changed" => "1"),
					"Set prefs" => array("source" => "Set prefs", "tran" => "Definir preferências"),
					),
				'total' => 6,
				);

		$obj = $this->getMock('LanguageTranslations', array('getFileTranslations', '_getDbTranslations'));
		$obj->expects($this->once())->method('getFileTranslations')->will($this->returnValue($fileTranslations));
		$obj->expects($this->once())->method('_getDbTranslations')->will($this->returnValue($dbTranslations));

		$this->assertEquals($expectedResult, $obj->getAllTranslations());
	}

	/**
	 * @dataProvider getAllTranslations_dataProvider
	 */
	public function testGetAllTranslations_filterByMaxRecordsAndOffset($fileTranslations, $dbTranslations)
	{
		$expectedResult = array(
				'translations' => array(
					"Delete comments" => array("source" => "Delete comments", "tran" => "Deletar comentários"),
					"Post" => array("id" => "16133", "source" => "Post", "lang" => "test_language", "tran" => "Enviar", "changed" => "1"),
					),
				'total' => 6,
				);

		$obj = $this->getMock('LanguageTranslations', array('getFileTranslations', '_getDbTranslations'));
		$obj->expects($this->once())->method('getFileTranslations')->will($this->returnValue($fileTranslations));
		$obj->expects($this->once())->method('_getDbTranslations')->will($this->returnValue($dbTranslations));

		$this->assertEquals($expectedResult, $obj->getAllTranslations(2, 3));
	}

	/**
	 * @dataProvider getAllTranslations_dataProvider
	 */
	public function testGetAllTranslations_filterByMaxRecordsOffsetAndSearch($fileTranslations, $dbTranslations)
	{
		$expectedResult = array(
				'translations' => array(
					"Set prefs" => array("source" => "Set prefs", "tran" => "Definir preferências"),
					),
				'total' => 2,
				);

		$obj = $this->getMock('LanguageTranslations', array('getFileTranslations', '_getDbTranslations'));
		$obj->expects($this->once())->method('getFileTranslations')->will($this->returnValue($fileTranslations));
		$obj->expects($this->once())->method('_getDbTranslations')->will($this->returnValue($dbTranslations));

		$this->assertEquals($expectedResult, $obj->getAllTranslations(2, 1, 're'));
	}

	/**
	 * @dataProvider getAllTranslations_dataProvider
	 */
	public function testGetAllTranslations_searchByTranslation($fileTranslations, $dbTranslations)
	{
		$expectedResult = array(
				'translations' => array(
					"Set prefs" => array("source" => "Set prefs", "tran" => "Definir preferências"),
					),
				'total' => 1,
				);

		$obj = $this->getMock('LanguageTranslations', array('getFileTranslations', '_getDbTranslations'));
		$obj->expects($this->once())->method('getFileTranslations')->will($this->returnValue($fileTranslations));
		$obj->expects($this->once())->method('_getDbTranslations')->will($this->returnValue($dbTranslations));

		$this->assertEquals($expectedResult, $obj->getAllTranslations(-1, 0, 'rê'));
	}

	public function testGetFileTranslations()
	{
		copy(dirname(__FILE__) . '/fixtures/custom.php', $this->langDir . '/custom.php');
		$this->assertEquals(27, count($this->obj->getFileTranslations()));
	}

	public function testGetFileTranslations_shouldEscapeSpecialCharacters()
	{
		$trans = $this->obj->getFileTranslations();
		$this->assertArrayHasKey('Second test special "characters" escaping', $trans);
	}

	public function testGetDbTranslations()
	{
		$obj = $this->getMock('LanguageTranslations', array('_diff'), array('test_language'));
		$obj->expects($this->any())->method('_diff');

		$dbTranslations = $obj->getDbTranslations('source_asc', -1, 0);
		$this->assertGreaterThan(0, $dbTranslations['total']);
		$this->assertEquals('Aprovado', $dbTranslations['translations']['Approved Status']['tran']);
	}

	public function testGetDbTranslationsMaxrecordsAndOffset()
	{
		$obj = $this->getMock('LanguageTranslations', array('_diff'), array('test_language'));
		$obj->expects($this->any())->method('_diff');

		$dbTranslations = $obj->getDbTranslations('source_asc', 2, 1);
		$this->assertEquals(2, $dbTranslations['total']);
		$this->assertEquals('Contribuições por autor', $dbTranslations['translations']['Contributions by author']['tran']);
	}

	public function testGetDbTranslationsSearch()
	{
		$obj = $this->getMock('LanguageTranslations', array('_diff'), array('test_language'));
		$obj->expects($this->any())->method('_diff');

		$dbTranslations = $obj->getDbTranslations('source_asc', -1, 0, 'Approved');
		$this->assertEquals(1, $dbTranslations['total']);
		$this->assertEquals('Aprovado', $dbTranslations['translations']['Approved Status']['tran']);
	}

	public function testGetDbUntranslated()
	{
		global $prefs;
		$prefs['record_untranslated'] = 'y';

		$expectedResult = array(
				'translations' => array(
					'Untranslated string 1' => array('source' => 'Untranslated string 1', 'tran' => null),
					'Untranslated string 2' => array('source' => 'Untranslated string 2', 'tran' => null),
					'Untranslated string 3' => array('source' => 'Untranslated string 3', 'tran' => null),
					),
				'total' => 3
				);

		$this->assertEquals($expectedResult, $this->obj->getDbUntranslated());
	}

	public function testGetDbUntranslated_filterByMaxRecordsAndOffset()
	{
		global $prefs;
		$prefs['record_untranslated'] = 'y';

		$expectedResult = array(
				'translations' => array(
					'Untranslated string 3' => array('source' => 'Untranslated string 3', 'tran' => null),
					),
				'total' => 3,
				);

		$this->assertEquals($expectedResult, $this->obj->getDbUntranslated(1, 2));
	}

	public function testGetDbUntranslated_filterBySearch()
	{
		global $prefs;
		$prefs['record_untranslated'] = 'y';

		$expectedResult = array(
				'translations' => array(
					'Untranslated string 3' => array('source' => 'Untranslated string 3', 'tran' => null),
					),
				'total' => 1,
				);

		$this->assertEquals($expectedResult, $this->obj->getDbUntranslated(-1, 0, 'string 3'));
	}

	public function getAllUntranslated_dataProvider()
	{
		$dbUntranslated = array(
				'Untranslated string 1' => array('source' => 'Untranslated string 1', 'tran' => null),
				'Untranslated string 2' => array('source' => 'Untranslated string 2', 'tran' => null),
				"Communication error" => array('source' => "Communication error", 'tran' => null),
				);

		$fileUntranslated = array(
				"Kalture Video" => array('source' => "Kalture Video", 'tran' => null),
				"Communication error" => array('source' => "Communication error", 'tran' => null),
				"Invalid response provided by the Kaltura server. Please retry" => array('source' => "Invalid response provided by the Kaltura server. Please retry", 'tran' => null),
				"Delete comments" => array('source' => "Delete comments", 'tran' => null),
				"Approved Status" => array('source' => "Approved Status", 'tran' => null),
				);

		$dbTranslations = array(
				"Approved Status" => array('source' => "Approved Status", 'tran' => 'Aprovado'),
				);

		return array(
				array($dbUntranslated, $fileUntranslated, $dbTranslations),
				);
	}

	/**
	 * @dataProvider getAllUntranslated_dataProvider
	 */
	public function testGetAllUntranslated($dbUntranslated, $fileUntranslated, $dbTranslations)
	{
		$obj = $this->getMock('LanguageTranslations', array('getFileUntranslated', '_getDbUntranslated', '_getDbTranslations'));
		$obj->expects($this->once())->method('getFileUntranslated')->will($this->returnValue($fileUntranslated));
		$obj->expects($this->once())->method('_getDbUntranslated')->will($this->returnValue($dbUntranslated));
		$obj->expects($this->once())->method('_getDbTranslations')->will($this->returnValue($dbTranslations));

		$expectedResult = array(
				'translations' => array(
					"Communication error" => array('source' => "Communication error", 'tran' => null),
					"Delete comments" => array('source' => "Delete comments", 'tran' => null),
					"Invalid response provided by the Kaltura server. Please retry" => array('source' => "Invalid response provided by the Kaltura server. Please retry", 'tran' => null),
					"Kalture Video" => array('source' => "Kalture Video", 'tran' => null),
					'Untranslated string 1' => array('source' => 'Untranslated string 1', 'tran' => null),
					'Untranslated string 2' => array('source' => 'Untranslated string 2', 'tran' => null),
					),
				'total' => 6
				);

		$this->assertEquals($expectedResult, $obj->getAllUntranslated());
	}
}
