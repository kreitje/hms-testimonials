<?php
$hms_testimonials_random_strings = '';
$hms_testimonials_is_js = true;

require_once '../../../wp-load.php';
require_once './admin.php';
require_once './shortcodes.php';

/**
 * Set the default blog ID
 **/
$blog_id = 1;
if (isset($_GET['blog_id']) && is_numeric($_GET['blog_id']))
	$blog_id = (int)$_GET['blog_id'];

$settings = HMS_Testimonials::getInstance()->get_options();

header('Content-type: application/javascript');

/**
 * Check to see if the option was enabled in the advanced settings page
 **/
if ($settings['js_load'] == 0)
	die("document.write('<div class=\"hms-testimonials-error\">Sorry, javascript loading is not enabled.</div>');");

/**
 * Piggy back off of the hms_testimonials shortcode function to generate the output
 **/

if (isset($_GET['rotating']))
	$ret = hms_testimonials_show_rotating($_GET) . $hms_testimonials_random_strings;
else
	$ret = hms_testimonials_show($_GET);

/**
 * Write it to the page. JSON encode to escape for javascript.
 **/
echo 'document.write(' . json_encode($ret) . ');';