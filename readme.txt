=== Plugin Name ===
Contributors: kreitje
Donate link: hitmyserver.com/wordpress-plugins/
Tags: testimonials, reviews, client reviews, what they are saying
Requires at least: 3.0.1
Tested up to: 3.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display your customer testimonials on pages or posts. Use groups to organize and display specific testimonnials on specific pages.


== Description ==

** Mega Changes **

* Templates have been redone. You now create your own templates using drag and drop.
* Custom fields! You can now add additional fields to your testimonials.
* You can now add an image to your testimonial. This allows you show a picture of the person who submitted the testimonial.

HMS Testimonials offers you three shortcodes and two widgets. The first shortcode allows you to show all of your testimonials, a group of testimonials or just a single testimonial. The second shortcode allows you to show all or a group of testimonials that rotate through one at a time. Our third shortcode will display a form on a page or post to alow your visitors to submit testimonials. This shortcode can be placed inside a page or a blog post. If your theme uses sidebars you can use one or both of the widgets we offer. The first widget does the same as our shortcode. It shows all, a group or just one testimonial. Our second widget rotates through the selected testimonials one at a time. After a few seconds it will fade out the old and fade in the new testimonial.

Create groups to display specific testimonials on different pages. You can change the order they are shown in using a simple drag and drop method.

Use the settings to allow access for your users to write testimonials about your company.
    

== Installation ==

1. Upload the `hms-testimonials` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Testimonals in the sidebar and add them in.


== Upgrade Notice ==

Go into Testimonials -> Templates and create new templates.

== Frequently Asked Questions ==

= Can I have visitors submit testimonials to me? =

Yes, add the [hms_testimonials_form] shortcode to a page or a post.

= Can I show only 1 testimonial? =

Yes, both the shortcode and the widget allow you to select and id to show. You can find the id in the first column of your testimonials.

= Can I set how fast the testimonials rotate? =

Yes, there is a setting in the HMS Testimonial Rotator widget that lets you specify the number of seconds. On the shortcode add a seconds attribute. Ex. [hms_testimonials_rotating seconds=10]

= Can I have multiple rotating sidebar widgets or shortcodes =

Yes, you can have multiple rotating widgets or shortcodes. Each one has it's own identifier so they don't conflict with each other.

= How many groups can I create? =

There is no limit.

= Can a testimonial be in more than one group? =

Yes, you can assign a testimonial to as many groups as you want

= How do you assign a testimonial to a group? =

Go to Testimonials. Then click the ID of the testimonial. On the right hand side you will see "Groups". This is where you select what group(s) you want your testimonial assigned to.

== Screenshots ==

1. All Testimonials
2. Add a Testimonial
3. Sidebar demonstration

== Changelog ==

= 2.0.7 =
* Fixed a bug with WordPress MultiSite not showing testimonials in wp-admin
* Fixed a few bugs with SQL queries regarding multisite functionality
* Added new option to hms_testimonials_show_rotating and rotating widget to not automatically start rotating. For the shortcode add autostart="false"  For the widget there is a checkbox.
* Added new option to hms_testimonials_show_rotating and rotating widget to change the position of the Prev, Pause/Play and Next links. Options are top, bottom, or both. It defaults to bottom. For the shortcode use link_position="top" or any of the 3 options. The widget now has a drop down to select the position.

= 2.0.6 =
* Added filters to the templates. hms_testimonials_system_id, hms_testimonials_system_testimonial, hms_testimonials_system_source, hms_testimonials_system_date, hms_testimonials_system_url, hms_testimonials_system_image, and all custom fields fall under hms_testimonials_cf_ with their name lower cased.

An example to limit a testimonial to 100 characters:

function testimonial_go($text) {
	return substr($text, 0, 100);
}
add_filter('hms_testimonials_system_testimonial', 'testimonial_go');

If you change the code to the following, you can get access to the testimonial array:

function testimonial_go($text, $testimonial) {
	return substr($text, 0, 100);
}
add_filter('hms_testimonials_system_testimonial', 'testimonial_go', 10, 2);

= 2.0.5 =
* Fixed a bug with pagination when not using permalinks. This also fixes an issue if you had any other query arguments in your url as well.

= 2.0.4 =
* Fixed a few issues noticed by Brit Albritton on the forums.

= 2.0.3 =
* Added filters to change the text for the public testimonial form fields.  hms_testimonials_sc_name, hms_testimonials_sc_website, hms_testimonials_sc_testimonial and hms_testimonials_sc_submit

= 2.0.2 =
* Now Catches an exception thrown on the testimonial date field when using a date format that DateTime doesn't like. A friendlier message is now shown.
* Added the jQuery UI datepicker to the testimonial date.

= 2.0.1 =
* Fixed a bug with templates not saving correctly

= 2.0 =
* Create custom fields
* Create your own templates for displaying your testimonials
* Add an image from the gallery to your testimonial
* Added the ability to change the collation of the testimonial fields. Useful for languages that require UTF-8
* Some settings moved to an advanced settings page
* Miscellaneous bug fixes
* Set the date format

= 1.7.1 =
* Miscellaneous bug fixes
* Added "order" and "direction" attributes to the [hms_testimonials] shortcode. Valid items for "order" are name,testimonial,url,testimonial_date, and display_order. Valid directions are ASC and DESC
* Added row classes to the form.

= 1.7 =
* Added the TinyMCE editor to the testimonial textarea
* Added a testimonial date field.
* Added more templates to account for the date field
* Added new options for the [hms_testimonials] shortcode to limit the results and paginate them. These are limit, start, next, prev, location.

= 1.6.2 =
* Fixed bug where new line breaks were not being added in some instances.

= 1.6.1 =
* Added a template attribute to the shortcodes and widgets to specify the order of the testimonial, author and url.
* Updated documentation to reflect the new template attribute.

= 1.6 =
* Added Prev,Pause(Play), and Next links to the rotating shortcode and widget.
* Updated the shortcode widget form to set the text for the links.
* Updated the documentation page to reflect these new options.

= 1.5 =
* Added a new shortcode ( [hms_testimonials_form] ) to allow website visitors to submit testimonials.
* Added reCAPTCHA settings (enable/disable, public/private keys) to the settings for the form
* Added a setting to allow or disallow a moderator to access the settings pages
* New testimonials have a display order set to put them at the end of the list

= 1.4.1 =
* Each testimonial now sits in a div container. Each part also sits in a container. testimonial, author and url classes have been added to each part.
* Added settings to show the URLs of testimonials as an active link and whether or not to add a nofollow relationship on them.

= 1.4 =
* Moved admin pages to a class. This helped decrease the amount of queries to the database and removed global functions
* Added a rotating testimonial shortcode. Use with [hms_testimonials_rotating group=1 seconds=6]
* Added custom created roles to the drop downs
* Added a moderator role setting
* Added the ability to limit the number of testimonials a can create.

= 1.3 =
* Added the ability to allow users of lesser roles add / edit their own testimonials. The administrator email address will receive a notification for all new and updated testimonials.

= 1.2 =
* Fixed the slashes being added in front of quotes and apostrophes in your testimonials.

= 1.1 =
* Plugin is released to WordPress
