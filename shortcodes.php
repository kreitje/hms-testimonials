<?php

function hms_testimonials_show( $atts ) {
	global $wpdb, $blog_id;

	extract(shortcode_atts(
		array(
			'id' => 0,
			'group' => 0
		), $atts
	));



	if ($id != 0) {

		$get = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `blog_id` = ".(int)$blog_id." AND `id` = ".(int)$id." AND `display` = 1 LIMIT 1", ARRAY_A);
		if (count($get)<1)
			return '';

		$ret = '<div class="hms-testimonial-single"><div class="testimonial">'.$get['testimonial'].'</div><span class="author">'.nl2br($get['name']);
		if ($get['url'] != '') {
			if (substr($get['url'],0,4)!='http')
				$href = 'http://'.$get['url'];
			else
				$href = $get['url'];

			$ret .= '<br /><a rel="nofollow" href="'.$href.'" target="_blank">'.$get['url'].'</a>';
		}

		$ret .= '</span></div>';
		


	} else {

		if ($group != 0) {
			$get = $wpdb->get_results("SELECT t.* FROM `".$wpdb->prefix."hms_testimonials` AS t 
									INNER JOIN `".$wpdb->prefix."hms_testimonials_group_meta` AS m
										ON m.testimonial_id = t.id
									WHERE t.blog_id = ".(int)$blog_id." AND t.display = 1 AND m.group_id = ".(int)$group." ORDER BY m.display_order ASC", ARRAY_A);
		} else {
			$get = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `blog_id` = ".(int)$blog_id." AND `display` = 1 ORDER BY `display_order` ASC", ARRAY_A);
		}


		if (count($get)<1)
			return '';

		$ret = '<div class="hms-testimonial-group">';
		foreach($get as $g) {

			$ret .= '<div class="hms-testimonial-single">
						<div class="testimonial">'.$g['testimonial'].'</div>
						<span class="author">'.nl2br($g['name']);
			if ($g['url'] != '') {
				if (substr($g['url'],0,4)!='http')
					$href = 'http://'.$g['url'];
				else
					$href = $g['url'];

				$ret .= '<br /><a rel="nofollow" href="'.$href.'" target="_blank">'.$g['url'].'</a>';
			}

			$ret .= '</span></div>';


		}

		$ret .= '</div>';
	}

	return $ret;

}