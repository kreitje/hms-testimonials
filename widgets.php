<?php

function hms_testimonials_widgets() {
	register_widget('HMS_Testimonials_View');
	register_widget('HMS_Testimonials_Rotator');
}

class HMS_Testimonials_View extends WP_Widget {

	public function __construct() {
		parent::__construct('hms_testimonial_view', 'HMS Testimonals', array('description' => __('Show 1 or several testimonials')));
	}

	public function form($instance) {

		$title = (isset($instance[ 'title' ])) ? $instance[ 'title' ] : __( 'Testimonials');
		$numshow = (isset($instance['numshow'])) ? $instance['numshow'] : 0;
		$show = (isset($instance['show'])) ? $instance['show'] : 'all';
		$show_value = (isset($instance['show_value'])) ? $instance['show_value'] : '';
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show' ); ?>"><?php _e('Display:'); ?></label>
			<select name="<?php echo $this->get_field_name( 'show' ); ?>" id="<?php echo $this->get_field_id( 'show' ); ?>">
				<option value="all" <?php if (esc_attr( $show )=='all') echo ' selected="selected"'; ?>>All</option>
				<option value="group" <?php if (esc_attr( $show )=='group') echo ' selected="selected"'; ?>>Group</option>
				<option value="single" <?php if (esc_attr( $show )=='single') echo ' selected="selected"'; ?>>Single</option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_value' ); ?>"><?php _e('Show Value:'); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'show_value' ); ?>" name="<?php echo $this->get_field_name( 'show_value' ); ?>" value="<?php echo esc_attr( $show_value ); ?>" />
		</p>
		<p>Enter the Group ID or Testimonial ID if applicable.</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'numshow' ); ?>"><?php _e( 'Number to Show:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'numshow' ); ?>" name="<?php echo $this->get_field_name( 'numshow' ); ?>" type="text" value="<?php echo esc_attr( $numshow ); ?>" style="width:25px;" />
		</p>
		<p>Set to 0 to show all</p>
		
		<?php 
	}

	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['numshow'] = (int)$new_instance['numshow'];
		$instance['show'] = (isset($new_instance['show'])) ? $new_instance['show'] : 'all';
		$instance['show_value'] = @$new_instance['show_value'];
		return $instance;

	}

	public function widget($args, $instance) {
		global $wpdb, $blog_id;
		$settings = get_option('hms_testimonials');

		if (!isset($instance['show']))
			$instance['show'] = 'all';
		if (!isset($instance['show_value']))
			$instance['show_value'] = 0;

		if (isset($instance['numshow']) && ((int)$instance['numshow'] > 0))
			$limit = "LIMIT ".(int)$instance['numshow'];
		else
			$limit = '';

		switch($instance['show']) {
			case 'single':
				$get = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `id` = ".(int)$instance['show_value']." AND `display` = 1 AND `blog_id` = ".(int)$blog_id, ARRAY_A);
				$single = 1;
			break;
			case 'group':
				$get = $wpdb->get_results("SELECT t.* FROM `".$wpdb->prefix."hms_testimonials` AS t INNER JOIN `".$wpdb->prefix."hms_testimonials_group_meta` AS m ON m.testimonial_id = t.id WHERE m.group_id = ".(int)$instance['show_value']." AND t.blog_id = ".$blog_id." AND t.display = 1 ORDER BY m.display_order ASC ".$limit, ARRAY_A);
				$single = 0;
			break;
			case 'all':
			default:
				$get = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `display` = 1 AND `blog_id` = ".(int)$blog_id." ORDER BY `display_order` ASC ".$limit, ARRAY_A);
				$single = 0;
			break;
		}

		if (count($get)<1)
			return true;

		echo $args['before_widget'];
		if (!empty($instance['title']))
				echo $args['before_title'].$instance['title'].$args['after_title'];

		if ($single==1) {
			echo '<div class="hms-testimonial-container">
				<div class="testimonial">'.nl2br($get['testimonial']).'</div><div class="author">'.nl2br($get['name']).'</div>';

			if ($get['url'] != '') {
				if (substr($get['url'],0,4)!='http')
					$href = 'http://'.$get['url'];
				else
					$href = $get['url'];


				if ($settings['show_active_links'] == 1) {
					$nofollow = '';

					if ($settings['active_links_nofollow'] == 1)
						$nofollow = 'rel="nofollow"';

					echo '<div class="url"><a '.$nofollow.' href="'.$href.'" target="_blank">'.$href.'</a></div>';
				} else {
					echo '<div class="url">'.$href.'</div>';
				}

			}

			echo '</div>';
		} else {
			$x = 1;
			foreach($get as $g) {

				echo '<div class="hms-testimonial-container hms-testimonial-counter-'.$x.'">
				 <div class="testimonial">'.nl2br($g['testimonial']).'</div><div class="author">'.nl2br($g['name']).'</div>';
				if ($g['url']!='') echo '<div class="url">'.$g['url'].'</div>';
				echo '</div>';
				$x++;
			}
		}
		echo $args['after_widget'];

	}

}

