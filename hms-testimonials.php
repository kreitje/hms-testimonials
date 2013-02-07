<?php
/*
Plugin Name: HMS Testimonials
Plugin URI: http://hitmyserver.com
Description: Displays your customer testimonials.
Version: 1.3
Author: HitMyServer LLC
Author URI: http://hitmyserver.com
*/


define('HMS_TESTIMONIALS', plugin_dir_path(__FILE__));
require_once HMS_TESTIMONIALS . 'setup.php';
require_once HMS_TESTIMONIALS . 'shortcodes.php';
require_once HMS_TESTIMONIALS . 'widgets.php';
require_once HMS_TESTIMONIALS . 'admin.php';

$hms_testimonials_db_version = 7;


function hms_testimonials_init() {
	wp_enqueue_script('jquery');
}

add_action('wp_enqueue_scripts', 'hms_testimonials_init');
add_action('plugins_loaded', 'hms_testimonials_db_check');
add_action('admin_menu', 'hms_testimonials_menu');
add_action( 'admin_init', 'hms_testimonials_admin_init');
add_action('widgets_init', 'hms_testimonials_widgets');

add_shortcode('hms_testimonials', 'hms_testimonials_show');
