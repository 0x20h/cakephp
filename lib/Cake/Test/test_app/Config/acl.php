<?php

$config = array();

$config['aro'] = array(
	'Role.Warriors' 	=> null,
	'Role.Wizards' 		=> null,
	'Role.Hobbits' 		=> null,
	'Role.Visitor'		=> null,
	// warriors
	'User.Aragorn' 		=> 'Role.Warriors',
	'User.Legolas'		=> 'Role.Warriors',
	'User.Gimli' 		=> 'Role.Warriors',
	// wizards
	'User.Gandalf' 		=> 'Role.Wizards',
	// hobbits
	'User.Frodo' 		=> 'Role.Hobbits',
	'User.Bilbo' 		=> 'Role.Hobbits',
	'User.Merry'		=> 'Role.Hobbits',
	'User.Pippin'		=> 'Role.Hobbits',	
	// visitors
	'User.Gollum'		=> 'Role.Visitor',
);

$config['aco.allow'] = array(
	'Weapons'		 	=> 'Role.Warriors',
	'Ale'				=> 'Role.Warriors, Role.Wizards, Role.Hobbits',
	'Elven Rations'	=> 'Role.Warriors',
	'Salted Pork'		=> 'Role.Warriors, Role.Wizards, Role.Visitor',
	'Diplomacy'		=> 'Role.Wizards, User.Aragorn, User.Pippin',
	'Ring'			=> 'User.Frodo',
	'Ring'			=> 'User.Frodo',

);

$config['aco.deny'] = array(
	'ale' 				=> 'User.Pippin'
);
