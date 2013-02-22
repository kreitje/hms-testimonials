<?php

function hms_testimonials_form( $atts ) {
	global $wpdb, $blog_id, $current_user;
	get_currentuserinfo();


	$settings = get_option('hms_testimonials');

	require_once HMS_TESTIMONIALS . 'recaptchalib.php';
	
	$ret = '';
	if (isset($_POST) && isset($_POST['hms_testimonial']) && ($_POST['hms_testimonial'] == 1)) {
		$errors = array();

		if (!isset($_POST['hms_testimonials_name']) || (($name = trim(@$_POST['hms_testimonials_name'])) == ''))
			$errors[] = 'Please enter your name.';

		if (!isset($_POST['hms_testimonials_testimonial']) || (($testimonial = trim(@$_POST['hms_testimonials_testimonial'])) == ''))
			$errors[] = 'Please enter your testimonial.';

		$website = '';
		if (isset($_POST['hms_testimonials_website']) && ($_POST['hms_testimonials_website'] != '')) {
			$website = $_POST['hms_testimonials_website'];

			if (!filter_var($website, FILTER_VALIDATE_URL))
				$errors[] = 'Please enter a valid URL.';
			
		}

		if ($settings['use_recaptcha'] == 1) { 
			$resp = recaptcha_check_answer ($settings['recaptcha_privatekey'], $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

        	if (!$resp->is_valid) {
        		switch($resp->error) {
        			case 'incorrect-captcha-sol':
        				$errors[] = 'You entered an incorrect captcha. Please try again.';
        			break;
        			default:
        				$errors[] = 'An error occured with your captcha. ( '.$resp->error.' )';
        			break;
        		}
        	}
        }


		if (count($errors)>0)
			$ret .= '<div class="hms_testimonial_errors">'.join('<br />', $errors).'</div><br />';
		else {

			$display_order = $wpdb->get_var("SELECT `display_order` FROM `".$wpdb->prefix."hms_testimonials` ORDER BY `display_order` DESC LIMIT 1");

			$wpdb->insert($wpdb->prefix."hms_testimonials", 
				array(
					'blog_id' => $blog_id, 'user_id' => $current_user->ID, 'name' => $name, 
					'testimonial' => $testimonial, 'display' => 0, 'display_order' => ($display_order+1),
					'url' => $website, 'created_at' => date('Y-m-d h:i:s')
				)
			);

			$id = $wpdb->insert_id;

			$visitor_name = 'A visitor ';
			if ($current_user->ID != 0)
				$visitor_name = $current_user->user_login.' ';

			$message = $visitor_name.' has added a testimonial to your site '.get_bloginfo('name')."\r\n\r\n";
			$message .= 'Name: '. $name."\r\n";
			$message .= 'Website: '.$website."\r\n";
			$message .= 'Testimonial: '. $testimonial."\r\n";
			$message .= "\r\n\r\n";
			$message .= 'View this testimonial at '.admin_url('admin.php?page=hms-testimonials-view&id='.$id);

			wp_mail(get_bloginfo('admin_email'), 'New Visitor Testimonial Added to '.get_bloginfo('name'), $message);
				
			if (!isset($settings['guest_submission_redirect']) || ($settings['guest_submission_redirect'] == ''))
				return '<div class="hms_testimonial_success">Your testimonial has been submitted.</div>';
			else
				die(header('Location: '.$settings['guest_submission_redirect']));
		}

	} else {
		$name = $current_user->user_firstname.' '.$current_user->user_lastname;
		$testimonial = '';
		$website = '';
	}


	$ret .= <<<HTML
<form method="post">
<input type="hidden" name="hms_testimonial" value="1" />
	<table class="hms-testimonials-form">
		<tr>
			<td>Name</td>
			<td><input type="text" name="hms_testimonials_name" value="{$name}" />
		</tr>
		<tr>
			<td>Website</td>
			<td><input type="text" name="hms_testimonials_website" value="{$website}" />
		</tr>
		<tr>
			<td valign="top">Testimonial</td>
			<td><textarea name="hms_testimonials_testimonial" rows="5" style="width:99%;">{$testimonial}</textarea></td>
		</tr>
HTML;

	if ($settings['use_recaptcha'] == 1) { 
		$ret .= '<tr>
					<td> </td>
					<td>'.recaptcha_get_html($settings['recaptcha_publickey'], null).'</td>
				</tr>';
	}

	$ret .= <<<HTML
		<tr>
			<td>&nbsp;</td>
			<td><input type="submit" value="Submit Testimonial" /></td>
		</tr>
	</table>
</form>
HTML;

	return $ret;
}

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
			'seconds' => 6,
			'show_links' => false,
			'link_prev' => '&laquo;',
			'link_next' => '&raquo;',
			'link_pause' => 'Pause',
			'link_play' => 'Play'
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
	if ($show_links && $show_links != "false")
		$return .= '<div class="controls"><a href="#" class="prev">'.$link_prev.'</a> <a href="#" class="playpause pause">'.$link_pause.'</a> <a href="#" class="next">'.$link_next.'</a></div>';
	
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
		var timeout_{$random_string} = null;
		var play_{$random_string} = 1;
		jQuery(document).ready(function() {
				si_{$random_string}();

				jQuery("#hms-testimonial-sc-{$random_string} .controls .playpause").click(function() {
					if (play_{$random_string} == 1) {
						jQuery(this).text('{$link_play}').removeClass('pause').addClass('play');
						clearInterval(timeout_{$random_string});
						play_{$random_string} = 0;
					} else {
						jQuery(this).text('{$link_pause}').removeClass('play').addClass('pause');
						si_{$random_string}();
						play_{$random_string} = 1;
					}

					return false;
				});

				jQuery("#hms-testimonial-sc-{$random_string} .controls .prev").click(function() {

					var new_index = (index_{$random_string} - 2);
					
					if (new_index < 0) {
						new_index = (jQuery("#hms-testimonial-sc-list-{$random_string} .hms-testimonial-container").length - 1);
					}
					console.log(new_index);

					var nextitem = jQuery("#hms-testimonial-sc-list-{$random_string} .hms-testimonial-container").get(new_index);
					if (nextitem == undefined) {
						index_{$random_string} = 0;
						var nextitem = jQuery("#hms-testimonial-sc-list-{$random_string} .hms-testimonial-container").get(0);
					}
					jQuery("#hms-testimonial-sc-{$random_string} .hms-testimonial-container").fadeOut('slow', function(){ jQuery(this).html(nextitem.innerHTML)}).fadeIn();
					index_{$random_string} = new_index + 1;

					if (play_{$random_string} == 1) {
						clearInterval(timeout_{$random_string});
						si_{$random_string}();
					}
					return false;

				});
				jQuery("#hms-testimonial-sc-{$random_string} .controls .next").click(function() {
					var nextitem = jQuery("#hms-testimonial-sc-list-{$random_string} .hms-testimonial-container").get(index_{$random_string});
					if (nextitem == undefined) {
						index_{$random_string} = 0;
						var nextitem = jQuery("#hms-testimonial-sc-list-{$random_string} .hms-testimonial-container").get(0);
					}
					jQuery("#hms-testimonial-sc-{$random_string} .hms-testimonial-container").fadeOut('slow', function(){ jQuery(this).html(nextitem.innerHTML)}).fadeIn();
					index_{$random_string} = index_{$random_string} + 1;

					if (play_{$random_string} == 1) {
						clearInterval(timeout_{$random_string});
						si_{$random_string}();
					}
					return false;
				});
		});

		function si_{$random_string}() {
			timeout_{$random_string} = setInterval(function() {
				var nextitem = jQuery("#hms-testimonial-sc-list-{$random_string} .hms-testimonial-container").get(index_{$random_string});
				if (nextitem == undefined) {
					index_{$random_string} = 0;
					var nextitem = jQuery("#hms-testimonial-sc-list-{$random_string} .hms-testimonial-container").get(0);
				}
				jQuery("#hms-testimonial-sc-{$random_string} .hms-testimonial-container").fadeOut('slow', function(){ jQuery(this).html(nextitem.innerHTML)}).fadeIn();
				index_{$random_string} = index_{$random_string} + 1;
			}, {$seconds}000);
		}
			
	</script>
JS;

	return $return;
}