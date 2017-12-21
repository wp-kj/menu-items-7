<?php
/*
Plugin Name: 7 Menu Items
Plugin URI: http://brinyinfoway.com/
Description: This plugin lets you add 7 menu items serving daily at your restaurant.
Author: KJ
Version: 1.0
Author URI: http://brinyinfoway.com/

PREFIX: mi7 (Menu Items 7)
*/

global $mi7_version;
$mi7_version = '1.0';
define('MI7_BASE', WP_PLUGIN_DIR.'/'.str_replace(basename(__FILE__), '', plugin_basename(__FILE__)));
define('MI7_URL',plugins_url('/menu-items-7/'));

register_activation_hook(__FILE__, 'mi7_activate');
register_deactivation_hook(__FILE__, 'mi7_deactivate');
register_uninstall_hook(__FILE__, 'mi7_uninstall');

add_action('plugins_loaded', 'mi7_update_check');
add_action('init', 'mi7_initialize');

require_once('inc/functions.php');