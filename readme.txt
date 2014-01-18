=== Plugin Name ===
Contributors: kreitje
Donate link: http://hitmyserver.com/wordpress-plugins-donations/
Tags: testimonials, reviews, client reviews, what they are saying
Requires at least: 3.5
Tested up to: 3.8
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display your customer testimonials on pages or posts. Use groups to organize and display specific testimonnials on specific pages.


== Description ==

** Now with Akismet anti-spam integration! **

* Create your own templates using drag and drop allowing you to display testimonials in different ways through your site.
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

For version 1.x to 2.x, go into Testimonials -> Templates and create new templates.

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

= 2.2.4 =
* You can not set the unit measurement of pixels or percent for the height and width of images.
* Added filters to override the custom field text

= 2.2.3 =
* Added new setting to redirect the hms_testimonials_form shortcode to another page on submission.
* Added redirect_url attribute to hms_testimonials_form to override the setting.
* Added the hms_testimonials_submitted_success filter to override the successful submission message
* Added filters to override error messages of custom fields. Filters follow the hms_testimonials_required_cf_{field id} format.
* Added filter to override invalid email on custom fields. Filter follows the hms_testimonials_email_cf_{field id} format.
* Added filter names to the Custom Fields page.

= 2.2.2 =
* Fixed a bug where the tables were not being created in a multi site.

= 2.2.1 =
* I forgot to add rotator.js to svn so it didn't get uploaded

= 2.2.0 =
* Rotating javascript has been movied to it's own file instead of inline
* Added the following filters: hms_testimonials_sc_error_token, hms_testimonials_sc_error_name, hms_testimonials_sc_error_testimonial, hms_testimonials_sc_error_image, hms_testimonials_sc_error_website, hms_testimonials_sc_error_captcha

= 2.1.15 =
* I broke reCAPTCHA so I fixed it.

= 2.1.14 =
* Fixed a notice in the group area
* Fixed reCaptcha method name collisions
* Adjust the number of minutes for flood control on the form shortcode
* Added image upload to form
* Added setting to turn image upload on or off. Defaults to off for security reasons
* Added setting to turn website field on or off.

= 2.1.13 =
* Fixed a bug in the testimonial form shortcode where a blank space was added to the name field.

= 2.1.12 =
* Fixed a bug where the session may be started after output thus throwing an error.

= 2.1.11 =
* Added multiple anti spam measures for the shortcode form.
* Fixed a PHP notice in the templates screen.
* Moved jquery-ui to only show on testimonial pages

= 2.1.10 =
* The js.php script now allows you to load rotating testimonials.

= 2.1.9 =
* Fixed a PHP notice dealing with custom fields
* Added random to the order attributes. It works the same as rand
* Changed email custom field on the form shortcode to be a type of email instead of text
* Added review microdata to testimonials. See: http://schema.org/Review

= 2.1.8 =
* hms_testimonials_form shortcode now escapes POST data.
* Fixed a bug where input did not have slashes stripped when an error occured.

= 2.1.7 =
* Fixed some notces that are displayed when debugging is turned on.
* When submitting a URL through the form shortcode it will auto append http:// to the website if it doesn't exist.

= 2.1.6 =
* Fixed a bug in multi-site mode where templates keep getting created on every page load

= 2.1.5 =
* Fixed a bug when generating a random string in the widgets and shortcodes

= 2.1.4 =
* Fixed a bug with pagination and determining the current page
* Fixed some notices / deperaction messages in HTMLPurifier

= 2.1.3 =
* Fixed a bug where email custom fields were required despite the "Required" checkbox not being checked.

= 2.1.2 =
* Fixed a bug with HTMLPurifier not working on some hosts.

= 2.1.1 =
* Moved the rotating javascript to the footer

= 2.1.0 =
* Added Read More settings
* Added a new field to testimonials to specify your own read more link on an indvidual basis.

= 2.0.12 =
* Template output now filters more data

= 2.0.11 =
* Fixed several CSRF (Cross Site Request Forgery) vulnerabilities. Thank you RogueCoder for finding them and disclosing them responsibly.
* Fixed several XSS (Cross Site Scripting) vulnerabilities. Thank you RogueCoder for finding them and disclosing them responsibly.
* Added HTMLPurifier library

= 2.0.10 =
* Fixed a bug where content was being sent before a redirect causing a blank page.

= 2.0.9 =
* Added the ability to toggle between using a div or a blockquote for the testimonial text

= 2.0.8 =
* Added word_limit to shortcodes and widgets. This limits the number of words shown on a testimonial.
* Added char_limit to shortcodes and widgets. This limits the number of characters shown on a testimonial.
* Allow loading testimonials through javascript. You MUST enable this option in the advanced settings area before using it.
* Updated documentation

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
