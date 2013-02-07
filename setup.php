<?php

function hms_testimonials_install() {

	global $wpdb, $hms_testimonials_db_version;

	$curr_ver = get_site_option('hms_testimonials_db_version');

	$table_name = $wpdb->prefix . "hms_testimonials";
	$table_name_2 = $wpdb->prefix . "hms_testimonials_groups";
	$table_name_3 = $wpdb->prefix . "hms_testimonials_group_meta";

	if ($curr_ver != $hms_testimonials_db_version) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$sql_1 = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			user_id int(11) DEFAULT '0' NOT NULL,
			blog_id int(11) DEFAULT '0' NOT NULL,
			name text NOT NULL,
			testimonial text NOT NULL,
			url VARCHAR(255) DEFAULT '' NOT NULL,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			display_order int(11) DEFAULT 0 NOT NULL,
			display int(1) DEFAULT 1 NOT NULL,
			UNIQUE KEY id (id)
		);";
		$sql_2 = "CREATE TABLE $table_name_2 (
			id int(11) NOT NULL AUTO_INCREMENT,
			blog_id int(11) DEFAULT '0' NOT NULL,
			name text NOT NULL,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			UNIQUE KEY id (id)
		);";
		$sql_3 = "CREATE TABLE $table_name_3 (
			id int(11) NOT NULL AUTO_INCREMENT,
			testimonial_id int(11) NOT NULL,
			group_id int(11) NOT NULL,
			display_order int(11) DEFAULT 0 NOT NULL,
			UNIQUE KEY id (id),
			KEY testimonial_id (testimonial_id),
			KEY group_id (group_id)
		);";
		
		dbDelta($sql_1);
		dbDelta($sql_2);
		dbDelta($sql_3);

		update_option('hms_testimonials_db_version', $hms_testimonials_db_version);
		update_option('hms_testimonials', array('role' => 'administrator', 'autoapprove' => 'administrator', 'resetapproval' => 1));
	}
}

function hms_testimonials_uninstall() {
	global $wpdb;
	
}

function hms_testimonials_db_check() {
	global $hms_testimonials_db_version;

	if (get_site_option('hms_testimonials_db_version') != $hms_testimonials_db_version)
		hms_testimonials_install();
}

register_activation_hook(__FILE__, 'hms_testimonials_install');