<?php
$dirname	= dirname(__FILE__); 
require_once($dirname . '/KB/Main.php');
require_once($dirname . '/KB/Http.php');

	// Delete Kitchenbug recipe tables
	global $wpdb;
	$wpdb->query('DROP TABLE ' . $wpdb->prefix . "kb_recipes");
	$wpdb->query('DROP TABLE ' . $wpdb->prefix . "kb_recipes_only");
	
	// Remove Kitchenbug options from WP table
	delete_option('KBSettings');
