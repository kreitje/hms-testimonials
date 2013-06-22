<?php

class HMS_Testimonials {

	private $roles = array();
	private $user_role = null;
	private $user_role_num = 0;

	private $options = array();

	private $current_user = null;
	private $wpdb = null;
	private $blog_id = 0;

	private static $instance = null;
	private static $template_cache = array();

	public function __construct() {
		global $wpdb, $blog_id;

		$this->wpdb = $wpdb;
		$this->blog_id = $blog_id;

		$this->current_user = wp_get_current_user();

		$roles = new WP_Roles();
		$this->roles = $roles->get_names();

		$getrole = $this->current_user->roles;
		$this->user_role = array_shift($getrole);

		$current_options = get_option('hms_testimonials');
		$default = array('administrator' => 5, 'editor' => 4, 'author' => 3, 'contributor' => 2, 'subscriber' => 1);

		$defaults = array(
			'role' => 'administrator',
			'autoapprove' => 'administrator',
			'resetapproval' => 1,
			'moderator' => 'administrator',
			'moderators_can_access_settings' => 0,
			'num_users_can_create' => 1,
			'use_recaptcha' => 0,
			'recaptcha_privatekey' => '',
			'recaptcha_publickey' => '',
			'roleorders' => $default,
			'image_width' => 100,
			'image_height' => 100,
			'date_format' => 'm/d/Y',
			'display_rows' => array('id','name','testimonial','url','testimonial_date','shortcode','user','display')
		);

		$this->options = array_merge($defaults, $current_options);

		if (isset($this->options['roleorders'][$this->user_role]))
			$this->user_role_num = $this->options['roleorders'][$this->user_role];


		foreach($this->roles as $i => $v) {
			if (!isset($this->options['roleorders'][$i]))
				$this->options['roleorders'][$i] = 0;
		}


		if (!isset($this->options['collation']) || ($this->options['collation'] == '')) {

			$collation = '';
			$get_collation = $this->wpdb->get_results("SHOW FULL COLUMNS FROM `".$this->wpdb->prefix."hms_testimonials`", ARRAY_A);
			foreach($get_collation as $g) {
				if ($g['Field'] == 'testimonial') {
					$collation = $g['Collation'];
					break;
				}
			}
			$this->options['collation'] = $collation;
			update_option('hms_testimonials', $this->options);
		}

		if (is_admin())
			self::enqueue_scripts();

	}

	public static function getInstance() {
		if (self::$instance == null)
			self::$instance = new HMS_Testimonials();

		return self::$instance;
	}

	public function admin_menus() {
		
		if ($this->user_role_num >= $this->options['roleorders'][$this->options['role']]) {
			$hook = add_menu_page('HMS Testimonials', 'Testimonials', $this->user_role, 'hms-testimonials', array($this, 'admin_page'));

			add_submenu_page('hms-testimonials', 'Add New Testimonial', '&nbsp;&nbsp;Add New', $this->user_role, 'hms-testimonials-addnew', array($this, 'testimonial_new_page'));
			add_submenu_page(null, 'View Testimonial', 'View Testimonial', $this->user_role, 'hms-testimonials-view', array($this, 'testimonial_view_page'));

		}

		$settings_role = 'administrator';

		if ($this->is_moderator()) {
			add_submenu_page('hms-testimonials', 'Groups', 'Groups', $this->user_role, 'hms-testimonials-groups', array($this, 'groups_page'));

			add_submenu_page(null, 'Add New Group', '&nbsp;&nbsp;Add New', $this->user_role, 'hms-testimonials-addnewgroup', array($this, 'groups_new_page'));
			add_submenu_page(null, 'Ajax Save', 'Ajax Save', $this->user_role, 'hms-testimonials-sortsave', array($this, 'ajax_sort_save'));
			add_submenu_page(null, 'View Group', 'View Group', $this->user_role, 'hms-testimonials-viewgroup', array($this, 'groups_view_page'));

			add_submenu_page(null, 'Delete Testimonial', 'Delete Testimonial', $this->user_role, 'hms-testimonials-delete', array($this, 'testimonial_delete_page'));
			add_submenu_page(null, 'Delete Testimonial From Group', 'Delete Testimonial From Group', $this->user_role, 'hms-testimonials-deletefg', array($this, 'testimonial_delete_from_group_page'));
			add_submenu_page(null, 'Delete Group', 'Delete Group', $this->user_role, 'hms-testimonials-deletegroup', array($this, 'groups_delete_page'));

			if ($this->options['moderators_can_access_settings'] == 1)
				$settings_role = $this->user_role;
				
		}

		add_submenu_page('hms-testimonials', 'Settings', 'Settings', $settings_role, 'hms-testimonials-settings', array($this, 'settings_page'));
		add_submenu_page('hms-testimonials', 'Advanced Settings', ' &nbsp; Advanced', $settings_role, 'hms-testimonials-settings-advanced', array($this, 'settings_advanced_page'));
		add_submenu_page('hms-testimonials', 'Custom Fields', ' &nbsp; Custom Fields', $settings_role, 'hms-testimonials-settings-fields', array($this, 'customfields_page'));
		add_submenu_page('hms-testimonials', 'Templates', ' &nbsp; Templates', $settings_role, 'hms-testimonials-templates', array($this, 'template_page'));

		add_submenu_page(null, 'Edit Custom Field', 'Edit Custom Field', $settings_role, 'hms-testimonials-settings-fields-edit', array($this, 'customfield_edit_page'));
		add_submenu_page(null, 'Delete Custom Field', 'Delete Custom Field', $settings_role, 'hms-testimonials-settings-fields-delete', array($this, 'customfield_delete_page'));
		add_submenu_page(null, 'Add a New Template', 'Add a New Template', $settings_role, 'hms-testimonials-templates-new', array($this, 'template_new_page'));
		add_submenu_page(null, 'Edit a Template', 'Edit a Template', $settings_role, 'hms-testimonials-templates-edit', array($this, 'template_edit_page'));
		add_submenu_page(null, 'Delete a Template', 'Delete a Template', $settings_role, 'hms-testimonials-templates-delete', array($this, 'template_delete_page'));

		add_submenu_page(null, 'Save Display Rows', 'Save Display Rows', $settings_role, 'hms-testimonials-templates-ajax-save-display-rows', array($this, 'ajax_display_rows_save'));

		if ($this->is_moderator()) {
			add_submenu_page('hms-testimonials', 'Short Codes', 'Short Codes', $this->user_role, 'hms-testimonials-shortcodes', array($this, 'shortcodes_page'));
			add_submenu_page('hms-testimonials', 'Documentation', 'Documentation', $this->user_role, 'hms-testimonials-help', array($this, 'help_page'));
		}
		add_submenu_page('hms-testimonials', 'New In 2.0', '<span style="font-weight:bold;color:red;">New In 2.0!</span>', $this->user_role, 'hms-testimonials-new-in-2-0', array($this, 'new_in_20'));
	}

	public static function enqueue_scripts() {
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('jquery-ui-droppable');
		wp_enqueue_script('jquery-ui-datepicker');

		wp_enqueue_style('plugin_name-admin-ui-css', plugins_url( '/jquery-ui.css' , __FILE__ ), false, '2.0.3', false);
	}

	public function admin_head() {
		
		?>
		<style type="text/css">
			.hms-testimonials-notice {
				background-color:#e0f5ff;
				border-color:#55a0e6;
				padding:0 .6em;
				margin:5px 0 15px;
				-webkit-border-radius: 3px;
				border-radius: 3px;
				border-width: 1px;
				border-style: solid;
			}
		</style>
		<?php

		$screen = get_current_screen();

		if ($screen->id == 'toplevel_page_hms-testimonials' || $screen->id == 'admin_page_hms-testimonials-viewgroup') {
			add_filter('screen_layout_columns', array($this, 'display_options'));
			$screen->add_option('display_options');
		}
	}

