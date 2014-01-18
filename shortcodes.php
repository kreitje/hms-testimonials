<?php

function hms_testimonials_form( $atts ) {
	global $wpdb, $blog_id, $current_user;
	get_currentuserinfo();

	$sc_atts = shortcode_atts( array('redirect_url' => ''), $atts );

	$settings = get_option('hms_testimonials');
	$allowed = array( 'image/jpg', 'image/jpeg', 'image/gif', 'image/png' );

	if (!isset($settings['form_show_url']))
		$settings['form_show_url'] = 1;

	if (!isset($settings['form_show_upload']))
		$settings['form_show_upload'] = 0;

	$url = '';
	if (isset($settings['redirect_url']) && $settings['redirect_url'] != '')
		$url = $settings['redirect_url'];
	
	if ($sc_atts['redirect_url'] != '')
		$url = $sc_atts['redirect_url'];

	/**
	 * Check if the Akismet plugin is enabled and valid. If so, use that.
	 **/
	$use_akismet = false;
	if ( function_exists( 'akismet_verify_key' ) && function_exists( 'akismet_get_key')) {
		
		$key = akismet_get_key();
		if ($key !== false && $key != '') {
			$response = akismet_verify_key($key);
			if ($response == 'valid')
				$use_akismet = true;
		}

		if ($use_akismet)
			require_once HMS_TESTIMONIALS . 'akismet.php';
	}


	if (isset($_SESSION['hms_testimonials_submitted']) && $_SESSION['hms_testimonials_submitted'] != '' && 
		isset($settings['flood_limit']) && ($settings['flood_limit'] > 0)) {

		$time = $settings['flood_limit'] * 60;

		$timeup = ( ((int)@$_SESSION['hms_testimonials_flood_limit'] + $time) <= time());

		if (!$timeup)
			return '<div class="hms_testimonial_success">' . __('Your testimonial has been submitted.', 'hms-testimonials' ) . '</div>';
	}
	


	
	$fields = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials_cf` WHERE `blog_id` = ".$blog_id." ORDER BY `name` ASC");

	$field_count = count($fields);

	require_once HMS_TESTIMONIALS . 'recaptchalib.php';
	
	$ret = '';
	if (isset($_POST) && isset($_POST['hms_testimonial']) && ($_POST['hms_testimonial'] == 1)) {

		if (! wp_verify_nonce(@$_REQUEST['_wpnonce'], 'hms-testimonials-form') ) die('Security check stopped this request. Not all required fields were entered. <a href="'.$_SERVER['REQUEST_URI'].'">Go back and try again.</a>');

		if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );


		$_POST = stripslashes_deep($_POST);

		$errors = array();

		if (isset($_POST['hms_testimonials_security_token']) && ($_POST['hms_testimonials_security_token'] != ''))
			$errors[] = apply_filters('hms_testimonials_sc_error_token', __('Invalid request.', 'hms-testimonials') );

		if (!isset($_POST['hms_testimonials_name']) || (($name = trim(@$_POST['hms_testimonials_name'])) == ''))
			$errors[] = apply_filters('hms_testimonials_sc_error_name', __('Please enter your name.', 'hms-testimonials' ) );

		if (!isset($_POST['hms_testimonials_testimonial']) || (($testimonial = trim(@$_POST['hms_testimonials_testimonial'])) == ''))
			$errors[] = apply_filters('hms_testimonials_sc_error_testimonial', __('Please enter your testimonial.', 'hms-testimonials' ) );

		if (isset($_FILES['hms_testimonials_image']) && ($_FILES['hms_testimonials_image']['size'] > 0) && $settings['form_show_upload'] == 1) {

			$get_file_type = wp_check_filetype( basename($_FILES['hms_testimonials_image']['name'] ) );
			$uploaded_type = $get_file_type['type'];

			if (!in_array($uploaded_type, $allowed))
				$errors[] = apply_filters('hms_testimonials_sc_error_image', __('You have uploaded an invalid file type.', 'hms-testimonials') );

		}

		$email = '';
		if ($field_count>0) {
			foreach($fields as $f) {

				if ($f->isrequired == 1 && (!isset($_POST['hms_testimonials_cf'][$f->id]) || trim($_POST['hms_testimonials_cf'][$f->id])=='')) {
					$errors[] = apply_filters( 'hms_testimonials_required_cf_' . $f->id, sprintf( __('%1$s is a required field.', 'hms-testimonials' ), $f->name ) );
					continue;
				}

				switch($f->type) {
					case 'email':
						if (isset($_POST['hms_testimonials_cf'][$f->id]) && ($_POST['hms_testimonials_cf'][$f->id] != '') && !filter_var($_POST['hms_testimonials_cf'][$f->id], FILTER_VALIDATE_EMAIL))
							$errors[] = apply_filters('hms_testimonials_email_cf_' . $f->id, sprintf( __('Please enter a valid email for the %1$s field.', 'hms-testimonials'), $f->name ) );

						$email = $_POST['hms_testimonials_cf'][$f->id];
					break;
				}

				$cf_{$f->id} = $_POST['hms_testimonials_cf'][$f->id];

			}
		}

		$website = '';
		if (isset($_POST['hms_testimonials_website']) && ($_POST['hms_testimonials_website'] != '') && $settings['form_show_url'] == 1) {
			$website = $_POST['hms_testimonials_website'];

			$website_parts = parse_url($website);
			if ($website_parts !== false && !isset($website_parts['scheme']))
				$website = 'http://' . $website;
			

			if (!filter_var($website, FILTER_VALIDATE_URL))
				$errors[] = apply_filters('hms_testimonials_sc_error_website', __('Please enter a valid URL.', 'hms-testimonials' ) );
			
		}

		if ($settings['use_recaptcha'] == 1) { 
			$resp = hms_tesitmonial_recaptcha_check_answer($settings['recaptcha_privatekey'], $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

        	if (!$resp->is_valid) {
        		switch($resp->error) {
        			case 'incorrect-captcha-sol':
        				$errors[] = apply_filters('hms_testimonials_sc_error_captcha', __('You entered an incorrect captcha. Please try again.', 'hms-testimonials' ) );
        			break;
        			default:
        				$errors[] = sprintf( __('An error occured with your captcha. ( %1$s )', 'hms-testimonials' ), $resp->error );
        			break;
        		}
        	}
        }

		if (count($errors)>0)
			$ret .= '<div class="hms_testimonial_errors">'.join('<br />', $errors).'</div><br />';
		else {
			$is_spam = false;

			if ($use_akismet) {
				$akismet = new HMS_Testimonials_Akismet(get_option('home'), $key);
				$akismet->setCommentAuthor( $name );

				if ($email != '')
					$akismet->setCommentAuthorEmail( $email );

 				if ($website != '')
 					$akismet->setCommentAuthorURL( $website );

 				$akismet->setCommentContent( $testimonial );
 				$akismet->setPermalink( get_permalink() );
     			if($akismet->isCommentSpam())
     				$is_spam = true;

			}

			$display_order = $wpdb->get_var("SELECT `display_order` FROM `".$wpdb->prefix."hms_testimonials` ORDER BY `display_order` DESC LIMIT 1");

			if (!$is_spam) {

				$attach_id = 0;
				/**
				 * If there is an image have WP move it where it goes
				 **/
				if (isset($_FILES['hms_testimonials_image']) && ($_FILES['hms_testimonials_image']['size'] > 0) && $settings['form_show_upload'] == 1) {
					
					$upload_overrides = array( 'test_form' => false );

					$uploaded_file = wp_handle_upload($_FILES['hms_testimonials_image'], $upload_overrides);

					if (isset($uploaded_file['file'])) {

						$name_location = $uploaded_file['file'];
						$file_title_for_library = strip_tags($name). ' Testimonial Image';

						$attachment = array(
							'post_mime_type' => $uploaded_type,
							'post_title' => 'Uploaded image ' . addslashes( $file_title_for_library ),
							'post_content' => '',
							'post_status' => 'inherit'
						);

						$attach_id = wp_insert_attachment( $attachment, $name_location );
						require_once(ABSPATH . "wp-admin" . '/includes/image.php');

						$attach_data = wp_generate_attachment_metadata( $attach_id, $name_location );
						wp_update_attachment_metadata($attach_id,  $attach_data);

					}

				}

				$wpdb->insert($wpdb->prefix."hms_testimonials", 
					array(
						'blog_id' => $blog_id, 'user_id' => $current_user->ID, 'name' => strip_tags($name), 
						'testimonial' => strip_tags($testimonial), 'display' => 0, 'display_order' => ($display_order+1),
						'image' => $attach_id,
						'url' => $website, 'created_at' => date('Y-m-d h:i:s'), 'testimonial_date' => date('Y-m-d h:i:s')
					)
				);

				$id = $wpdb->insert_id;


			}

			$e_message = '';
			if ($field_count > 0) {
				foreach($fields as $f) {

					if (isset($_POST['hms_testimonials_cf'][$f->id]) && !$is_spam) {
						$wpdb->insert($wpdb->prefix."hms_testimonials_cf_meta", 
							array(
								'testimonial_id' => $id, 'key_id' => $f->id, 'value' => trim($_POST['hms_testimonials_cf'][$f->id])
							)
						);
					}
					$e_message .= $f->name.': '.@$_POST['hms_testimonials_cf'][$f->id]."\r\n";
				}

			}


			$visitor_name = __('A visitor', 'hms-testimonials' ) .' ';
			if ($current_user->ID != 0)
				$visitor_name = $current_user->user_login.' ';

			$message = sprintf( __('%1$s as added a testimonial to your site %2$s', 'hms-testimonials' ), $visitor_name, get_bloginfo('name'))."\r\n\r\n";

			if ($is_spam)
				$message .= __('This message has been detected as spam by Akismet. It has ** NOT ** been saved to your database.', 'hms-testimonials')."\r\n\r\n";

			if ($use_akismet)
				$message .= sprintf( __('Spam Status: %1$s', 'hms-testimonials' ), (($is_spam) ? 'Spam' : 'Not Spam'))."\r\n";
			
			$message .= sprintf( __('Name: %1$s', 'hms-testimonials' ), $name)."\r\n";
			$message .= sprintf( __('Website: %1$s', 'hms-testimonials' ), $website)."\r\n";
			$message .= sprintf( __('Testimonial: %1$s', 'hms-testimonials' ), $testimonial)."\r\n";

			$message .= $e_message;

			$message .= "\r\n\r\n";

			if (!$is_spam)
				$message .= sprintf( __('View this testimonial at %1$s', 'hms-testimonials' ), admin_url('admin.php?page=hms-testimonials-view&id='.$id));

			wp_mail(get_bloginfo('admin_email'), sprintf( __('New Visitor Testimonial Added to %1$s', 'hms-testimonials' ), get_bloginfo('name') ), $message);
			
			$_SESSION['hms_testimonials_submitted'] = 1;
			$_SESSION['hms_testimonials_flood_limit'] = time();

			if (!isset($settings['guest_submission_redirect']) || ($settings['guest_submission_redirect'] == '')) {
				if ($url == '')
					return apply_filters( 'hms_testimonials_submitted_success', '<div class="hms_testimonial_success">' . __('Your testimonial has been submitted.', 'hms-testimonials' ) . '</div>' );
				else {

					die(header('Location: ' . $url));
				}
			} else
				die(header('Location: '.$settings['guest_submission_redirect']));
		}

	} else {
		$name = trim( $current_user->user_firstname.' '.$current_user->user_lastname );
		$testimonial = '';
		$website = '';

		if ($field_count>0) {
			foreach($fields as $f)
				$cf_{$f->id} = '';
		}
	}

	/**
	 * Adding filters to the default fields. Their value defaults to the second parameter
	 **/

	$name_text = apply_filters('hms_testimonials_sc_name', __('Name', 'hms-testimonials' ));
	$website_text = apply_filters('hms_testimonials_sc_website', __('Website', 'hms-testimonials' ));
	$testimonial_text = apply_filters('hms_testimonials_sc_testimonial', __('Testimonial', 'hms-testimonials' ));
	$image_text = apply_filters('hms_testimonials_sc_image', __('Profile Picture', 'hms-testimonials'));
	$submit_text = apply_filters('hms_testimonials_sc_submit', __('Submit Testimonial', 'hms-testimonials' ));
	$nf = wp_nonce_field('hms-testimonials-form', '_wpnonce', true, false);

	$ret .= <<<HTML
<form method="post" enctype="multipart/form-data">
{$nf}
<input type="hidden" name="hms_testimonials_security_token" value="" />
<input type="hidden" name="hms_testimonial" value="1" />
	<table class="hms-testimonials-form">
		<tr class="name required">
			<td>{$name_text}</td>
			<td><input type="text" class="hms_testimonials_name" name="hms_testimonials_name" value="{$name}" />
		</tr>
HTML;

if ($settings['form_show_url'] == 1) {
	$ret .= <<<HTML
		<tr class="website">
			<td>{$website_text}</td>
			<td><input type="text" class="hms_testimonials_website" name="hms_testimonials_website" value="{$website}" />
		</tr>
HTML;
}
if ($settings['form_show_upload'] == 1) {
	$ret .= <<<HTML
		<tr class="image">
			<td>{$image_text}</td>
			<td><input type="file" class="hms_testimonials_image" name="hms_testimonials_image" value="" />
		</tr>
HTML;
}

	$ret .= <<<HTML
		<tr class="testimonial required">
			<td valign="top">{$testimonial_text}</td>
			<td><textarea name="hms_testimonials_testimonial" class="hms_testimonials_testimonial" rows="5" style="width:99%;">{$testimonial}</textarea></td>
		</tr>
HTML;

	
	if ($field_count>0) {
		foreach($fields as $f) {
			$name = strtolower( str_replace(' ', '_', $f->name) );
			$ret .= '
			<tr class="cf-'.$name.(($f->isrequired == 1) ? ' required' : '').'">
				<td valign="top">'.apply_filters( 'hms_testimonials_cf_text_' . $f->id, $f->name).'</td>
				<td>';

				$value = '';
				if ( isset( $cf_{$f->id} ))
					$value = $cf_{$f->id};

				switch($f->type) {
					case 'email':
						$ret .= '<input type="email" class="hms_testimonials_cf_'.$name.'" name="hms_testimonials_cf['.$f->id.']" value="'.$value.'" />';
					break;
					case 'text':
						$ret .= '<input type="text" class="hms_testimonials_cf_'.$name.'" name="hms_testimonials_cf['.$f->id.']" value="'.$value.'" />';
					break;
					case 'textarea':
						$ret .= '<textarea name="hms_testimonials_cf['.$f->id.']"  class="hms_testimonials_cf_'.$name.'" rows="5" style="width:99%;">'.$value.'</textarea>';
					break;
				}

			$ret .='	</td>
			</tr>';
		}
	}


	if ($settings['use_recaptcha'] == 1) { 
		$ret .= '<tr>
					<td> </td>
					<td>'.hms_tesitmonial_recaptcha_get_html($settings['recaptcha_publickey'], null).'</td>
				</tr>';
	}

	$ret .= <<<HTML
		<tr>
			<td>&nbsp;</td>
			<td><input type="submit" value="{$submit_text}" /></td>
		</tr>
	</table>
</form>
HTML;

	return $ret;
}

function hms_testimonials_show( $atts ) {
	global $wpdb, $blog_id;

	$order_by = array('id', 'name','testimonial','url','testimonial_date','display_order', 'image', 'rand', 'random');

	$settings = get_option('hms_testimonials');

	extract(shortcode_atts(
		array(
			'id' => 0,
			'group' => 0,
			'template' => 1,
			'limit' => -1,
			'start' => 0,
			'prev' => '&laquo;',
			'next' => '&raquo;',
			'location' => 'both',
			'order' => 'display_order',
			'direction' => 'ASC',
			'word_limit' => 0,
			'char_limit' => 0,
			'readmore_link' => HMS_Testimonials::getInstance()->getOption('readmore_link', ''),
			'readmore_text' => HMS_Testimonials::getInstance()->getOption('readmore_text', '...'),
		), $atts
	));

	if (!in_array($order, $order_by)) $order = 'display_order';
	if ($order == 'rand' || $order == 'random') $order = 'RAND()';
	if ($direction != 'DESC') $direction = 'ASC';
	if ($start != 0) $start = (int)$start - 1;

	$options = array(
		'readmore_link' => $readmore_link,
		'readmore_text' => $readmore_text
	);

	$sql_limit = '';
	
	$pages = 0;
	$total_results = 0;
	$current_page = 1;

	if ($limit != -1) {

		if ($group != 0) {

			$get_count = $wpdb->get_results("SELECT t.* FROM `".$wpdb->prefix."hms_testimonials` AS t 
									INNER JOIN `".$wpdb->prefix."hms_testimonials_group_meta` AS m
										ON m.testimonial_id = t.id
									WHERE t.blog_id = ".(int)$blog_id." AND t.display = 1 AND m.group_id = ".(int)$group, ARRAY_A);
		} else {
			$get_count = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `blog_id` = ".(int)$blog_id." AND `display` = 1", ARRAY_A);
		}

		$total_results = count($get_count);
		$pages = ceil($total_results/$limit);

		/**
		 * If not set or is an invalid value make it the first page
		 **/
		if (!isset($_GET['hms_testimonials_page']) || (int)$_GET['hms_testimonials_page'] <= 1) {
			$current_page = 1;
			$new_start = $start;

		/**
		 * If the page number is set but greater than the number of pages, set it to the last page
		 **/
		} elseif ((int)$_GET['hms_testimonials_page'] > $pages) {
			$current_page = $pages;
			$new_start = (($current_page * $limit) - $limit) + $start;

		/**
		 * We are inbetween 1 and the maximum number of pages
		 **/
		} else {
			$current_page = (int)$_GET['hms_testimonials_page'];
			$new_start = (($current_page * $limit) - $limit) + $start;
		}

		$sql_limit = 'LIMIT '.intval($new_start).', '.intval($limit);
	}



	if ($id != 0) {

		$get = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `blog_id` = ".(int)$blog_id." AND `id` = ".(int)$id." AND `display` = 1 LIMIT 1", ARRAY_A);
		if (count($get)<1)
			return '';

		$ret = '<div class="hms-testimonial-container hms-testimonial-single hms-testimonial-'.$get['id'].' hms-testimonial-template-'.$template.'" itemprop="review" itemscope itemtype="http://schema.org/Review">';
			$ret .= HMS_Testimonials::template($template, $get, (int)$word_limit, (int)$char_limit, $options);
		$ret .= '</div>';
		


	} else {

		if ($group != 0) {
			if ($order == 'display_order')
				$order = 'm.display_order';
			elseif ($order != 'RAND()')
				$order = 't.'.$order;
			
			$get = $wpdb->get_results("SELECT t.* FROM `".$wpdb->prefix."hms_testimonials` AS t 
									INNER JOIN `".$wpdb->prefix."hms_testimonials_group_meta` AS m
										ON m.testimonial_id = t.id
									WHERE t.blog_id = ".(int)$blog_id." AND t.display = 1 AND m.group_id = ".(int)$group." ORDER BY ".$order." ".$direction." ".$sql_limit, ARRAY_A);
		} else {
			$get = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `blog_id` = ".(int)$blog_id." AND `display` = 1 ORDER BY ".$order." ".$direction." ".$sql_limit, ARRAY_A);
		}


		if (count($get)<1)
			return '';

		$ret = '<div class="hms-testimonial-group">';

		$paging = '';
		if ($pages > 1)
			$paging = hms_testimonials_build_pagination($current_page, $pages, $prev, $next);

		if ($paging != '' && ($location == 'top' || $location == 'both'))
				$ret .= '<div class="paging top">'.$paging.'</div>';

		foreach($get as $g) {

			$ret .= '<div class="hms-testimonial-container hms-testimonial-'.$g['id'].' hms-testimonial-template-'.$template.'" itemprop="review" itemscope itemtype="http://schema.org/Review">';

				$ret .= HMS_Testimonials::template($template, $g, (int)$word_limit, (int)$char_limit, $options);

			$ret .= '</div>';


		}

		if ($paging != '' && ($location == 'bottom' || $location == 'both'))
			$ret .= '<div class="paging">'.$paging.'</div>';

		$ret .= '</div>';
	}

	return $ret;

}

function hms_testimonials_show_rotating( $atts ) {
	global $wpdb, $blog_id;

	$order_by = array('id', 'name','testimonial','url','testimonial_date','display_order', 'image', 'rand', 'random');
	$settings = get_option('hms_testimonials');

	extract(shortcode_atts(
		array(
			'group' => 0,
			'template' => 1,
			'seconds' => 6,
			'show_links' => false,
			'autostart' => true,
			'link_position' => 'bottom',
			'link_prev' => '&laquo;',
			'link_next' => '&raquo;',
			'link_pause' => 'Pause',
			'link_play' => 'Play',
			'order' => 'display_order',
			'direction' => 'ASC',
			'word_limit' => 0,
			'char_limit' => 0,
			'readmore_link' => HMS_Testimonials::getInstance()->getOption('readmore_link', ''),
			'readmore_text' => HMS_Testimonials::getInstance()->getOption('readmore_text', '...'),
		), $atts
	));

	if (!in_array($order, $order_by)) $order = 'display_order';
	if ($order == 'rand' || $order == 'random') $order = 'RAND()';
	if ($direction != 'DESC') $direction = 'ASC';
	if ($link_position != 'top' && $link_position != 'both') $link_position = 'bottom';

	$options = array(
		'readmore_link' => $readmore_link,
		'readmore_text' => $readmore_text
	);

	$start = false;
	$start_int = 0;
	if ($autostart) {
		if (is_bool($autostart) || (is_string($autostart) && ($autostart != 'false'))) {
			$start = true;
			$start_int = 1;
		}
	}


	$play_pause_init = ($start) ? __($link_pause) : __($link_play);
	$play_pause_class = ($start) ? 'pause' : 'play';


	$random_string = '';
	$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    for ($i = 0; $i < 5; $i++)
    	$random_string .= $characters[rand(0, 51)];


    if ($group == 0)
		$get = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `display` = 1 AND `blog_id` = ".(int)$blog_id." ORDER BY ".$order." ".$direction, ARRAY_A);
	else {
		if ($order == 'display_order')
			$order = 'm.display_order';
		elseif ($order != 'RAND()')
			$order = 't.'.$order;

		$get = $wpdb->get_results("SELECT t.* FROM `".$wpdb->prefix."hms_testimonials` AS t INNER JOIN `".$wpdb->prefix."hms_testimonials_group_meta` AS m ON m.testimonial_id = t.id WHERE m.group_id = ".(int)$group." AND t.blog_id = ".$blog_id." AND t.display = 1 ORDER BY ".$order." ".$direction, ARRAY_A);
	}
		



	$return = '<div id="hms-testimonial-sc-'.$random_string.'" class="hms-testimonials-rotator" data-start="' . ((!$start) ? 0 : 1) .'" data-seconds="' . $seconds . '" data-play-text="' . $link_play . '" data-pause-text="' . $link_pause .'">';

	if ($show_links && $show_links != "false" && ($link_position == 'top' || $link_position == 'both'))
		$return .= '<div class="controls"><a href="#" class="prev">'.$link_prev.'</a> <a href="#" class="playpause '.$play_pause_class.'">'.$play_pause_init.'</a> <a href="#" class="next">'.$link_next.'</a></div>';

		$return .= '<div class="hms-testimonial-container hms-testimonial-'.$get[0]['id'].' hms-testimonial-template-'.$template.'" itemprop="review" itemscope itemtype="http://schema.org/Review">';
						
		$return .= HMS_Testimonials::template($template, $get[0], (int)$word_limit, (int)$char_limit, $options);

		$return .= '</div>';

	if ($show_links && $show_links != "false" && ($link_position == 'bottom' || $link_position == 'both'))
		$return .= '<div class="controls"><a href="#" class="prev">'.$link_prev.'</a> <a href="#" class="playpause '.$play_pause_class.'">'.$play_pause_init.'</a> <a href="#" class="next">'.$link_next.'</a></div>';
	

	$return .= '<div class="hms-testimonial-items" style="display:none;">';
		
	foreach($get as $g) {
		$return .= '<div class="hms-testimonial-item hms-testimonial-'.$g['id'].' hms-testimonial-template-'.$template.'" itemprop="review" itemscope itemtype="http://schema.org/Review">';
		
			$return .= HMS_Testimonials::template($template, $g, (int)$word_limit, (int)$char_limit, $options);

		$return .= '</div>';	
	}
	
	$return .= '</div>';
	$return .= '</div>';
 
	return $return;
}


/**
 * Create pagination links
 **/

function hms_testimonials_build_pagination($current_page, $total_pages, $prev, $next) {
	$url = explode('?', $_SERVER['REQUEST_URI']);

	if (isset($_GET['hms_testimonials_page']))
		unset($_GET['hms_testimonials_page']);

	if (count($_GET)>0)
		$url[0] .= '?'. http_build_query($_GET) . '&';
	else
		$url[0] .= '?';

	$return = '';
	
	if ($current_page > 1)
		$return .= '<a href="' . $url[0] . 'hms_testimonials_page='.($current_page - 1).'" class="prev">'.$prev.'</a> ';

	for($x = 1; $x <= $total_pages; $x++) {

		if ($x == $current_page)
			$return .= '<span class="current-page">'.$x.'</span> ';
		else
			$return .= '<a href="'.$url[0] . 'hms_testimonials_page='.$x.'">'.$x.'</a> ';
	}

	if ($current_page < $total_pages)
		$return .= '<a href="'.$url[0] . 'hms_testimonials_page='.($current_page + 1).'" class="next">'.$next.'</a> ';

	return $return;
}