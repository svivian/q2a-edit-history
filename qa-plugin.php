<?php
/*
	Plugin Name: Edit History
	Plugin URI: https://github.com/svivian/q2a-edit-history
	Plugin Description: Edit History plugin for Q2A
	Plugin Version: 1.4
	Plugin Date: 2014-04-08
	Plugin Author: Scott Vivian
	Plugin Author URI: http://codelair.com/
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Minimum PHP Version: 5.2
	Plugin Update Check URI: https://raw.github.com/svivian/q2a-edit-history/master/qa-plugin.php

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

if (!defined('QA_VERSION')) exit;


qa_register_plugin_module('event', 'qa-edit-history.php', 'qa_edit_history', 'Edit History');
qa_register_plugin_module('page', 'qa-edh-revisions.php', 'qa_edh_revisions', 'Post revisions');
qa_register_plugin_layer('qa-edh-layer.php', 'Edit History Layer');
qa_register_plugin_phrases('qa-edh-lang-*.php', 'edithistory');
