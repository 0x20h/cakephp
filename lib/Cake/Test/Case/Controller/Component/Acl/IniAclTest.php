<?php
/**
 * IniAclTest file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AclComponent', 'Controller/Component');
App::uses('IniAcl', 'Controller/Component/Acl');
App::uses('IniReader', 'Configure');
class_exists('AclComponent');

/**
 * Test case for the IniAcl implementation
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class IniAclTest extends CakeTestCase {

	public $IniAcl;

	public function setUp() {
		$iniFile = CAKE . 'Test' . DS . 'test_app' . DS . 'Config'. DS . 'acl.ini.php';
		$IniReader = new IniReader(dirname($iniFile)); 
		$config = $IniReader->read(basename($iniFile), false);

		$this->IniAcl = new IniAcl();
		$this->IniAcl->build($config);
	}


	public function testRoleInheritance() {
		// peter is an accountant, single role inheritance
		$roles = $this->IniAcl->Aro->roles('User.peter');
		$this->assertTrue(in_array('Role.accounting', $roles[0]));
		$this->assertTrue(in_array('User.peter', $roles[1]));

		$roles = $this->IniAcl->Aro->roles('hardy');
		$this->assertEquals(array('Role.database_manager', 'Role.data_acquirer'), $roles[0]);
		$this->assertEquals(array('Role.data_analyst', 'Role.accounting'), $roles[1]);
		$this->assertTrue(in_array('Role.accounting_manager', $roles[2]));
		$this->assertTrue(in_array('Role.reports', $roles[2]));
		$this->assertTrue(in_array('User.hardy', $roles[3]));
	}


	public function testMapDeterminesAroResolve() {
		$map = $this->IniAcl->Aro->map;
		$this->IniAcl->Aro->map = array(
			'User' => 'FooModel.nickname',
			'Role' => 'FooModel.role',
		);

		$this->assertEquals('User.hardy', $this->IniAcl->Aro->resolve('FooModel.hardy'));
		$this->assertEquals('User.hardy', $this->IniAcl->Aro->resolve('hardy'));
		$this->assertEquals('User.hardy', $this->IniAcl->Aro->resolve(array('FooModel' => array('nickname' => 'hardy'))));
		$this->assertEquals('Role.admin', $this->IniAcl->Aro->resolve(array('FooModel' => array('role' => 'admin'))));
		$this->assertEquals('Role.admin', $this->IniAcl->Aro->resolve('Role.admin'));
		
		$this->assertEquals('User.admin', $this->IniAcl->Aro->resolve('admin'));
		$this->assertEquals('User.admin', $this->IniAcl->Aro->resolve('FooModel.admin'));

		$this->IniAcl->Aro->map = $map;
	}

/**
 * testIniCheck method
 *
 * @return void
 */
	public function testCheck() {
		$this->assertTrue($this->IniAcl->check('Role.admin', 'foo/bar'));
		$this->assertTrue($this->IniAcl->check('jan', 'foo/bar'));
		$this->assertTrue($this->IniAcl->check('Role.admin', 'controllers/bar'));
		$this->assertTrue($this->IniAcl->check(array('User' => array('username' =>'jan')), 'controlers/bar/bll'));

		$this->assertTrue($this->IniAcl->check('Role.database_manager', 'controllers/db/create'));
		$this->assertTrue($this->IniAcl->check('User.db_manager_2', 'controllers/db/create'));

		$this->assertTrue($this->IniAcl->check('Role.database_manager', 'controllers/db/select'));
		$this->assertTrue($this->IniAcl->check('User.db_manager_2', 'controllers/db/select'));

		$this->assertTrue($this->IniAcl->check('Role.database_manager', 'controllers/db/drop'));
		$this->assertTrue($this->IniAcl->check('User.db_manager_1', 'controllers/db/drop'));
		$this->assertFalse($this->IniAcl->check('User.db_manager_2', 'controllers/db/drop'));

		$this->assertFalse($this->IniAcl->check('Role.database_manager', 'controllers/invoices/edit'));
		$this->assertFalse($this->IniAcl->check('User.db_manager_1', 'controllers/invoices/edit'));
		$this->assertTrue($this->IniAcl->check('User.db_manager_2', 'controllers/invoices/edit'));
	}


	public function testAllow() {
		$this->assertFalse($this->IniAcl->check('jeff', 'foo/bar'));
		$this->assertTrue($this->IniAcl->allow('jeff', 'foo/bar'));
		$this->assertTrue($this->IniAcl->check('jeff', 'foo/bar'));
		$this->assertFalse($this->IniAcl->check('peter', 'foo/bar'));
		$this->assertFalse($this->IniAcl->check('peter', 'foo/bar'));
		$this->assertFalse($this->IniAcl->check('hardy', 'foo/bar'));

		$this->assertTrue($this->IniAcl->allow('Role.accounting', 'foo/bar'));
		$this->assertTrue($this->IniAcl->check('peter', 'foo/bar'));
		$this->assertTrue($this->IniAcl->check('hardy', 'foo/bar'));
	}
}

