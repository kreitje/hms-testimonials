<?php

function hms_testimonials_install() {

	global $wpdb, $hms_testimonials_db_version, $blog_id;

	$curr_ver = get_site_option('hms_testimonials_db_version');

	$table_name = $wpdb->prefix . "hms_testimonials";
	$table_name_2 = $wpdb->prefix . "hms_testimonials_groups";
	$table_name_3 = $wpdb->prefix . "hms_testimonials_group_meta";

	$table_name_4 = $wpdb->prefix . "hms_testimonials_cf";
	$table_name_5 = $wpdb->prefix . "hms_testimonials_cf_meta";
	$table_name_6 = $wpdb->prefix . "hms_testimonials_templates";

	if ($curr_ver != $hms_testimonials_db_version) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$sql_1 = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			user_id int(11) DEFAULT '0' NOT NULL,
			blog_id int(11) DEFAULT '0' NOT NULL,
			name text NOT NULL,
			image int(11) DEFAULT '0' NOT NULL,
			testimonial text NOT NULL,
			url VARCHAR(255) DEFAULT '' NOT NULL,
			testimonial_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
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
		$sql_4 = "CREATE TABLE $table_name_4 (
			id int(11) NOT NULL AUTO_INCREMENT,
			blog_id int(11) DEFAULT '0' NOT NULL,
			name VARCHAR(255) DEFAULT '' NOT NULL,
			type VARCHAR(255) DEFAULT '' NOT NULL,
			isrequired int(1) DEFAULT 0 NOT NULL,
			showonform int(1) DEFAULT 0 NOT NULL,
			UNIQUE KEY id (id)
		);";
		$sql_5 = "CREATE TABLE $table_name_5 (
			id int(11) NOT NULL AUTO_INCREMENT,
			testimonial_id int(11) NOT NULL,
			key_id int(11) NOT NULL,
			value text NOT NULL,
			UNIQUE KEY id (id),
			KEY testimonial_id (testimonial_id)
		);";

		$sql_6 = "CREATE TABLE $table_name_6 (
			id int(11) NOT NULL AUTO_INCREMENT,
			blog_id int(11) DEFAULT '0' NOT NULL,
			name VARCHAR(255) DEFAULT '' NOT NULL,
			data text  NOT NULL,
			UNIQUE KEY id (id)
		);";
		
		dbDelta($sql_1);
		dbDelta($sql_2);
		dbDelta($sql_3);
		dbDelta($sql_4);
		dbDelta($sql_5);
		dbDelta($sql_6);

		$collation = '';
		$get_collation = $wpdb->get_results("SHOW FULL COLUMNS FROM ".$table_name, ARRAY_A);
		foreach($get_collation as $g) {
			if ($g['Field'] == 'testimonial') {
				$collation = $g['Collation'];
				break;
			}
		}


		update_option('hms_testimonials_db_version', $hms_testimonials_db_version);

		if ($curr_ver < 10) {

			$templates = array(
				'Testimonial, Author, URL, Date' => array('system_testimonial', 'system_source', 'system_url', 'system_date'),
				'Testimonial, URL, Author, Date' => array('system_testimonial', 'system_url', 'system_source', 'system_date'),
				'Author, Testimonial, URL, Date' => array('system_source', 'system_testimonial', 'system_url', 'system_date'),
				'Author, URL, Testimonial, Date' => array('system_source', 'system_url', 'system_testimonial', 'system_date'),
				'URL, Author, Testimonial, Date' => array('system_url', 'system_source', 'system_testimonial', 'system_date'),
				'URL, Testimonial, Author, Date' => array('system_url', 'system_testimonial', 'system_source', 'system_date'),

				'Testimonial, Author, Date, URL' => array('system_testimonial', 'system_source', 'system_date', 'system_url'),
				'Testimonial, URL, Date, Author' => array('system_testimonial', 'system_url', 'system_date', 'system_source'),
				'Testimonial, Date, Author, URL' => array('system_testimonial', 'system_date', 'system_source', 'system_url'),
				'Testimonial, Date, URL, Author' => array('system_testimonial', 'system_date', 'system_url', 'system_source'),

				'Author, Testimonial, Date, URL' => array('system_source', 'system_testimonial', 'system_date', 'system_url'),
				'Author, URL, Date, Testimonial' => array('system_source', 'system_url', 'system_date', 'system_testimonial'),
				'Author, Date, Testimonial, URL' => array('system_source', 'system_date', 'system_testimonial', 'system_url'),
				'Author, Date, URL, Testimonial' => array('system_source', 'system_date', 'system_url', 'system_testimonial'),

				'URL, Author, Date, Testimonial' => array('system_url', 'system_source', 'system_date', 'system_testimonial'),
				'URL, Testimonial, Date, Author' => array('system_url', 'system_testimonial', 'system_date', 'system_source'),
				'URL, Date, Author, Testimonial' => array('system_url', 'system_date', 'system_source', 'system_testimonial'),
				'URL, Date, Testimonial, Author' => array('system_url', 'system_date', 'system_testimonial', 'system_source'),

				'Date, Testimonial, Author, URL' => array('system_date', 'system_testimonial', 'system_source', 'system_url'),
				'Date, Testimonial, URL, Author' => array('system_date', 'system_testimonial', 'system_url', 'system_source'),
				'Date, Author, Testimonial, URL' => array('system_date', 'system_source', 'system_testimonial', 'system_url'),
				'Date, Author, URL, Testimonial' => array('system_date', 'system_source', 'system_url', 'system_testimonial'),
				'Date, URL, Author, Testimonial' => array('system_date', 'system_url', 'system_source', 'system_testimonial'),
				'Date, URL, Testimonial, Author' => array('system_date', 'system_url', 'system_testimonial', 'system_source')
			);

			foreach($templates as $i => $t)
				$wpdb->insert($wpdb->prefix.'hms_testimonials_templates', array('blog_id' => $blog_id, 'name' => $i, 'data' => serialize($t)));
			

		}

		if ($curr_ver < 9) {
			update_option('hms_testimonials', 
				array('role' => 'administrator', 'autoapprove' => 'administrator', 'moderator' => 'administrator', 'resetapproval' => 1, 'num_users_can_create' => 1, 
					  'show_active_links' => 0, 'active_links_nofollow' => 1, 'moderators_can_access_settings' => 1, 'collation' => $collation,
					  'use_recaptcha' => 0, 'recaptcha_privatekey' => '', 'recaptcha_publickey' => '', 'image_width' => 100, 'image_height' => 100
				));
		}
	}
}

function hms_testimonials_uninstall() {
	
	delete_option('hms_testimonials_db_version');
	delete_option('hms_testimonials');
}

function hms_testimonials_db_check() {
	global $hms_testimonials_db_version;

	if (get_site_option('hms_testimonials_db_version') != $hms_testimonials_db_version)
		hms_testimonials_install();
}

register_activation_hook(__FILE__, 'hms_testimonials_install');