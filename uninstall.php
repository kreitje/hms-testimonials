<?php
global $wpdb;

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

if (is_multisite()) {
    $blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
    if ($blogs) {
        foreach($blogs as $blog) {
            switch_to_blog($blog['blog_id']);
            delete_option('hms_testimonials_db_version');
        }
        restore_current_blog();
    }
} else {
    delete_option('hms_testimonials_db_version');
}


$wpdb->query("DROP TABLE `".$wpdb->prefix."hms_testimonials`, `".$wpdb->prefix."hms_testimonials_groups`, `".$wpdb->prefix."hms_testimonials_group_meta`, `".$wpdb->prefix."hms_testimonials_cf`,`".$wpdb->prefix."hms_testimonials_cf_meta`,`".$wpdb->prefix."hms_testimonials_templates`");
