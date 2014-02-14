<?php
/*
	Plugin Name: تاریخچه‌ی ویرایش
	Plugin URI: http://qanet.ir/files/modules/edit-history/versions/edit-history-current.zip
	Plugin Update Check URI: http://qanet.ir/files/modules/edit-history/edit-history.php
	Plugin Description: افزونه‌ی تاریخچه‌ی ویرایش
	Plugin Version: 1.2.0
	Plugin Date: 2014-02-14
	Plugin Author: Scott Vivian
	Plugin Author URI: http://codelair.co.uk/
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.4
	Plugin Traslator: Jalal Jaberi

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.gnu.org/licenses/gpl.html
*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
}


qa_register_plugin_module('event', 'qa-edit-history.php', 'qa_edit_history', 'تاریخچه‌ی ویرایش');
qa_register_plugin_module('page', 'qa-edh-revisions.php', 'qa_edh_revisions', 'بازبینی‌های ارسال');
qa_register_plugin_layer('qa-edh-layer.php', 'لایه‌ی تاریخچه‌ی ویرایش');
qa_register_plugin_phrases('qa-edh-lang-*.php', 'edithistory');