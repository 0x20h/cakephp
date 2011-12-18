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
		$this->assertEquals(array('Role.accounting', 'Role.data_analyst'), $roles[1]);
		$this->assertTrue(in_array('Role.accounting_manager', $roles[2]));
		$this->assertTrue(in_array('Role.reports', $roles[2]));
		$this->assertTrue(in_array('User.hardy', $roles[3]));
	}

	public function testAddRole() {
		$this->assertEquals(array(array(IniAro::DEFAULT_ROLE)), $this->IniAcl->Aro->roles('foobar'));
		$this->IniAcl->Aro->addRole(array('User.foobar' => 'Role.accounting'));
		$this->assertEquals(array(array('Role.accounting'), array('User.foobar')), $this->IniAcl->Aro->roles('foobar'));
	}

	public function testAroResolve() {
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
		
		$this->assertEquals('Role.admin', $this->IniAcl->Aro->resolve('admin'));
		$this->assertEquals('Role.admin', $this->IniAcl->Aro->resolve('FooModel.admin'));
		$this->assertEquals('Role.accounting', $this->IniAcl->Aro->resolve('accounting'));

		$this->assertEquals(IniAro::DEFAULT_ROLE, $this->IniAcl->Aro->resolve('bla'));
		$this->assertEquals(IniAro::DEFAULT_ROLE, $this->IniAcl->Aro->resolve(array('FooModel' => array('role' => 'hardy'))));
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
		// inheritance: hardy -> reports -> data_analyst -> database_manager
		$this->assertTrue($this->IniAcl->check('User.hardy', 'controllers/db/create'));
		$this->assertFalse($this->IniAcl->check('User.jeff', 'controllers/db/create'));

		$this->assertTrue($this->IniAcl->check('Role.database_manager', 'controllers/db/select'));
		$this->assertFalse($this->IniAcl->check('User.jeff', 'controllers/db/select'));
		$this->assertTrue($this->IniAcl->check('User.db_manager_2', 'controllers/db/select'));

		$this->assertTrue($this->IniAcl->check('Role.database_manager', 'controllers/db/drop'));
		$this->assertTrue($this->IniAcl->check('User.db_manager_1', 'controllers/db/drop'));
		$this->assertFalse($this->IniAcl->check('User.db_manager_2', 'controllers/db/drop'));

		$this->assertFalse($this->IniAcl->check('Role.database_manager', 'controllers/invoices/edit'));
		$this->assertFalse($this->IniAcl->check('User.db_manager_1', 'controllers/invoices/edit'));
		$this->assertTrue($this->IniAcl->check('User.db_manager_2', 'controllers/invoices/edit'));

		// Role.manager is allowed controllers.*.*_manager
		$this->assertTrue($this->IniAcl->check('User.stan', 'controllers/invoices/manager_edit'));
		$this->assertTrue($this->IniAcl->check('Role.manager', 'controllers/baz/manager_foo'));
		$this->assertFalse($this->IniAcl->check('User.stan', 'custom/foo/manager_edit'));
		$this->assertFalse($this->IniAcl->check('User.stan', 'bar/baz/manager_foo'));
		$this->assertFalse($this->IniAcl->check('Role.accounting', 'bar/baz/manager_foo'));
		$this->assertFalse($this->IniAcl->check('Role.accounting', 'controllers/baz/manager_foo'));
	}


	public function testCheckCaseInsensitive() {
		$this->assertTrue($this->IniAcl->check('hardy', 'controllers/forms/new'));
		$this->assertTrue($this->IniAcl->check('Role.data_acquirer', 'controllers/forms/new'));
		$this->assertTrue($this->IniAcl->check('hardy', 'controllers/FORMS/NEW'));
		$this->assertTrue($this->IniAcl->check('Role.data_acquirer', 'controllers/FORMS/NEW'));
	}


	public function testAllow() {
		$this->assertFalse($this->IniAcl->check('jeff', 'foo/bar'));

		$this->IniAcl->allow('jeff', 'foo/bar');

		$this->assertTrue($this->IniAcl->check('jeff', 'foo/bar'));
		$this->assertFalse($this->IniAcl->check('peter', 'foo/bar'));
		$this->assertFalse($this->IniAcl->check('peter', 'foo/bar'));
		$this->assertFalse($this->IniAcl->check('hardy', 'foo/bar'));

		$this->IniAcl->allow('Role.accounting', 'foo/bar');

		$this->assertTrue($this->IniAcl->check('peter', 'foo/bar'));
		$this->assertTrue($this->IniAcl->check('hardy', 'foo/bar'));

		$this->assertFalse($this->IniAcl->check('Role.reports', 'foo/bar'));
	}


	public function testDeny() {
		$this->assertTrue($this->IniAcl->check('stan', 'controllers/baz/manager_foo'));

		$this->IniAcl->deny('stan', 'controllers/baz/manager_foo');

		$this->assertFalse($this->IniAcl->check('stan', 'controllers/baz/manager_foo'));
		$this->assertTrue($this->IniAcl->check('Role.manager', 'controllers/baz/manager_foo'));
		$this->assertTrue($this->IniAcl->check('stan', 'controllers/baz/manager_bar'));
		$this->assertTrue($this->IniAcl->check('stan', 'controllers/baz/manager_foooooo'));
	}


	public function testDenyRuleIsStrongerThanAllowRule() {	
		$this->assertFalse($this->IniAcl->check('peter', 'baz/bam'));
		$this->IniAcl->allow('peter', 'baz/bam');
		$this->assertTrue($this->IniAcl->check('peter', 'baz/bam'));
		$this->IniAcl->deny('peter', 'baz/bam');
		$this->assertFalse($this->IniAcl->check('peter', 'baz/bam'));

		$this->assertTrue($this->IniAcl->check('stan', 'controllers/reports/foo'));
		// stan is denied as he's sales and sales is denied controllers.*.delete
		$this->assertFalse($this->IniAcl->check('stan', 'controllers/reports/delete'));
		$this->IniAcl->allow('stan', 'controllers/reports/delete');
		$this->assertFalse($this->IniAcl->check('Role.sales', 'controllers/reports/delete'));
		$this->assertTrue($this->IniAcl->check('stan', 'controllers/reports/delete'));
		$this->IniAcl->deny('stan', 'controllers/reports/delete');
		$this->assertFalse($this->IniAcl->check('stan', 'controllers/reports/delete'));

		// there is already an equally specific deny rule that will win
		$this->IniAcl->allow('stan', 'controllers/reports/delete');
		$this->assertFalse($this->IniAcl->check('stan', 'controllers/reports/delete'));
	}

	
	public function testInvalidConfigWithAroMissing() {
		$this->setExpectedException(
			'IniAclException',
			'"aro" section not found in configuration'
		);
		$config = array('aco.allow' => array('foo' => ''));
		$this->IniAcl->build($config);

	}

	
	public function testInvalidConfigWithAcosMissing() {
		$this->setExpectedException(
			'IniAclException',
			'Neither a "aco.allow" nor a "aco.deny" section was found in configuration.'
		);

		$config = array(
			'aro' => array('Role.foo' => null),
		);

		$this->IniAcl->build($config);
	}

	public function testAcoResolve() {
		$this->assertEquals(array('foo', 'bar'), $this->IniAcl->Aco->resolve('foo/bar'));
		$this->assertEquals(array('foo', 'bar'), $this->IniAcl->Aco->resolve('foo.bar'));
		$this->assertEquals(array('foo', 'bar', 'baz'), $this->IniAcl->Aco->resolve('foo/bar/baz'));
		$this->assertEquals(array('foo', '*-bar', '?-baz'), $this->IniAcl->Aco->resolve('foo.*-bar.?-baz'));
	}

	public function testAroDeclarationContainsCycles() {
		$config = array(
			'aro' => array(
				'Role.a' => null,
				'Role.b' => 'User.b',
				'User.a' => 'Role.a, Role.b',
				'User.b' => 'User.a',

			),
			'aco.allow' => array(
				'*' => 'Role.a',
			),
		);

		$this->expectException('IniAroException', 'cycle detected when inheriting User.b from User.a');
		$this->IniAcl->build($config);
	}

	public function testPolicy() {}
}

