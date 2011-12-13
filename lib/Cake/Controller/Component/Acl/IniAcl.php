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


	public function __construct() {
		$this->options = array(
			'policy' => self::DENY,
			'config' => APP . 'Config' . DS . 'acl.ini.php',
		);
	}
/**
 * Initialize method
 *
 * @param AclBase $component
 * @return void
 */
	public function initialize($Component) {
		if (!empty($Component->settings['ini_acl'])) {
			$this->options = array_merge($this->options, $Component->settings['ini_acl']);
		}

		$config = $this->readConfigFile($this->options['config']);
		$this->build($config);
	}


	public function build($config) {
		if ($config instanceOf ConfigReaderInterface) {
			$config = $config->read(basename($this->options['config']), false);
		}

		if (empty($config['aro'])) {
			throw new IniAclException(__d('cake_dev','"aro" section not found in configuration.'));
		}

		if (empty($config['aco.allow']) && empty($config['aco.deny'])) {
			throw new IniAclException(__d('cake_dev','Neither a "aco.allow" nor a "aco.deny" section were found in configuration.'));
		}

		$allow = !empty($config['aco.allow']) ? $config['aco.allow'] : array();
		$deny = !empty($config['aco.deny']) ? $config['aco.deny'] : array();
		$aro = !empty($config['aro']) ? $config['aro'] : array();
		$map = !empty($config['map']) ? $config['map'] : array();

		$this->Aro = new IniAro($aro, $map);
		$this->Aco = new IniAco($allow, $deny);

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
		return $this->Aco->allow($this->Aro->resolve($aro), $aco, $action);
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

	public static $modifiers = array(
		'*' => '.*',
		'?' => '.?',
	);

	public function __construct(array $allow = array(), array $deny = array()) {
		$this->tree = $this->build($allow, $deny);
	}

	public function node($aco) {
		$aco = $this->resolve($aco);
		$tree = $this->tree;
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
 * return path to aco with allow and deny rules for each level as array
 *
 * @return array
 */
	public function path($aco) {
		$aco = $this->resolve($aco);
		$path = array();
		$level = 0;
		$root = $this->tree;
		
		// add allow/deny rules from matchable nodes
		$stack = array(array($root, 0));

		while (!empty($stack)) {
			list($root, $level) = array_pop($stack);

			if (empty($path[$level])) {
				$path[$level] = array();
			}

			foreach ($root as $node => $elements) {
				if (strpos($node, '*') === false && $node != $aco[$level]) {
					continue;
				}

				$pattern = '#^'.str_replace(array_keys(self::$modifiers), array_values(self::$modifiers), $node).'$#';

				if ($node == $aco[$level] || preg_match($pattern, $aco[$level])) {
					// merge allow/denies with $path of current level
					foreach (array('allow', 'deny') as $policy) {
						if (!empty($elements[$policy])) {
							if (empty($path[$level][$policy])) {
								$path[$level][$policy] = array();
							}

							$path[$level][$policy] = array_merge($path[$level][$policy], $elements[$policy]);
						}
					}

					// traverse
					if (!empty($elements['children']) && isset($aco[$level + 1])) {
						array_push($stack, array($elements['children'], $level + 1));
					}
				}	
			}
		}

		return $path;
	}


/**
 * add $aro to the allow section of aco
 *
 * @return 
 */
	public function allow($aro, $aco, $action) {
		$aco = $this->resolve($aco);
		$depth = count($aco);
		$tree = $this->tree;
		$root = &$tree;
		
		foreach ($aco as $i => $node) {
			if (!isset($root[$node])) {
				$root[$node]  = array(
					'children' => array(),
				);
			}

			if ($i < $depth - 1) {
				$root = &$root[$node]['children'];
			} else {
				$root[$node]['allow'][] = $aro;
			}
		}

		$this->tree = &$tree;
		return true;
	}

/**
 * resolve given ACO string to a path
 *
 * @return array path
 */
	public function resolve($aco) {
		return array_map('trim', explode('/', $aco));
	}

/**
 * build a tree representation from the given allow/deny informations for ACO paths
 *
 * @return array tree 
 */
	public function build(array $allow, array $deny = array()) {
		$stack = array();
		$tree = array();
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
				if ($i < $depth - 1) {
					$tree = &$tree[$node]['children'];
				}
			}

			$tree[$node]['allow'] = $aros;
			$tree = &$root;
		}
		
		foreach ($deny as $dotPath => $commaSeparatedAros) {
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
			
			$tree[$node]['deny'] = $aros;
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
		'User' => 'User.username',
		'Role' => 'User.role'
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
 * return pathes to the given ARO
 *
 * @return string dot separated aro string (e.g. User.jeff)
 */
	public function resolve($aro) {
		foreach ($this->map as $aroGroup => $map) {
			list ($model, $field) = explode('.', $map);
			
			if (is_array($aro) && isset($aro['model']) && isset($aro['foreign_key'])) {
				return $aroGroup .  '.' . $aro['foreign_key'];
			}
			
			if (isset($aro[$model][$field])) {
				return $aroGroup . '.' . $aro[$model][$field];
			}

			if (is_string($aro)) {
				if (strpos($aro, '.') === false) {
					return $aroGroup . '.' . $aro;
				}

				list($aroModel, $aroValue) =  explode('.', $aro);

				if ($aroModel == $model || $aroModel == $aroGroup) {
					return $aroGroup . '.' . $aroValue;
				}
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