	public function display_options() {
		$screen = get_current_screen();
		
		?>
		<div style="padding:10px;">
			<form id="frm-display-rows" method="post" action="" />
				<strong>Show Columns</strong><br /><br />

				<input type="checkbox" class="hms-testimonial-row-selector" name="id" id="id" value="1"<?php if (in_array('id', $this->options['display_rows'])) echo ' checked="checked"'; ?> /> <label for="id">ID</label> &nbsp;&nbsp;
				<input type="checkbox" class="hms-testimonial-row-selector" name="name" id="name" value="1"<?php if (in_array('name', $this->options['display_rows'])) echo ' checked="checked"'; ?> /> <label for="name">Name</label> &nbsp;&nbsp;
				<input type="checkbox" class="hms-testimonial-row-selector" name="testimonial" id="testimonial" value="1"<?php if (in_array('testimonial', $this->options['display_rows'])) echo ' checked="checked"'; ?> /> <label for="testimonial">Testimonial</label> &nbsp;&nbsp;
				<input type="checkbox" class="hms-testimonial-row-selector" name="url" id="url" value="1"<?php if (in_array('url', $this->options['display_rows'])) echo ' checked="checked"'; ?> /> <label for="url">URL</label> &nbsp;&nbsp;
				<input type="checkbox" class="hms-testimonial-row-selector" name="testimonial_date" id="testimonial_date" value="1"<?php if (in_array('testimonial_date', $this->options['display_rows'])) echo ' checked="checked"'; ?> /> <label for="testimonial_date">Testimonial Date</label> &nbsp;&nbsp;
				<input type="checkbox" class="hms-testimonial-row-selector" name="shortcode" id="shortcode" value="1"<?php if (in_array('shortcode', $this->options['display_rows'])) echo ' checked="checked"'; ?> /> <label for="shortcode">Shortcode</label> &nbsp;&nbsp;
				<input type="checkbox" class="hms-testimonial-row-selector" name="user" id="user" value="1"<?php if (in_array('user', $this->options['display_rows'])) echo ' checked="checked"'; ?> /> <label for="user">User</label> &nbsp;&nbsp;
				<input type="checkbox" class="hms-testimonial-row-selector" name="display" id="display" value="1"<?php if (in_array('display', $this->options['display_rows'])) echo ' checked="checked"'; ?> /> <label for="display">Display</label> &nbsp;&nbsp;
				<br /><br />
				<?php
				$fields = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf` WHERE `blog_id` = ".(int)$this->blog_id." ORDER BY `name` ASC");
				if (count($fields)>0) {
					foreach($fields as $f) {
						echo '<input type="checkbox" class="hms-testimonial-row-selector" name="cf_'.$f->id.'" id="cf_'.$f->id.'" value="1" '; if (in_array('cf_'.$f->id, $this->options['display_rows'])) echo 'checked="checked"'; echo ' /> <label for="cf_'.$f->id.'">'.$f->name.'</label> &nbsp;&nbsp;';
					}
				} ?>
			</form>
		</div>
		<?php
	}


	public function settings_link($links, $file = '') {

		if ($file == plugin_basename(dirname(__FILE__).'/hms-testimonials.php')){
			$settings_link = '<a href="'.admin_url('admin.php?page=hms-testimonials-settings').'">Settings</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;

	}

	private function can_access($option) {

		if ($this->is_moderator())
			return true;

		if (!isset($this->options[$option]))
			return false;

		if ($this->options['roleorders'][$this->options[$option]] == '')
			return false;

		if ($this->user_role_num >= $this->options['roleorders'][$this->options[$option]])
			return true;

		return false;

	}

	private function is_moderator() {

		if ($this->user_role_num >= $this->options['roleorders'][$this->options['moderator']])
			return true;

		return false;
	}

	private function load_sortable() {
		$return = <<<JS
		<script type="text/javascript">
				var fixHelper = function(e, ui) {
					ui.children().each(function() {
						jQuery(this).width(jQuery(this).width());
					});
					return ui;
				};

				jQuery(document).ready(function() {
					jQuery("#sortable tbody").sortable({
						helper: fixHelper,
						update: function(event, ui) {
							jQuery.post(jQuery("#sort-update").attr('action'), jQuery("#sort-update").serialize());
						}
					});
				});
		</script>
JS;

		return $return;
	}

	public function new_in_20() {
		?>
		<div class="wrap">
			<h2>Awesome New Things in <strong>2.0</strong></h2>
			<br /><br />

			<div style="padding-left:40px;">
				<h3>1. Easier Settings</h3>
				<p>We have moved the advanced settings to their own page.</p>
				<br />

				<h3>2. Custom Fields</h3>
				<p>Add custom fields to your testimonials. Show them on the public form if you would like as well.</p>
				<br />

				<h3>3. Custom Templates</h3>
				<p>Build your <strong>OWN</strong> templates with the fields you need using drag and drop. Crap just got real! Don't worry though, we imported the default 
					templates from the old system for you.</p>
				<br />

				<h3>4. Short Codes Menu Item</h3>
				<p>Use the Short Codes menu item to build your shotcode with commonly used items.</p>
				<br />

				<h3>5. Add Images To Your Testimonial</h3>
				<p>Need to upload a face or log with a testimonial? We've got you covered. Use the WordPress gallery system to add your images.</p>

				<br /><br /><br />
				<center><iframe width="640" height="480" src="http://www.youtube.com/embed/gPyVU1gExhA" frameborder="0" allowfullscreen></iframe></center>
			</div>
		<?php
	}

	public function settings_page() {

		if (isset($_POST) && (count($_POST)>0)) {
			
			$options = $this->options;
			
			$options['show_active_links'] = (isset($_POST['show_active_links']) && $_POST['show_active_links'] == '1') ? 1 : 0;
			$options['active_links_nofollow'] = (isset($_POST['active_links_nofollow']) && $_POST['active_links_nofollow'] == '1') ? 1 : 0;

			$options['use_recaptcha'] = (isset($_POST['use_recaptcha']) && $_POST['use_recaptcha'] == '1') ? 1 : 0;
			$options['recaptcha_privatekey'] = (isset($_POST['recaptcha_privatekey'])) ? $_POST['recaptcha_privatekey'] : '';
			$options['recaptcha_publickey'] = (isset($_POST['recaptcha_publickey'])) ? $_POST['recaptcha_publickey'] : '';

			$options['image_width'] = (isset($_POST['image_width'])) ? (int)$_POST['image_width'] : 100;
			$options['image_height'] = (isset($_POST['image_height'])) ? (int)$_POST['image_height'] : 100;
			$options['date_format'] = (isset($_POST['date_format'])) ? $_POST['date_format'] : 'm/d/Y';

			update_option('hms_testimonials', $options);
			$this->options = $options;
			$updated = 1;
		}
		?>

		<div class="wrap">
			<div id="icon-edit-comments" class="icon32"></div>
			<h2>HMS Testimonials Settings</h2>
			<br />

			<?php if (isset($updated) && $updated == 1) { ?>
			<div id="message" class="updated">
				<p>Your settings have been updated.</p>
			</div>
			<?php } ?>

			<style type="text/css">
			.form-table th { width:auto !important;}
			</style>

			<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-settings'); ?>">

				<div style="float:left;width:50%;">
					<h3>Settings</h3>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">1. If a testimonial has a url, show it as an active link?</th>
								<td><input type="checkbox" name="show_active_links" value="1" <?php if ($this->options['show_active_links']==1) echo ' checked="checked"'; ?> /></td>
							</tr>

							<tr>
								<th scope="row">2. Add a nofollow relationship to the active link of a testimonial?</th>
								<td><input type="checkbox" name="active_links_nofollow" value="1" <?php if ($this->options['active_links_nofollow']==1) echo ' checked="checked"'; ?> /></td>
							</tr>

							<tr>
								<td colspan="2"><strong>Adding an image to your testimonials? Set the dimensions here.</strong></td>
							</tr>
							<tr>
								<th scope="row">3. Width of the image</th>
								<td><input type="text" name="image_width" value="<?php echo $this->options['image_width']; ?>" size="3" />px</td>
							</tr>
							<tr>
								<th scope="row">4. Height of the image</th>
								<td><input type="text" name="image_height" value="<?php echo $this->options['image_height']; ?>" size="3" />px</td>
							</tr>
							<tr>
								<th scope="row">5. Date format</th>
								<td><input type="text" name="date_format" value="<?php echo $this->options['date_format']; ?>" size="10" /> <a href="<?php echo admin_url('admin.php?page=hms-testimonials-help'); ?>#date_format" target="_blank">Read More</a></td>
							</tr>
						</tbody>
					</table>
					<br />

					<h3>reCAPTCHA Settings</h3>
					<p>We offer a shortcode to allow your visitors to submit testimonials. If you would like to use reCAPTCHA for spam measures, enter those settings here.</p>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">Use reCAPTCHA?</th>
								<td><input type="checkbox" name="use_recaptcha" value="1" <?php if ($this->options['use_recaptcha']==1) echo ' checked="checked"'; ?> /></td>
							</tr>
							<tr>
								<th scope="row">Public Key</th>
								<td><input type="text" name="recaptcha_publickey" value="<?php echo $this->options['recaptcha_publickey']; ?>" /></td>
							</tr>
							<tr>
								<th scope="row">Private Key</th>
								<td><input type="text" name="recaptcha_privatekey" value="<?php echo $this->options['recaptcha_privatekey']; ?>" /></td>
							</tr>
							<tr>
								<th scope="row" colspan="2"><br />
									<strong>Need a reCAPTCHA account?</strong> &nbsp;&nbsp;&nbsp;<a href="http://www.google.com/recaptcha" target="_blank">Sign Up Here It's Free!</a>
								</th>
							</tr>
						</tbody>
					</table>

					<p class="submit"><input type="submit" class="button-primary" name="save" value="Save Settings" /></p>
				</div>

			</form>
		</div>

		<?php
		
	}

	public function settings_advanced_page() {

		$col = $this->wpdb->get_results("SHOW COLLATION WHERE `Default` = 'Yes'", ARRAY_A);

		if (isset($_POST) && (count($_POST)>0)) {
			
			$options = $this->options;
			$options['moderator'] = (isset($_POST['moderator'])) ? $_POST['moderator'] : 'administrator';
			$options['role'] = $_POST['roles'];
			$options['num_users_can_create'] = (isset($_POST['num_users_can_create'])) ? (int)$_POST['num_users_can_create'] : 1;
			$options['autoapprove'] = $_POST['autoapprove'];
			$options['resetapproval'] = (isset($_POST['resetapproval']) && $_POST['resetapproval'] == '1') ? 1 : 0;
			$options['moderators_can_access_settings'] = (isset($_POST['moderators_can_access_settings']) && $_POST['moderators_can_access_settings'] == '1') ? 1 : 0;


			$x = count($_POST['roleorder']);
			$order = array();

			$order['administrator'] = $x+1;
			foreach($_POST['roleorder'] as $index => $v) {
				if (!isset($this->roles[$v])) continue;
				if ($v == 'administrator') continue;

				$order[$v] = $x;
				$x--;
			}
			$options['roleorders'] = $order;

			$charset = 'utf8';
			$collate = 'utf8_general_ci';

		
			if ($options['collation'] != $_POST['collation']) {
				$charset = '';
				$collate = '';
				foreach($col as $c) {
					if ($c['Collation'] == $_POST['collation']) {
						$charset = $c['Charset'];
						$collate = $c['Collation'];
					}
				}


				$this->wpdb->query("ALTER TABLE  `".$this->wpdb->prefix."hms_testimonials` 
						CHANGE  `name`  `name` TEXT CHARACTER SET ".$charset." COLLATE ".$collate." NOT NULL,
						CHANGE  `testimonial`  `testimonial` TEXT CHARACTER SET ".$charset." COLLATE ".$collate." NOT NULL,
						CHANGE  `url`  `url` VARCHAR( 255 ) CHARACTER SET ".$charset." COLLATE ".$collate." NOT NULL DEFAULT  ''");

				$options['collation'] = $collate;
			}


			
			update_option('hms_testimonials', $options);

			$this->options = $options;



			$updated = 1;
		}
		?>

		<div class="wrap">
			<div id="icon-edit-comments" class="icon32"></div>
			<h2>HMS Testimonials Advanced Settings</h2>
			<br />

			<?php if (isset($updated) && $updated == 1) { ?>
			<div id="message" class="updated">
				<p>Your settings have been updated.</p>
			</div>
			<?php } ?>

			<style type="text/css">
			.form-table th { width:auto !important;}
			</style>

			<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-settings-advanced'); ?>">

				<div style="float:left;width:50%;">
					<h3>Advanced Settings</h3>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">1. Minimum role to be a moderator:<br />
									<strong><small>Moderators can add/edit/delete ALL testimonails and groups.</small></strong></th>
								<td>
									<select name="moderator">
										<?php foreach($this->options['roleorders'] as $v => $i) {
											echo '<option value="'.$v.'"'. (($this->options['moderator']==$v) ? ' selected="selected"' : '').'>'.$this->roles[$v].'</option>';
										} ?>
									</select>
								</td>
							</tr>
							
							<tr>
								<th scope="row">2. Minimum role to add/edit testimonials:</th>
								<td>
									<select name="roles">
										<?php foreach($this->options['roleorders'] as $v => $i) {
											echo '<option value="'.$v.'"'. (($this->options['role']==$v) ? ' selected="selected"' : '').'>'.$this->roles[$v].'</option>';
										} ?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">3. Number of testimonials a non moderator can create:</th>
								<td><input type="text" name="num_users_can_create" value="<?php echo $this->options['num_users_can_create']; ?>" size="3" /></td>
							</tr>

							<tr>
								<th scope="row">4. Minimum role a user can mark their testimonial displayed:</th>
								<td>
									<select name="autoapprove">
										<?php foreach($this->options['roleorders'] as $v => $i) {
											echo '<option value="'.$v.'"'. (($this->options['autoapprove']==$v) ? ' selected="selected"' : '').'>'.$this->roles[$v].'</option>';
										} ?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">5. If a user is below the role level in option 4, set displayed field to <strong>NO</strong> when that user changes or updates their testimonial?</th>
								<td><input type="checkbox" name="resetapproval" value="1" <?php if ($this->options['resetapproval']==1) echo ' checked="checked"'; ?> /></td>
							</tr>
							<tr>
								<th scope="row">6. Moderators can access the settings page?</th>
								<td><input type="checkbox" name="moderators_can_access_settings" value="1" <?php if ($this->options['moderators_can_access_settings']==1) echo ' checked="checked"'; ?> /></td>
							</tr>
							<tr>
								<th scope="row">7. Character Set/Collation<br />
									<strong>Note:</strong> Changing this will modify your tables.</th>
								<td><select name="collation">
									<?php 
									foreach($col as $c) {
										echo '<option value="'.$c['Collation'].'"'; if ($this->options['collation'] == $c['Collation']) echo ' selected="selected"'; echo '>'.$c['Collation'].'</option>';
									} ?>
									</select>
								</td>
							</tr>
							
						</tbody>
					</table>
					<br />

					<p class="submit"><input type="submit" class="button-primary" name="save" value="Save Settings" /></p>
				</div>

				<div style="float:right;width:40%;">

					<table id="sortable" class="wp-list-table widefat">
						<thead>
							<tr><th>Role</th><th>#</th></tr>
						</thead>
						<tbody>
							<?php 
							foreach($this->options['roleorders'] as $v => $i) {
								if ($v != 'administrator')
								echo '<tr><td>'.$this->roles[$v].'<input type="hidden" name="roleorder[]" value="'.$v.'" /></td><td>'.$i.'</td></tr>';	
							}
							?>
						</tbody>
					</table>
					<p>Set the role order. Any dropdown on the left that you select, any user with that role or a higher role will be permitted that action. This 
						allows you to let non administrators have some control over the testimonials.</p>

					<strong>Drag and drop to sort roles by importance.</strong>

				</div>
			</form>
		</div>

		<?php echo $this->load_sortable();
	}

	
	public function admin_page() {

		$rows_to_show = array('id','name','testimonial','url','testimonial_date','shortcode','user','display');

		$fields = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf` WHERE `blog_id` = ".(int)$this->blog_id." ORDER BY `name` ASC");
		$field_count = count($fields);
		$row_th = '';

		if ($field_count > 0) {

			foreach($fields as $f) {
				$rows_to_show[] = 'cf_'.$f->id;
				$row_th .= '<th class="row-cf_'.$f->id.'">'.$f->name.'</th>';
			}
		}

		?>
		<style type="text/css">
			<?php foreach($rows_to_show as $r) {
				if (in_array($r, $this->options['display_rows'])) {
					?>.row-<?php echo $r; ?> { display:table-cell; }<?php
				} else {
					?>.row-<?php echo $r; ?> { display:none;}<?php
				}
			} ?>
		</style>
		<div class="wrap">
			<div id="icon-edit-comments" class="icon32"></div>
			<h2><?php if ($this->is_moderator()) { ?>HMS Testimonials<?php } else { ?>My Testimonials<?php } ?></h2>
			<br /><br />
			<?php if (isset($_GET['message']) && ($_GET['message'] != '')) { ?>
				<div id="message" class="updated"><p><?php echo strip_tags(urldecode($_GET['message'])); ?></p></div>
			<?php } ?>


			<?php if ($this->is_moderator()) { ?><h3 align="center">Shortcode: [hms_testimonials]</h3><?php } ?>
			<br />
			<?php if ($this->is_moderator()) { ?><strong>Note:</strong> You can drag and drop to set the display order.<br /><?php } ?>
			<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-sortsave&noheader=true'); ?>" id="sort-update">
			<input type="hidden" name="type" value="testimonials" />
			<table class="wp-list-table widefat" id="sortable">
				<thead>
					<tr>
						<th class="row-id">ID</th>
						<th class="row-name">Name</th>
						<th class="row-testimonial">Testimonial</th>
						<th class="row-url">URL</th>
						<th class="row-testimonial_date">Testimonial Date</th>
						<th class="row-shortcode">Shortcode</th>
						<th class="row-user">User</th>
						<th class="row-display">Display?</th>
						<?php echo $row_th; ?>
						<th>Action</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th class="row-id">ID</th>
						<th class="row-name">Name</th>
						<th class="row-testimonial">Testimonial</th>
						<th class="row-url">URL</th>
						<th class="row-testimonial_date">Testimonial Date</th>
						<th class="row-shortcode">Shortcode</th>
						<th class="row-user">User</th>
						<th class="row-display">Display?</th>
						<?php echo $row_th; ?>
						<th>Action</th>
					</tr>
				</tfoot>
				<tbody>
					<?php

					if ($this->is_moderator()) {
						$get = $this->wpdb->get_results("SELECT t.*, u.user_login 
													FROM `".$this->wpdb->prefix."hms_testimonials` AS t 
													LEFT JOIN `".$this->wpdb->users."` AS u 
														ON u.ID = t.user_id
													WHERE t.blog_id = ".(int)$this->blog_id." ORDER BY t.display_order ASC", ARRAY_A);
					} else {
						$get = $this->wpdb->get_results("SELECT t.*, u.user_login 
														FROM `".$this->wpdb->prefix."hms_testimonials` AS t 
														LEFT JOIN `".$this->wpdb->users."` AS u
															ON u.ID = t.user_id
														WHERE t.blog_id = ".(int)$this->blog_id." AND t.user_id = ".(int)$this->current_user->ID." ORDER BY t.display_order ASC", ARRAY_A);
					}

					if (count($get)<1) { ?>
						<tr>
							<td colspan="8">No testimonials exist</td>
						</tr>
					<?php } else { 

						foreach($get as $g) {
							$t_fields = array();
							if ($field_count > 0) {
								$get_t_fields = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf_meta` WHERE `testimonial_id` = ".(int)$g['id']);
								if (count($get_t_fields)>0) {
									foreach($get_t_fields as $f)
										$t_fields[$f->key_id] = $f->value;

								}
							}
							?>
							<tr>
								<td class="row-id"><input type="hidden" name="sort[]" value="<?php echo $g['id']; ?>" /><a href="<?php echo admin_url('admin.php?page=hms-testimonials-view&id='.$g['id']); ?>"><?php echo $g['id']; ?></a></td>
								<td class="row-name"><?php echo nl2br($g['name']); ?></td>
								<td class="row-testimonial" width="250"><?php echo substr(nl2br($g['testimonial']),0,100).'...'; ?></td>
								<td class="row-url"><?php echo $g['url']; ?></td>
								<td class="row-testimonial_date"><?php if ($g['testimonial_date'] != '0000-00-00 00:00:00') echo date($this->options['date_format'], strtotime($g['testimonial_date'])); else echo 'Not Set'; ?></td>
								<td class="row-shortcode">[hms_testimonials id="<?php echo $g['id']; ?>"]</td>
								<td class="row-user"><?php if ($g['user_id'] == 0) echo 'Website Visitor'; else echo $g['user_login']; ?></td>
								<td class="row-display"><?php echo ($g['display']==1) ? 'Yes' : 'No'; ?></td>
								<?php if ($field_count>0) {
									foreach($fields as $f) {
										echo '<td class="row-cf_'.$f->id.'">';
											if (isset($t_fields[$f->id]))
												echo $t_fields[$f->id];
										echo '</td>';
									}
								} ?>
								<td>
									<a href="<?php echo admin_url('admin.php?page=hms-testimonials-view&id='.$g['id']); ?>">Edit</a> &nbsp;|&nbsp; 
									<a href="<?php echo admin_url('admin.php?page=hms-testimonials-delete&id='.$g['id'].'&noheader=true'); ?>" onclick="if (!confirm('Are you sure you want to delete this testimonial?')) return false;">Delete</a>
								</td>
							</tr>
							<?php
						}
					} ?>
				</tbody>
			</table>
			</form>
			<br />
			<a class="button-primary" href="<?php echo admin_url('admin.php?page=hms-testimonials-addnew'); ?>">New Testimonial</a>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('.hms-testimonial-row-selector').click(function() {
					jQuery('.hms-testimonial-row-selector').each(function() {
						if (jQuery(this).is(':checked')) {
							jQuery('#sortable tr .row-' + jQuery(this).attr('id')).css('display', 'table-cell');
						} else {
							jQuery('#sortable tr .row-' + jQuery(this).attr('id')).css('display', 'none');
						}
					});

					var data = jQuery('#frm-display-rows').serialize();
					jQuery.post('<?php echo admin_url('admin.php?page=hms-testimonials-templates-ajax-save-display-rows&noheader=true&'); ?>', data, function(response) {
						console.log(response);
					});
				});
			});
		</script>

		<?php 
		if ($this->is_moderator())
			echo $this->load_sortable();
	}

	public function help_page() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2>Documentation</h2>

			<p>This plugin allows you to add customer testimonials to your site in an easy to manage way. HMS Testimonials offers 2 shortcodes with multiple options and 2 widgets.</p>
			<br />
			<strong>Jump To:</strong> <a href="#hms_testimonials_features">Features</a> | 
				<a href="#hms_testimonials_shortcodes">Shortcodes</a> | 
				<a href="#hms_testimonials_widgets">Widgets</a> | 
				<a href="#hms_testimonials_css">CSS Classes</a> | 
				<a href="#hms_testimonials_templates">Templates</a> | 
				<a href="#date_format">Date Format</a> | 
				<a href="#form_shortcode">Form Short Code</a>
			<br /><br />

			
			<h3 id="hms_testimonials_features">Features</h3>

			<ol>
				<li>Set permissiosn based on user roles to allow your users to add testimonials</li>
				<li>Drag and Drop to set the display order for all testimonials even in groups</li>
				<li>Hide items by unchecking their Display checkbox</li>
				<li>Add a testimonial to 1 or more groups</li>
				<li>Use our shortcodes to show all, a group or just 1 testimonial</li>
				<li>Use our widgets to display your testimonials in the sidebar</li>
			</ol>

			<br /><br />

			<h3 id="hms_testimonials_shortcodes">Shortcodes</h3>

			<p>Our shortcode <strong>[hms_testimonials]</strong> offers a few options.</p>
			<ol>
				<li><strong>[hms_testimonials]</strong> &nbsp; Shows all of your testimonials that are set to be displayed.</li>
				<li><strong>[hms_testimonials group="1"]</strong> &nbsp; Shows all of your testimonials in a particular group defined by "group". In this case, group 1</li>
				<li><strong>[hms_testimonials id="1"]</strong> &nbsp; Only shows 1 testimonial with the id specified. In this case, 1.</li>
				<li><strong>[hms_testimonials template="1"]</strong> &nbsp; Sets which template to use. By default it uses 1 (Testimonial, Author, URL).</li>
				<li><strong>[hms_testimonials limit="15" start="1" next="&raquo;" prev="&laquo;" location="both"]</strong> &nbsp; If you want to limit the number of results shown and paginate them 
					you can use the limit attribute. If you need to skip a few before starting change the start number. The next an prev attributes set the text for the next and previous link in the 
					page numbers. Lastly, location sets where to display the page numbers. Both places it at the top and buttom, top places it only at the top, and bottom only at the bottom. The default 
					for limit is -1 which will show all testimonials and not use any paging.
			</ol>

			<br /><br />
			<p>We also offer <strong>[hms_testimonials_rotating]</strong> to rotate your testimonials inside of a page or post.</p>
			<ol>
				<li><strong>[hms_testimonials_rotating]</strong> &nbsp; Rotates through all of your testimonials that are set to be displayed</li>
				<li><strong>[hms_testimonials group="1"]</strong> &nbsp; Rotates through all of your testimonials in a particular group defined by "group". In this case, group 1</li>
				<li><strong>[hms_testimonials template="1"]</strong> &nbsp; Sets which template to use. By default it uses 1 (Testimonial, Author, URL).
				<li><strong>[hms_testimonials seconds="6"]</strong> &nbsp; Sets the interval in seconds for how often the testimonials are rotated.</li>
				<li><strong>[hms_testimonials show_links="true"]</strong> &nbsp; Show Prev,Pause(Play) and Next links. Defaults to false</li>
				<li><strong>[hms_testimonials link_prev="Previous"]</strong> &nbsp; Text for the previous link. Defaults to &laquo;</li>
				<li><strong>[hms_testimonials link_next="Next"]</strong> &nbsp; Text for the next link. Defaults to &raquo;</li>
				<li><strong>[hms_testimonials link_pause="Pause"]</strong> &nbsp; Text for the pause link. Defaults to Pause</li>
				<li><strong>[hms_testimonials link_next="Play"]</strong> &nbsp; Text for the play link. Defaults to Play</li>
			</ol>

			<br /><br />

			<p>Use <strong>[hms_testimonials_form]</strong> to allow your visitors to submit testimonials.  To help combat spam you can enable reCAPTCHA in the settings.</p>

			<p>Place these shortcodes in your posts or pages. If you prefer to stick them in your sidebar see below for the widgets we offer.</p>

			<br /><br />
			<h3 id="hms_testimonials_widgets">Widgets</h3>
			<p>We offer a standard widget called HMS Testimonials where you can display all, a group or a single testimonial. We also offer a rotating widget called 
				HMS Testimonial Rotator that will show 1 at a time of the entire list or a group and swap them out after x amount of seconds</p>


			<br /><br />
			<h3 id="hms_testimonials_css">CSS Classes</h3>
			<p>We have added some classes to different parts of the testimonial to allow you better styling.</p>
			<table width="100%">
				<tr>
					<td>hms-testimonial-container</td>
					<td>A div container that the testimonial sits in</td>
				</tr>
				<tr>
					<td> &nbsp;&nbsp; testimonial</td>
					<td>A div container that the testimonial text is wrapped in</td>
				</tr>
				<tr>
					<td> &nbsp;&nbsp; author</td>
					<td>A div container that the author/name is wrapped in</td>
				</tr>
				<tr>
					<td> &nbsp;&nbsp; url</td>
					<td>A div container that the URL if entered is wrapped in</td>
				</tr>
				<tr>
					<td>hms-testimonial-group</td>
					<td>A div container that contains all the testimonials. Only applicable when all or a group of testimonials are shown.</td>
				</tr>
				<tr>
					<td>hms-testimonial-single</td>
					<td>Added to the hms-testimonial-container class if only 1 testimonial is shown.</td>
				</tr>
				<tr>
					<td>paging</td>
					<td>A div container for any pagination elements</td>
				</tr>
				<tr>
					<td> &nbsp;&nbsp; current-page</td>
					<td>A span element that is the current page number.</td>
				</tr>
				<tr>
					<td> &nbsp;&nbsp; prev</td>
					<td>The previous page link</td>
				</tr>
				<tr>
					<td> &nbsp;&nbsp; next</td>
					<td>The next page link</td>
				</tr>

				<tr>
					<td>hms-testimonials-rotator</td>
					<td>Added to the parent testimonial container for rotating testimonials</td>
				</tr>
				<tr>
					<td> &nbsp;&nbsp; controls</td>
					<td>A div container for the previous, pause/play and next links</td>
				</tr>
				<tr>
					<td> &nbsp;&nbsp;&nbsp;&nbsp; prev</td>
					<td>The previous link</td>
				</tr>
				<tr>
					<td> &nbsp;&nbsp;&nbsp;&nbsp; next</td>
					<td>The next link</td>
				</tr>
				<tr>
					<td> &nbsp;&nbsp;&nbsp;&nbsp; playpause</td>
					<td>The play/pause link</td>
				</tr>

				<tr>
					<td> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; play</td>
					<td>A class added to the play/pause link when showing the play text</td>
				</tr>
				<tr>
					<td> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; pause</td>
					<td>A class added to the play/pause link when showing the pause text</td>
				</tr>
			</table>

			<h3 id="date_format">Date Format</h3>
			<p>You can set the format of the date when showing it in your testimonials. The format is based on the way <a href="http://www.php.net/manual/en/function.date.php" target="_blank">PHP</a> does it.</p>

			<strong>Common Formats</strong>
			<table width="50%">
				<tr>
					<td>USA</td>
					<td>m/d/Y</td>
					<td><?php echo date('m/d/Y'); ?></td>
				</tr>
				<tr>
					<td>European</td>
					<td>d/m/Y</td>
					<td><?php echo date('d/m/Y'); ?>
				</tr>
				<tr>
					<td>Month Name</td>
					<td>F d, Y</td>
					<td><?php echo date('F d, Y'); ?>
				</tr>
				<tr>
					<td>Month Name Short</td>
					<td>M d, Y</td>
					<td><?php echo date('M d, Y'); ?>
				</tr>
			</table>
			<br />

			<h3 id="form_shortcode">Form Shortcode</h3>
			<p>The [hms_testimonials_form] shortcode allows you to have visitors submit testimonials to you. 
				When they do, you will receive an email and can go approve the testimonial.</p>

			<p>At times you may want to change the text on the form fields.  We have built in 4 filters for the default fields.</p>
			<ol>
				<li>hms_testimonials_sc_name - Changes the "Name" text.</li>
				<li>hms_testimonials_sc_website - Changes the "Website" text.</li>
				<li>hms_testimonials_sc_testimonial - Changes the "Testimonial" text.</li>
				<li>hms_testimonials_sc_submit - Changes the text for the submit button.</li>
			</ol>

			<p>You can add these filters into your themes functions.php file.</p>
			<br />

			<strong>Example to change "Name" to "Please Enter Your Name":</strong>
			<pre style="color:red;">
function hms_name_override($text) {
	return 'Please Enter Your Name';
}
add_filter('hms_testimonials_sc_name', 'hms_name_override');
			</pre>


			</pre>

			<br /><br />

			<br /><br />
			<div align="center">
				<a href="http://hitmyserver.com" target="_blank"><img src="<?php echo plugin_dir_url(__FILE__); ?>images/logo.gif" alt="HitMyServer LLC" /></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Testimonials
	 **/

	public function testimonial_new_page() {

		$image_url = '';

		if (!$this->is_moderator() && $this->options['num_users_can_create']>=0) {
			$get_count = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials` WHERE `user_id` = ".(int)$this->current_user->ID);
			if (count($get_count)>= $this->options['num_users_can_create']) {
				?>
				<div class="wrap">
					<div id="icon-edit-pages" class="icon32"></div><h2>Add A New Testimonial</h2>
					<br />
					<div id="message" class="updated"><p>Sorry, you have reached the amount of testimonials you can leave.</p></div>

				</div>
				<?php
				return;
			}
		}


		$get_groups = $this->wpdb->get_results("SELECT g.*, (SELECT COUNT(id) FROM `".$this->wpdb->prefix."hms_testimonials_group_meta` WHERE `group_id` = g.id) AS `testimonials` FROM `".$this->wpdb->prefix."hms_testimonials_groups` AS g WHERE g.blog_id = ".(int)$this->blog_id." ORDER BY `name` ASC", ARRAY_A);
		$groups = array();

		foreach($get_groups as $g)
			$groups[$g['id']] = $g['name'];

		$fields = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf` WHERE `blog_id` = ".(int)$this->blog_id." ORDER BY `name` ASC");
		$fields_count = count($fields);
		
		$errors = array();
		if (isset($_POST) && (count($_POST)>0)) {
			if (!isset($_POST['name']) || trim($_POST['name']) == '')
				$errors[] = 'Please enter a name for this testimonial.';

			if (!isset($_POST['testimonial']) || (trim($_POST['testimonial'])==''))
				$errors[] = 'You forgot to enter the testimonial.';


			if ($fields_count>0) {
				foreach($fields as $f) {

					if ($f->isrequired == 1 && (!isset($_POST['cf'][$f->id]) || trim($_POST['cf'][$f->id])=='')) {
						$errors[] = $f->name.' is a required field.';
						continue;
					}

					switch($f->type) {
						case 'email':
							if (!filter_var($_POST['cf'][$f->id], FILTER_VALIDATE_EMAIL))
								$errors[] = 'Please enter a valid email for the '.$f->name.' field.';
						break;
					}

				}
			}

			$url = '';
			if (isset($_POST['url'])&&(trim($_POST['url'])!='')) {
				if (substr($_POST['url'],0,4)!='http')
					$url = 'http://'.$_POST['url'];
				else
					$url = $_POST['url'];
			}

			$testimonial_date = '0000-00-00 00:00:00';
			if (isset($_POST['testimonial_date']) && ($_POST['testimonial_date'] != '')) {
				try {
					$tdate = new DateTime($_POST['testimonial_date']);
					if (($testimonial_date = $tdate->format('Y-m-d H:i:s')) === false)
						$testimonial_date = '0000-00-00 00:00:00';
				} catch(Exception $e) {
					$errors[] = 'Please enter a valid testimonial date. Use the format mm/dd/YYYY such as '.date('m/d/Y');
				}
			}

			if (isset($_POST['image']) && ($_POST['image'] != 0))
				$image_url = wp_get_attachment_url($_POST['image']);
			


			$display = 0;
			if ($this->can_access('autoapprove')) {
				if (isset($_POST['display']) && ($_POST['display']=='1'))
					$display = 1;
			}

			if (count($errors)<1) {

				$_POST = stripslashes_deep($_POST);

				$display_order = $this->wpdb->get_var("SELECT `display_order` FROM `".$this->wpdb->prefix."hms_testimonials` ORDER BY `display_order` DESC LIMIT 1");

				$this->wpdb->insert($this->wpdb->prefix."hms_testimonials", 
					array(
						'blog_id' => $this->blog_id, 'user_id' => $this->current_user->ID, 'name' => trim($_POST['name']), 
						'testimonial' => trim($_POST['testimonial']), 'display' => $display, 'display_order' => ($display_order+1),
						'url' => $url, 'testimonial_date' => $testimonial_date, 'created_at' => date('Y-m-d h:i:s'),
						'image' => (($image_url != '') ? (int)$_POST['image'] : 0)));

				$id = $this->wpdb->insert_id;
				$added = 1;

				if ($fields_count>0) {
					foreach($fields as $f) {
						if (isset($_POST['cf'][$f->id]) && trim($_POST['cf'][$f->id])!='') {
						
							$this->wpdb->insert($this->wpdb->prefix."hms_testimonials_cf_meta", 
								array(
									'testimonial_id' => $id,
									'key_id' => $f->id,
									'value' => $_POST['cf'][$f->id]
								)
							);

						}
					}
				}


				if ($this->is_moderator()) {
					if (isset($_POST['groups']) && is_array($_POST['groups'])) {
						foreach($_POST['groups'] as $gid) {
							if (isset($groups[$gid])) {
								$row_count = $this->wpdb->get_var("SELECT COUNT(*) FROM `".$this->wpdb->prefix."hms_testimonials_group_meta` WHERE `group_id` = ".(int)$gid);

								$this->wpdb->insert($this->wpdb->prefix."hms_testimonials_group_meta", array('testimonial_id' => $id, 'group_id' => $gid, 'display_order' => ($row_count + 1)));
							}
						}
					}
				} else {

					$message = $this->current_user->user_login.' has added a testimonial to your site '.get_bloginfo('name')."\r\n\r\n";
					$message .= 'Name: '. trim($_POST['name'])."\r\n";
					$message .= 'Website: '.$url."\r\n";
					$message .= 'Testimonial: '. trim($_POST['testimonial'])."\r\n";
					$message .= 'Displayed: '. (($display==1) ? 'Yes' : 'No')."\r\n";
					$message .= 'Testimonial Date: '.$testimonial_date."\r\n";
					$message .= "\r\n\r\n";
					$message .= 'View this testimonial at '.admin_url('admin.php?page=hms-testimonials-view&id='.$id);

					wp_mail(get_bloginfo('admin_email'), 'New Testimonial Added to '.get_bloginfo('name'), $message);
				}

				unset($_POST);
				unset($image_url);
			}
		} else {
			$display = 1;
		}

		wp_enqueue_media();

		?>
		<div class="wrap">
			<div id="icon-edit-pages" class="icon32"></div><h2>Add A New Testimonial</h2>
			<br />

			<?php if (isset($added)) {
				echo '<div id="message" class="updated"><p>Your testimonial has been saved.</p></div>';
			}
			if (count($errors)>0) {
				echo '<div class="error"><p><strong>The following errors occured:</strong></p><ol>';
				foreach($errors as $e)
					echo '<li>'.$e.'</li>';
				echo '</ol></div>';
			} ?>

			<br />

			<style type="text/css">
				.testimonial-image {
					height: <?php echo $this->options['image_height']; ?>px;
					width: <?php echo $this->options['image_width']; ?>px;
				}
			</style>

			<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-addnew'); ?>" accept-charset="UTF-8">
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">

						<div class="stuffbox">
							<h3><label for="name"><span style="color:red;">*</span> Source of Testimonial</label></h3>
							<div class="inside">
								<textarea id="name" name="name"  style="width:99%;" rows="3"><?php echo @$_POST['name']; ?></textarea>
								<div style="float:left;width:50%;">
									<p>Example:<br /> &nbsp;&nbsp;John Doe<br />&nbsp;&nbsp;ACME LLC</p>
								</div>
								<div style="float:right;width:49%;">
									<p><a href="#" class="upload_image_button" class="button">Upload/Attach an image</a> <?php if ($image_url != '') { ?> &nbsp; / &nbsp;<a href="#" class="remove_image_button">Remove Image</a> <?php } ?></p>
									<div class="image-container"><?php if ($image_url != '') echo '<img src="'.$image_url.'" class="testimonial-image" />'; ?>
									</div>
									<input type="hidden" name="image" id="attachment_id" value="<?php if (isset($_POST['image'])&&($_POST['image']!=0)) echo $_POST['image']; ?>" />
								</div>
								<div style="clear:both;"> </div>
							</div>
						</div>

						<div class="stuffbox">
							<h3><label for="testimonial_date">Testimonial Date:</label></h3>
							<div class="inside">
								<input type="text" class="datepicker" id="testimonial_date" name="testimonial_date" size="50" value="<?php echo @$_POST['testimonial_date']; ?>" />
								<p>Example: <?php echo date('m/d/Y'); ?></p>
							</div>
						</div>

						<div class="stuffbox">
							<h3><label for="website">Website:</label></h3>
							<div class="inside">
								<input type="text" id="website" name="url" size="50" value="<?php echo @$_POST['url']; ?>" />
								<p>Example: http://hitmyserver.com</p>
							</div>
						</div>

						<div class="stuffbox">
							<h3><label for="testimonial"><span style="color:red;">*</span> Testimonial:</label></h3>
							<div class="inside">
								<?php wp_editor(@$_POST['testimonial'], 'testimonial', array('textarea_name' => 'testimonial', 'textarea_rows' => 10) ); ?>
								<br /><br />
								<strong>HTML is allowed.</strong>
							</div>
						</div>

						<?php
						if ($fields_count>0) {

							foreach($fields as $f) {
								?>
								<div class="stuffbox">
									<h3><label for="cf<?php echo $f->id; ?>"><?php if ($f->isrequired == 1) { ?><span style="color:red;">*</span><?php } ?> <?php echo $f->name; ?>:</label></h3>
									<div class="inside">

										<?php
										switch($f->type) {
											case 'text':
											case 'email':
												echo '<input type="text" id="cf'.$f->id.'" name="cf['.$f->id.']" size="50" value="'. @$_POST['cf'][$f->id].'" />';
											break;
											case 'textarea':
												echo '<textarea id="cf'.$f->id.'" name="cf['.$f->id.']" style="width:99%;" rows="3">'. @$_POST['cf'][$f->id].'</textarea>';
											break;
										}
										?>
									</div>
								</div>
								<?php
							}

						}
						?>
						
					</div>

					<div class="postbox-container" id="postbox-container-1">
						<div id="side-sortables">
						<?php if ($this->is_moderator()) { ?>
							<div class="postbox">
								<h3><label for="groups">Groups:</label></h3>
								<select name="groups[]" multiple="multiple" style="width:99%;" id="groups">
									<?php foreach($groups as $id => $name):
											echo '<option value="'.$id.'"'; if (in_array($id, (is_array(@$_POST['groups']) ? $_POST['groups'] : array()))) echo ' selected="selected"'; echo '>'.$name.'</option>';
									endforeach; ?>
								</select><br /><br />
								&nbsp;&nbsp; <a href="#" onclick="jQuery('#groups').val('');return false;" class="button">Clear Selected Groups</a>
								<br /><br />
								&nbsp;&nbsp; <strong>Hint:</strong> Hold down ctrl to select multiple groups.
								<br /><br />
							</div>
						<?php } ?>

						<div class="postbox">
							<h3>Save</h3>
							<br />
							<?php
							if ($this->can_access('autoapprove')) { ?>
								&nbsp;&nbsp;&nbsp; <input id="display" type="checkbox" name="display" value="1"<?php if ((isset($_POST['display'])&&($_POST['display']=='1') || $display == 1)) echo ' checked="checked"'; ?> /> &nbsp;&nbsp;<label for="display">Display?</label><br /><br />
							<?php } ?>
							&nbsp;&nbsp;&nbsp; <input type="submit" name="save" value="Save Testimonial" class="button-primary" /> <br />
							&nbsp;
							<br />
						</div>

					</div>
				
				</div>
			</div>
			
			</form>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('.datepicker').datepicker({changeMonth: true, changeYear: true});
			});
		</script>
		
		<?php
		echo $this->load_media_frame();
	}

	public function testimonial_view_page() {

		if (!isset($_GET['id'])||!is_numeric($_GET['id'])) $_GET['id'] = 0;
		$get_testimonial = $this->wpdb->get_row("SELECT *, DATE_FORMAT(testimonial_date, '%c/%e/%Y') AS testimonial_date FROM `".$this->wpdb->prefix."hms_testimonials` WHERE `id` = ".(int)$_GET['id']." AND `blog_id` = ".(int)$this->blog_id, ARRAY_A);


		if (!$this->is_moderator()) {
			if ($get_testimonial['user_id'] != $this->current_user->ID) {
				die('You do not have access to this testimonial.');
			}
		}

		$fields = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf` WHERE `blog_id` = ".(int)$this->blog_id." ORDER BY `name` ASC");
		$fields_count = count($fields);

		$custom_fields = array();
		$cf_meta = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf_meta` WHERE `testimonial_id` = ".(int)$get_testimonial['id']);
		
		if (count($cf_meta)>0) {
			foreach($cf_meta as $v)
				$custom_fields[$v->key_id] = $v->value;
		}

		$get_groups = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_groups` WHERE `blog_id` = ".(int)$this->blog_id, ARRAY_A);

		$groups = array();
		foreach($get_groups as $g)
			$groups[$g['id']] = $g['name'];

		$my_groups = array();
		$get_my_groups = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_group_meta` WHERE `testimonial_id` = ".(int)$get_testimonial['id'], ARRAY_A);

		foreach($get_my_groups as $m)
			$my_groups[$m['group_id']] = $m['group_id'];


		$image_url = '';
		$errors = array();
		if (isset($_POST) && (count($_POST)>0) && count($get_testimonial)>0) {
			if (!isset($_POST['name']) || trim($_POST['name']) == '')
				$errors[] = 'Please enter a name for this testimonial.';

			if (!isset($_POST['testimonial']) || (trim($_POST['testimonial'])==''))
				$errors[] = 'You forgot to enter the testimonial.';


			if ($fields_count>0) {
				foreach($fields as $f) {

					if ($f->isrequired == 1 && (!isset($_POST['cf'][$f->id]) || trim($_POST['cf'][$f->id])=='')) {
						$errors[] = $f->name.' is a required field.';
						continue;
					}

					switch($f->type) {
						case 'email':
							if (!filter_var($_POST['cf'][$f->id], FILTER_VALIDATE_EMAIL))
								$errors[] = 'Please enter a valid email for the '.$f->name.' field.';
						break;
					}

				}
			}

			$url = '';
			if (isset($_POST['url'])&&(trim($_POST['url'])!='')) {
				if (substr($_POST['url'],0,4)!='http')
					$url = 'http://'.$_POST['url'];
				else
					$url = $_POST['url'];
			}

			$testimonial_date = '0000-00-00 00:00:00';
			if (isset($_POST['testimonial_date']) && ($_POST['testimonial_date'] != '')) {
				try {
					$tdate = new DateTime($_POST['testimonial_date']);
					if (($testimonial_date = $tdate->format('Y-m-d H:i:s')) === false)
						$testimonial_date = '0000-00-00 00:00:00';
				} catch(Exception $e) {
					$errors[] = 'Please enter a valid testimonial date. Use the format mm/dd/YYYY such as '.date('m/d/Y');
				}
			}


			$new_groups = array();
			$display = 0;
			
			if (isset($_POST['display']) && ($_POST['display']=='1'))
				$display = 1;

			if (count($errors)<1) {
				$_POST = stripslashes_deep($_POST);


				if (isset($_POST['image']) && ($_POST['image'] != 0)) {
					$image_url = wp_get_attachment_url($_POST['image']);
					if ($image_url != '')
						$image = $_POST['image'];
					else
						$image = 0;
				} else {
					$image = 0;
				}

				$updates = array(
					'name' => trim($_POST['name']), 
					'testimonial' => trim($_POST['testimonial']), 
					'url' => $url,
					'testimonial_date' => $testimonial_date,
					'image' => $image
				);

				if ($this->can_access('autoapprove'))
					$updates['display'] = $display;
				elseif ($this->options['resetapproval'] == 1 && $this->current_user->ID == $get_testimonial['user_id'])
					$updates['display'] = 0;
				else
					$updates['display'] = $get_testimonial['display'];

				$display = $get_testimonial['display'];


				$this->wpdb->update($this->wpdb->prefix."hms_testimonials", 
					$updates,
					array('id' => $get_testimonial['id']));


				if ($fields_count>0) {
					foreach($fields as $f) {
						if (isset($_POST['cf'][$f->id]) && trim($_POST['cf'][$f->id])!='') {
						
							if (!isset($custom_fields[$f->id])) {

								$this->wpdb->insert($this->wpdb->prefix."hms_testimonials_cf_meta", 
									array(
										'testimonial_id' => $get_testimonial['id'],
										'key_id' => $f->id,
										'value' => $_POST['cf'][$f->id]
									)
								);
							} else {
								$this->wpdb->update($this->wpdb->prefix."hms_testimonials_cf_meta", 
									array('value' => $_POST['cf'][$f->id]),
									array('testimonial_id' => $get_testimonial['id'], 'key_id' => $f->id)
								);
							}
						}
					}
				}

				if (!$this->is_moderator()) {
					$message = $this->current_user->user_login.' has updated their testimonial on '.get_bloginfo('name')."\r\n\r\n";

					$message .= "The following changes were made: \r\n";

					if ($_POST['name'] != $get_testimonial['name'])
						$message .= 'Name: '. trim($_POST['name'])."\r\n";

					if ($url != $get_testimonial['url'])
						$message .= 'Website: '.$url."\r\n";

					if (trim($_POST['testimonial']) != $get_testimonial['testimonial'])
						$message .= 'Testimonial: '. trim($_POST['testimonial'])."\r\n";

					if ($updates['display'] != $get_testimonial['display'])
						$message .= 'Displayed: '. (($updates['display']==1) ? 'Yes' : 'No')."\r\n";

					$message .= "\r\n\r\n";
					$message .= 'View this testimonial at '.admin_url('admin.php?page=hms-testimonials-view&id='.$get_testimonial['id']);

					wp_mail(get_bloginfo('admin_email'), 'Updated Testimonial Added to '.get_bloginfo('name'), $message);
				

				} else {


					if (isset($_POST['groups']) && is_array($_POST['groups'])) {

						$del_groups = $my_groups;
						foreach($_POST['groups'] as $gid) {

							if (isset($groups[$gid])) {
								
								if (!isset($my_groups[$gid])) {
									
									$this->wpdb->insert($this->wpdb->prefix."hms_testimonials_group_meta", array('testimonial_id' => $get_testimonial['id'], 'group_id' => $gid));
									$new_groups[$gid] = $gid;
									
								} else {
									$new_groups[$gid] = $gid;
									unset($del_groups[$gid]);
								}

							}
						}


						foreach($del_groups as $did)
							$this->wpdb->query("DELETE FROM `".$this->wpdb->prefix."hms_testimonials_group_meta` WHERE `testimonial_id` = ".(int)$get_testimonial['id']." AND `group_id` = ".(int)$did);
					} else {
						$this->wpdb->query("DELETE FROM `".$this->wpdb->prefix."hms_testimonials_group_meta` WHERE `testimonial_id` = ".(int)$get_testimonial['id']);
					}

					$my_groups = $new_groups;

				}
				$added = 1;
			
			}
		} else {
			if ($get_testimonial['image'] != 0) {
				$image_url = wp_get_attachment_url($get_testimonial['image']);
			}
		}

		wp_enqueue_media();
		?>
		<div class="wrap">

			<?php

			if (count($get_testimonial)<1) {
				echo '<div id="icon-edit-pages" class="icon32"></div><h2>Testimonial Does Not Exist. <a href="'.admin_url('admin.php?page=hms-testimonials-addnew').'" class="add-new-h2">Add New</a></h2>';
			} else {

				?><div id="icon-edit-pages" class="icon32"></div>
				<h2>Viewing: <?php echo $get_testimonial['id']; ?> <a href="<?php echo admin_url('admin.php?page=hms-testimonials-addnew'); ?>" class="add-new-h2">Add New</a></h2>
				<br /><br />
				
				<?php if (isset($added)) {
					echo '<div id="message" class="updated"><p>Your update has been saved.</p></div>';
				}
				if (count($errors)>0) {
					echo '<div class="error"><p><strong>The following errors occured:</strong></p><ol>';
					foreach($errors as $e)
						echo '<li>'.$e.'</li>';
					echo '</ol></div>';
				} ?>

				<style type="text/css">
				.testimonial-image {
					height: <?php echo $this->options['image_height']; ?>px;
					width: <?php echo $this->options['image_width']; ?>px;
				}
				</style>
				<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-view&id='.$get_testimonial['id']); ?>" accept-charset="UTF-8">
					<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">

						<div class="stuffbox">
							<h3><label for="name">Name</label></h3>
							<div class="inside">
								<textarea id="name" name="name"  style="width:99%;" rows="3"><?php echo (!isset($_POST['name']) ? $get_testimonial['name'] : $_POST['name']); ?></textarea>
								<div style="float:left;width:50%;">
									<p>Example:<br /> &nbsp;&nbsp;John Doe<br />&nbsp;&nbsp;ACME LLC</p>
								</div>
								<div style="float:right;width:49%;">
									<p><a href="#" class="upload_image_button" class="button">Upload/Attach an image</a><?php if ($image_url != '') { ?> &nbsp; / &nbsp;<a href="#" class="remove_image_button">Remove Image</a> <?php } ?></p>
									<div class="image-container"><?php if ($image_url != '') echo '<img src="'.$image_url.'" class="testimonial-image" />'; ?>
									</div>
									<input type="hidden" name="image" id="attachment_id" value="<?php if (isset($_POST['image'])&&($_POST['image']!=0)) echo $_POST['image']; else echo $get_testimonial['image']; ?>" />
								</div>
								<div style="clear:both;"> </div>
							</div>
						</div>

						<div class="stuffbox">
							<h3><label for="testimonial_date">Testimonial Date:</label></h3>
							<div class="inside">
								<?php if ($get_testimonial['testimonial_date'] == '0/0/0000')
									$get_testimonial['testimonial_date'] = '';
								?>
								<input type="text" class="datepicker" id="testimonial_date" name="testimonial_date" size="50" value="<?php echo (!isset($_POST['testimonial_date']) ? $get_testimonial['testimonial_date'] : $_POST['testimonial_date']); ?>" />
								<p>Example: <?php echo date('m/d/Y'); ?></p>
							</div>
						</div>

						<div class="stuffbox">
							<h3><label for="website">Website:</label></h3>
							<div class="inside">
								<input type="text" id="website" name="url" size="50" value="<?php echo (!isset($_POST['url']) ? $get_testimonial['url'] : $_POST['url']); ?>" />
								<p>Example: http://hitmyserver.com</p>
							</div>
						</div>

						<div class="stuffbox">
							<h3><label for="testimonial">Testimonial:</label></h3>
							<div class="inside">
								<?php wp_editor((!isset($_POST['testimonial']) ? $get_testimonial['testimonial'] : $_POST['testimonial']), 'testimonial', array('textarea_name' => 'testimonial', 'textarea_rows' => 10) ); ?>
								<br /><br />
								<strong>HTML is allowed.</strong>
							</div>
						</div>

						<?php
						if ($fields_count>0) {

							foreach($fields as $f) {
								?>
								<div class="stuffbox">
									<h3><label for="cf<?php echo $f->id; ?>"><?php if ($f->isrequired == 1) { ?><span style="color:red;">*</span><?php } ?> <?php echo $f->name; ?>:</label></h3>
									<div class="inside">

										<?php
										switch($f->type) {
											case 'text':
											case 'email':
												if (!isset($_POST['cf'][$f->id]) && isset($custom_fields[$f->id]))
													echo '<input type="text" id="cf'.$f->id.'" name="cf['.$f->id.']" size="50" value="'. $custom_fields[$f->id].'" />';
												else
													echo '<input type="text" id="cf'.$f->id.'" name="cf['.$f->id.']" size="50" value="'. @$_POST['cf'][$f->id].'" />';
											break;
											case 'textarea':
												if (!isset($_POST['cf'][$f->id]) && isset($custom_fields[$f->id]))
													echo '<textarea id="cf'.$f->id.'" name="cf['.$f->id.']" style="width:99%;" rows="3">'. $custom_fields[$f->id].'</textarea>';
												else
													echo '<textarea id="cf'.$f->id.'" name="cf['.$f->id.']" style="width:99%;" rows="3">'. @$_POST['cf'][$f->id].'</textarea>';
											break;
										}
										?>
									</div>
								</div>
								<?php
							}

						}
						?>

						
					</div>

					<div class="postbox-container" id="postbox-container-1">
						<div id="side-sortables">
						<?php if ($this->is_moderator()) { ?>
						<div class="postbox">
							<h3><label for="groups">Groups:</label></h3>
							<select name="groups[]" multiple="multiple" style="width:99%;" id="groups">
								<?php 
									if (isset($_POST['groups'])&&(is_array($_POST['groups']))) {

										foreach($groups as $id => $name):
											echo '<option value="'.$id.'"'; if (in_array($id, $_POST['groups'])) echo ' selected="selected"'; echo '>'.$name.'</option>';
										endforeach; 

									} else {

										foreach($groups as $id => $name):
											echo '<option value="'.$id.'"'; if (in_array($id, $my_groups)) echo ' selected="selected"'; echo '>'.$name.'</option>';
										endforeach; 
									}
									?>
								</select><br /><br />
								&nbsp;&nbsp; <a href="#" onclick="jQuery('#groups').val('');return false;" class="button">Clear Selected Groups</a>
								<br /><br />
								&nbsp;&nbsp; <strong>Hint:</strong> Hold down ctrl to select multiple groups.
								
								<br /><br /><br />
						</div>
						<?php } ?>
						<div class="postbox">
							<h3>Save</h3>
							<br />
							<?php if ($this->can_access('autoapprove')) { ?>
							&nbsp;&nbsp;&nbsp; <input id="display" type="checkbox" name="display" value="1"<?php if ((isset($_POST['display'])&&($_POST['display']=='1')) || ((count($_POST)<1)&&$get_testimonial['display']==1)) echo ' checked="checked"'; ?> /> &nbsp;&nbsp;<label for="display">Display?</label><br /><br />
							<?php } ?>
							&nbsp;&nbsp;&nbsp; <input type="submit" name="save" value="Save Testimonial" class="button-primary" /> <br />
							&nbsp;
							<br />
						</div>

					</div>
				
				</div>
			</div>

				</form>
				

				<?php

			}
			?>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('.datepicker').datepicker({changeMonth: true, changeYear: true});
			});
		</script>
		<?php
		echo $this->load_media_frame();
	}

	public function testimonial_delete_page() {

		if (!isset($_GET['id'])||!is_numeric($_GET['id'])) $_GET['id'] = 0;
		$get_t = $this->wpdb->get_row("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials` WHERE `id` = ".(int)$_GET['id']." AND `blog_id` = ".(int)$this->blog_id, ARRAY_A);
		
		if (count($get_t)<1) {

			if (isset($_GET['noheader'])) {
				die(header('Location: '.admin_url('admin.php?page=hms-testimonials')));
			} else {
				?>
				<div class="wrap">
					<h2>Delete Testimonial</h2>
					<br /><br />
					The testimonial you are trying to delete could not be found.
				</div>
				<?php
				return;
			}
		}

		$this->wpdb->query("DELETE FROM `".$this->wpdb->prefix."hms_testimonials_group_meta` WHERE `testimonial_id` = ".$get_t['id']);
		$this->wpdb->query("DELETE FROM `".$this->wpdb->prefix."hms_testimonials` WHERE `id` = ".$get_t['id']);


		if (isset($_GET['noheader'])) {
			die(header('Location: '.admin_url('admin.php?page=hms-testimonials&message='.urlencode('The testimonial was removed.'))));

		} else {
			?>
			<div class="wrap">
				<h2>Delete <?php echo $get_t['name']; ?></h2>
				<br /><br />
				<?php echo $get_t['name']; ?> has been removed.  <a href="<?php echo admin_url('admin.php?page=hms-testimonials'); ?>" class="button">Click here to return to your testimonials</a>
			</div>
			<?php

			return;
		}
	}

	public function testimonial_delete_from_group_page() {

		if (!isset($_GET['id'])||!is_numeric($_GET['id'])) $_GET['id'] = 0;
		if (!isset($_GET['group_id'])||!is_numeric($_GET['group_id'])) $_GET['group_id'] = 0;

		$get_t = $this->wpdb->get_row("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials` WHERE `id` = ".(int)$_GET['id']." AND `blog_id` = ".(int)$this->blog_id,ARRAY_A);
		$group = $this->wpdb->get_row("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_groups` WHERE `id` = ".(int)$_GET['group_id']." AND `blog_id` = ".(int)$this->blog_id,ARRAY_A);
		if (count($get_t)<1 || count($group)<1) {

			if (isset($_GET['noheader'])) {
				die(header('Location: '.admin_url('admin.php?page=hms-testimonials&message='.urlencode('The testimonial or group it was in that you were trying to delete could not be found.'))));
			} else {
				?>
				<div class="wrap">
					<h2>Delete Testimonial</h2>
					<br /><br />
					The testimonial you are trying to delete could not be found.
				</div>
				<?php

				return;
			}
		}

		$this->wpdb->query("DELETE FROM `".$this->wpdb->prefix."hms_testimonials_group_meta` WHERE `testimonial_id` = ".$get_t['id']." AND `group_id` = ".$group['id']);

		if (isset($_GET['noheader'])) {
			die(header('Location: '.admin_url('admin.php?page=hms-testimonials&message='.urlencode('The testimonial has been removed from '.$group['name']))));

		} else {
			?>
			<div class="wrap">
				<h2>Delete <?php echo $get_t['name']; ?></h2>
				<br /><br />
				<?php echo $get_t['name']; ?> has been removed from <?php echo $group['name']; ?>.  <a href="<?php echo admin_url('admin.php?page=hms-testimonials'); ?>" class="button">Click here to return to your testimonials</a>
			</div>
			<?php
			return;
		}
	}

	/**
	 * Groups
	 **/
	public function groups_page() {
		?>
		<div class="wrap">
			<div id="icon-users" class="icon32"></div>
			<h2>HMS Testimonial Groups</h2>
			<br /><br />
			
			<div class="hms-testimonials-notice">
				<p>Groups allow you to organize your testimonials into different sections.  Testimonials can belong to multiple groups giving you great flexibility.</p>
			</div>
			<br />

			<table class="wp-list-table widefat">
				<thead>
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Testimonials</th>
						<th>Shortcode</th>
						<th>Action</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Testimonials</th>
						<th>Shortcode</th>
						<th>Action</th>
					</tr>
				</tfoot>
				<tbody>
					<?php
					$get = $this->wpdb->get_results("SELECT *, (SELECT COUNT(id) FROM `".$this->wpdb->prefix."hms_testimonials_group_meta` WHERE `group_id` = g.id) AS `testimonials` FROM `".$this->wpdb->prefix."hms_testimonials_groups` AS g WHERE g.blog_id = ".(int)$this->blog_id." ORDER BY `name` ASC", ARRAY_A);

					if (count($get)<1) { ?>
						<tr>
							<td colspan="5">No groups exist</td>
						</tr>
					<?php } else { 
						foreach($get as $g) {
							?>
							<tr>
								<td><a href="<?php echo admin_url('admin.php?page=hms-testimonials-viewgroup&id='.$g['id']); ?>"><?php echo $g['id']; ?></a></td>
								<td><?php echo $g['name']; ?></td>
								<td><?php echo $g['testimonials']; ?></td>
								<td>[hms_testimonials group="<?php echo $g['id']; ?>"]</td>
								<td>
									<a href="<?php echo admin_url('admin.php?page=hms-testimonials-deletegroup&groupid='.$g['id']); ?>" onclick="if (!confirm('Are you sure you want to delete <?php echo $g['name']; ?>?')) return false;">Delete</a>
								</td>
							</tr>
							<?php
						}
					} ?>
				</tbody>
			</table>
			<br />
			<a class="button-primary" href="<?php echo admin_url('admin.php?page=hms-testimonials-addnewgroup'); ?>">New Group</a>
		</div>
		<?php
	}

	public function groups_new_page() {

		$testimonials = $this->wpdb->get_results("SELECT t.* FROM `".$this->wpdb->prefix."hms_testimonials` AS t WHERE t.blog_id = ".(int)$this->blog_id." ORDER BY t.display_order ASC", ARRAY_A);

		$t_ids = array();
		if (count($testimonials)>0) {
			foreach($testimonials as $t)
				$t_ids[] = $t['id'];
		}


		if (isset($_POST) && (isset($_POST['name'])) && (trim(strip_tags($_POST['name']))!='')) {
			$_POST = stripslashes_deep($_POST);
			$_POST['name'] = str_replace('"', '', $_POST['name']);
			$_POST['name'] = str_replace("'", '', $_POST['name']);
			
			$this->wpdb->insert($this->wpdb->prefix."hms_testimonials_groups", array('blog_id' => $this->blog_id, 'name' => trim(strip_tags($_POST['name'])), 'created_at' => date('Y-m-d h:i:s')));
			$group_id = $this->wpdb->insert_id;

			if (isset($_POST['testimonial']) && is_array($_POST['testimonial'])) {
				$counter = 0;

				foreach($_POST['testimonial'] as $index => $id) {

					if (in_array($id, $t_ids)) {
						$this->wpdb->insert($this->wpdb->prefix."hms_testimonials_group_meta", array('testimonial_id' => $id, 'group_id' => $group_id, 'display_order' => $counter));

						$counter++;
					}
				}
			}

			die(header('Location: '.admin_url('admin.php?page=hms-testimonials-groups')));
		
		} elseif (isset($_POST) && count($_POST)>0) {
			die(header('Location: '.admin_url('admin.php?page=hms-testimonials-addnewgroup')));
		}

		

		?>
		<div class="wrap">
			<div id="icon-users" class="icon32"></div>
			<h2>Add A New Group</h2>
			<br />

			After you add a group don't forget to add testimonials to it. From there you can use the shortcode provided to list those testimonials.

			<br />
			<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-addnewgroup&noheader=true'); ?>">
				<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-1">
					<div id="post-body-content">

						<div class="stuffbox">
							<h3><label for="name">Name</label></h3>
							<div class="inside">
								<input type="text" id="name" name="name" />
								<p>Example: &nbsp;&nbsp;Government Testimonials</p>
							</div>
						</div>

						<div class="stuffbox">
							<h3><label for="name">Add These Testimonials</label></h3>
							<div class="inside">
								<table class="wp-list-table widefat" id="sortable">
									<thead>
										<tr>
											<th width="15"> </th>
											<th>Name</th>
											<th>Testimonial</th>
										</tr>
									</thead>
									<tfoot>
										<tr>
											<th> </th>
											<th>Name</th>
											<th>Testimonial</th>
										</tr>
									</tfoot>
									<tbody>
										<?php if (count($testimonials) < 1) {
											?><tr><td colspan="3">There are no testimonials to add to this group.</td></tr><?php
										} else {
											foreach($testimonials as $t) {
												?>
												<tr>
													<td valign="top"><input type="checkbox" name="testimonial[]" value="<?php echo $t['id']; ?>" /></td>
													<td valign="top"><?php echo nl2br($t['name']); ?></td>
													<td valign="top"><?php echo substr(nl2br($t['testimonial']),0,100).'...' ?></td>
												</tr>
												<?php
											}
										} ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
				</div>
				<input type="submit" name="save" value="Save Group" class="button-primary" />
			</form>
		</div>
		<?php

		echo $this->load_sortable();
	}

	public function groups_view_page() {

		$group_id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
		$in_group = array();
		$group_found = false;
		$testimonials = array();

		$get_group = $this->wpdb->get_row("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_groups` WHERE `id` = ".$group_id." AND `blog_id` = ".(int)$this->blog_id, ARRAY_A);

		if (count($get_group)>0) {
			$group_found = true;


			$get = $this->wpdb->get_results("SELECT t.*, m.display_order AS group_display_order, u.user_login FROM `".$this->wpdb->prefix."hms_testimonials` AS t 
								LEFT JOIN `".$this->wpdb->users."` AS u 
									ON u.ID = t.user_id
								INNER JOIN `".$this->wpdb->prefix."hms_testimonials_group_meta` AS m 
									ON m.testimonial_id = t.id 
								WHERE t.blog_id = ".(int)$this->blog_id." AND m.group_id = ".(int)$get_group['id']." ORDER BY m.display_order ASC", ARRAY_A);

			$total_in_group = count($get);
			if ($total_in_group>0) {
				foreach($get as $g) {
					$in_group[] = $g['id'];

					$testimonials[$g['group_display_order']] = $g;
				}
			}
			$num_in_group = $total_in_group;
			$total_in_group++;


			ksort($testimonials);
			
			$get_testimonials = $this->wpdb->get_results("SELECT t.* FROM `".$this->wpdb->prefix."hms_testimonials` AS t WHERE t.blog_id = ".(int)$this->blog_id." ORDER BY t.display_order ASC", ARRAY_A);
			foreach($get_testimonials as $t) {
				$t_ids[] = $t['id'];

				if (!in_array($t['id'], $in_group)) {
					$testimonials[$total_in_group] = $t;
					$total_in_group++;
				}
			}
		}

		if ($group_found) {
			$rows_to_show = array('id','name','testimonial','url','testimonial_date','shortcode','user','display');

			$fields = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf` WHERE `blog_id` = ".(int)$this->blog_id." ORDER BY `name` ASC");
			$field_count = count($fields);
			$row_th = '';

			if ($field_count > 0) {

				foreach($fields as $f) {
					$rows_to_show[] = 'cf_'.$f->id;
					$row_th .= '<th class="row-cf_'.$f->id.'">'.$f->name.'</th>';
				}
			}

			?>
			<style type="text/css">
				<?php foreach($rows_to_show as $r) {
					if (in_array($r, $this->options['display_rows'])) {
						?>.row-<?php echo $r; ?> { display:table-cell; }<?php
					} else {
						?>.row-<?php echo $r; ?> { display:none;}<?php
					}
				} ?>
			</style>
			<?php
		}

		if (isset($_POST) && (count($_POST)>0)) {
			$counter = 1;

			if (isset($_POST['testimonial']) && is_array($_POST['testimonial']) && (count($_POST['testimonial'])>0)) {
				$raw_group = $in_group;
				foreach($_POST['testimonial'] as $i => $v) {

					/* 
						check to see if testimonial exists
						check to see if its already in the group
							- if not added it
					*/
							
					if (!in_array($v, $t_ids)) continue;
					if (!in_array($v, $raw_group)) {
						$this->wpdb->insert($this->wpdb->prefix."hms_testimonials_group_meta", array('testimonial_id' => $v, 'group_id' => $get_group['id'], 'display_order' => $counter));
					} else {

						/** update group display order **/
						$this->wpdb->update($this->wpdb->prefix."hms_testimonials_group_meta", array('display_order' => $counter), array('testimonial_id' => $v, 'group_id' => $get_group['id']));

						if (($key = array_search($v, $in_group)) !== false)
							unset($in_group[$key]);
					}

					$counter++;

				}
			}

			if (count($in_group)>0) {
				/* delete removed items */
				foreach($in_group as $tid) {
					$this->wpdb->query("DELETE FROM `".$this->wpdb->prefix."hms_testimonials_group_meta` WHERE `group_id` = ".$get_group['id'].' AND `testimonial_id` = '.(int)$tid);
				}
			}
			

			die(header('Location: '.admin_url('admin.php?page=hms-testimonials-viewgroup&id='.$_GET['id'].'&message='.urlencode('This group has been updated.'))));
		}


		?>
		<div class="wrap">
			<div id="icon-users" class="icon32"></div>
			<?php
			if (!$group_found)
				echo '<h2>No Group found.</h2>';
			else {
				?>
				<h2>Group: <?php echo $get_group['name']; ?> <a href="<?php echo admin_url('admin.php?page=hms-testimonials-addnewgroup'); ?>" class="add-new-h2">Add New Group</a></h2>
				
				<?php if (isset($_GET['message'])) { ?>
					<div id="message" class="updated"><p><?php echo strip_tags($_GET['message']); ?></p></div>
				<?php } ?>
				<br />
				<h3 align="center">Shortcode: [hms_testimonials group="<?php echo $get_group['id']; ?>"]</h3>
				<br /><br />

								
				<h3>
					<span style="float:right;"><strong>Note:</strong> You can drag and drop to set the display order.</span>
					Add/Remove/Sort Testimonials For This Group
				</h3>
				
				<div style="clear:both;"> </div>
				<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-viewgroup&id='.$get_group['id'].'&noheader=1'); ?>">
				<table class="wp-list-table widefat" id="sortable">
					<thead>
						<tr>
							<th width="15"> </th>
							<th class="row-id">ID</th>
							<th class="row-name">Name</th>
							<th class="row-testimonial">Testimonial</th>
							<th class="row-url">URL</th>
							<th class="row-testimonial_date">Testimonial Date</th>
							<th class="row-shortcode">Shortcode</th>
							<th class="row-user">User</th>
							<th class="row-display">Display?</th>
							<?php echo $row_th; ?>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th> </th>
							<th class="row-id">ID</th>
							<th class="row-name">Name</th>
							<th class="row-testimonial">Testimonial</th>
							<th class="row-url">URL</th>
							<th class="row-testimonial_date">Testimonial Date</th>
							<th class="row-shortcode">Shortcode</th>
							<th class="row-user">User</th>
							<th class="row-display">Display?</th>
							<?php echo $row_th; ?>
						</tr>
					</tfoot>
					<tbody>
						<?php if (count($testimonials) < 1) {
							?><tr><td colspan="3">There are no testimonials to add to this group.</td></tr><?php
						} else {
							foreach($testimonials as $t) {
								$t_fields = array();
								if ($field_count > 0) {
									$get_t_fields = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf_meta` WHERE `testimonial_id` = ".(int)$t['id']);
									if (count($get_t_fields)>0) {
										foreach($get_t_fields as $f)
											$t_fields[$f->key_id] = $f->value;

									}
								}
								?>
								<tr>
									<td valign="top"><input type="checkbox" name="testimonial[]" value="<?php echo $t['id']; ?>" <?php if (in_array($t['id'], $in_group)) echo ' checked="checked"'; ?> /></td>
									<td class="row-id" valign="top"><a href="<?php echo admin_url('admin.php?page=hms-testimonials-view&id='.$t['id']); ?>"><?php echo $t['id']; ?></a></td>
									<td class="row-name" valign="top"><?php echo nl2br($t['name']); ?></td>
									<td class="row-testimonial" valign="top"><?php echo substr(nl2br($t['testimonial']),0,100).'...' ?></td>
									<td class="row-url"><?php echo $t['url']; ?></td>
									<td class="row-testimonial_date"><?php if ($t['testimonial_date'] != '0000-00-00 00:00:00') echo date($this->options['date_format'], strtotime($t['testimonial_date'])); else echo 'Not Set'; ?></td>
									<td class="row-shortcode">[hms_testimonials id="<?php echo $t['id']; ?>"]</td>
									<td class="row-user"><?php if ($t['user_id'] == 0) echo 'Website Visitor'; else echo $t['user_login']; ?></td>
									<td class="row-display"><?php echo ($t['display']==1) ? 'Yes' : 'No'; ?></td>
									<?php if ($field_count>0) {
										foreach($fields as $f) {
											echo '<td class="row-cf_'.$f->id.'">';
												if (isset($t_fields[$f->id]))
													echo $t_fields[$f->id];
											echo '</td>';
										}
									} ?>
								</tr>
								<?php
							}
						} ?>
					</tbody>
				</table>

				<p class="submit"><input type="submit" name="save" value="Save Group" class="button-primary" /></p>
				</form>

				<script type="text/javascript">
					jQuery('a.group-tables-toggle').click(function() {
						if (jQuery('#group-tables').hasClass('collapsed')) {
							jQuery('#group-tables').removeClass('collapsed');
							jQuery('#group-tables tbody').css('display', 'table-row-group');
							jQuery(this).text('(- Click to hide)');
						} else {
							jQuery('#group-tables tbody').css('display', 'none');
							jQuery('#group-tables').addClass('collapsed');
							jQuery(this).text('(+ Click to show)');
						}

						return false;
					});
				</script>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery('.hms-testimonial-row-selector').click(function() {
							jQuery('.hms-testimonial-row-selector').each(function() {
								if (jQuery(this).is(':checked')) {
									jQuery('#sortable tr .row-' + jQuery(this).attr('id')).css('display', 'table-cell');
								} else {
									jQuery('#sortable tr .row-' + jQuery(this).attr('id')).css('display', 'none');
								}
							});

							var data = jQuery('#frm-display-rows').serialize();
							jQuery.post('<?php echo admin_url('admin.php?page=hms-testimonials-templates-ajax-save-display-rows&noheader=true&'); ?>', data, function(response) {
								console.log(response);
							});
						});
					});
				</script>

				<?php
				echo $this->load_sortable();
				
			}
			?></div>
		<?php
	}

