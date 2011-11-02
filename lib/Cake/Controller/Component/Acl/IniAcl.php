<?php

/**
 * IniAcl implements an access control system using an INI file.  An example
 * of the ini file used can be found in /config/acl.ini.php.
 *
 * @package       Cake.Controller.Component
 */
class IniAcl extends Object implements AclInterface {

/**
 * Array with configuration, parsed from ini file
 *
 * @var array
 */
	public $config = null;

	public $Aro = null;

	public $Aco = null;

/**
 * Initialize method
 *
 * @param AclBase $component
 * @return void
 */
	public function initialize($component) {
		App::uses('IniReader', 'Configure');
		$iniFile = new IniReader(APP . 'Config' . DS);
		$this->config = $iniFile->read(basename('acl.ini.php'));
		// read config file, set up aro/aco objects
		$this->Aro = new IniAro($this->config);
		$this->Aco = new IniAco($this->config);
	}

/**
 * No op method, allow cannot be done with IniAcl
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function allow($aro, $aco, $action = "*") {
	}

/**
 * No op method, deny cannot be done with IniAcl
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function deny($aro, $aco, $action = "*") {

	}

/**
 * No op method, inherit cannot be done with IniAcl
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function inherit($aro, $aco, $action = "*") {

	}

/**
 * Main ACL check function. Checks to see if the ARO (access request object) has access to the
 * ACO (access control object).Looks at the acl.ini.php file for permissions
 * (see instructions in /config/acl.ini.php).
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $aco_action Action
 * @return boolean Success
 */
	public function check($aro, $aco, $aco_action = null) {
		if ($this->config == null) {
			$this->config = $this->readConfigFile(APP . 'Config' . DS . 'acl.ini');
		}
		$aclConfig = $this->config;
		debug($aclConfig);
		// check all aco's to match 
		// on each match:
		// 	check allows. if any allow matches aro -> allow
		// 	* matches all aros 

		if (is_array($aro)) {
			$aro = Set::classicExtract($aro, $this->userPath);
		}

		if (isset($aclConfig[$aro]['deny'])) {
			$userDenies = $this->arrayTrim(explode(",", $aclConfig[$aro]['deny']));

			if (array_search($aco, $userDenies)) {
				return false;
			}
		}

		if (isset($aclConfig[$aro]['allow'])) {
			$userAllows = $this->arrayTrim(explode(",", $aclConfig[$aro]['allow']));

			if (array_search($aco, $userAllows)) {
				return true;
			}
		}

		if (isset($aclConfig[$aro]['groups'])) {
			$userGroups = $this->arrayTrim(explode(",", $aclConfig[$aro]['groups']));

			foreach ($userGroups as $group) {
				if (array_key_exists($group, $aclConfig)) {
					if (isset($aclConfig[$group]['deny'])) {
						$groupDenies = $this->arrayTrim(explode(",", $aclConfig[$group]['deny']));

						if (array_search($aco, $groupDenies)) {
							return false;
						}
					}

					if (isset($aclConfig[$group]['allow'])) {
						$groupAllows = $this->arrayTrim(explode(",", $aclConfig[$group]['allow']));

						if (array_search($aco, $groupAllows)) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}

/**
 * Parses an INI file and returns an array that reflects the INI file's section structure. Double-quote friendly.
 *
 * @param string $filename File
 * @return array INI section structure
 */
	public function readConfigFile($filename) {
	}

/**

 * Removes trailing spaces on all array elements (to prepare for searching)
 *
 * @param array $array Array to trim
 * @return array Trimmed array
 */
	public function arrayTrim($array) {
		foreach ($array as $key => $value) {
			$array[$key] = trim($value);
		}
		array_unshift($array, "");
		return $array;
	}

	public function tree($class, $identifier = '') {
		return array();
	}
}

class IniAco {
	public function __construct(array $config = array()) {
	}

	public function node($aco) {

	}
}

class IniAro {
	public function __construct(array $config = array()) {
		if (!empty($config['aro']) {

		}
	}

	public function node($aro) {

	}
}
