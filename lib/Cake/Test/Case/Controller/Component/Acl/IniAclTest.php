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
class_exists('AclComponent');

/**
 * Test case for the IniAcl implementation
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class IniAclTest extends CakeTestCase {

	public function setUp() {
		Configure::write('Acl.classname', 'IniAcl');
		$Collection = new ComponentCollection();
		$this->IniAcl = new IniAcl();
		$this->Acl = new AclComponent($Collection, array(
			'ini_acl' => array(
				'config' => CAKE . 'Test' . DS . 'test_app' . DS . 'Config'. DS . 'acl.ini.php',
			),
		));
		$this->Acl->adapter($this->IniAcl);
	}


	public function testRoleInheritance() {
		// peter is an accountant, single role inheritance
		$roles = $this->Acl->Aro->roles('User.peter');
		$this->assertTrue(in_array('Role.accounting', $roles[0]));
		$this->assertTrue(in_array('User.peter', $roles[1]));

		$roles = $this->Acl->Aro->roles('hardy');
		$this->assertEquals(array('Role.database_manager', 'Role.data_acquirer'), $roles[0]);
		$this->assertEquals(array('Role.accounting', 'Role.data_analyst'), $roles[1]);
		$this->assertTrue(in_array('Role.accounting_manager', $roles[2]));
		$this->assertTrue(in_array('Role.reports', $roles[2]));
		$this->assertTrue(in_array('User.hardy', $roles[3]));
	}

	public function testAddRole() {
		$this->assertEquals(array(array(IniAro::DEFAULT_ROLE)), $this->Acl->Aro->roles('foobar'));
		$this->Acl->Aro->addRole(array('User.foobar' => 'Role.accounting'));
		$this->assertEquals(array(array('Role.accounting'), array('User.foobar')), $this->Acl->Aro->roles('foobar'));
	}

	public function testAroResolve() {
		$map = $this->Acl->Aro->map;
		$this->Acl->Aro->map = array(
			'User' => 'FooModel.nickname',
			'Role' => 'FooModel.role',
		);

		$this->assertEquals('User.hardy', $this->Acl->Aro->resolve('FooModel.hardy'));
		$this->assertEquals('User.hardy', $this->Acl->Aro->resolve('hardy'));
		$this->assertEquals('User.hardy', $this->Acl->Aro->resolve(array('FooModel' => array('nickname' => 'hardy'))));
		$this->assertEquals('Role.admin', $this->Acl->Aro->resolve(array('FooModel' => array('role' => 'admin'))));
		$this->assertEquals('Role.admin', $this->Acl->Aro->resolve('Role.admin'));
		
		$this->assertEquals('Role.admin', $this->Acl->Aro->resolve('admin'));
		$this->assertEquals('Role.admin', $this->Acl->Aro->resolve('FooModel.admin'));
		$this->assertEquals('Role.accounting', $this->Acl->Aro->resolve('accounting'));

		$this->assertEquals(IniAro::DEFAULT_ROLE, $this->Acl->Aro->resolve('bla'));
		$this->assertEquals(IniAro::DEFAULT_ROLE, $this->Acl->Aro->resolve(array('FooModel' => array('role' => 'hardy'))));
		$this->Acl->Aro->map = $map;
	}

/**
 * testIniCheck method
 *
 * @return void
 */
	public function testCheck() {
		$this->assertTrue($this->Acl->check('Role.admin', 'foo/bar'));
		$this->assertTrue($this->Acl->check('role/admin', 'foo/bar'));
		$this->assertTrue($this->Acl->check('jan', 'foo/bar'));
		$this->assertTrue($this->Acl->check('user/jan', 'foo/bar'));

		$this->assertTrue($this->Acl->check('Role.admin', 'controllers/bar'));
		$this->assertTrue($this->Acl->check(array('User' => array('username' =>'jan')), 'controlers/bar/bll'));

		$this->assertTrue($this->Acl->check('Role.database_manager', 'controllers/db/create'));
		$this->assertTrue($this->Acl->check('User.db_manager_2', 'controllers/db/create'));
		// inheritance: hardy -> reports -> data_analyst -> database_manager
		$this->assertTrue($this->Acl->check('User.hardy', 'controllers/db/create'));
		$this->assertFalse($this->Acl->check('User.jeff', 'controllers/db/create'));

		$this->assertTrue($this->Acl->check('Role.database_manager', 'controllers/db/select'));
		$this->assertFalse($this->Acl->check('User.jeff', 'controllers/db/select'));
		$this->assertTrue($this->Acl->check('User.db_manager_2', 'controllers/db/select'));

		$this->assertTrue($this->Acl->check('Role.database_manager', 'controllers/db/drop'));
		$this->assertTrue($this->Acl->check('User.db_manager_1', 'controllers/db/drop'));
		$this->assertFalse($this->Acl->check('User.db_manager_2', 'controllers/db/drop'));

		$this->assertFalse($this->Acl->check('Role.database_manager', 'controllers/invoices/edit'));
		$this->assertFalse($this->Acl->check('User.db_manager_1', 'controllers/invoices/edit'));
		$this->assertTrue($this->Acl->check('User.db_manager_2', 'controllers/invoices/edit'));

		// Role.manager is allowed controllers.*.*_manager
		$this->assertTrue($this->Acl->check('User.stan', 'controllers/invoices/manager_edit'));
		$this->assertTrue($this->Acl->check('Role.manager', 'controllers/baz/manager_foo'));
		$this->assertFalse($this->Acl->check('User.stan', 'custom/foo/manager_edit'));
		$this->assertFalse($this->Acl->check('User.stan', 'bar/baz/manager_foo'));
		$this->assertFalse($this->Acl->check('Role.accounting', 'bar/baz/manager_foo'));
		$this->assertFalse($this->Acl->check('Role.accounting', 'controllers/baz/manager_foo'));
	}


	public function testCheckIsCaseInsensitive() {
		$this->assertTrue($this->Acl->check('hardy', 'controllers/forms/new'));
		$this->assertTrue($this->Acl->check('Role.data_acquirer', 'controllers/forms/new'));
		$this->assertTrue($this->Acl->check('hardy', 'controllers/FORMS/NEW'));
		$this->assertTrue($this->Acl->check('Role.data_acquirer', 'controllers/FORMS/NEW'));
	}


	public function testAllow() {
		$this->assertFalse($this->Acl->check('jeff', 'foo/bar'));

		$this->Acl->allow('jeff', 'foo/bar');

		$this->assertTrue($this->Acl->check('jeff', 'foo/bar'));
		$this->assertFalse($this->Acl->check('peter', 'foo/bar'));
		$this->assertFalse($this->Acl->check('peter', 'foo/bar'));
		$this->assertFalse($this->Acl->check('hardy', 'foo/bar'));

		$this->Acl->allow('Role.accounting', 'foo/bar');

		$this->assertTrue($this->Acl->check('peter', 'foo/bar'));
		$this->assertTrue($this->Acl->check('hardy', 'foo/bar'));

		$this->assertFalse($this->Acl->check('Role.reports', 'foo/bar'));
	}


	public function testDeny() {
		$this->assertTrue($this->Acl->check('stan', 'controllers/baz/manager_foo'));

		$this->Acl->deny('stan', 'controllers/baz/manager_foo');

		$this->assertFalse($this->Acl->check('stan', 'controllers/baz/manager_foo'));
		$this->assertTrue($this->Acl->check('Role.manager', 'controllers/baz/manager_foo'));
		$this->assertTrue($this->Acl->check('stan', 'controllers/baz/manager_bar'));
		$this->assertTrue($this->Acl->check('stan', 'controllers/baz/manager_foooooo'));
	}


	public function testDenyRuleIsStrongerThanAllowRule() {	
		$this->assertFalse($this->Acl->check('peter', 'baz/bam'));
		$this->Acl->allow('peter', 'baz/bam');
		$this->assertTrue($this->Acl->check('peter', 'baz/bam'));
		$this->Acl->deny('peter', 'baz/bam');
		$this->assertFalse($this->Acl->check('peter', 'baz/bam'));

		$this->assertTrue($this->Acl->check('stan', 'controllers/reports/foo'));
		// stan is denied as he's sales and sales is denied controllers.*.delete
		$this->assertFalse($this->Acl->check('stan', 'controllers/reports/delete'));
		$this->Acl->allow('stan', 'controllers/reports/delete');
		$this->assertFalse($this->Acl->check('Role.sales', 'controllers/reports/delete'));
		$this->assertTrue($this->Acl->check('stan', 'controllers/reports/delete'));
		$this->Acl->deny('stan', 'controllers/reports/delete');
		$this->assertFalse($this->Acl->check('stan', 'controllers/reports/delete'));

		// there is already an equally specific deny rule that will win
		$this->Acl->allow('stan', 'controllers/reports/delete');
		$this->assertFalse($this->Acl->check('stan', 'controllers/reports/delete'));
	}

	
	public function testInvalidConfigWithAroMissing() {
		$this->setExpectedException(
			'AclException',
			'"aro" section not found in configuration'
		);
		$config = array('aco.allow' => array('foo' => ''));
		$this->IniAcl->build($config);
	}

	
	public function testInvalidConfigWithAcosMissing() {
		$this->setExpectedException(
			'AclException',
			'Neither a "aco.allow" nor a "aco.deny" section was found in configuration.'
		);

		$config = array(
			'aro' => array('Role.foo' => null),
		);

		$this->IniAcl->build($config);
	}

	public function testAcoResolve() {
		$this->assertEquals(array('foo', 'bar'), $this->Acl->Aco->resolve('foo/bar'));
		$this->assertEquals(array('foo', 'bar'), $this->Acl->Aco->resolve('foo.bar'));
		$this->assertEquals(array('foo', 'bar', 'baz'), $this->Acl->Aco->resolve('foo/bar/baz'));
		$this->assertEquals(array('foo', '*-bar', '?-baz'), $this->Acl->Aco->resolve('foo.*-bar.?-baz'));
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

		$this->expectError('PHPUnit_Framework_Error', 'cycle detected' /* ... */);
		$this->IniAcl->build($config);
	}