	public function groups_delete_page() {

		$get_group = $this->wpdb->get_row("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_groups` WHERE `id` = ".(int)$_GET['groupid']." AND `blog_id` = ".(int)$this->blog_id, ARRAY_A);
		if (count($get_group)<1) {

			if (isset($_GET['noheader'])) {
				die(header('Location: '.admin_url('admin.php?page=hms-testimonials-groups')));
			} else {
				?>
				<div class="wrap">
					<div id="icon-users" class="icon32"></div>
					<h2>Delete Group</h2>
					<br /><br />
					The group you are trying to delete could not be found.
				</div>
				<?php

				return;
			}
		}

		$this->wpdb->query("DELETE FROM `".$this->wpdb->prefix."hms_testimonials_group_meta` WHERE `group_id` = ".$get_group['id']);
		$this->wpdb->query("DELETE FROM `".$this->wpdb->prefix."hms_testimonials_groups` WHERE `id` = ".$get_group['id']);


		if (isset($_GET['noheader'])) {

			die(header('Location: '.admin_url('admin.php?page=hms-testimonials-groups')));

		} else {
			?>
			<div class="wrap">
				<div id="icon-users" class="icon32"></div>
				<h2>Delete <?php echo $get_group['name']; ?></h2>
				<br /><br />
				<?php echo $get_group['name']; ?> has been removed.  <a href="<?php echo admin_url('admin.php?page=hms-testimonials-groups'); ?>" class="button">Click here to return to Groups</a>
			</div>
			<?php

			return;
		}
	}

