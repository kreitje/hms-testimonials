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

	public function __construct() {
		global $wpdb, $blog_id;

		$this->wpdb = $wpdb;
		$this->blog_id = $blog_id;

		$this->current_user = wp_get_current_user();

		$roles = new WP_Roles();
		$this->roles = $roles->get_names();

		$getrole = $this->current_user->roles;
		$this->user_role = array_shift($getrole);

		$this->options = get_option('hms_testimonials');
		if (!isset($this->options['role']))
			$this->options['role'] = 'administrator';

		if (!isset($this->options['autoapprove']))
			$this->options['autoapprove'] = 'administrator';

		if (!isset($this->options['autoapprove']))
			$this->options['resetapproval'] = 1;

		if (!isset($this->options['moderator']))
			$this->options['moderator'] = 'administrator';

		if (!isset($this->options['moderators_can_access_settings']))
			$this->options['moderators_can_access_settings'] = 0;

		if (!isset($this->options['num_users_can_create']))
			$this->options['num_users_can_create'] = 1;

		if (!isset($this->options['roleorders'])) {
			$default = array('administrator' => 5, 'editor' => 4, 'author' => 3, 'contributor' => 2, 'subscriber' => 1);
			$this->options['roleorders'] = $default;
		}

		if (isset($this->options['roleorders'][$this->user_role]))
			$this->user_role_num = $this->options['roleorders'][$this->user_role];

		if (!isset($this->options['use_recaptcha']))
			$this->options['use_recaptcha'] = 0;

		if (!isset($this->options['recaptcha_privatekey']))
			$this->options['recaptcha_privatekey'] = '';

		if (!isset($this->options['recaptcha_publickey']))
			$this->options['recaptcha_publickey'] = '';

		foreach($this->roles as $i => $v) {
			if (!isset($this->options['roleorders'][$i]))
				$this->options['roleorders'][$i] = 0;
		}

		self::enqueue_scripts();

	}

	public static function getInstance() {
		if (self::$instance == null)
			self::$instance = new HMS_Testimonials();

		return self::$instance;
	}

	public function admin_menus() {
		
		if ($this->user_role_num >= $this->options['roleorder'][$this->options['role']]) {
			add_menu_page('HMS Testimonials', 'Testimonials', $this->user_role, 'hms-testimonials', array($this, 'admin_page'));

			add_submenu_page('hms-testimonials', 'Add New Testimonial', '&nbsp;&nbsp;Add New', $this->user_role, 'hms-testimonials-addnew', array($this, 'testimonial_new_page'));
			add_submenu_page(null, 'View Testimonial', 'View Testimonial', $this->user_role, 'hms-testimonials-view', array($this, 'testimonial_view_page'));
		}

		$settings_role = 'administrator';

		if ($this->is_moderator()) {
			add_submenu_page('hms-testimonials', 'Groups', 'Groups', $this->user_role, 'hms-testimonials-groups', array($this, 'groups_page'));
			add_submenu_page('hms-testimonials', 'Documentation', 'Documentation', $this->user_role, 'hms-testimonials-help', array($this, 'help_page'));

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

		
		
	}

	public static function enqueue_scripts() {
		wp_enqueue_script('jquery-ui-sortable');
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


	public function settings_page() {

		if (isset($_POST) && (count($_POST)>0)) {
			
			$options = array();
			$options['role'] = $_POST['roles'];
			$options['autoapprove'] = $_POST['autoapprove'];
			$options['resetapproval'] = (isset($_POST['resetapproval']) && $_POST['resetapproval'] == '1') ? 1 : 0;
			$options['moderator'] = (isset($_POST['moderator'])) ? $_POST['moderator'] : 'administrator';
			$options['num_users_can_create'] = (isset($_POST['num_users_can_create'])) ? (int)$_POST['num_users_can_create'] : 1;
			$options['show_active_links'] = (isset($_POST['show_active_links']) && $_POST['show_active_links'] == '1') ? 1 : 0;
			$options['active_links_nofollow'] = (isset($_POST['active_links_nofollow']) && $_POST['active_links_nofollow'] == '1') ? 1 : 0;
			$options['moderators_can_access_settings'] = (isset($_POST['moderators_can_access_settings']) && $_POST['moderators_can_access_settings'] == '1') ? 1 : 0;

			$options['use_recaptcha'] = (isset($_POST['use_recaptcha']) && $_POST['use_recaptcha'] == '1') ? 1 : 0;
			$options['recaptcha_privatekey'] = (isset($_POST['recaptcha_privatekey'])) ? $_POST['recaptcha_privatekey'] : '';
			$options['recaptcha_publickey'] = (isset($_POST['recaptcha_publickey'])) ? $_POST['recaptcha_publickey'] : '';


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
								<th scope="row">7. If a testimonial has a url, show it as an active link?</th>
								<td><input type="checkbox" name="show_active_links" value="1" <?php if ($this->options['show_active_links']==1) echo ' checked="checked"'; ?> /></td>
							</tr>

							<tr>
								<th scope="row">8. Add a nofollow relationship to the active link of a testimonial?</th>
								<td><input type="checkbox" name="active_links_nofollow" value="1" <?php if ($this->options['active_links_nofollow']==1) echo ' checked="checked"'; ?> /></td>
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
						</tbody>
					</table>

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
					<p>Set the role order. Any dropdown on the left that you select, any user with that role or a higher role will be permitted taht action.</p>

					<strong>Drag and drop to sort roles by importance.</strong>

					<br /><br />
					<hr />
					<br />
					<strong>Need a reCAPTCHA account?</strong><br />
					<a href="http://www.google.com/recaptcha" target="_blank">Sign Up Here It's Free!</a>
				</div>
			</form>
		</div>

		<?php echo $this->load_sortable();
		
	}

	public function admin_page() {
		?>
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
						<th>ID</th>
						<th>Name</th>
						<th>Testimonial</th>
						<th>URL</th>
						<th>Shortcode</th>
						<th>User</th>
						<th>Display?</th>
						<th>Action</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Testimonial</th>
						<th>URL</th>
						<th>Shortcode</th>
						<th>User</th>
						<th>Display?</th>
						<th>Action</th>
					</tr>
				</tfoot>
				<tbody>
					<?php

					if ($this->is_moderator()) {
						$get = $this->wpdb->get_results("SELECT t.*, u.user_login 
													FROM `".$this->wpdb->prefix."hms_testimonials` AS t 
													LEFT JOIN `".$this->wpdb->prefix."users` AS u 
														ON u.ID = t.user_id
													WHERE t.blog_id = ".(int)$this->blog_id." ORDER BY t.display_order ASC", ARRAY_A) or die(mysql_error());
					} else {
						$get = $this->wpdb->get_results("SELECT t.*, u.user_login 
														FROM `".$this->wpdb->prefix."hms_testimonials` AS t 
														LEFT JOIN `".$this->wpdb->prefix."users` AS u
															ON u.ID = t.user_id
														WHERE t.blog_id = ".(int)$this->blog_id." AND t.user_id = ".(int)$this->current_user->ID." ORDER BY t.display_order ASC", ARRAY_A);
					}

					if (count($get)<1) { ?>
						<tr>
							<td colspan="8">No testimonials exist</td>
						</tr>
					<?php } else { 
						foreach($get as $g) {
							?>
							<tr>
								<td><input type="hidden" name="sort[]" value="<?php echo $g['id']; ?>" /><a href="<?php echo admin_url('admin.php?page=hms-testimonials-view&id='.$g['id']); ?>"><?php echo $g['id']; ?></td>
								<td><?php echo nl2br($g['name']); ?></td>
								<td><?php echo substr(nl2br($g['testimonial']),0,100).'...'; ?></td>
								<td><?php echo $g['url']; ?></td>
								<td>[hms_testimonials id="<?php echo $g['id']; ?>"]</td>
								<td><?php if ($g['user_id'] == 0) echo 'Website Visitor'; else echo $g['user_login']; ?></td>
								<td><?php echo ($g['display']==1) ? 'Yes' : 'No'; ?></td>
								<td><a href="<?php echo admin_url('admin.php?page=hms-testimonials-delete&id='.$g['id'].'&noheader=true'); ?>" onclick="if (!confirm('Are you sure you want to delete this testimonial?')) return false;">Delete</a></td>
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

			
			<h3>Features</h3>

			<ol>
				<li>Set permissiosn based on user roles to allow your users to add testimonials</li>
				<li>Drag and Drop to set the display order for all testimonials even in groups</li>
				<li>Hide items by unchecking their Display checkbox</li>
				<li>Add a testimonial to 1 or more groups</li>
				<li>Use our shortcodes to show all, a group or just 1 testimonial</li>
				<li>Use our widgets to display your testimonials in the sidebar</li>
			</ol>

			<br /><br />

			<h3>Shortcode</h3>

			<p>Our shortcode <strong>[hms_testimonials]</strong> offers a few options.</p>
			<ol>
				<li><strong>[hms_testimonials]</strong> &nbsp; Shows all of your testimonials that are set to be displayed.</li>
				<li><strong>[hms_testimonials group="1"]</strong> &nbsp; Shows all of your testimonials in a particular group defined by "group". In this case, group 1</li>
				<li><strong>[hms_testimonials id="1"]</strong> &nbsp; Only shows 1 testimonial with the id specified. In this case, 1.</li>
			</ol>

			<br /><br />
			<p>We also offer <strong>[hms_testimonials_rotating]</strong> to rotate your testimonials inside of a page or post.</p>
			<ol>
				<li><strong>[hms_testimonials_rotating]</strong> &nbsp; Rotates through all of your testimonials that are set to be displayed</li>
				<li><strong>[hms_testimonials group="1"]</strong> &nbsp; Rotates through all of your testimonials in a particular group defined by "group". In this case, group 1</li>
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
			<h3>Widgets</h3>
			<p>We offer a standard widget called HMS Testimonials where you can display all, a group or a single testimonial. We also offer a rotating widget called 
				HMS Testimonial Rotator that will show 1 at a time of the entire list or a group and swap them out after x amount of seconds</p>


			<br /><br />
			<h3>CSS Classes</h3>
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
		
		$errors = array();
		if (isset($_POST) && (count($_POST)>0)) {
			if (!isset($_POST['name']) || trim($_POST['name']) == '')
				$errors[] = 'Please enter a name for this testimonial.';

			if (!isset($_POST['testimonial']) || (trim($_POST['testimonial'])==''))
				$errors[] = 'You forgot to enter the testimonial.';

			$url = '';
			if (isset($_POST['url'])&&(trim($_POST['url'])!='')) {
				if (substr($_POST['url'],0,4)!='http')
					$url = 'http://'.$_POST['url'];
				else
					$url = $_POST['url'];
			}


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
						'url' => $url, 'created_at' => date('Y-m-d h:i:s')));

				$id = $this->wpdb->insert_id;
				$added = 1;


				if ($this->is_moderator()) {
					if (isset($_POST['groups']) && is_array($_POST['groups'])) {
						foreach($_POST['groups'] as $gid) {
							if (isset($groups[$gid]))
								$this->wpdb->insert($this->wpdb->prefix."hms_testimonials_group_meta", array('testimonial_id' => $id, 'group_id' => $gid));
						}
					}
				} else {

					$message = $this->current_user->user_login.' has added a testimonial to your site '.get_bloginfo('name')."\r\n\r\n";
					$message .= 'Name: '. trim($_POST['name'])."\r\n";
					$message .= 'Website: '.$url."\r\n";
					$message .= 'Testimonial: '. trim($_POST['testimonial'])."\r\n";
					$message .= 'Displayed: '. (($display==1) ? 'Yes' : 'No')."\r\n";
					$message .= "\r\n\r\n";
					$message .= 'View this testimonial at '.admin_url('admin.php?page=hms-testimonials-view&id='.$id);

					wp_mail(get_bloginfo('admin_email'), 'New Testimonial Added to '.get_bloginfo('name'), $message);
				}

				unset($_POST);
			}
		} else {
			$display = 1;
		}

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

			<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-addnew'); ?>">
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">

						<div class="stuffbox">
							<h3><label for="name"><span style="color:red;">*</span> Name</label></h3>
							<div class="inside">
								<textarea id="name" name="name"  style="width:99%;" rows="3"><?php echo @$_POST['name']; ?></textarea>
								<p>Example:<br /> &nbsp;&nbsp;John Doe<br />&nbsp;&nbsp;ACME LLC</p>
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
								<textarea id="testimonial" name="testimonial" style="width:99%;" rows="10"><?php echo @$_POST['testimonial']; ?></textarea>
								<br /><br />
								<strong>HTML is allowed.</strong>
							</div>
						</div>

						
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
		<?php
	}

	public function testimonial_view_page() {

		if (!isset($_GET['id'])||!is_numeric($_GET['id'])) $_GET['id'] = 0;
		$get_testimonial = $this->wpdb->get_row("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials` WHERE `id` = ".(int)$_GET['id']." AND `blog_id` = ".(int)$this->blog_id, ARRAY_A);


		if (!$this->is_moderator()) {
			if ($get_testimonial['user_id'] != $this->current_user->ID) {
				die('You do not have access to this testimonial.');
			}
		}


		$get_groups = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_groups` WHERE `blog_id` = ".(int)$this->blog_id, ARRAY_A);

		$groups = array();
		foreach($get_groups as $g)
			$groups[$g['id']] = $g['name'];

		$my_groups = array();
		$get_my_groups = $this->wpdb->get_results("SELECT * FROM `".$this->wpdb->prefix."hms_testimonials_group_meta` WHERE `testimonial_id` = ".(int)$get_testimonial['id'], ARRAY_A);

		foreach($get_my_groups as $m)
			$my_groups[$m['group_id']] = $m['group_id'];


		$errors = array();
		if (isset($_POST) && (count($_POST)>0) && count($get_testimonial)>0) {
			if (!isset($_POST['name']) || trim($_POST['name']) == '')
				$errors[] = 'Please enter a name for this testimonial.';

			if (!isset($_POST['testimonial']) || (trim($_POST['testimonial'])==''))
				$errors[] = 'You forgot to enter the testimonial.';

			$url = '';
			if (isset($_POST['url'])&&(trim($_POST['url'])!='')) {
				if (substr($_POST['url'],0,4)!='http')
					$url = 'http://'.$_POST['url'];
				else
					$url = $_POST['url'];
			}

			$new_groups = array();
			$display = 0;
			
			if (isset($_POST['display']) && ($_POST['display']=='1'))
				$display = 1;

			if (count($errors)<1) {
				$_POST = stripslashes_deep($_POST);

				$updates = array(
					'name' => trim($_POST['name']), 
					'testimonial' => trim($_POST['testimonial']), 
					'url' => $url
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
		}


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
				<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-view&id='.$get_testimonial['id']); ?>">
					<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">

						<div class="stuffbox">
							<h3><label for="name">Name</label></h3>
							<div class="inside">
								<textarea id="name" name="name"  style="width:99%;" rows="3"><?php echo (!isset($_POST['name']) ? $get_testimonial['name'] : $_POST['name']); ?></textarea>
								<p>Example:<br /> &nbsp;&nbsp;John Doe<br />&nbsp;&nbsp;ACME LLC</p>
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
								<textarea id="testimonial" name="testimonial" style="width:99%;" rows="10"><?php echo (!isset($_POST['testimonial']) ? $get_testimonial['testimonial'] : $_POST['testimonial']); ?></textarea>
								<br /><br />
								<strong>HTML is allowed.</strong>
							</div>
						</div>

						
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
		<?php
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
			
			Groups allow you to organize your testimonials into different sections.  Testimonials can belong to multiple groups giving you great flexibility.

			<br /><br />

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
								<td><a href="<?php echo admin_url('admin.php?page=hms-testimonials-viewgroup&id='.$g['id']); ?>"><?php echo $g['id']; ?></td>
								<td><?php echo $g['name']; ?></td>
								<td><?php echo $g['testimonials']; ?></td>
								<td>[hms_testimonials group="<?php echo $g['id']; ?>"]</td>
								<td><a href="<?php echo admin_url('admin.php?page=hms-testimonials-deletegroup&groupid='.$g['id']); ?>" onclick="if (!confirm('Are you sure you want to delete <?php echo $g['name']; ?>?')) return false;">Delete</a></td>
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


			$get = $this->wpdb->get_results("SELECT t.*, m.display_order AS group_display_order FROM `".$this->wpdb->prefix."hms_testimonials` AS t 
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

				<h3>Testimonials Currently In This Group ( <?php echo $num_in_group; ?> )</h3>
				<input type="hidden" name="type" value="group" />
				<table class="wp-list-table widefat">
					<thead>
						<tr>
							<th>ID</th>
							<th>Name</th>
							<th>Testimonial</th>
							<th>URL</th>
							<th>Shortcode</th>
							<th>Display?</th>
							<th>Action</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th>ID</th>
							<th>Name</th>
							<th>Testimonial</th>
							<th>URL</th>
							<th>Shortcode</th>
							<th>Display?</th>
							<th>Action</th>
						</tr>
					</tfoot>
					<tbody>
						<?php

						if (count($get)<1) { ?>
							<tr>
								<td colspan="7">No testimonials exist. &nbsp;&nbsp; <a href="<?php echo admin_url('admin.php?page=hms-testimonials-addnew'); ?>">Click here to create one!</a></td>
							</tr>
						<?php } else { 
							foreach($get as $g) {
								
								?>
								<tr>
									<td><input type="hidden" name="sort[]" value="<?php echo $g['id']; ?>" /><a href="<?php echo admin_url('admin.php?page=hms-testimonials-view&id='.$g['id']); ?>"><?php echo $g['id']; ?></td>
									<td><?php echo nl2br($g['name']); ?></td>
									<td><?php echo substr(nl2br($g['testimonial']),0,100).'...'; ?></td>
									<td><?php echo $g['url']; ?></td>
									<td>[hms_testimonials id="<?php echo $g['id']; ?>"]</td>
									<td><?php echo ($g['display']==1) ? 'Yes' : 'No'; ?></td>
									<td><a href="<?php echo admin_url('admin.php?page=hms-testimonials-deletefg&id='.$g['id'].'&group_id='.$get_group['id'].'&noheader=true'); ?>" onclick="if (!confirm('Are you sure you want to delete this testimonial from this group?')) return false;">Delete</a></td>
								</tr>
								<?php
							}
						} ?>
					</tbody>
				</table>

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
							<th>ID</th>
							<th>Name</th>
							<th>Testimonial</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th> </th>
							<th>ID</th>
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
									<td valign="top"><input type="checkbox" name="testimonial[]" value="<?php echo $t['id']; ?>" <?php if (in_array($t['id'], $in_group)) echo ' checked="checked"'; ?> /></td>
									<td valign="top"><?php echo $t['id']; ?></td>
									<td valign="top"><?php echo nl2br($t['name']); ?></td>
									<td valign="top"><?php echo substr(nl2br($t['testimonial']),0,100).'...' ?></td>
								</tr>
								<?php
							}
						} ?>
					</tbody>
				</table>

				<p class="submit"><input type="submit" name="save" value="Save Group" class="button-primary" /></p>
				</form>

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
}