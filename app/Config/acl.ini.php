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
; like array('User' => array('login' => 'foo', 'role' => 'bar'))
; and a map is defined like
; [map]
; User = User.login
; Role = User.role
;
; then IniAcl will resolve this array to User.foo, Role.bar or Role.default.
; Resolving takes place in the order you specified the map. So if you have a line in the
; [aro] section referencing User.foo, User.foo will be returned. If not, then if you have defined a role
; like Role.bar it will be returned. Otherwise no reference is found and the default role will be
; returned. This way you don't need to reference every User in this file. Either stick to the Role or
; define access for unknown users using the default role "Role.default"
;
; [aro]
; Role.default = null
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
; controllers.*.* = Role.manager				; 
; controllers.Articles.delete = User.peter		; only peter may delete and
; controllers.Articles.publish = User.peter		; publish
; controllers.Invoices.delete = User.sarah		; overwrite deny rule for sarah
; controllers.Users.dashboard = Role.default	;
;
; [aco.deny]
; controllers.*.delete = Role.manager			; deny delete actions for managers


; define mapping 
[map]
User = User.username
Department = User.department
Role = User.role

[aro]
Role.admin 		= null							; root aro
Role.sales 		= null							;
Role.accounting = Role.sales					;
Role.manager 	= Role.sales					; manager inherits from sales
User.peter 		= Role.manager, Role.accounting	; uber boss
User.sarah 		= Role.accounting				; secretary
User.jeff 		= Role.manager					; another worker bee
User.dev		= Role.admin					;

[aco.allow]
* = User.dev
controllers.* = Role.admin
*ontrollers.*.manager_* = Role.manager
controllers.Articles.* = Role.manager
controllers.Articles.* = User.peter
; overwrite the deny rule for Role.manager and allow baz.manager_delete only for jeff
controllers.baz.manager_delete = User.jeff
controllers.Users.dashboard = Role.default
controllers.Reports.view = Role.sales

[aco.deny]
controllers.Articles.publish = Role.manager
controllers.Articles.delete = Role.manager	
controllers.*.manager_delete = Role.manager