	/**
	* Custom Fields
	**/
	public function customfields_page() {

		if (isset($_POST) && count($_POST)>0) {
			$errors = array();

			$_POST = stripslashes_deep($_POST);

			if (!isset($_POST['name']) || empty($_POST['name']))
				$errors[] = 'Please enter a name for this custom field.';

			if (!isset($_POST['type']) || empty($_POST['type']))
				$errors[] = 'Please select a field type for this custom field.';

			if (count($errors) == 0) {

				$isrequired = (isset($_POST['required']) && ($_POST['required'] == 1)) ? 1 : 0;
				$showonform = (isset($_POST['showonform']) && ($_POST['showonform']==1)) ? 1 : 0;
				$this->wpdb->insert($this->wpdb->prefix."hms_testimonials_cf", 
						array(
							'blog_id' => $this->blog_id, 'name' => $_POST['name'], 'type' => $_POST['type'], 'isrequired' => $isrequired, 'showonform' => $showonform
						)
				);

				$submitted = 1;
			}

		}

		$fields = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf` WHERE `blog_id` = ".(int)$this->blog_id." ORDER BY `name` ASC");

		?>
		<div class="wrap">
			<div id="icon-edit-comments" class="icon32"></div>
			<h2>Add Custom Fields to Your Testimonials</h2>
			<br /><br />
			<?php if (isset($_GET['message']) && ($_GET['message'] != '')) { ?>
				<div id="message" class="updated"><p><?php echo strip_tags(urldecode($_GET['message'])); ?></p></div>
			<?php } ?>
			<?php if (isset($submitted) && ($submitted == 1)) { ?>
				<div id="message" class="updated"><p>Your custom field has been added.</p></div>
			<?php }
			if (count($errors)>0) {
				echo '<div class="error"><p><strong>The following errors occured:</strong></p><ol>';
				foreach($errors as $e)
					echo '<li>'.$e.'</li>';
				echo '</ol></div>';
			} ?>

			<table border="0" class="wp-list-table widefat">
				<tr>
					<td><strong>Name</strong></td>
					<td><strong>Type</strong></td>
					<td><strong>Required</strong></td>
					<td><strong>Show on Public Form</strong></td>
					<td> </td>
				</tr>
				<tr>
					<td>ID</td>
					<td>System</td>
					<td>Automatic</td>
					<td>No</td>
					<td> </td>
				</tr>
				<tr>
					<td>Name</td>
					<td>System</td>
					<td>Yes</td>
					<td>Yes</td>
					<td> </td>
				</tr>
				<tr>
					<td>Testimonial</td>
					<td>System</td>
					<td>Yes</td>
					<td>Yes</td>
					<td> </td>
				</tr>
				<tr>
					<td>URL</td>
					<td>System</td>
					<td>No</td>
					<td>Yes</td>
					<td> </td>
				</tr>
				<tr>
					<td>Image</td>
					<td>System</td>
					<td>No</td>
					<td>No</td>
					<td> </td>
				</tr>
				<?php if (count($fields)>0) {
					foreach($fields as $f):
						?>
						<tr>
							<td><?php echo $f->name; ?></td>
							<td><?php echo $f->type; ?></td>
							<td><?php echo (($f->isrequired == 1) ? 'Yes' : 'No'); ?></td>
							<td><?php echo (($f->showonform == 1) ? 'Yes' : 'No'); ?></td>
							<td><a href="<?php echo admin_url('admin.php?page=hms-testimonials-settings-fields-edit&id='.$f->id); ?>">Edit</a> | 
								<a href="<?php echo admin_url('admin.php?page=hms-testimonials-settings-fields-delete&id='.$f->id.'&noheader=true'); ?>" onclick="if (!confirm('Are you sure you want to delete this custom field?')) return false;">Delete</a>
							</td>
						</tr>
						<?php
					endforeach;
				} ?>
			</table>
			
			<br /><br />
			<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-settings-fields'); ?>">
				<h3>Add a New Custom Field</h3>
				<table>
					<tr>
						<td><label for="name">Name:</label></td>
						<td><input type="text" name="name" id="name" value="<?php echo @$_POST['name']; ?>" /></td>
					</tr>
					<tr>
						<td><label for="type">Type:</label></td>
						<td>
							<select name="type" id="type">
								<option value="text"<?php if (@$_POST['type']=='text') echo ' selected="selected"'; ?>>Text</option>
								<option value="email"<?php if (@$_POST['type']=='email') echo ' selected="selected"'; ?>>Email</option>
								<option value="textarea"<?php if (@$_POST['type']=='textarea') echo ' selected="selected"'; ?>>Text Area</option>
							</select>
						</td>
					</tr>
					<tr>
						<td><label for="required">Required:</label></td>
						<td><input type="checkbox" name="required" id="required" value="1" <?php if (@$_POST['required']==1) echo ' checked="checked"'; ?> /></td>
					</tr>
					<tr>
						<td><label for="showonform">Show on Public Form:</label></td>
						<td><input type="checkbox" name="showonform" id="showonform" value="1" <?php if (@$_POST['showonform']==1) echo ' checked="checked"'; ?> /></td>
					</tr>
				</table>
				<br />
				<input type="submit" name="save" value="Save Field" class="button-primary" />
			</form>
		</div>
		<?php
	}

	public function customfield_delete_page() {

		$get_group = $this->wpdb->get_row("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf` WHERE `id` = ".(int)$_GET['id']." AND `blog_id` = ".(int)$this->blog_id, ARRAY_A);
		if (count($get_group)<1) {

			if (isset($_GET['noheader'])) {
				die(header('Location: '.admin_url('admin.php?page=hms-testimonials-settings-fields')));
			} else {
				?>
				<div class="wrap">
					<div id="icon-users" class="icon32"></div>
					<h2>Delete Custom Field</h2>
					<br /><br />
					The custom field you are trying to delete could not be found.
				</div>
				<?php

				return;
			}
		}

		$this->wpdb->query("DELETE FROM `".$this->wpdb->prefix."hms_testimonials_cf_meta` WHERE `key_id` = ".$get_group['id']);
		$this->wpdb->query("DELETE FROM `".$this->wpdb->prefix."hms_testimonials_cf` WHERE `id` = ".$get_group['id']);


		if (isset($_GET['noheader'])) {

			die(header('Location: '.admin_url('admin.php?page=hms-testimonials-settings-fields')));

		} else {
			?>
			<div class="wrap">
				<div id="icon-users" class="icon32"></div>
				<h2>Delete <?php echo $get_group['name']; ?></h2>
				<br /><br />
				<?php echo $get_group['name']; ?> has been removed.  <a href="<?php echo admin_url('admin.php?page=hms-testimonials-settings-fields'); ?>" class="button">Click here to return to Custom Fields</a>
			</div>
			<?php

			return;
		}
	}

