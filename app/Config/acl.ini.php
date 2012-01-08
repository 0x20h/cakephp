;<?php exit() ?>
;/**
; * ACL Configuration
; *
; *
; * PHP 5
; *
; * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
; * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
; *
; *  Licensed under The MIT License
; *  Redistributions of files must retain the above copyright notice.
; *
; * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
; * @link          http://cakephp.org CakePHP(tm) Project
; * @package       app.Config
; * @since         CakePHP(tm) v 0.10.0.1076
; * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
; */

; acl.ini.php - Cake ACL Configuration
; ---------------------------------------------------------------------
; Use this file to specify user permissions.
;
; aco = access control object (something you restrict access to in your application)
; examples:
;	/controllers/users/add
;	/custom/rule/sendCustomerInvoiceEmails 
;	
; aro = access request object (something requesting access)
; examples:
;	User/jeff
;	Role/admin
;	Department/sales
;
; First, define a mapping to resolve AROs. E.g. if $acl->check() gets an ARO
; like array('User' => array('login' => 'jeff', 'role' => 'editor'))
; and a map is defined like
;
; [map]
; User = User/login
; Role = User/role
;
; then IniAcl will resolve this array to User/jeff, Role/editor or Role/default 
; depending on the definitions of AROs in the [aro] section.
; Resolving takes place in the order you specified the map. So if you have a 
; line in the [aro] section referencing User/jeff, rules for User/jeff will be 
; checked. If User/jeff is not referenced, then if you have defined a role like 
; Role/editor it will be used instead. Otherwise, if no reference is found,
; the default role will be returned. This way you don't need to reference every 
; User in this file. Either stick to the Role or define access for new
; users using the default role "Role/default". If your role information is stored 
; in the user model as a foreign_key (e.g. role_id => 4 instead of role => editor) 
; you can optionally define aliases for these foreign_keys:
; 
; [alias]
; Role/4 = Role/editor
; 
; Now role_id 4 will be resolved to Role/editor. This way you can keep your ini file
; readable. The [aro] section then defines roles that can be granted or denied access to ACOs in the 
; [aco.allow] and [aco.deny] sections.
;
; [aro]
; Role/admin = null
; Role/manager = null
; User/peter = Role/manager
; User/sarah = Role/manager
; User/sue = Role/default
; User/jonny_dev = Role/admin
;
; In the [aco.allow] and [aco.deny] sections you can define access controlled
; objects. E.g. if AuthComponent is configured to use "/controllers" as actionPath
; you can define /controllers/CONTROLLER/ACTION to reference them and grant access to
; several AROs. The left hand side in [aco.allow] and [aco.deny] sections supports 
; wildcards, so if you specify a rule for Role/manager for an ACO like
; "/controllers/*/manager_*" every manager is allowed all actions starting with
; "manager_" on all controllers.
; 
; [aco.allow]
; # allow everything for admins
; /* = Role/admin
; # allow all actions starting with manager_ on all conrollers for managers
; /controllers/*/manager_* = Role/manager			
; # allow peter manager_delete actions on all controllers 
; /controllers/*/manager_delete = User/peter
; # allow users dashboard to every authenticated user
; /controllers/Users/dashboard = Role/default
;
; [aco.deny]
; controllers/invoices/manager_delete = Role/manager	; deny manager_delete actions for managers

[map]
User = User/username
Role = User/group_id

[alias]
Role/1 = Role/admin
Role/2 = Role/accounting
Role/3 = Role/manager
Role/4 = Role/editor

[aro]
Role/admin = null
Role/accounting = null
Role/editor = null
Role/manager = Role/editor, Role/accounting
User/jeff = Role/manager

[aco.allow]
/* = Role/admin
/controllers/*/view = Role/default
/controllers/* = Role/manager
/controllers/invoices/* = Role/accounting
/controllers/articles/* = Role/editor

;# in your controller: $this->Acl->check('jeff', 'some/custom/rule') === true
/some/custom/rule = User/jeff 

[aco.deny]
controllers/users/delete = Role/manager
controllers/invoices/delete = Role/manager
