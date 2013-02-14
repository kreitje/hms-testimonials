<?php

function hms_testimonials_show( $atts ) {
	global $wpdb, $blog_id;

	$settings = get_option('hms_testimonials');

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

		$ret = '<div class="hms-testimonial-container hms-testimonial-single">
			<div class="testimonial">'.$get['testimonial'].'</div><div class="author">'.nl2br($get['name']).'</div>';
		if ($get['url'] != '') {
			if (substr($get['url'],0,4)!='http')
				$href = 'http://'.$get['url'];
			else
				$href = $get['url'];

			if ($settings['show_active_links'] == 1) {
				$nofollow = '';

				if ($settings['active_links_nofollow'] == 1)
					$nofollow = 'rel="nofollow"';

				$ret .= '<div class="url"><a '.$nofollow.' href="'.$href.'" target="_blank">'.$href.'</a></div>';
			} else {
				$ret .= '<div class="url">'.$href.'</div>';
			}
		}

		$ret .= '</div>';
		


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

			$ret .= '<div class="hms-testimonial-container">
						<div class="testimonial">'.$g['testimonial'].'</div>
						<div class="author">'.nl2br($g['name']).'</div>';
			if ($g['url'] != '') {
				if (substr($g['url'],0,4)!='http')
					$href = 'http://'.$g['url'];
				else
					$href = $g['url'];


				if ($settings['show_active_links'] == 1) {
					$nofollow = '';

					if ($settings['active_links_nofollow'] == 1)
						$nofollow = 'rel="nofollow"';

					$ret .= '<div class="url"><a '.$nofollow.' href="'.$href.'" target="_blank">'.$href.'</a></div>';
				} else {
					$ret .= '<div class="url">'.$href.'</div>';
				}

			}

			$ret .= '</div>';


		}

		$ret .= '</div>';
	}

	return $ret;

}

function hms_testimonials_show_rotating( $atts ) {
	global $wpdb, $blog_id;

	$settings = get_option('hms_testimonials');

	extract(shortcode_atts(
		array(
			'group' => 0,
			'seconds' => 6
		), $atts
	));

	$random_string = '';
	$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    for ($i = 0; $i < 5; $i++)
    	$random_string .= $characters[rand(0, strlen($characters))];


    if ($group == 0)
		$get = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `display` = 1 AND `blog_id` = ".(int)$blog_id." ORDER BY `display_order` ASC ", ARRAY_A);
	else
		$get = $wpdb->get_results("SELECT t.* FROM `".$wpdb->prefix."hms_testimonials` AS t INNER JOIN `".$wpdb->prefix."hms_testimonials_group_meta` AS m ON m.testimonial_id = t.id WHERE m.group_id = ".(int)$group." AND t.blog_id = ".$blog_id." AND t.display = 1 ORDER BY m.display_order ASC", ARRAY_A);



	$return = '<div id="hms-testimonial-sc-'.$random_string.'" class="hms-testimonials-rotator">';
		$return .= '<div class="hms-testimonial-container">
						<div class="testimonial">'.nl2br($get[0]['testimonial']).'</div>
						<div class="author">'.nl2br($get[0]['name']).'</div>';
		
		if ($get[0]['url']!='') {
			if (substr($get[0]['url'],0,4)!='http')
				$href = 'http://'.$get[0]['url'];
			else
				$href = $get['url'];

			if ($settings['show_active_links'] == 1) {
				$nofollow = '';

				if ($settings['active_links_nofollow'] == 1)
					$nofollow = 'rel="nofollow"';

				$return .= '<div class="url"><a '.$nofollow.' href="'.$href.'" target="_blank">'.$href.'</a></div>';
			} else {
				$return .= '<div class="url">'.$href.'</div>';
			}

		}

		$return .= '</div>';
	$return .= '</div>';


	$return .= '<div style="display:none;" id="hms-testimonial-sc-list-'.$random_string.'">';
		
	foreach($get as $g) {
		$return .= '<div class="hms-testimonial-container">
						<div class="testimonial">'.nl2br($g['testimonial']).'</div>
						<div class="author">'.nl2br($g['name']).'</div>';
		if ($g['url']!='') {
			if (substr($g['url'],0,4)!='http')
				$href = 'http://'.$g['url'];
			else
				$href = $g['url'];

			if ($settings['show_active_links'] == 1) {
				$nofollow = '';

				if ($settings['active_links_nofollow'] == 1)
					$nofollow = 'rel="nofollow"';

				$return .= '<div class="url"><a '.$nofollow.' href="'.$href.'" target="_blank">'.$href.'</a></div>';
			} else {
				$return .= '<div class="url">'.$href.'</div>';
			}

		}
		
		$return .= '</div>';	
	}
	
	$return .= '</div>';

	$return .= <<<JS
	<script type="text/javascript">
		var index_{$random_string} = 1;
		jQuery(document).ready(function() {
				setInterval(function() {
					var nextitem = jQuery("#hms-testimonial-sc-list-{$random_string} .hms-testimonial-container").get(index_{$random_string});
					if (nextitem == undefined) {
						index_{$random_string} = 0;
						var nextitem = jQuery("#hms-testimonial-sc-list-{$random_string} .hms-testimonial-container").get(0);
					}
					jQuery("#hms-testimonial-sc-{$random_string}").fadeOut('slow', function(){ jQuery(this).html(nextitem.innerHTML)}).fadeIn();
					index_{$random_string} = index_{$random_string} + 1;
				}, {$seconds}000);
			});
			
	</script>
JS;

	return $return;
}