<?php

function hms_testimonials_form( $atts ) {
	global $wpdb, $blog_id, $current_user;
	get_currentuserinfo();


	$settings = get_option('hms_testimonials');
	$fields = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials_cf` WHERE `blog_id` = ".$blog_id." ORDER BY `name` ASC");

	$field_count = count($fields);

	require_once HMS_TESTIMONIALS . 'recaptchalib.php';
	
	$ret = '';
	if (isset($_POST) && isset($_POST['hms_testimonial']) && ($_POST['hms_testimonial'] == 1)) {
		$errors = array();

		if (!isset($_POST['hms_testimonials_name']) || (($name = trim(@$_POST['hms_testimonials_name'])) == ''))
			$errors[] = __('Please enter your name.', 'hms-testimonials' );

		if (!isset($_POST['hms_testimonials_testimonial']) || (($testimonial = trim(@$_POST['hms_testimonials_testimonial'])) == ''))
			$errors[] = __('Please enter your testimonial.', 'hms-testimonials' );

		if ($field_count>0) {
			foreach($fields as $f) {

				if ($f->isrequired == 1 && (!isset($_POST['hms_testimonials_cf'][$f->id]) || trim($_POST['hms_testimonials_cf'][$f->id])=='')) {
					$errors[] = sprintf( __('%1$s is a required field.', 'hms-testimonials' ), $f->name );
					continue;
				}

				switch($f->type) {
					case 'email':
						if (!filter_var($_POST['hms_testimonials_cf'][$f->id], FILTER_VALIDATE_EMAIL))
							$errors[] = sprintf( __('Please enter a valid email for the %1$s field.', 'hms-testimonials'), $f->name );
					break;
				}

				$cf_{$f->id} = $_POST['hms_testimonials_cf'][$f->id];

			}
		}

		$website = '';
		if (isset($_POST['hms_testimonials_website']) && ($_POST['hms_testimonials_website'] != '')) {
			$website = $_POST['hms_testimonials_website'];

			if (!filter_var($website, FILTER_VALIDATE_URL))
				$errors[] = __('Please enter a valid URL.', 'hms-testimonials' );
			
		}

		if ($settings['use_recaptcha'] == 1) { 
			$resp = recaptcha_check_answer ($settings['recaptcha_privatekey'], $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

        	if (!$resp->is_valid) {
        		switch($resp->error) {
        			case 'incorrect-captcha-sol':
        				$errors[] = __('You entered an incorrect captcha. Please try again.', 'hms-testimonials' );
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

			$display_order = $wpdb->get_var("SELECT `display_order` FROM `".$wpdb->prefix."hms_testimonials` ORDER BY `display_order` DESC LIMIT 1");

			$wpdb->insert($wpdb->prefix."hms_testimonials", 
				array(
					'blog_id' => $blog_id, 'user_id' => $current_user->ID, 'name' => $name, 
					'testimonial' => $testimonial, 'display' => 0, 'display_order' => ($display_order+1),
					'url' => $website, 'created_at' => date('Y-m-d h:i:s'), 'testimonial_date' => date('Y-m-d h:i:s')
				)
			);

			$id = $wpdb->insert_id;

			$e_message = '';
			if ($field_count > 0) {
				foreach($fields as $f) {

					if (isset($_POST['hms_testimonials_cf'][$f->id])) {
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
			$message .= sprintf( __('Name: %1$s', 'hms-testimonials' ), $name)."\r\n";
			$message .= sprintf( __('Website: %1$s', 'hms-testimonials' ), $website)."\r\n";
			$message .= sprintf( __('Testimonial: %1$s', 'hms-testimonials' ), $testimonial)."\r\n";

			$message .= $e_message;

			$message .= "\r\n\r\n";
			$message .= sprintf( __('View this testimonial at %1$s', 'hms-testimonials' ), admin_url('admin.php?page=hms-testimonials-view&id='.$id));

			wp_mail(get_bloginfo('admin_email'), sprintf( __('New Visitor Testimonial Added to %1$s', 'hms-testimonials' ), get_bloginfo('name') ), $message);
				
			if (!isset($settings['guest_submission_redirect']) || ($settings['guest_submission_redirect'] == ''))
				return '<div class="hms_testimonial_success">' . __('Your testimonial has been submitted.', 'hms-testimonials' ) . '</div>';
			else
				die(header('Location: '.$settings['guest_submission_redirect']));
		}

	} else {
		$name = $current_user->user_firstname.' '.$current_user->user_lastname;
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
	$submit_text = apply_filters('hms_testimonials_sc_submit', __('Submit Testimonial', 'hms-testimonials' ));

	$ret .= <<<HTML
<form method="post">
<input type="hidden" name="hms_testimonial" value="1" />
	<table class="hms-testimonials-form">
		<tr class="name required">
			<td>{$name_text}</td>
			<td><input type="text" name="hms_testimonials_name" value="{$name}" />
		</tr>
		<tr class="website">
			<td>{$website_text}</td>
			<td><input type="text" name="hms_testimonials_website" value="{$website}" />
		</tr>
		<tr class="testimonial required">
			<td valign="top">{$testimonial_text}</td>
			<td><textarea name="hms_testimonials_testimonial" rows="5" style="width:99%;">{$testimonial}</textarea></td>
		</tr>
HTML;

	
	if ($field_count>0) {
		foreach($fields as $f) {
			$ret .= '
			<tr class="cf-'.strtolower($f->name).(($f->isrequired == 1) ? ' required' : '').'">
				<td valign="top">'.$f->name.'</td>
				<td>';

				switch($f->type) {
					case 'email':
					case 'text':
						$ret .= '<input type="text" name="hms_testimonials_cf['.$f->id.']" value="'.$cf_{$f->id}.'" />';
					break;
					case 'textarea':
						$ret .= '<textarea name="hms_testimonials_cf['.$f->id.']" rows="5" style="width:99%;">'.$cf_{$f->id}.'</textarea>';
					break;
				}

			$ret .='	</td>
			</tr>';
		}
	}


	if ($settings['use_recaptcha'] == 1) { 
		$ret .= '<tr>
					<td> </td>
					<td>'.recaptcha_get_html($settings['recaptcha_publickey'], null).'</td>
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

	$order_by = array('id', 'name','testimonial','url','testimonial_date','display_order', 'image', 'rand');

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
		), $atts
	));

	if (!in_array($order, $order_by)) $order = 'display_order';
	if ($order == 'rand') $order = 'RAND()';
	if ($direction != 'DESC') $direction = 'ASC';
	if ($start != 0) $start = (int)$start - 1;

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

		if (!isset($_GET['hms_testimonials_page']) || !is_numeric($_GET['hms_testimonials_page']) || $_GET['hms_testimonials_page'] > $pages || $_GET['hms_testimonials_page'] < 1) {
			$current_page = 1;
			$new_start = $start;
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

		$ret = '<div class="hms-testimonial-container hms-testimonial-single hms-testimonial-'.$get['id'].' hms-testimonial-template-'.$template.'">';
			$ret .= HMS_Testimonials::template($template, $get);
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

			$ret .= '<div class="hms-testimonial-container hms-testimonial-'.$g['id'].' hms-testimonial-template-'.$template.'">';

				$ret .= HMS_Testimonials::template($template, $g);

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

	$order_by = array('id', 'name','testimonial','url','testimonial_date','display_order', 'image', 'rand');
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
			
		), $atts
	));

	if (!in_array($order, $order_by)) $order = 'display_order';
	if ($order == 'rand') $order = 'RAND()';
	if ($direction != 'DESC') $direction = 'ASC';
	if ($link_position != 'top' && $link_position != 'both') $link_position = 'bottom';

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
    	$random_string .= $characters[rand(0, strlen($characters))];


    if ($group == 0)
		$get = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `display` = 1 AND `blog_id` = ".(int)$blog_id." ORDER BY ".$order." ".$direction, ARRAY_A);
	else {
		if ($order == 'display_order')
			$order = 'm.display_order';
		elseif ($order != 'RAND()')
			$order = 't.'.$order;

		$get = $wpdb->get_results("SELECT t.* FROM `".$wpdb->prefix."hms_testimonials` AS t INNER JOIN `".$wpdb->prefix."hms_testimonials_group_meta` AS m ON m.testimonial_id = t.id WHERE m.group_id = ".(int)$group." AND t.blog_id = ".$blog_id." AND t.display = 1 ORDER BY ".$order." ".$direction, ARRAY_A);
	}
		



	$return = '<div id="hms-testimonial-sc-'.$random_string.'" class="hms-testimonials-rotator">';

	if ($show_links && $show_links != "false" && ($link_position == 'top' || $link_position == 'both'))
		$return .= '<div class="controls"><a href="#" class="prev">'.$link_prev.'</a> <a href="#" class="playpause '.$play_pause_class.'">'.$play_pause_init.'</a> <a href="#" class="next">'.$link_next.'</a></div>';

		$return .= '<div class="hms-testimonial-container hms-testimonial-'.$get[0]['id'].' hms-testimonial-template-'.$template.'"">';
						
		$return .= HMS_Testimonials::template($template, $get[0]);

		$return .= '</div>';

	if ($show_links && $show_links != "false" && ($link_position == 'bottom' || $link_position == 'both'))
		$return .= '<div class="controls"><a href="#" class="prev">'.$link_prev.'</a> <a href="#" class="playpause '.$play_pause_class.'">'.$play_pause_init.'</a> <a href="#" class="next">'.$link_next.'</a></div>';
	
	$return .= '</div>';


	$return .= '<div style="display:none;" id="hms-testimonial-sc-list-'.$random_string.'">';
		
	foreach($get as $g) {
		$return .= '<div class="hms-testimonial-container hms-testimonial-'.$g['id'].' hms-testimonial-template-'.$template.'"">';
		
			$return .= HMS_Testimonials::template($template, $g);

		$return .= '</div>';	
	}
	
	$return .= '</div>';

	$return .= <<<JS
	<script type="text/javascript">
		var index_{$random_string} = 1;
		var timeout_{$random_string} = null;
		var play_{$random_string} = $start_int;
		jQuery(document).ready(function() {
JS;
		if ($start)
			$return .= 'si_'.$random_string.'();';
		

	$return .= <<<JS
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