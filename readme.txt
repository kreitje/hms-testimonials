=== Plugin Name ===
Contributors: kreitje
Donate link: hitmyserver.com/wordpress-plugins/
Tags: testimonials, client reviews
Requires at least: 3.0.1
Tested up to: 3.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display your customer testimonials on pages or posts. Our new rotating shortcode allows you to display 1 quote at a time and rotate them after x amount of seconds. Use our widgets to display them in your sidebars and even have them rotate through. Use the settings area to allow your users to write their own testimonials on your site.


== Description ==

HMS Testimonials offers you three shortcodes and two widgets. The first shortcode allows you to show all of your testimonials, a group of testimonials or just a single testimonial. The second shortcode allows you to show all or a group of testimonials that rotate through one at a time. Our third shortcode will display a form on a page or post to alow your visitors to submit testimonials. This shortcode can be placed inside a page or a blog post. If your theme uses sidebars you can use one or both of the widgets we offer. The first widget does the same as our shortcode. It shows all, a group or just one testimonial. Our second widget rotates through the selected testimonials one at a time. After a few seconds it will fade out the old and fade in the new testimonial.

Create groups to display specific testimonials on different pages. You can change the order they are shown in using a simple drag and drop method.

Use the settings to allow access for your users to write testimonials about your company.
    
**BIG Changes**

* Add/Edit screens of testimonials now have the full WordPress Editor at your disposal. You **cannot** use shortcodes inside these.
* Added a testimonial date field
* The [hms_testimonials] attribute now supports pagination.

== Installation ==

1. Upload the `hms-testimonials` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Testimonals in the sidebar and add them in.


== Upgrade Notice ==

If upgrading from version 1.3 to a new version please go to the settings and resave them.

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
