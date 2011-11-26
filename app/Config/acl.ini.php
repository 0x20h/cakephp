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
; controllers.Articles.add = Role.manager
; controllers.Articles.edit = Role.manager
; controllers.Articles.delete = User.peter
; controllers.Articles.publish = User.peter

[aro]
Role.admin = null
Role.manager = null
Role.sales = null
User.peter = Role.manager, Role.sales 		;uber boss
User.sarah = Role.manager					;secretary
User.jeff = Role.manager					;another worker bee
User.jonny_dev = Role.admin					;nerd, the

[aco.allow]
controllers = Role.admin
controllers.Articles.add = Role.manager
controllers.Articles.edit = Role.manager
controllers.Articles.delete = User.peter
controllers.Articles.publish = User.peter
controllers.Reports.view = Role.sales

[aco.deny]
controllers.Articles.add = User.jeff		;jeff should not add articles
