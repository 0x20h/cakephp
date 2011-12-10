<?php

/**
 * IniAcl implements an access control system using an INI file.  An example
 * of the ini file used can be found in /config/acl.ini.php.
 *
 * @package       Cake.Controller.Component
 */
class IniAcl extends Object implements AclInterface {

	const DENY = 0;
	const ALLOW = 1;

/**
 * Options array 
 *
 * @var array
 */
	public $options = array();

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
		$this->options = array(
			'policy' => self::DENY,
			'config' => APP . 'Config' . DS . 'acl.ini.php',
		);

		$config = $this->readConfigFile($this->options['config']);
		$this->Aro = new IniAro($config['aro'], $config['map']);
		$this->Aco = new IniAco($config['aco.allow'], $config['aco.deny'], $config['map']);
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
		return $this->Aco->allow($aro, $aco, $action);
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
		return $this->Aco->deny($aro, $aco, $action);
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
		$allow = $this->options['policy'];
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
		debug($class);
		debug($identifier);
		return $this->$class->node($identifier);
	}
}

/**
 * Access Control Object
 *
 */
class IniAco {

	public function __construct(array $allow = array(), array $deny = array()) {
		$this->tree = $this->build($allow, $deny);
	}

	public function node($aco) {
		$aco = $this->resolve($aco);
		$tree = &$this->tree;
		$depth = count($aco);
		
		foreach ($aco as $i => $node) {
			if (!isset($tree[$node])) {
				return false;
			}
			if ($i < $depth - 1) {
				$tree = &$tree[$node]['children'];
			}
		}

		return $tree;
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


	public function allow($aro, $aco, $action) {
		$aco = $this->resolve($aco);
		$tree = &$this->tree;
		$depth = count($aco);
		
		foreach ($aco as $i => $node) {
			if (!isset($tree[$node])) {
				$tree[$node]  = array(
					'children' => array(),
				);
			}

			if ($i < $depth - 1) {
				$tree = &$tree[$node]['children'];
			} else {
				$tree[$node]['allow'][] = $aro;
			}
		}

		return true;
	}

/**
 * return path from ACO string
 *
 * @return array path
 */
	public function resolve($aco) {
		return array_map('trim', explode('/', $aco));
	}

	public function build(array $allow, array $deny = array(), array $tree = array()) {
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
	public $map = array(
		'User' => 'User.username'
	);

	public function __construct(array $aro = array(), array $map = array()) {
		!empty($map) && $this->map = $map;
		$this->tree = $this->build($aro);
	}

	
	public function roles($aro) {
		$aros = array();
		$aro = $this->resolve($aro);
		$stack = array(array($aro, 0));
		$path = array();

		while (!empty($stack)) {
			list($element, $depth) = array_pop($stack);
			$aros[$depth][] = $element;

			foreach ($this->tree as $node => $children) {
				if (in_array($element, $children)) {
					array_push($stack, array($node, $depth + 1));
				}
			}
		}

		return array_reverse($aros);
	}


/**
 * return path from ARO
 *
 * @return string dot separated aro string (e.g. User.jeff)
 */
	public function resolve(array $aro) {
		foreach ($this->map as $aroGroup => $map) {
			list ($model, $field) = explode('.', $map);
			
			if (isset($aro[$model][$field])) {
				return $model . '.' . $aro[$model][$field];
			}
		}

		trigger_error('no map entry found in aro:'.print_r($aro, true), E_USER_WARNING);
	}


	public function build(array $sections, $prefix = '') {
		$tree = array();
		$root = &$tree;

		foreach ($sections as $node => $commaSeparatedDeps) {
			if ($commaSeparatedDeps) {
				$deps = array_map('trim', explode(',', $commaSeparatedDeps));
				
				foreach ($deps as $dependency) {
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
