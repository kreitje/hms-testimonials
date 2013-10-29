<?php
/*
Plugin Name: HMS Testimonials
Plugin URI: http://hitmyserver.com
Description: Displays your customer testimonials.
Version: 2.1.14
Author: HitMyServer LLC
Author URI: http://hitmyserver.com
*/


define('HMS_TESTIMONIALS', plugin_dir_path(__FILE__));
require_once HMS_TESTIMONIALS . 'setup.php';
require_once HMS_TESTIMONIALS . 'shortcodes.php';
require_once HMS_TESTIMONIALS . 'widgets.php';
require_once HMS_TESTIMONIALS . 'admin.php';

/**
 * What database version of the plugin are we on
 **/
$hms_testimonials_db_version = 13;

/**
 * This is populated from the rotating shortcode and widget.
 * It is used for the wp_footer action to load the javascript there
 **/
$hms_testimonials_random_strings = '';


add_action('wp_enqueue_scripts', create_function('', 'wp_enqueue_script(\'jquery\');'));
add_action('plugins_loaded', 'hms_testimonials_db_check');

add_action('init', create_function('', 'if (session_id() == \'\' && !headers_sent()) session_start();'));
add_action('admin_init', create_function('', 'HMS_Testimonials::getInstance();'));
add_action('admin_menu', create_function('', 'HMS_Testimonials::getInstance()->admin_menus();'));
add_action('admin_head', create_function('', 'HMS_Testimonials::getInstance()->admin_head();'));

add_action('widgets_init', 'hms_testimonials_widgets');

add_shortcode('hms_testimonials', 'hms_testimonials_show');
add_shortcode('hms_testimonials_rotating', 'hms_testimonials_show_rotating');
add_shortcode('hms_testimonials_form', 'hms_testimonials_form');

add_action('wp_footer', 'hms_testimonial_footer_js', 50);

add_filter('plugin_action_links', array('HMS_Testimonials', 'settings_link'), 10, 2);