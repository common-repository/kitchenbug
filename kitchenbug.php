<?php

/*
  Plugin Name: Kitchenbug
  Plugin URI: http://www.kitchenbug.com
  Description: Kitchenbug is the ultimate tool for food bloggers. Our team of certified cooks, UX experts, computer engineers and registered dietitians, strives continuously to identify food bloggers needs, and meet them.
  Author: Liquee Technologies Ltd.
  Version: 0.6.4
  Author URI: http://www.kitchenbug.com
*/

//PHP Version check
if (5 > phpversion())
{
	throw new Exception('Kitchenbug Wordpress plugin requires PHP version 5!');
}

require_once('KB/Main.php');
$kb_path = KB_Main::getIncludePath() . '/';
$kbInstance = KB_Main::getInstance(plugin_basename(dirname(__FILE__)));

require_once($kb_path . 'Lang.php');
require_once($kb_path . 'Posts.php');
require_once($kb_path . 'Http.php');
require_once($kb_path . 'Db.php');
require_once($kb_path . 'Wordpress.php');
require_once($kb_path . 'View.php');

// Indicate to WordPress the name of the function that
// should be called when it activates the plugin
register_activation_hook(__FILE__, 'kb_db_install');

if (!is_admin())
{
	require_once($kb_path . 'User.php');
	$kb = new KB_User;
}
else
{
	require_once($kb_path . 'Admin.php');
	$kb = new KB_Admin;
}

function kb_db_install()
{
	// Create Kitchenbug recipe table
	global $wpdb;
	$recipes_table = $wpdb->prefix . "kb_recipes";
	$recipes_table_only = $wpdb->prefix . "kb_recipes_only";
	// Check if the table already exists
	if ($wpdb->get_var("SHOW TABLES LIKE '$recipes_table'") != $recipes_table)
	{
		$sql = "CREATE TABLE " . $recipes_table . " (
            post_id BIGINT(20) UNSIGNED NOT NULL UNIQUE KEY,
			recipeID BIGINT(20) UNSIGNED NOT NULL,
            content MEDIUMTEXT,
            kb_plugin_version varchar(20)
        	)DEFAULT CHARSET=utf8;";

		$wpdb->query($sql);

		$sql2 = "CREATE TABLE " . $recipes_table_only . " (
            recipeID BIGINT(20) UNSIGNED NOT NULL UNIQUE KEY,
			recipeName varchar(512) DEFAULT NULL,
            content MEDIUMTEXT,
            engine_version varchar(32),
			lastupdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        	)DEFAULT CHARSET=utf8;";

		$wpdb->query($sql2);

		KB_Main::getInstance()->InitializePlugin();
	}
	else
	{
		// Table exists, upgrade if needed
		KB_Main::getInstance()->upgradePlugin();
	}

	// Create Kitchenbug options in WP table
	KB_Db::getInstance()->create();
}