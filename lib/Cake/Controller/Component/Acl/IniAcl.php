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

		App::uses('IniReader', 'Configure');
		$Reader = new IniReader(dirname($this->options['config']));
		$config = $Reader->read(basename($this->options['config']), false);
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
		return $this->Aco->access($this->Aro->resolve($aro), $aco, $action, 'allow');
	}

/**
 * deny ARO access to ACO
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function deny($aro, $aco, $action = "*") {
		return $this->Aco->access($this->Aro->resolve($aro), $aco, $action, 'deny');
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
}

/**
 * Access Control Object
 *
 */
class IniAco {

/**
 * holds internal ACO representation
 *
 * @var array
 */
	protected $tree = array();

/**
 * map modifiers for ACO paths to their respective PCRE pattern
 * 
 * @var array
 */
	public static $modifiers = array(
		'*' => '.*',
		'?' => '.?',
	);

	public function __construct(array $allow = array(), array $deny = array()) {
		$this->build($allow, $deny);
	}

/**
 * return path to the requested ACO with allow and deny rules for each level
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
 * allow/deny ARO access to ARO
 *
 * @return void 
 */
	public function access($aro, $aco, $action, $type = 'deny') {
		$aco = $this->resolve($aco);
		$depth = count($aco);
		$root = $this->tree;
		$tree = &$root;
		
		foreach ($aco as $i => $node) {
			if (!isset($tree[$node])) {
				$tree[$node]  = array(
					'children' => array(),
				);
			}

			if ($i < $depth - 1) {
				$tree = &$tree[$node]['children'];
			} else {
				if (empty($tree[$node][$type])) {
					$tree[$node][$type] = array();
				}

				$tree[$node][$type] = array_merge(is_array($aro) ? $aro : array($aro), $tree[$node][$type]);
			}
		}

		$this->tree = &$root;
	}

/**
 * resolve given ACO string to a path
 *
 * @param string $aco ACO string
 * @return array path
 */
	public function resolve($aco) {
		if (is_array($aco)) {
			return $aco;
		}

		$char = strpos($aco, '.') ? '.' : '/';
		return array_map('trim', explode($char, $aco));
	}

/**
 * build a tree representation from the given allow/deny informations for ACO paths
 *
 * @return array tree 
 */
	public function build(array $allow, array $deny = array()) {
		$stack = array();
		$this->tree = array();
		$tree = array();
		$root = &$tree;

		foreach ($allow as $dotPath => $commaSeparatedAros) {
			$aros = array_map('trim', explode(',', $commaSeparatedAros));
			$this->access($aros, $dotPath, null, 'allow');
		}
	
		foreach ($deny as $dotPath => $commaSeparatedAros) {
			$aros = array_map('trim', explode(',', $commaSeparatedAros));
			$this->access($aros, $dotPath, null, 'deny');
		}
	}


}

/**
 * Access Request Object
 *
 */
class IniAro {
	public $map = array(
		'User' => 'User.username',
		'Role' => 'Role.name'
	);

	protected $tree = array();

	public function __construct(array $aro = array(), array $map = array()) {
		!empty($map) && $this->map = $map;
		$this->build($aro);
	}


/**
 * From the perspective of the given ARO, walk down the tree and
 * collect all inherited AROs levelwise such that AROs from different
 * branches with equal distance to the requested ARO will be collected at the same
 * index. The resulting array will contain a prioritized list of (list of) roles ordered from 
 * the most distant AROs to the requested one itself.
 * 
 * @param mixed $aro An ARO identifier
 * @return array prioritized AROs
 */
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
 * resolve an ARO identifier to an internal ARO string using
 * the internal mapping information
 *
 * @param mixed $aro ARO identifier (User.jeff, array('User' => ...), etc)
 * @return string dot separated aro string (e.g. User.jeff, Role.admin)
 */
	public function resolve($aro) {
		foreach ($this->map as $aroGroup => $map) {
			list ($model, $field) = explode('.', $map);

			if (is_array($aro) && isset($aro['model']) && isset($aro['foreign_key']) && $aro['model']  == $aroGroup) {
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

		new IniAroException(__d('cake_core', 'no map entry found in aro:'.print_r($aro, true)));
	}


/**
 * build an ARO tree structure for internal processing
 *
 * @param array $sections ini file  
 * @return array ARO tree
 */
	public function build(array $sections) {
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

		$this->tree = $tree;
	}
}

class IniAclException extends Exception {}
class IniAroException extends IniAclException {}
class IniAcoException extends IniAclException {}
