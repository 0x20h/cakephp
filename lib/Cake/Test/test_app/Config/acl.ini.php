;<?php exit() ?>
; SVN FILE: $Id$
;/**
; * Test App Ini Based Acl Config File
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
;; * @package       Cake.Test.test_app.Config
; * @since         CakePHP(tm) v 0.10.0.1076
; * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
; */

;-------------------------------------
; AROs
;-------------------------------------

[aro]
Role.admin = null
Role.data_acquirer = null
Role.accounting = null
Role.database_manager = null
Role.data_analyst = Role.data_acquirer, Role.database_manager
Role.reports = Role.data_analyst
Role.sales = null
Role.manager = Role.accounting, Role.sales
Role.accounting_manager = Role.accounting

;# managers
User.hardy = Role.accounting_manager, Role.reports		;uber boss
User.stan = Role.manager								;assistant

;# accountants
User.peter = Role.accounting
User.jeff = Role.accounting

;# admins
User.jan = Role.admin
;# database
User.db_manager_1 = Role.database_manager
User.db_manager_2 = Role.database_manager
;-------------------------------------
; ACOs
;-------------------------------------

[aco.allow]
* = Role.admin

controllers.*.manager_* = Role.manager
controllers.reports.* = Role.sales
controllers.reports.invoices = Role.accounting
controllers.invoices.* = Role.accounting
controllers.invoices.edit = User.db_manager_2
controllers.users.* = Role.manager, User.peter
controllers.db.* = Role.database_manager
controllers.*.add = User.stan
controllers.*.edit = User.stan
controllers.*.publish = User.stan

;# test for case insensitivity
controllers.Forms.NEW = Role.data_acquirer

rules.custom.* = User.stan
rules.custom.sendInvoiceMails = User.peter, User.hardy

rules.custom.user.resetPassword = User.jan, User.jeff

[aco.deny]
;# accountants and sales should not delete anything
controllers.*.delete = Role.sales, Role.accounting
controllers.db.drop = User.db_manager_2
