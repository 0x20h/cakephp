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

/**
 * Aro Object
 *
 * @var IniAro
 */
	public $Aro = null;

/**
 * Aco Object
 * 
 * @var IniAco
 */
	public $Aco = null;

/**
 * Initialize method
 *
 * @param AclBase $component
 * @return void
 */
	public function initialize($component) {
		App::uses('IniReader', 'Configure');
		$this->config = $this->readConfigFile(APP . 'Config' . DS . 'acl.ini.php');
		$this->Aro = new IniAro($this->config['aro']);
		$this->Aco = new IniAco($this->config['aco.allow'], $this->config['aco.deny']);
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
		$this->Aco->allow($aro, $aco, $action);
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
		$this->Aco->deny($aro, $aco, $action);
	}

/**
 * No op method, inherit cannot be done with IniAc
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function inherit($aro, $aco, $action = "*") {
		$this->Aco->inherit($aro, $aco, $action);
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
		$defaultPolicy = 'deny';
		$allow = 0;
		$prioritizedAros = $this->Aro->roles($aro);
		$path = $this->Aco->path($aco);	
		
		if (empty($path)) {
			return $allow;
		}

		foreach ($path as $depth => $node) {
			foreach ($prioritizedAros as $aros) {
				if (!empty($node['allow'])) {			
					$allow = $allow || count(array_intersect($node['allow'], $aros)) > 0;
				}

				if (!empty($node['deny'])) {
					$allow = $allow && count(array_intersect($node['deny'], $aros)) == 0;
				}
			}
		}

		return $allow;
	}

/**
 * Parses an INI file and returns an array that reflects the INI file's section structure. Double-quote friendly.
 *
 * @param string $filename File
 * @return array INI section structure
 */
	public function readConfigFile($filename) {
		$sections = parse_ini_file($filename, true);
		return $sections;
	}

	public function tree($class, $identifier = '') {
		return array();
	}


}

/**
 * Access Control Object
 *
 */
class IniAco {
	public function __construct(array $allow = array(), array $deny = array()) {
		$this->tree = $this->buildTree($allow, $deny);
	}

	public function node($aco) {
		debug(__METHOD);
		debug($aco);
		exit;
	}

/**
 * return path to aco as array
 *
 * @return array
 */
	public function path($aco) {
		$aco = $this->resolve($aco);
		$path = array();
		$tree = &$this->tree;

		foreach ($aco as $node) {
			if (empty($tree[$node])) {
				break;
			}

			$element = array();

			if (!empty($tree[$node]['allow'])) {
				$element['allow'] = $tree[$node]['allow'];
			}

			if (!empty($tree[$node]['deny'])) {
				$element['deny'] = $tree[$node]['deny'];
			}

			$path[] = $element;

			if (empty($tree[$node]['children'])) {
				break;
			}

			$tree = &$tree[$node]['children'];
		}

		return $path;
	}

/**
 * return path from ACO string
 *
 * @return array path
 */
	public function resolve($aco) {
		return array_map('trim', explode('/', $aco));
	}

	public function buildTree(array $allow, array $deny = array(), array $tree = array()) {
		$stack = array();
		$root = &$tree;

		foreach ($allow as $dotPath => $commaSeparatedAros) {
			$path = array_map('trim', explode('.', $dotPath));
			$aros = array_map('trim', explode(',', $commaSeparatedAros));
			$depth = count($path);

			foreach ($path as $i => $node) {
				if (!isset($tree[$node]['children'])) {
					$tree[$node] = array(
						'children' => array(),
					);
				}

				// keep reference to leaf node
				if ($i + 1 < $depth) {
					$tree = &$tree[$node]['children'];
				}
			}

			$tree[$node]['allow'] = $aros;

			if (!empty($deny[$dotPath])) {
				$tree[$node]['deny'] = array_map('trim', explode(',', $deny[$dotPath]));
			}

			$tree = &$root;
		}

		return $tree;
	}
}

/**
 * Access Request Object
 *
 */
class IniAro {
	public function __construct(array $aro = array()) {
		$this->tree = $this->buildTree($aro);
	}

	public function node($aro) {
	}

	public function roles($aro) {
		$aros = array();
		$aro = $this->resolve($aro);
		
		foreach ($this->tree as $node => $children) {
			if (in_array($aro, $children)) {
				$aros[0][] = $node;
			}
		}

		$aros[1][] = $aro;
		return $aros;
	}


/**
 * return path from ARO
 *
 * @return string dot separated aro string (e.g. User.jeff)
 */
	public function resolve(array $aro) {
		return $aro['model'].'.'.$aro['foreign_key'];
	}

	public function buildTree(array $sections, $prefix = '') {
		$tree = array();
		$root = &$tree;

		foreach ($sections as $node => $commaSeparatedDeps) {
			if ($commaSeparatedDeps) {
				$deps = array_map('trim', explode(',', $commaSeparatedDeps));
				
				foreach ($deps as $dependency) {
					// 1. find or insert dep
					// 2. add node as child
					$node = $this->node($dependency, $root);
					if (!$node) {
					}

					if (!isset($tree[$dependency])) {
						$tree[$dependency] = array();
					}
					
					$tree[$dependency][] = $node;
				}
			}

			
		}

		return $tree;
	}
}
