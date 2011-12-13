<?php
/**
 * IniReaderTest
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Configure
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('IniReader', 'Configure');

class IniReaderTest extends CakeTestCase {

/**
 * The test file that will be read.
 *
 * @var string
 */
	public $file;

/**
 * setup
 *
 * @return void
 */
	public function setup() {
		parent::setup();
		$this->path = CAKE . 'Test' . DS . 'test_app' . DS . 'Config'. DS;
	}

/**
 * test constrction
 *
 * @return void
 */
	public function testConstruct() {
		$reader = new IniReader($this->path);
		$config = $reader->read('acl.ini.php');

		$this->assertTrue(isset($config['aro']));
		$this->assertTrue(isset($config['aro']['Role']['manager']));
		$this->assertEquals('Role.admin', $config['aco.allow']['*']);
	}

/**
 * no other sections should exist.
 *
 * @return void
 */
	public function testReadingOnlyOneSection() {
		$reader = new IniReader($this->path, 'aco.allow');
		$config = $reader->read('acl.ini.php');

		$this->assertTrue(isset($config['controllers']['*']['manager_*']));
		$this->assertEquals('Role.manager', $config['controllers']['*']['manager_*']);
	}

/**
 * test without section
 *
 * @return void
 */
	public function testReadingWithoutSection() {
		$reader = new IniReader($this->path);
		$config = $reader->read('no_section.ini');

		$expected = array(
			'some_key' => 'some_value',
			'bool_key' => true
		);
		$this->assertEquals($config, $expected);
	}

/**
 * test that names with .'s get exploded into arrays.
 *
 * @return void
 */
	public function testReadingValuesWithDots() {
		$reader = new IniReader($this->path);
		$config = $reader->read('nested.ini');

		$this->assertTrue(isset($config['database']['db']['username']));
		$this->assertEquals('mark', $config['database']['db']['username']);
		$this->assertEquals(3, $config['nesting']['one']['two']['three']);
	}

/**
 * test boolean reading
 *
 * @return void
 */
	public function testBooleanReading() {
		$reader = new IniReader($this->path);
		$config = $reader->read('nested.ini');

		$this->assertTrue($config['bools']['test_on']);
		$this->assertFalse($config['bools']['test_off']);

		$this->assertTrue($config['bools']['test_yes']);
		$this->assertFalse($config['bools']['test_no']);

		$this->assertTrue($config['bools']['test_true']);
		$this->assertFalse($config['bools']['test_false']);

		$this->assertFalse($config['bools']['test_null']);
	}

/**
 * test read file without extension
 *
 * @return void
 */
	public function testReadingWithoutExtension() {
		$reader = new IniReader($this->path);
		$config = $reader->read('nested');
		$this->assertTrue($config['bools']['test_on']);
	}
}
