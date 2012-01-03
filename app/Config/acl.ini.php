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
;	controllers.users.add
;	custom.rule.sendCustomerInvoiceEmails 
;	
; aro = access request object (something requesting access)
; examples:
;	User.jeff
;	Role.admin
;	Department.sales
;
; define a mapping to resolve AROs. E.g. if check() gets an ARO
; like array('User' => array('login' => 'jeff', 'role' => 'editor'))
; and a map is defined like
; [map]
; User = User.login
; Role = User.role
;
; then IniAcl will resolve this array to User.jeff, Role.editor or Role.default.
; Resolving takes place in the order you specified the map. So if you have a 
; line in the [aro] section referencing User.jeff, rules for User.jeff will be 
; checked. If User.jeff is not referenced, then if you have defined a role like 
; Role.editor it will be used instead. Otherwise, if no reference is found,
; the default role will be returned. This way you don't need to reference every 
; User in this file. Either stick to the Role or define access for unknown 
; users using the default role "Role.default"
;
; [aro]
; Role.admin = Role.default
; Role.manager = Role.default
; User.peter = Role.manager 		;uber boss
; User.sarah = Role.manager			;secretary
; User.jonny_dev = Role.admin		;nerd
;
; define access control objects
; 
; [aco.allow]
; * = Role.admin								; allow admins everything
; controllers.*.manager_* = Role.manager		; allow all manager actions
; controllers.Articles.delete = User.peter		; only peter may delete and
; controllers.Articles.publish = User.peter		; publish
; controllers.Invoices.delete = User.sarah		; overwrite deny rule for sarah
; controllers.Users.dashboard = Role.default	; allow dashboard to all authenticated users
;
; [aco.deny]
; controllers.invoices.manager_delete = Role.manager	; deny delete actions for managers

[map]
User = User.username
Role = User.role

[aro]
Role.admin = null
Role.accounting = null
Role.editor = null
Role.manager = Role.editor, Role.accounting
User.jeff = Role.manager

[aco.allow]
* = Role.admin
controllers.*.view = Role.editor, Role.accounting
controllers.*.add = Role.editor, Role.accounting
some.custom.rule = User.jeff 

[aco.deny]
controllers.users.add = Role.editor, Role.accounting