class HMS_Testimonials_Rotator extends WP_Widget {

	public function __construct() {
		parent::__construct('hms_testimonial_rotator', 'HMS Testimonial Rotator', array('description' => __('Rotates your testimonials')));
	}

	public function form($instance) {
		global $wpdb;

		$title = (isset($instance[ 'title' ])) ? $instance[ 'title' ] : __( 'Testimonials');
		$group = (isset($instance[ 'group' ])) ? $instance[ 'group' ] : 0;
		$seconds = (isset($instance[ 'seconds' ])) ? $instance[ 'seconds' ] : 10;
		$show_links = (isset($instance[ 'show_links' ]) && $instance['show_links'] == 1) ? 1 : 0;
		$link_next = (isset($instance['link_next'])) ? $instance['link_next'] : '';
		$link_prev = (isset($instance['link_prev'])) ? $instance['link_prev'] : '';
		$link_pause = (isset($instance['link_pause'])) ? $instance['link_pause'] : '';
		$link_play = (isset($instance['link_play'])) ? $instance['link_play'] : '';

		
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'group' ); ?>"><?php _e('Group:'); ?></label>
			<select name="<?php echo $this->get_field_name( 'group' ); ?>" id="<?php echo $this->get_field_id( 'group' ); ?>">
				<option value="all" <?php if (esc_attr( $group )=='0') echo ' selected="selected"'; ?>>All</option>
				<?php
				$get_groups = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials_groups` ORDER BY `name` ASC", ARRAY_A);
				foreach($get_groups as $g):
					echo '<option value="'.$g['id'].'"'; if ($group == $g['id']) echo ' selected="selected"';  echo '>'.$g['name'].'</option>';
				endforeach;
				?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'seconds' ); ?>"><?php _e( 'Seconds Between:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'seconds' ); ?>" name="<?php echo $this->get_field_name( 'seconds' ); ?>" type="text" value="<?php echo esc_attr( $seconds ); ?>" style="width:25px;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('show_links'); ?>"><?php _e( 'Show Links:' ); ?></label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_links' ); ?>" name="<?php echo $this->get_field_name( 'show_links' ); ?>" value="1" <?php if ($show_links == 1) echo ' checked="checked"'; ?> />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'link_next' ); ?>"><?php _e( 'Next Text:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'link_next' ); ?>" name="<?php echo $this->get_field_name( 'link_next' ); ?>" type="text" value="<?php echo esc_attr( $link_next ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'link_prev' ); ?>"><?php _e( 'Previous Text:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'link_prev' ); ?>" name="<?php echo $this->get_field_name( 'link_prev' ); ?>" type="text" value="<?php echo esc_attr( $link_prev ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'link_play' ); ?>"><?php _e( 'Play Text:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'link_play' ); ?>" name="<?php echo $this->get_field_name( 'link_play' ); ?>" type="text" value="<?php echo esc_attr( $link_play ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'link_pause' ); ?>"><?php _e( 'Pause Text:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'link_pause' ); ?>" name="<?php echo $this->get_field_name( 'link_pause' ); ?>" type="text" value="<?php echo esc_attr( $link_pause ); ?>" />
		</p>
		
		
		<?php 
	}

	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['group'] = (int)$new_instance['group'];
		$instance['seconds'] = (int)$new_instance['seconds'];
		$instance['show_links'] = (int)$new_instance['show_links'];

		$instance['link_next'] = $new_instance['link_next'];
		$instance['link_prev'] = $new_instance['link_prev'];
		$instance['link_pause'] = $new_instance['link_pause'];
		$instance['link_play'] = $new_instance['link_play'];
		
		return $instance;
	}

	public function widget($args, $instance) {
		global $wpdb, $blog_id;
		$settings = get_option('hms_testimonials');

		if (!isset($instance['group']))
			$instance['group'] = 0;

		if ($instance['group'] == 0) {
			$get = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `display` = 1 AND `blog_id` = ".(int)$blog_id." ORDER BY `display_order` ASC ", ARRAY_A);
		} else {
			$get = $wpdb->get_results("SELECT t.* FROM `".$wpdb->prefix."hms_testimonials` AS t INNER JOIN `".$wpdb->prefix."hms_testimonials_group_meta` AS m ON m.testimonial_id = t.id WHERE m.group_id = ".(int)$instance['group']." AND t.blog_id = ".$blog_id." AND t.display = 1 ORDER BY m.display_order ASC", ARRAY_A);
		}

		if (count($get)<1)
			return true;

		$identifier = $this->_randomstring();



		echo $args['before_widget'];
		if (!empty($instance['title']))
			echo $args['before_title'].$instance['title'].$args['after_title'];

		echo '<div id="hms-testimonial-'.$identifier.'">';

			echo '<div class="hms-testimonial-container">
				<div class="testimonial">'.nl2br($get[0]['testimonial']).'</div><div class="author">'.nl2br($get[0]['name']).'</div>';

			if ($get[0]['url'] != '') {
				if (substr($get[0]['url'],0,4)!='http')
					$href = 'http://'.$get[0]['url'];
				else
					$href = $get[0]['url'];


				if ($settings['show_active_links'] == 1) {
					$nofollow = '';

					if ($settings['active_links_nofollow'] == 1)
						$nofollow = 'rel="nofollow"';

					echo '<div class="url"><a '.$nofollow.' href="'.$href.'" target="_blank">'.$href.'</a></div>';
				} else {
					echo '<div class="url">'.$href.'</div>';
				}

			}
			
			echo '</div>';

			if ($instance['show_links'] == 1)
				echo '<div class="controls"><a href="#" class="prev">'.$instance['link_prev'].'</a> <a href="#" class="playpause pause">'.$instance['link_pause'].'</a> <a href="#" class="next">'.$instance['link_next'].'</a></div>';
		echo '</div>';
		?>

		<div style="display:none;" id="hms-testimonial-list-<?php echo $identifier; ?>">

			<?php
				foreach($get as $g) {
					echo '<div class="hms-testimonial-container">
							<div class="testimonial">'.nl2br($g['testimonial']).'</div>
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

							echo '<div class="url"><a '.$nofollow.' href="'.$href.'" target="_blank">'.$href.'</a></div>';
						} else {
							echo '<div class="url">'.$href.'</div>';
						}

					}

					echo '</div>';
				} ?>
		</div>

		<script type="text/javascript">
			var index_<?php echo $identifier; ?> = 1;
			var timeout_<?php echo $identifier; ?> = null;
			var play_<?php echo $identifier; ?> = 1;

			jQuery(document).ready(function() {

				si_<?php echo $identifier; ?>();

				jQuery("#hms-testimonial-<?php echo $identifier; ?> .controls .pause").click(function() {
					if (play_<?php echo $identifier; ?> == 1) {
						jQuery(this).text('<?php echo $instance['link_play']; ?>').removeClass('pause').addClass('play');
						clearInterval(timeout_<?php echo $identifier; ?>);
						play_<?php echo $identifier; ?> = 0;
					} else {
						jQuery(this).text('<?php echo $instance['link_pause']; ?>').removeClass('play').addClass('pause');
						si_<?php echo $identifier; ?>();
						play_<?php echo $identifier; ?> = 1;
					}

					return false;
				});

				jQuery("#hms-testimonial-<?php echo $identifier; ?> .controls .prev").click(function() {

					var new_index = (index_<?php echo $identifier; ?> - 2);
					
					if (new_index < 0) {
						new_index = (jQuery("#hms-testimonial-list-<?php echo $identifier; ?> .hms-testimonial-container").length - 1);
					}
					

					var nextitem = jQuery("#hms-testimonial-list-<?php echo $identifier; ?> .hms-testimonial-container").get(new_index);
					if (nextitem == undefined) {
						index_<?php echo $identifier; ?> = 0;
						var nextitem = jQuery("#hms-testimonial-list-<?php echo $identifier; ?> .hms-testimonial-container").get(0);
					}
					jQuery("#hms-testimonial-<?php echo $identifier; ?> .hms-testimonial-container").fadeOut('slow', function(){ jQuery(this).html(nextitem.innerHTML)}).fadeIn();
					index_<?php echo $identifier; ?> = new_index + 1;

					if (play_<?php echo $identifier; ?> == 1) {
						clearInterval(timeout_<?php echo $identifier; ?>);
						si_<?php echo $identifier; ?>();
					}
					return false;

				});
				jQuery("#hms-testimonial-<?php echo $identifier; ?> .controls .next").click(function() {
					var nextitem = jQuery("#hms-testimonial-list-<?php echo $identifier; ?> .hms-testimonial-container").get(index_<?php echo $identifier; ?>);
					if (nextitem == undefined) {
						index_<?php echo $identifier; ?> = 0;
						var nextitem = jQuery("#hms-testimonial-list-<?php echo $identifier; ?> .hms-testimonial-container").get(0);
					}
					jQuery("#hms-testimonial-<?php echo $identifier; ?> .hms-testimonial-container").fadeOut('slow', function(){ jQuery(this).html(nextitem.innerHTML)}).fadeIn();
					index_<?php echo $identifier; ?> = index_<?php echo $identifier; ?> + 1;

					if (play_<?php echo $identifier; ?> == 1) {
						clearInterval(timeout_<?php echo $identifier; ?>);
						si_<?php echo $identifier; ?>();
					}
					return false;
				});
				
			});

			function si_<?php echo $identifier; ?>() {

				timeout_<?php echo $identifier; ?> = setInterval(function() {
					var nextitem = jQuery("#hms-testimonial-list-<?php echo $identifier; ?> .hms-testimonial-container").get(index_<?php echo $identifier; ?>);
					if (nextitem == undefined) {
						index_<?php echo $identifier; ?> = 0;
						var nextitem = jQuery("#hms-testimonial-list-<?php echo $identifier; ?> .hms-testimonial-container").get(0);
					}
					jQuery("#hms-testimonial-<?php echo $identifier; ?> .hms-testimonial-container").fadeOut('slow', function(){ jQuery(this).html(nextitem.innerHTML)}).fadeIn();
					index_<?php echo $identifier; ?> = index_<?php echo $identifier; ?> + 1;
				}, <?php echo $instance['seconds']; ?>000);
			}
			
		</script>
		<?php
		
		echo $args['after_widget'];
	}

	public function _randomstring() {
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	$randstring = '';
    	for ($i = 0; $i < 5; $i++)
            $randstring .= $characters[rand(0, strlen($characters))];
        
    	return $randstring;
	}

}