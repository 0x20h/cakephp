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
; aco = access control object (something in your application)
; aro = access request object (something requesting access)
;
; access request objects are added as follows
;
; [aro]
; Role.admin = null
; Role.manager = null
; User.peter = Role.manager 		;uber boss
; User.sarah = Role.manager			;secretary
; User.jonny_dev = Role.admin		;nerd
;
; [aco.allow]
; controllers = Role.admin						; nerd rules
; controllers.Articles.add = Role.manager		; 
; controllers.Articles.edit = Role.manager		;
; controllers.Articles.delete = User.peter		; only peter may delete and
; controllers.Articles.publish = User.peter		; publish
; controllers.Invoices = Role.manager			; 
; controllers.Invoices.delete = User.peter		; overwrite deny rule for peter
;
; [aco.deny]
; controllers.Invoices.delete = Role.manager	; deny delete, only peter is allowed
[map]
Role = User.role
User = User.username

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
controllers = Role.admin
controllers.Articles = Role.manager
controllers.Articles.delete = User.peter
controllers.Articles.publish = User.peter
controllers.Reports.view = Role.sales

[aco.deny]
controllers.Articles.publish = Role.manager
controllers.Articles.delete = Role.manager	
