<?php

function hms_testimonials_widgets() {
	register_widget('HMS_Testimonials_View');
	register_widget('HMS_Testimonials_Rotator');
}

class HMS_Testimonials_View extends WP_Widget {

	public function __construct() {
		parent::__construct('hms_testimonial_view', __('HMS Testimonals', 'hms-testimonials' ), array('description' => __('Show 1 or several testimonials', 'hms-testimonials' )));
	}

	public function form($instance) {
		global $wpdb, $blog_id;

		$title = (isset($instance[ 'title' ])) ? $instance[ 'title' ] : __( 'Testimonials');
		$numshow = (isset($instance['numshow'])) ? $instance['numshow'] : 0;
		$show = (isset($instance['show'])) ? $instance['show'] : 'all';
		$show_value = (isset($instance['show_value'])) ? $instance['show_value'] : '';
		$template = (isset($instance['template'])) ? $instance['template'] : '1';
		$sortby = (isset($instance['sortby'])) ? $instance['sortby'] : 'display_order';
		$order = (isset($instance['order'])) ? $instance['order'] : 'ASC';
		$char_limit = (isset($instance['char_limit'])) ? $instance['char_limit'] : 0;
		$word_limit = (isset($instance['word_limit'])) ? $instance['word_limit'] : 0;

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'hms-testimonials' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show' ); ?>"><?php _e('Display', 'hms-testimonials' ); ?>:</label><br />
			<select name="<?php echo $this->get_field_name( 'show' ); ?>" id="<?php echo $this->get_field_id( 'show' ); ?>">
				<option value="all" <?php if (esc_attr( $show )=='all') echo ' selected="selected"'; ?>>All</option>
				<option value="group" <?php if (esc_attr( $show )=='group') echo ' selected="selected"'; ?>>Group</option>
				<option value="single" <?php if (esc_attr( $show )=='single') echo ' selected="selected"'; ?>>Single</option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_value' ); ?>"><?php _e('Group ID or Testimonial ID if applicable', 'hms-testimonials' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'show_value' ); ?>" name="<?php echo $this->get_field_name( 'show_value' ); ?>" value="<?php echo esc_attr( $show_value ); ?>" />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e('Template', 'hms-testimonials' ); ?>:</label><br />
			<select name="<?php echo $this->get_field_name( 'template' ); ?>" id="<?php echo $this->get_field_id( 'template' ); ?>">
				<?php
				$get_templates = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials_templates` WHERE `blog_id` = ".(int)$blog_id." ORDER BY `name` ASC", ARRAY_A);
				foreach($get_templates as $t):
					echo '<option value="'.$t['id'].'"'; if ($template == $t['id']) echo ' selected="selected"';  echo '>'.$t['name'].'</option>';
				endforeach;
				?>
			</select>
		</p>

		<?php $sort_by = esc_attr($sortby); ?>
		<p><label for="<?php echo $this->get_field_id( 'sortby' ); ?>"><?php _e('Sort By', 'hms-testimonials' ); ?>:</label><br />
			<select name="<?php echo $this->get_field_name( 'sortby' ); ?>" id="<?php echo $this->get_field_id( 'sortby' ); ?>">
				<option value="display_order">display order*</option>
				<option value="id" <?php if ($sort_by == 'id') echo 'selected="selected"'; ?>>id</option>
				<option value="name" <?php if ($sort_by == 'name') echo 'selected="selected"'; ?>>source (name)</option>
				<option value="testimonial" <?php if ($sort_by == 'testimonial') echo 'selected="selected"'; ?>>testimonial content</option>
				<option value="url" <?php if ($sort_by == 'url') echo 'selected="selected"'; ?>>url</option>
				<option value="testimonial_date" <?php if ($sort_by == 'testimonial_date') echo 'selected="selected"'; ?>>testimonial date</option>
				<option value="image" <?php if ($sort_by == 'image') echo 'selected="selected"'; ?>>has image</option>
				<option value="rand" <?php if ($sort_by == 'rand') echo 'selected="selected"'; ?>>random</option>
			</select>
		</p>
		<?php $order_by = esc_attr($order); ?>
		<p>
			<select name="<?php echo $this->get_field_name( 'order' ); ?>" id="<?php echo $this->get_field_id( 'order' ); ?>">
				<option value="ASC" <?php if ($order_by == 'ASC') echo 'selected="selected"'; ?>>ascending*</option>
				<option value="DESC" <?php if ($order_by == 'DESC') echo 'selected="selected"'; ?>>descending</option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'numshow' ); ?>"><?php _e( 'Number to Show', 'hms-testimonials'  ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'numshow' ); ?>" name="<?php echo $this->get_field_name( 'numshow' ); ?>" type="text" value="<?php echo esc_attr( $numshow ); ?>" style="width:25px;" />
		</p>
		<p>&nbsp;&nbsp; <?php _e('Set to 0 to show all', 'hms-testimonials' ); ?></p>

		<p>
			<label for="<?php echo $this->get_field_id( 'word_limit' ); ?>"><?php _e( 'Truncate to (x) words', 'hms-testimonials'  ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'word_limit' ); ?>" name="<?php echo $this->get_field_name( 'word_limit' ); ?>" type="text" value="<?php echo esc_attr( $word_limit ); ?>" style="width:25px;" />
		</p>
		<p>&nbsp;&nbsp; <?php _e('Set to 0 to show all', 'hms-testimonials' ); ?></p>

		<p>
			<label for="<?php echo $this->get_field_id( 'char_limit' ); ?>"><?php _e( 'Truncate to (x) characters', 'hms-testimonials'  ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'char_limit' ); ?>" name="<?php echo $this->get_field_name( 'char_limit' ); ?>" type="text" value="<?php echo esc_attr( $char_limit ); ?>" style="width:25px;" />
		</p>
		<p>&nbsp;&nbsp; <?php _e('Set to 0 to show all', 'hms-testimonials' ); ?></p>
		
		<?php 
	}

	public function update($new_instance, $old_instance) {
		

		$instance = array();
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['numshow'] = (int)$new_instance['numshow'];
		$instance['show'] = (isset($new_instance['show'])) ? $new_instance['show'] : 'all';
		$instance['show_value'] = (int)$new_instance['show_value'];
		$instance['template'] = (isset($new_instance['template'])) ? $new_instance['template'] : 1;
		$instance['sortby'] = (isset($new_instance['sortby'])) ? $new_instance['sortby'] : 'display_order';
		$instance['order'] = (isset($new_instance['order']) && $new_instance['order']=='DESC') ? 'DESC' : 'ASC';

		$instance['word_limit'] = (isset($new_instance['word_limit'])) ? (int)$new_instance['word_limit'] : 0;
		$instance['char_limit'] = (isset($new_instance['char_limit'])) ? (int)$new_instance['char_limit'] : 0;
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

		$order = ($instance['order'] == 'DESC') ? 'DESC' : 'ASC';
		$sort = $instance['sortby'];

		$sort_by_valid = array('id', 'name','testimonial','url','testimonial_date','display_order', 'image', 'rand');
		if (!in_array($sort, $sort_by_valid)) $sort = 'display_order';

		switch($instance['show']) {
			case 'single':
				$get = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `id` = ".(int)$instance['show_value']." AND `display` = 1 AND `blog_id` = ".(int)$blog_id, ARRAY_A);
				$single = 1;
			break;
			case 'group':

				if ($sort == 'display_order')
					$sort = 'm.display_order';
				elseif ($sort == 'rand')
					$sort = 'RAND()';
				else
					$sort = 't.'.$sort;

				$get = $wpdb->get_results("SELECT t.* FROM `".$wpdb->prefix."hms_testimonials` AS t INNER JOIN `".$wpdb->prefix."hms_testimonials_group_meta` AS m ON m.testimonial_id = t.id WHERE m.group_id = ".(int)$instance['show_value']." AND t.blog_id = ".$blog_id." AND t.display = 1 ORDER BY ".$sort." ".$order." ".$limit, ARRAY_A);
				$single = 0;
			break;
			case 'all':
			default:
				if ($sort == 'rand')
					$sort = 'RAND()';
				else
					$sort = '`'.$sort.'`';

				$get = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `display` = 1 AND `blog_id` = ".(int)$blog_id." ORDER BY ".$sort." ".$order." ".$limit, ARRAY_A);
				$single = 0;
			break;
		}

		if (count($get)<1)
			return true;

		echo $args['before_widget'];
		if (!empty($instance['title']))
				echo $args['before_title'].$instance['title'].$args['after_title'];

		if ($single==1) {
			echo '<div class="hms-testimonial-container hms-testimonial-single hms-testimonial-'.$get['id'].'" itemscope itemtype="http://schema.org/Review">';
				
			echo HMS_Testimonials::template($instance['template'], $get, (int)$instance['word_limit'], (int)$instance['char_limit']);

			echo '</div>';
		} else {
			$x = 1;
			foreach($get as $g) {

				echo '<div class="hms-testimonial-container hms-testimonial-counter-'.$x.'" itemscope itemtype="http://schema.org/Review">';
				
					echo HMS_Testimonials::template($instance['template'], $g, (int)$instance['word_limit'], (int)$instance['char_limit']);
				
				echo '</div>';
				$x++;
			}
		}
		echo $args['after_widget'];

	}

}

class HMS_Testimonials_Rotator extends WP_Widget {

	public function __construct() {
		parent::__construct('hms_testimonial_rotator', __('HMS Testimonial Rotator', 'hms-testimonials' ), array('description' => __('Rotates your testimonials', 'hms-testimonials' )));
	}

	public function form($instance) {
		global $wpdb, $blog_id;

		$title = (isset($instance[ 'title' ])) ? $instance[ 'title' ] : __( 'Testimonials');
		$group = (isset($instance[ 'group' ])) ? $instance[ 'group' ] : 0;
		$seconds = (isset($instance[ 'seconds' ])) ? $instance[ 'seconds' ] : 10;
		$show_links = (isset($instance[ 'show_links' ]) && $instance['show_links'] == 1) ? 1 : 0;
		$link_next = (isset($instance['link_next'])) ? $instance['link_next'] : '';
		$link_prev = (isset($instance['link_prev'])) ? $instance['link_prev'] : '';
		$link_pause = (isset($instance['link_pause'])) ? $instance['link_pause'] : '';
		$link_play = (isset($instance['link_play'])) ? $instance['link_play'] : '';
		$template = (isset($instance['template'])) ? $instance['template'] : '1';

		$sortby = (isset($instance['sortby'])) ? $instance['sortby'] : 'display_order';
		$order = (isset($instance['order'])) ? $instance['order'] : 'ASC';

		$autostart = (isset($instance['autostart'])) ? $instance['autostart'] : 1;
		$link_position = (isset($instance['link_position'])) ? $instance['link_position'] : 'bottom';

		$char_limit = (isset($instance['char_limit'])) ? $instance['char_limit'] : 0;
		$word_limit = (isset($instance['word_limit'])) ? $instance['word_limit'] : 0;
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'hms-testimonials'  ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'group' ); ?>"><?php _e('Group', 'hms-testimonials' ); ?>:</label><br />
			<select name="<?php echo $this->get_field_name( 'group' ); ?>" id="<?php echo $this->get_field_id( 'group' ); ?>">
				<option value="all" <?php if (esc_attr( $group )=='0') echo ' selected="selected"'; ?>>All</option>
				<?php
				$get_groups = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials_groups` WHERE `blog_id` = ".(int)$blog_id." ORDER BY `name` ASC", ARRAY_A);
				foreach($get_groups as $g):
					echo '<option value="'.$g['id'].'"'; if ($group == $g['id']) echo ' selected="selected"';  echo '>'.$g['name'].'</option>';
				endforeach;
				?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e('Template', 'hms-testimonials' ); ?>:</label><br />
			<select name="<?php echo $this->get_field_name( 'template' ); ?>" id="<?php echo $this->get_field_id( 'template' ); ?>">
				<?php
				$get_templates = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials_templates` WHERE `blog_id` = ".(int)$blog_id." ORDER BY `name` ASC", ARRAY_A);
				foreach($get_templates as $t):
					echo '<option value="'.$t['id'].'"'; if ($template == $t['id']) echo ' selected="selected"';  echo '>'.$t['name'].'</option>';
				endforeach;
				?>
			</select>
		</p>
		<?php $sort_by = esc_attr($sortby); ?>
		<p><label for="<?php echo $this->get_field_id( 'sortby' ); ?>"><?php _e('Sort By', 'hms-testimonials' ); ?>:</label><br />
			<select name="<?php echo $this->get_field_name( 'sortby' ); ?>" id="<?php echo $this->get_field_id( 'sortby' ); ?>">
				<option value="display_order">display order*</option>
				<option value="id" <?php if ($sort_by == 'id') echo 'selected="selected"'; ?>>id</option>
				<option value="name" <?php if ($sort_by == 'name') echo 'selected="selected"'; ?>>source (name)</option>
				<option value="testimonial" <?php if ($sort_by == 'testimonial') echo 'selected="selected"'; ?>>testimonial content</option>
				<option value="url" <?php if ($sort_by == 'url') echo 'selected="selected"'; ?>>url</option>
				<option value="testimonial_date" <?php if ($sort_by == 'testimonial_date') echo 'selected="selected"'; ?>>testimonial date</option>
				<option value="image" <?php if ($sort_by == 'image') echo 'selected="selected"'; ?>>has image</option>
				<option value="rand" <?php if ($sort_by == 'rand') echo 'selected="selected"'; ?>>random</option>
			</select>
		</p>
		<?php $order_by = esc_attr($order); ?>
		<p>
			<select name="<?php echo $this->get_field_name( 'order' ); ?>" id="<?php echo $this->get_field_id( 'order' ); ?>">
				<option value="ASC" <?php if ($order_by == 'ASC') echo 'selected="selected"'; ?>>ascending*</option>
				<option value="DESC" <?php if ($order_by == 'DESC') echo 'selected="selected"'; ?>>descending</option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'seconds' ); ?>"><?php _e( 'Seconds Between', 'hms-testimonials'  ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'seconds' ); ?>" name="<?php echo $this->get_field_name( 'seconds' ); ?>" type="text" value="<?php echo esc_attr( $seconds ); ?>" style="width:25px;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'word_limit' ); ?>"><?php _e( 'Truncate to (x) words', 'hms-testimonials'  ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'word_limit' ); ?>" name="<?php echo $this->get_field_name( 'word_limit' ); ?>" type="text" value="<?php echo esc_attr( $word_limit ); ?>" style="width:25px;" />
		</p>
		<p>&nbsp;&nbsp; <?php _e('Set to 0 to show all', 'hms-testimonials' ); ?></p>

		<p>
			<label for="<?php echo $this->get_field_id( 'char_limit' ); ?>"><?php _e( 'Truncate to (x) characters', 'hms-testimonials'  ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'char_limit' ); ?>" name="<?php echo $this->get_field_name( 'char_limit' ); ?>" type="text" value="<?php echo esc_attr( $char_limit ); ?>" style="width:25px;" />
		</p>
		<p>&nbsp;&nbsp; <?php _e('Set to 0 to show all', 'hms-testimonials' ); ?></p>


		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'autostart' ); ?>" name="<?php echo $this->get_field_name( 'autostart' ); ?>" value="1" <?php if ($autostart == 1) echo ' checked="checked"'; ?> /> &nbsp;&nbsp;
			<label for="<?php echo $this->get_field_id('autostart'); ?>"><?php _e( 'Automatically Start Rotating', 'hms-testimonials'  ); ?></label>
		</p>

		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_links' ); ?>" name="<?php echo $this->get_field_name( 'show_links' ); ?>" value="1" <?php if ($show_links == 1) echo ' checked="checked"'; ?> /> &nbsp;&nbsp;
			<label for="<?php echo $this->get_field_id('show_links'); ?>"><?php _e( 'Show Links', 'hms-testimonials'  ); ?></label>
		</p>

		<p><label for="<?php echo $this->get_field_id( 'link_position' ); ?>"><?php _e('Links Position', 'hms-testimonials' ); ?>:</label><br />
			<select name="<?php echo $this->get_field_name( 'link_position' ); ?>" id="<?php echo $this->get_field_id( 'link_position' ); ?>">
				<option value="bottom" <?php if ($link_position == 'bottom') echo 'selected="selected"'; ?>>Bottom*</option>
				<option value="top" <?php if ($link_position == 'top') echo 'selected="selected"'; ?>>Top</option>
				<option value="both" <?php if ($link_position == 'both') echo 'selected="selected"'; ?>>Both</option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'link_next' ); ?>"><?php _e( 'Next Text', 'hms-testimonials'  ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'link_next' ); ?>" name="<?php echo $this->get_field_name( 'link_next' ); ?>" type="text" value="<?php echo esc_attr( $link_next ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'link_prev' ); ?>"><?php _e( 'Previous Text', 'hms-testimonials'  ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'link_prev' ); ?>" name="<?php echo $this->get_field_name( 'link_prev' ); ?>" type="text" value="<?php echo esc_attr( $link_prev ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'link_play' ); ?>"><?php _e( 'Play Text', 'hms-testimonials'  ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'link_play' ); ?>" name="<?php echo $this->get_field_name( 'link_play' ); ?>" type="text" value="<?php echo esc_attr( $link_play ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'link_pause' ); ?>"><?php _e( 'Pause Text', 'hms-testimonials'  ); ?>:</label> 
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
		$instance['template'] = (isset($new_instance['template'])) ? $new_instance['template'] : 1;
		
		$instance['link_next'] = $new_instance['link_next'];
		$instance['link_prev'] = $new_instance['link_prev'];
		$instance['link_pause'] = $new_instance['link_pause'];
		$instance['link_play'] = $new_instance['link_play'];
		
		$instance['sortby'] = (isset($new_instance['sortby'])) ? $new_instance['sortby'] : 'display_order';
		$instance['order'] = (isset($new_instance['order']) && $new_instance['order']=='DESC') ? 'DESC' : 'ASC';

		$valid = array('top', 'bottom', 'both');

		$instance['link_position'] = (isset($new_instance['link_position']) && in_array($new_instance['link_position'], $valid)) ? $new_instance['link_position'] : 'bottom';
		$instance['autostart'] = (isset($new_instance['autostart']) && $new_instance['autostart']=='1') ? 1 : 0;
		$instance['word_limit'] = (isset($new_instance['word_limit'])) ? (int)$new_instance['word_limit'] : 0;
		$instance['char_limit'] = (isset($new_instance['char_limit'])) ? (int)$new_instance['char_limit'] : 0;

		return $instance;
	}

	public function widget($args, $instance) {
		global $wpdb, $blog_id, $hms_testimonials_random_strings;
		$settings = get_option('hms_testimonials');

		if (!isset($instance['group']))
			$instance['group'] = 0;

		if (!isset($instance['autostart']))
			$instance['autostart'] = 0;

		if (!isset($instance['word_limit']))
			$instance['word_limit'] = 0;

		if (!isset($instance['char_limit']))
			$instance['char_limit'] = 0;

		if (!isset($instance['show_links']))
			$instance['show_links'] = 0;

		if (!isset($instance['seconds']))
			$instance['seconds'] = 10;

		$order = ($instance['order'] == 'DESC') ? 'DESC' : 'ASC';
		$sort = $instance['sortby'];

		$sort_by_valid = array('id', 'name','testimonial','url','testimonial_date','display_order', 'image', 'rand');
		if (!in_array($sort, $sort_by_valid)) $sort = 'display_order';

		if ($instance['group'] == 0) {
			if ($sort == 'rand')
				$sort = 'RAND()';
			else
				$sort = '`'.$sort.'`';

			$get = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `display` = 1 AND `blog_id` = ".(int)$blog_id." ORDER BY ".$sort." ".$order, ARRAY_A);
		} else {
			if ($sort == 'display_order')
				$sort = 'm.display_order';
			elseif ($sort == 'rand')
				$sort = 'RAND()';
			else
				$sort = 't.'.$sort;

			$get = $wpdb->get_results("SELECT t.* FROM `".$wpdb->prefix."hms_testimonials` AS t INNER JOIN `".$wpdb->prefix."hms_testimonials_group_meta` AS m ON m.testimonial_id = t.id WHERE m.group_id = ".(int)$instance['group']." AND t.blog_id = ".$blog_id." AND t.display = 1 ORDER BY ".$sort." ".$order, ARRAY_A);
		}

		if (count($get)<1)
			return true;

		$identifier = $this->_randomstring();

		$link_text = ($instance['autostart'] == 1) ? $instance['link_pause'] : $instance['link_play'];
		$play_pause_class = ($instance['autostart'] == 1) ? 'pause' : 'play';


		echo $args['before_widget'];
		if (!empty($instance['title']))
			echo $args['before_title'].$instance['title'].$args['after_title'];

		echo '<div class="hms-testimonials-rotator" id="hms-testimonial-'.$identifier.'" data-start="' . (($instance['autostart'] == 0) ? 0 : 1) .'" data-seconds="' . $instance['seconds'] .'" data-play-text="' . $instance['link_play']. '" data-pause-text="' . $instance['link_pause'] . '">';

			if ($instance['show_links'] == 1 && ($instance['link_position'] == 'top' || $instance['link_position'] == 'both'))
				echo '<div class="controls"><a href="#" class="prev">'.$instance['link_prev'].'</a> <a href="#" class="playpause '.$play_pause_class.'">'.$link_text.'</a> <a href="#" class="next">'.$instance['link_next'].'</a></div>';

			echo '<div class="hms-testimonial-container" itemscope itemtype="http://schema.org/Review">';
				echo HMS_Testimonials::template($instance['template'], $get[0], (int)$instance['word_limit'], (int)$instance['char_limit']);
			echo '</div>';

			if ($instance['show_links'] == 1 && ($instance['link_position'] == 'bottom' || $instance['link_position'] == 'both'))
				echo '<div class="controls"><a href="#" class="prev">'.$instance['link_prev'].'</a> <a href="#" class="playpause '.$play_pause_class.'">'.$link_text.'</a> <a href="#" class="next">'.$instance['link_next'].'</a></div>';
		
		?>

			<div style="display:none;" class="hms-testimonial-items" id="hms-testimonial-list-<?php echo $identifier; ?>">

				<?php
					foreach($get as $g) {
						echo '<div itemscope itemtype="http://schema.org/Review">';
							echo HMS_Testimonials::template($instance['template'], $g, (int)$instance['word_limit'], (int)$instance['char_limit']);
						echo '</div>';
					} ?>
			</div>
		</div>
		<?php
		$autostart = ($instance['autostart'] == 1) ? 1 : 0;
		$link_pause = $instance['link_pause'];
		$link_play = $instance['link_play'];
		$seconds = $instance['seconds'].'000';

	
		echo $args['after_widget'];
	}

	public function _randomstring() {
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	$randstring = '';
    	for ($i = 0; $i < 5; $i++)
            $randstring .= $characters[rand(0, 51)];
        
    	return $randstring;
	}

}