	public function customfield_edit_page() {

		$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
		$field = $this->wpdb->get_row("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf` WHERE `id` = ".(int)$id." AND `blog_id` = ".(int)$this->blog_id, ARRAY_A);
		
		if (count($field) < 1) {
			?>
			<div class="wrap">
				<div id="icon-edit-comments" class="icon32"></div>
				<h2>Update Custom Field</h2>

				<p>The field you are trying to update does not exist.</p>
			</div>
			<?php
			die;
		}

		if (isset($_POST) && (count($_POST)>0)) {
			$errors = array();
			$_POST = stripslashes_deep($_POST);

			if (!isset($_POST['name']) || empty($_POST['name']))
				$errors[] = 'Please enter a name for this custom field.';

			if (!isset($_POST['type']) || empty($_POST['type']))
				$errors[] = 'Please select a field type for this custom field.';

			if (count($errors) == 0) {

				$isrequired = (isset($_POST['required']) && ($_POST['required']==1)) ? 1 : 0;
				$showonform = (isset($_POST['showonform']) && ($_POST['showonform']==1)) ? 1 : 0;
				$this->wpdb->update($this->wpdb->prefix."hms_testimonials_cf", array('name' => $_POST['name'], 'type' => $_POST['type'], 'isrequired' => $isrequired, 'showonform' => $showonform), array('id' => $field['id'], 'blog_id' => $this->blog_id));
				$submitted = 1;
			}
		} else {
			$_POST = $field;
			$_POST['required'] = $field['isrequired'];
		}

		?>
		<div class="wrap">
			<div id="icon-edit-comments" class="icon32"></div>
			<h2>Update Custom Field: </h2>
			<br /><br />
			<?php if (isset($_GET['message']) && ($_GET['message'] != '')) { ?>
				<div id="message" class="updated"><p><?php echo strip_tags(urldecode($_GET['message'])); ?></p></div>
			<?php } ?>
			<?php if (isset($submitted) && ($submitted == 1)) { ?>
				<div id="message" class="updated"><p>Your custom field has been saved.</p></div>
			<?php }
			if (count($errors)>0) {
				echo '<div class="error"><p><strong>The following errors occured:</strong></p><ol>';
				foreach($errors as $e)
					echo '<li>'.$e.'</li>';
				echo '</ol></div>';
			} ?>

			
			<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-settings-fields-edit&id='.$field['id']); ?>">
				<table>
					<tr>
						<td><label for="name">Name:</label></td>
						<td><input type="text" name="name" id="name" value="<?php echo @$_POST['name']; ?>" /></td>
					</tr>
					<tr>
						<td><label for="type">Type:</label></td>
						<td>
							<select name="type" id="type">
								<option value="text"<?php if (@$_POST['type']=='text') echo ' selected="selected"'; ?>>Text</option>
								<option value="email"<?php if (@$_POST['type']=='email') echo ' selected="selected"'; ?>>Email</option>
								<option value="textarea"<?php if (@$_POST['type']=='textarea') echo ' selected="selected"'; ?>>Text Area</option>
							</select>
						</td>
					</tr>
					<tr>
						<td><label for="required">Required:</label></td>
						<td><input type="checkbox" name="required" id="required" value="1" <?php if (@$_POST['required']==1) echo ' checked="checked"'; ?> /></td>
					</tr>
					<tr>
						<td><label for="showonform">Show on Public Form:</label></td>
						<td><input type="checkbox" name="showonform" id="showonform" value="1" <?php if (@$_POST['showonform']==1) echo ' checked="checked"'; ?> /></td>
					</tr>
				</table>
				<br />
				<input type="submit" name="save" value="Save Field" class="button-primary" />
			</form>
		</div>
		<?php
	}