/**
 * test that with policy allow, only denies count
 */
	public function testPolicy() {
		// allow by default
		$this->Acl->settings['ini_acl']['policy'] = IniAcl::ALLOW;
		$this->IniAcl->initialize($this->Acl);

		$this->assertTrue($this->Acl->check('Role.sales', 'foo'));
		$this->assertTrue($this->Acl->check('Role.sales', 'controllers/bla/create'));
		$this->assertTrue($this->Acl->check('Role.default', 'foo'));
		// undefined user, undefined aco
		$this->assertTrue($this->Acl->check('foobart', 'foo/bar'));

		// deny rule: Role.sales -> controllers.*.delete
		$this->assertFalse($this->Acl->check('Role.sales', 'controllers/bar/delete'));
		$this->assertFalse($this->Acl->check('Role.sales', 'controllers/bar', 'delete'));
	}


/**
 * acl data taken from the docs, declared in test_app/Config/acl.php
 *
 */
	public function testPhpConfig() {
		$this->Acl->settings['ini_acl']['config'] = CAKE . 'Test' . DS . 'test_app' . DS . 'Config'. DS . 'acl.php';
		$this->Acl->settings['ini_acl']['reader'] = 'php';
		$this->IniAcl->initialize($this->Acl);
		$this->assertTrue($this->IniAcl->check('Legolas', 'weapons'));

		$this->assertFalse($this->IniAcl->check('Hobbit', 'ring'));
		$this->assertFalse($this->IniAcl->check('Gandalf', 'ring'));
		$this->assertFalse($this->IniAcl->check('Gollum', 'ring'));
		$this->assertTrue($this->IniAcl->check('Frodo', 'ring'));
		
		// see docs for DbAcl. 
		// Using iniAcl you can write like that
		$this->IniAcl->deny('Legolas', 'weapons/delete');
		$this->IniAcl->deny('Gimli', 'weapons/delete');

		// these are equivalent
		$this->assertTrue($this->Acl->check('Warriors', 'weapons', 'delete'));
		$this->assertTrue($this->Acl->check('Role.Warriors', 'weapons/delete'));

		$this->assertTrue($this->Acl->check('Aragorn', 'weapons/delete'));
		$this->assertTrue($this->Acl->check('Aragorn', 'weapons', 'delete'));

		$this->assertFalse($this->Acl->check('Gimli', 'weapons/delete'));
		$this->assertFalse($this->Acl->check('Gimli', 'weapons', 'delete'));
		$this->assertFalse($this->Acl->check('Legolas', 'weapons/delete'));
		$this->assertFalse($this->Acl->check('Legolas', 'weapons', 'delete'));
	}
}