	/**
	* Templates
	**/

	public function template_page() {

		$templates = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_templates` WHERE `blog_id` = ".(int)$this->blog_id." ORDER BY `name` ASC", ARRAY_A);

		?>
		<div class="wrap">
			<div id="icon-edit-comments" class="icon32"></div>
			<h2>HMS Testimonial Templates</h2>
			<br /><br />
			<?php if (isset($_GET['message']) && ($_GET['message'] != '')) { ?>
				<div id="message" class="updated"><p><?php echo strip_tags(urldecode($_GET['message'])); ?></p></div>
			<?php } ?>

			<br /><br />

			<h2>Templates</h2>
			<table class="wp-list-table widefat" id="sortable">
				<thead>
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Items</th>
						<th> </th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Items</th>
						<th> </th>
					</tr>
				</tfoot>
				<tbody>
					<?php if (count($templates)<1) {
						echo '<tr><td colspan="4">No templates exist. <a href="'.admin_url('admin.php?page=hms-testimonials-templates-new').'">Click here to create one.</a></td></tr>';
					} else {
						foreach($templates as $t) {
							if ($t['data'] == '')
								$items = array();
							else
								$items = @unserialize($t['data']);

							echo '<tr>
									<td>'.$t['id'].'</td>
									<td>'.$t['name'].'</td>
									<td>'.count($items).'</td>
									<td><a href="'.admin_url('admin.php?page=hms-testimonials-templates-edit&id='.$t['id']).'">Edit</a> | <a href="'.admin_url('admin.php?page=hms-testimonials-templates-delete&id='.$t['id']).'" onclick="if (!confirm(\'Are you sure you want to delete this template?\')) return false;">Delete</a></td>
								  </tr>';
						}
					}?>
				</tbody>
			</table>
			<br /><br />
			<a href="<?php echo admin_url('admin.php?page=hms-testimonials-templates-new'); ?>" class="button-primary">Add a New Template</a>
		</div>
		<?php
	}

	public function template_new_page() {
		
		$system_fields = array(
			'system_id' => 'ID &nbsp;&nbsp;&nbsp; ( System )',
			'system_testimonial' => 'Testimonial &nbsp;&nbsp;&nbsp; ( System )',
			'system_source' => 'Source &nbsp;&nbsp;&nbsp; ( System )',
			'system_url' => 'URL &nbsp;&nbsp;&nbsp; ( System )',
			'system_image' => 'Image &nbsp;&nbsp;&nbsp; ( System )',
			'system_date' => 'Testimonial Date &nbsp;&nbsp;&nbsp; ( System )'
		);
		$user_fields = array();
		$used_fields = array();
		
		$fields = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf` WHERE `blog_id` = ".(int)$this->blog_id." ORDER BY `name` ASC");
		if (count($fields) > 0) {
			foreach($fields as $f)
				$user_fields[$f->id] = $f->name;	
		}
		$id = 0;
		

		if (isset($_POST) && (count($_POST)>0)) {
			$errors = array();
			$save = array();
			
			if (!isset($_POST['name']) || empty($_POST['name']))
				$errors[] = 'Please enter a name for this template.';


			if (count($_POST['item'])>0) {
				$_POST = stripslashes_deep($_POST);

				foreach($_POST['item'] as $i) {
					
					if (isset($system_fields[$i])) {
						$used_fields[$i] = $system_fields[$i];
						unset($system_fields[$i]);

						$save[] = $i;
					} elseif (isset($user_fields[$i])) {
						$used_fields[$i] = $user_fields[$i];
						unset($user_fields[$i]);
						$save[] = $i;
					}


				}
				
				if (count($errors) < 1) {
					$saved_ar = serialize($save);

					$this->wpdb->insert($this->wpdb->prefix."hms_testimonials_templates", 
						array('blog_id' => $this->blog_id, 'name' => $_POST['name'], 'data' => $saved_ar));

					$id = $this->wpdb->insert_id;
					
					$_POST = array();
					$added = 1;
				}
			} else {
				$errors[] = 'Please add at least 1 item to your template.';
			}

		}

		$unused_fields = array();
		if (count($system_fields)>0) {
			foreach($system_fields as $k => $v)
				$unused_fields[$k] = $v;

		}
		if (count($user_fields)>0) {
			foreach($user_fields as $i => $j)
				$unused_fields[$i] = $j;

		}


		if (isset($added) && ($added == 1)) {
			$used_fields = array();
		}


		?>
		<style type="text/css">
			ul.connect-sortable {
				border:1px solid #d2ac2a;
				padding:2px;
				min-height: 100px;
			}
			ul.connect-sortable li {
				padding:5px;
				border:1px solid #d4d4d4;
			}
		</style>
		<div class="wrap">
			<div id="icon-edit-comments" class="icon32"></div>
			<h2>Create a New Template</h2>
			<br /><br />
			<?php if (isset($_GET['message']) && ($_GET['message'] != '')) { ?>
				<div id="message" class="updated"><p><?php echo strip_tags(urldecode($_GET['message'])); ?></p></div>
			<?php } ?>
			<?php if (isset($added)) {
				echo '<div id="message" class="updated"><p>Your testimonial has been saved. <a href="'.admin_url('admin.php?page=hms-testimonials-templates-edit&id='.$id).'">View it here</a></p></div>';
			}
			if (count($errors)>0) {
				echo '<div class="error"><p><strong>The following errors occured:</strong></p><ol>';
				foreach($errors as $e)
					echo '<li>'.$e.'</li>';
				echo '</ol></div>';
			} ?>

			<br /><br />

			<div style="float:left;width:30%;">
				<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-templates-new'); ?>">

					<label for="name"><strong>Template Name</strong></label><br />
					<input type="text" name="name" id="name" value="<?php echo @$_POST['name']; ?>" />
					<br /><br />

					<h4>Active Items</h4>
					<ul id="forms" class="connect-sortable">
						<?php foreach($used_fields as $i => $v)
							echo '<li><input type="hidden" name="item[]" value="'.$i.'" />'.$v.'</li>';
						?>
					</ul>

					<input type="submit" name="save" value="Save Template" class="button-primary" /> 
				</form>
			</div>
			<div style="float:left;width:30%;margin-left:15px;margin-top:8px;">
				<br /><br /><br />
				<h4>Unused Items</h4>
				<ul id="unused" class="connect-sortable">
					<?php foreach($unused_fields as $i => $v)
						echo '<li><input type="hidden" name="item[]" value="'.$i.'" />'.$v.'</li>';
					?>
				</ul>
			</div>
			<div style="clear:both;"> </div>
			<br />
			<strong>Drag items from the unused items to the active items.</strong>

		</div>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('#forms, #unused').sortable({
					connectWith: '.connect-sortable'
				}).disableSelection();
			});
		</script>
		<?php
	}

	public function template_delete_page() {

		$get_template = $this->wpdb->get_row("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_templates` WHERE `id` = ".(int)$_GET['id']." AND `blog_id` = ".(int)$this->blog_id, ARRAY_A);
		if (count($get_template)<1) {

			if (isset($_GET['noheader'])) {
				die(header('Location: '.admin_url('admin.php?page=hms-testimonials-templates')));
			} else {
				?>
				<div class="wrap">
					<div id="icon-users" class="icon32"></div>
					<h2>Delete Template</h2>
					<br /><br />
					The template you are trying to delete could not be found.
				</div>
				<?php

				return;
			}
		}

		$this->wpdb->query("DELETE FROM `".$this->wpdb->prefix."hms_testimonials_templates` WHERE `id` = ".$get_template['id']);


		if (isset($_GET['noheader'])) {

			die(header('Location: '.admin_url('admin.php?page=hms-testimonials-templates')));

		} else {
			?>
			<div class="wrap">
				<div id="icon-users" class="icon32"></div>
				<h2>Delete <?php echo $get_template['name']; ?></h2>
				<br /><br />
				<?php echo $get_template['name']; ?> has been removed.  <a href="<?php echo admin_url('admin.php?page=hms-testimonials-templates'); ?>" class="button">Click here to return to your Templates.</a>
			</div>
			<?php

			return;
		}
	}

	public function template_edit_page() {

		$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
		$template = $this->wpdb->get_row("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_templates` WHERE `id` = ".(int)$id." AND `blog_id` = ".(int)$this->blog_id, ARRAY_A);
		
		if (count($template) < 1) {
			?>
			<div class="wrap">
				<div id="icon-edit-comments" class="icon32"></div>
				<h2>Update Templates</h2>

				<p>The template you are trying to update does not exist.</p>
			</div>
			<?php
			die;
		}


		$system_fields = array(
			'system_id' => 'ID &nbsp;&nbsp;&nbsp; ( System )',
			'system_testimonial' => 'Testimonial &nbsp;&nbsp;&nbsp; ( System )',
			'system_source' => 'Source &nbsp;&nbsp;&nbsp; ( System )',
			'system_url' => 'URL &nbsp;&nbsp;&nbsp; ( System )',
			'system_image' => 'Image &nbsp;&nbsp;&nbsp; ( System )',
			'system_date' => 'Testimonial Date &nbsp;&nbsp;&nbsp; ( System )'
		);
		$user_fields = array();
		$used_fields = array();
		
		$fields = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf` WHERE `blog_id` = ".(int)$this->blog_id." ORDER BY `name` ASC");
		if (count($fields) > 0) {
			foreach($fields as $f)
				$user_fields[$f->id] = $f->name;	
		}


		if (isset($_POST) && (count($_POST)>0)) {
			$errors = array();
			$saved = array();
			$_POST = stripslashes_deep($_POST);

			if (!isset($_POST['name']) || empty($_POST['name']))
				$errors[] = 'Please enter a name for this custom field.';

			
			if (count($_POST['item'])>0) {
				$_POST = stripslashes_deep($_POST);

				foreach($_POST['item'] as $i) {
					

					if (isset($system_fields[$i])) {
						$used_fields[$i] = $system_fields[$i];
						unset($system_fields[$i]);

						$save[] = $i;
					} elseif (isset($user_fields[$i])) {
						$used_fields[$i] = $user_fields[$i];
						unset($user_fields[$i]);
						$save[] = $i;
					}


				}

				
			} else {
				$errors[] = 'Please add at least 1 item to your template.';
			}


			if (count($errors) == 0) {
				$saved_ar = serialize($save);
				$this->wpdb->update($this->wpdb->prefix."hms_testimonials_templates", array('name' => $_POST['name'], 'data' => $saved_ar), array('id' => $template['id'], 'blog_id' => $this->blog_id));
				$submitted = 1;
			}

			
		} else {
			$data = array();
			if ($template['data'] != '')
				$data = @unserialize($template['data']);

			if (!is_array($data))
				$data = array();

			foreach($data as $d) {

				if (isset($system_fields[$d])) {
					$used_fields[$d] = $system_fields[$d];
					unset($system_fields[$d]);
					continue;
				}

				if (isset($user_fields[$d])) {
					$used_fields[$d] = $user_fields[$d];
					unset($user_fields[$d]);
					continue;
				}				
			}

			$_POST['name'] = $template['name'];
		}

		$unused_fields = array();
		if (count($system_fields)>0) {
			foreach($system_fields as $k => $v)
				$unused_fields[$k] = $v;
		}

		if (count($user_fields)>0) {
			foreach($user_fields as $i => $j)
				$unused_fields[$i] = $j;
		}

		

		?>
		<style type="text/css">
			ul.connect-sortable {
				border:1px solid #d2ac2a;
				padding:2px;
				min-height: 100px;
			}
			ul.connect-sortable li {
				padding:5px;
				border:1px solid #d4d4d4;
			}
		</style>
		<div class="wrap">
			<div id="icon-edit-comments" class="icon32"></div>
			<h2>Edit Template</h2>
			<br /><br />
			<?php if (isset($_GET['message']) && ($_GET['message'] != '')) { ?>
				<div id="message" class="updated"><p><?php echo strip_tags(urldecode($_GET['message'])); ?></p></div>
			<?php } ?>
			<?php if (isset($submitted) && ($submitted == 1)) { ?>
				<div id="message" class="updated"><p>Your template has been saved.</p></div>
			<?php }
			if (count($errors)>0) {
				echo '<div class="error"><p><strong>The following errors occured:</strong></p><ol>';
				foreach($errors as $e)
					echo '<li>'.$e.'</li>';
				echo '</ol></div>';
			} ?>

			
			<div style="float:left;width:30%;">
				<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-templates-edit&id='.$template['id']); ?>">

					<label for="name"><strong>Template Name</strong></label><br />
					<input type="text" name="name" id="name" value="<?php echo @$_POST['name']; ?>" />
					<br /><br />

					<h4>Active Items</h4>
					<ul id="forms" class="connect-sortable">
						<?php foreach($used_fields as $i => $v)
							echo '<li><input type="hidden" name="item[]" value="'.$i.'" />'.$v.'</li>';
						?>
					</ul>

					<input type="submit" name="save" value="Save Template" class="button-primary" /> 
				</form>
			</div>
			<div style="float:left;width:30%;margin-left:15px;margin-top:8px;">
				<br /><br /><br />
				<h4>Unused Items</h4>
				<ul id="unused" class="connect-sortable">
					<?php foreach($unused_fields as $i => $v)
						echo '<li><input type="hidden" name="item[]" value="'.$i.'" />'.$v.'</li>';
					?>
				</ul>
			</div>
			<div style="clear:both;"> </div>
			<br />
			<strong>Drag items from the unused items to the active items.</strong>

		</div>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('#forms, #unused').sortable({
					connectWith: '.connect-sortable'
				}).disableSelection();
			});
		</script>
		<?php
	}

	/**
	 * Shortcodes Builder Page
	 **/
	public function shortcodes_page() {
		$groups = $this->wpdb->get_results("SELECT *, (SELECT COUNT(id) FROM `".$this->wpdb->prefix."hms_testimonials_group_meta` WHERE `group_id` = g.id) AS `testimonials` FROM `".$this->wpdb->prefix."hms_testimonials_groups` AS g WHERE g.blog_id = ".(int)$this->blog_id." ORDER BY `name` ASC", ARRAY_A);
		$templates = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_templates` WHERE `blog_id` = ".(int)$this->blog_id." ORDER BY `name` ASC", ARRAY_A);
		?>
		<div class="wrap">
			<div id="icon-edit-comments" class="icon32"></div>
			<h2>Shortcode Helpers</h2>
			<br />

			<div class="hms-testimonials-notice">
				<p>Use this to help your build the short codes you need. Once you have selected all of your options copy and paste your shortcode into a page or post.</p>
			</div>
			<br />

			<strong>Select a shortcode:</strong> &nbsp;&nbsp; <select class="shortcode-selector">
				<option value="0">Select a shortcode...</option>
				<option value="hms_testimonials">[hms_testimonials]</option>
				<option value="hms_testimonials_rotating">[hms_testimonials_rotating]</option>
			</select>
			<hr />

			<div id="short-code-hms-testimonials" style="display:none;">
				<br />
				I want to show &nbsp;&nbsp; 
				<select class="sc-1-show" id="sc-1-what">
					<option value="all">all</option>
					<option value="group">a group of</option>
					<option value="one">one</option>
				</select> &nbsp;&nbsp; testimonial(s).
				<span id="sc-1-group" style="display:none;">The group of testimonials I want to show is 
					<select class="sc-1-show" id="sc-1-what-group">
						<?php if (count($groups)>0) {
							foreach($groups as $g)
								echo '<option value="'.$g['id'].'">'.$g['name'].'</option>';
						} ?>
					</select>.
				</span>
				<span id="sc-1-id" style="display:none;">The id of the testimonial I want to show is 
					<input type="text" size="3" id="sc-1-what-id" class="sc-1-show" />
				</span>

				<br /><br />
				The template I want to use for this shortcode is 
				<select class="sc-1-show" id="sc-1-what-template">
					<?php if (count($templates)>0) {
						foreach($templates as $t)
							echo '<option value="'.$t['id'].'">'.$t['name'].'</option>';
					} ?>
				</select>
				<br /><br />
				I want to sort my testimonials by &nbsp;&nbsp; 
				<select class="sc-1-show" id="sort-by">
					<option value="0">Select One...</option>
					<option value="display_order">display order*</option>
					<option value="id">id</option>
					<option value="name">source (name)</option>
					<option value="testimonial">testimonial content</option>
					<option value="url">url</option>
					<option value="testimonial_date">testimonial date</option>
					<option value="image">has image</option>
					<option value="random">random</option>
				</select> &nbsp;&nbsp; and order them &nbsp;&nbsp; 
				<select class="sc-1-show" id="sort-by-order">
					<option value="0">Select One...</option>
					<option value="ASC">ascending*</option>
					<option value="DESC">descending</option>
				</select>.

				<br /><br />
				<br /><br />
				<strong>Your short code is:</strong> &nbsp;&nbsp; <span id="sc-1">[hms_testimonials]</span>
			</div>
			<div id="short-code-hms-testimonials-rotating" style="display:none;">
				<br />

				I want to rotate &nbsp;&nbsp; 
				<select class="sc-2-show" id="sc-2-what">
					<option value="all">all</option>
					<option value="group">a group of</option>
				</select> &nbsp;&nbsp; testimonial(s).
				<span id="sc-2-group" style="display:none;">The group of testimonials I want to show is 
					<select class="sc-2-show" id="sc-2-what-group">
						<?php if (count($groups)>0) {
							foreach($groups as $g)
								echo '<option value="'.$g['id'].'">'.$g['name'].'</option>';
						} ?>
					</select>.
				</span>
				<br /><br />
				The template I want to use for this shortcode is 
				<select class="sc-2-show" id="sc-2-what-template">
					<?php if (count($templates)>0) {
						foreach($templates as $t)
							echo '<option value="'.$t['id'].'">'.$t['name'].'</option>';
					} ?>
				</select>
				<br /><br />
				I want to sort my testimonials by &nbsp;&nbsp; 
				<select class="sc-2-show" id="sc-2-sort-by">
					<option value="0">Select One...</option>
					<option value="display_order">display order*</option>
					<option value="id">id</option>
					<option value="name">source (name)</option>
					<option value="testimonial">testimonial content</option>
					<option value="url">url</option>
					<option value="testimonial_date">testimonial date</option>
					<option value="image">has image</option>
					<option value="random">random</option>
				</select> &nbsp;&nbsp; and order them &nbsp;&nbsp; 
				<select class="sc-2-show" id="sc-2-sort-by-order">
					<option value="0">Select One...</option>
					<option value="ASC">ascending*</option>
					<option value="DESC">descending</option>
				</select>.

				<br /><br />
				<br /><br />
				<strong>Your short code is:</strong> &nbsp;&nbsp; <span id="sc-2">[hms_testimonials_rotating]</span>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery('.shortcode-selector').change(function() {
					if (jQuery(this).val() == 'hms_testimonials') {
						jQuery('#short-code-hms-testimonials').css('display', 'block');
						jQuery('#short-code-hms-testimonials-rotating').css('display', 'none');
					} else if (jQuery(this).val() == 'hms_testimonials_rotating') {
						jQuery('#short-code-hms-testimonials-rotating').css('display', 'block');
						jQuery('#short-code-hms-testimonials').css('display', 'none');
					} else {
						jQuery('#short-code-hms-testimonials-rotating').css('display', 'none');
						jQuery('#short-code-hms-testimonials').css('display', 'none');
					}
				});


				var sc_1 = '',
					sc_2 = '';
				jQuery('.sc-1-show').change(function() {
					sc_1 = '';

					if (jQuery('#sc-1-what').val() == 'group') {
						jQuery('#sc-1-group').css('display', 'inline');
						jQuery('#sc-1-id').css('display', 'none');

						sc_1 = ' group="'+jQuery('#sc-1-what-group').val()+'"';
					} else if (jQuery('#sc-1-what').val() == 'one') {
						jQuery('#sc-1-group').css('display', 'none');
						jQuery('#sc-1-id').css('display', 'inline');

						sc_1 = ' id="'+jQuery('#sc-1-what-id').val()+'"';
					} else {
						jQuery('#sc-1-group').css('display', 'none');
						jQuery('#sc-1-id').css('display', 'none');
					}

					sc_1 = sc_1 + ' template="' + jQuery('#sc-1-what-template').val() + '"';


					if (jQuery('#sort-by').val() != '0') {
						sc_1 = sc_1 + ' order="' + jQuery('#sort-by').val() + '"';
					}

					if (jQuery('#sort-by-order').val() != '0') {
						sc_1 = sc_1 + ' direction="' + jQuery('#sort-by-order').val() + '"';
					}
					
					jQuery('#sc-1').html('[hms_testimonials' + sc_1 + ']');
				});



				
				jQuery('.sc-2-show').change(function() {
					sc_2 = '';

					if (jQuery('#sc-2-what').val() == 'group') {
						jQuery('#sc-2-group').css('display', 'inline');
						jQuery('#sc-2-id').css('display', 'none');

						sc_2 = ' group="'+jQuery('#sc-2-what-group').val()+'"';
					} else if (jQuery('#sc-2-what').val() == 'one') {
						jQuery('#sc-2-group').css('display', 'none');
						jQuery('#sc-2-id').css('display', 'inline');

						sc_2 = ' id="'+jQuery('#sc-2-what-id').val()+'"';
					} else {
						jQuery('#sc-2-group').css('display', 'none');
						jQuery('#sc-2-id').css('display', 'none');
					}

					sc_2 = sc_2 + ' template="' + jQuery('#sc-2-what-template').val() + '"';


					if (jQuery('#sc-2-sort-by').val() != '0') {
						sc_2 = sc_2 + ' order="' + jQuery('#sc-2-sort-by').val() + '"';
					}

					if (jQuery('#sc-2-sort-by-order').val() != '0') {
						sc_2 = sc_2 + ' direction="' + jQuery('#sc-2-sort-by-order').val() + '"';
					}
					
					jQuery('#sc-2').html('[hms_testimonials_rotating' + sc_2 + ']');
				});
			});

		</script>
		<?php
	}

	/**
	 * Save the sort order in the background as an ajax call
	 **/

	public function ajax_sort_save() {

		if (!isset($_POST['type'])) return true;
		if ($_POST['type'] == 'group' && !isset($_GET['group'])) return true;

		if ($_POST['type'] == 'group') {
			$get_group = $this->wpdb->get_row("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_groups` WHERE `id` = ".(int)$_GET['group']." AND `blog_id` = ".(int)$this->blog_id, ARRAY_A);
			if (count($get_group)<1) return true;

		}

		if (isset($_POST['sort']) && is_array($_POST['sort'])) {
			$counter = 0;
			foreach($_POST['sort'] as $id) {
				if ($_POST['type'] == 'testimonials')
					$this->wpdb->update($this->wpdb->prefix."hms_testimonials", array('display_order' => $counter), array('id' => $id, 'blog_id' => $this->blog_id));
				elseif ($_POST['type'] == 'group')
					$this->wpdb->update($this->wpdb->prefix."hms_testimonials_group_meta", array('display_order' => $counter), array('testimonial_id' => $id, 'group_id' => $_GET['group']));
				

				$counter++;
			}
		}
	}

	public function ajax_display_rows_save() {

		$defaults = array('id','name','testimonial','url','testimonial_date','shortcode','user','display');
		$fields = array();
		$get_fields = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_cf` WHERE `blog_id` = ".(int)$this->blog_id." ORDER BY `name` ASC");
		if (count($get_fields)>0) {
			foreach($get_fields as $f)
				$fields[] = 'cf_'.$f->id;
		}


		if (isset($_POST) && (count($_POST)>0)) {
			$fields_to_save = array();

			foreach($_POST as $k => $v) {
				if ($v != 1) continue;

				if (in_array($k, $defaults) || in_array($k, $fields))
					$fields_to_save[] = $k;
			}

			$count_fts = count($fields_to_save);

			if ($count_fts>0) {
				$this->options['display_rows'] = $fields_to_save;
				update_option('hms_testimonials', $this->options);
			}
		}
		
		echo $count_fts;
		die;
	}

	/**
	 * Loads the WordPress media frame for uploading images to testimonials.
	 * Why do it ourselves when WordPress does a great job of it.
	 **/

	public function load_media_frame() {
		?>
		<script type="text/javascript">
			jQuery('.upload_image_button').click(function(event) {
				if (typeof file_frame !== 'undefined') {
					file_frame.open();
					return false;
				}

				file_frame = wp.media.frames.file_frame = wp.media({
					title: 'Add an image to this testimonial.',
					multiple: false,
					library: { type: 'image'}
				});

				file_frame.on('select', function() {
					attachment = file_frame.state().get('selection').first().toJSON();
					
					if (attachment.type != "image") {
						alert('Your file was not attached. Please select an image instead.');
						return false;
					} else {
						jQuery('.image-container').html('<img src="' + attachment.url + '" style="height:100px;width:100px;" />');
						jQuery('#attachment_id').val(attachment.id);

						
						if (jQuery('.remove_image_button').length == 0) {
							jQuery('.upload_image_button').after('&nbsp; / &nbsp;<a href="#" class="remove_image_button">Remove Image</a>');
						}
					}
					
					
				});

				file_frame.open();

				return false;
			});

			jQuery(document).on('click', '.remove_image_button', function() {
				jQuery('.image-container').html('');
				jQuery('#attachment_id').val(0);
				return false;
			});

		</script>
		<?php
	}

	/**
	 * Takes the template id and loads the testimonial in it.
	 * If the template does not exist it shows the source (name) and testimonial as a fall back.
	 **/

	public static function template($template_id, $testimonial) {
		global $wpdb, $blog_id;



		$default_items = array('system_source', 'system_testimonial');

		if (!isset(self::$template_cache[$template_id])) {
			$template = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."hms_testimonials_templates` WHERE `id` = ".(int)$template_id." AND `blog_id` = ".(int)$blog_id, ARRAY_A);
			if (count($template)>0) {
				$data = @unserialize($template['data']);
				if (!is_array($data))
					self::$template_cache[$template_id] = $default_items;
				else
					self::$template_cache[$template_id] = $data;

			} else {
				self::$template_cache[$template_id] = $default_items;
			}
		}

		$custom_fields_names = array();
		$custom_fields = array();
		$get_custom_fields = $wpdb->get_results("SELECT m.*, c.name 
													FROM `".$wpdb->prefix."hms_testimonials_cf_meta` AS m 
													INNER JOIN `".$wpdb->prefix."hms_testimonials_cf` AS c
														ON c.id = m.key_id
													WHERE m.testimonial_id = ".(int)$testimonial['id']);
		if (count($get_custom_fields)>0) {
			foreach($get_custom_fields as $c) {
				$custom_fields[$c->key_id] = $c->value;
				$custom_fields_names[$c->key_id] = $c->name;
			}
		}

		$builder = '';

		foreach(self::$template_cache[$template_id] as $k) {
			switch($k) {
				case 'system_id':
					$builder .= '<div class="id">'.apply_filters('hms_testimonials_system_id', $testimonial['id'], $testimonial).'</div>';
				break;
				case 'system_testimonial':
					$builder .= '<div class="testimonial">'.apply_filters('hms_testimonials_system_testimonial', nl2br($testimonial['testimonial']), $testimonial).'</div>';
				break;
				case 'system_source':
					$builder .= '<div class="author">'.apply_filters('hms_testimonials_system_source', nl2br($testimonial['name']), $testimonial).'</div>';
				break;
				case 'system_date':
					$date = strtotime($testimonial['testimonial_date']);
					$show_date = date(HMS_Testimonials::getInstance()->options['date_format'], $date);
					$builder .= '<div class="date">'.apply_filters('hms_testimonials_system_date', (($testimonial['testimonial_date'] == '0000-00-00 00:00:00') ? '' : $show_date), $testimonial).'</div>';
				break;
				case 'system_url':
					$url = '';

					if ($testimonial['url'] != '') {
						if (substr($testimonial['url'],0,4)!='http')
							$href = 'http://'.$testimonial['url'];
						else
							$href = $testimonial['url'];

						if (HMS_Testimonials::getInstance()->options['show_active_links'] == 1) {
							$nofollow = '';

							if (HMS_Testimonials::getInstance()->options['active_links_nofollow'] == 1)
								$nofollow = 'rel="nofollow"';

							$url = '<a '.$nofollow.' href="'.$href.'" target="_blank">'.$href.'</a>';
						} else {
							$url = $href;
						}

					}

					$builder .= '<div class="url">'.apply_filters('hms_testimonials_system_url', $url, $testimonial).'</div>';
					
				break;
				case 'system_image':
					$image_url = wp_get_attachment_url($testimonial['image']);
					if ($image_url == '')
						continue;

					$height = HMS_Testimonials::getInstance()->options['image_height'].'px';
					$width = HMS_Testimonials::getInstance()->options['image_width'].'px';

					$builder .= apply_filters('hms_testimonials_system_image', '<img class="image" src="'.$image_url.'" style="height:'.$height.';width:'.$width.';" />', $testimonial);

				break;
				default:

					if (isset($custom_fields[(int)$k])) {
						$lower = strtolower($custom_fields_names[$k]);
						$builder .= '<div class="cf-' . $lower . '">'.apply_filters('hms_testimonials_cf_' . $lower, $custom_fields[$k], $testimonial).'</div>';

					}

				break;
			}
		}

		return $builder;
	}

}