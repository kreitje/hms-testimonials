<?php

function hms_testimonials_roles() {
	$avail_roles = array('administrator' => 10, 'editor' => 9, 'author' => 8, 'contributor' => 7, 'subscriber' => 6);

	return $avail_roles;
}

function hms_testimonials_menu() {
	global $current_user;

	$getrole = $current_user->roles;
	$role = array_shift($getrole);

	$options = get_option('hms_testimonials');
	if (!isset($options['role'])) {
		$options['role'] = 'administrator';
		$options['autoapprove'] = 'administrator';
		$options['resetapproval'] = 1;
		update_option('hms_testimonials', $options);
	}

	
	$avail_roles = hms_testimonials_roles();
	if ($avail_roles[$role] >= $avail_roles[$options['role']]) {

		add_menu_page('HMS Testimonials', 'Testimonials', $role, 'hms-testimonials', 'hms_testimonials_admin');
		add_submenu_page('hms-testimonials', 'Add New Testimonial', '&nbsp;&nbsp;Add New', $role, 'hms-testimonials-addnew', 'hms_testimonials_admin_new');
		add_submenu_page(null, 'View Testimonial', 'View Testimonial', $role, 'hms-testimonials-view', 'hms_testimonials_admin_view');
	}

	add_submenu_page('hms-testimonials', 'Groups', 'Groups', 'administrator', 'hms-testimonials-groups', 'hms_testimonials_admin_groups');
	add_submenu_page('hms-testimonials', 'Settings', 'Settings', 'administrator', 'hms-testimonials-settings', 'hms_testimonials_settings');
	add_submenu_page('hms-testimonials', 'Documentation', 'Documentation', 'administrator', 'hms-testimonials-help', 'hms_testimonials_admin_help');

	add_submenu_page(null, 'Add New Group', '&nbsp;&nbsp;Add New', 'administrator', 'hms-testimonials-addnewgroup', 'hms_testimonials_admin_newgroup');
	add_submenu_page(null, 'Ajax Save', 'Ajax Save', 'administrator', 'hms-testimonials-sortsave', 'hms_testimonials_admin_sortsave');
	add_submenu_page(null, 'View Group', 'View Group', 'administrator', 'hms-testimonials-viewgroup', 'hms_testimonials_admin_viewgroup');
	

	add_submenu_page(null, 'Delete Testimonial', 'Delete Testimonial', 'administrator', 'hms-testimonials-delete', 'hms_testimonials_admin_delete');
	add_submenu_page(null, 'Delete Testimonial From Group', 'Delete Testimonial From Group', 'administrator', 'hms-testimonials-deletefg', 'hms_testimonials_admin_deletefg');
	add_submenu_page(null, 'Delete Group', 'Delete Group', 'administrator', 'hms-testimonials-deletegroup', 'hms_testimonials_admin_deletegroup');
	
}

function hms_testimonials_admin_init() {
	wp_enqueue_script('jquery-ui-sortable');
}

function hms_testimonials_settings() {
	global $wpdb, $blog_id;

	$options = get_option('hms_testimonials');

	if (isset($_POST) && (count($_POST)>0)) {
		if (!is_array($options))
			$options = array();

		$options['role'] = $_POST['roles'];
		$options['autoapprove'] = $_POST['autoapprove'];
		$options['resetapproval'] = (isset($_POST['resetapproval']) && $_POST['resetapproval'] == '1') ? 1 : 0;
		update_option('hms_testimonials', $options);
	}

	?>

	<div class="wrap">
		<div id="icon-edit-comments" class="icon32"></div>
		<h2>HMS Testimonials Settings</h2>
		<br />

		<div id="message" class="updated"><p>
			<strong>Note:</strong> Roles Editor and Administrator will be able to edit all testimonials. Author and below will only be able to edit their own.</p>
		</div>

		<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-settings'); ?>">

			1. Minimum role to add/edit testimonials: &nbsp;&nbsp;&nbsp;
			<select name="roles">
				<option value="administrator" <?php if ($options['role']=='administrator') echo ' selected="selected"'; ?>>Administrator</option>
				<option value="editor" <?php if ($options['role']=='editor') echo ' selected="selected"'; ?>>Editor</option>
				<option value="author" <?php if ($options['role']=='author') echo ' selected="selected"'; ?>>Author</option>
				<option value="contributor" <?php if ($options['role']=='contributor') echo ' selected="selected"'; ?>>Contributor</option>
				<option value="subscriber" <?php if ($options['role']=='subscriber') echo ' selected="selected"'; ?>>Subscriber</option>
			</select>

			<br /><br />

			2. Minimum role a user can mark their testimonial displayed:  &nbsp;&nbsp;&nbsp;
			<select name="autoapprove">
				<option value="administrator" <?php if ($options['autoapprove']=='administrator') echo ' selected="selected"'; ?>>Administrator</option>
				<option value="editor" <?php if ($options['autoapprove']=='editor') echo ' selected="selected"'; ?>>Editor</option>
				<option value="author" <?php if ($options['autoapprove']=='author') echo ' selected="selected"'; ?>>Author</option>
				<option value="contributor" <?php if ($options['autoapprove']=='contributor') echo ' selected="selected"'; ?>>Contributor</option>
				<option value="subscriber" <?php if ($options['autoapprove']=='subscriber') echo ' selected="selected"'; ?>>Subscriber</option>
			</select>

			<br /><br />

			3. If a user is below the role level in option 2, set displayed field to <strong>NO</strong> when that user changes or updates their testimonial? &nbsp;&nbsp;&nbsp;
			<input type="checkbox" name="resetapproval" value="1" <?php if ($options['resetapproval']==1) echo ' checked="checked"'; ?> />

			<br /><br />
			<br /><br />

			<input type="submit" class="button-primary" name="save" value="Save Settings" />
		</form>
	</div>
	<?php
}

function hms_testimonials_admin() {
	global $wpdb, $blog_id, $current_user;

	$getrole = $current_user->roles;
	$role = array_shift($getrole);
	
	?>
	<div class="wrap">
		<div id="icon-edit-comments" class="icon32"></div>
		<h2><?php if ($role == 'administrator' || $role == 'editor') { ?>HMS Testimonials<?php } else { ?>My Testimonials<?php } ?></h2>
		<br /><br />
		<?php if ($role == 'administrator' || $role == 'editor') { ?><h3 align="center">Shortcode: [hms_testimonials]</h3><?php } ?>
		<br />
		<?php if ($role == 'administrator' || $role == 'editor') { ?><strong>Note:</strong> You can drag and drop to set the display order.<br /><?php } ?>
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

				if ($role == 'administrator' || $role == 'editor') {
					$get = $wpdb->get_results("SELECT t.*, u.user_login 
												FROM `".$wpdb->prefix."hms_testimonials` AS t 
												LEFT JOIN `".$wpdb->prefix."users` AS u 
													ON u.ID = t.user_id
												WHERE t.blog_id = ".(int)$blog_id." ORDER BY t.display_order ASC", ARRAY_A) or die(mysql_error());
				} else {
					$get = $wpdb->get_results("SELECT t.*, u.user_login 
													FROM `".$wpdb->prefix."hms_testimonials` AS t 
													LEFT JOIN `".$wpdb->prefix."users` AS u
														ON u.ID = t.user_id
													WHERE t.blog_id = ".(int)$blog_id." AND t.user_id = ".(int)$current_user->ID." ORDER BY t.display_order ASC", ARRAY_A);
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
							<td><?php echo $g['user_login']; ?></td>
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

	<?php if ($role == 'administrator' || $role == 'editor') { ?>
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
	<?php
	}
}

function hms_testimonials_admin_new() {
	global $wpdb, $blog_id, $current_user;

	$options = get_option('hms_testimonials');
	$getrole = $current_user->roles;
	$role = array_shift($getrole);

	$get_groups = $wpdb->get_results("SELECT g.*, (SELECT COUNT(id) FROM `".$wpdb->prefix."hms_testimonials_group_meta` WHERE `group_id` = g.id) AS `testimonials` FROM `".$wpdb->prefix."hms_testimonials_groups` AS g WHERE g.blog_id = ".(int)$blog_id." ORDER BY `name` ASC", ARRAY_A);
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
		if (isset($_POST['display']) && ($_POST['display']=='1'))
			$display = 1;

		if (count($errors)<1) {
			$current_user = wp_get_current_user();

			$_POST = stripslashes_deep($_POST);

			$wpdb->insert($wpdb->prefix."hms_testimonials", 
				array(
					'blog_id' => $blog_id, 'user_id' => $current_user->ID, 'name' => trim($_POST['name']), 
					'testimonial' => trim($_POST['testimonial']), 'display' => $display,
					'url' => $url, 'created_at' => date('Y-m-d h:i:s')));

			$id = $wpdb->insert_id;

			if ($role == 'administrator') {
				if (isset($_POST['groups']) && is_array($_POST['groups'])) {
					foreach($_POST['groups'] as $gid) {
						if (isset($groups[$gid]))
							$wpdb->insert($wpdb->prefix."hms_testimonials_group_meta", array('testimonial_id' => $id, 'group_id' => $gid));
					}
				}
			}

			$added = 1;
			


			if ($role != 'administrator' && $role != 'editor') {
				$message = $current_user->user_login.' has added a testimonial to your site '.get_bloginfo('name')."\r\n\r\n";
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
			echo '<div id="message" class="updated"><p>Your testimonial has been added.</p></div>';
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
					<?php if ($role == 'administrator' || $role == 'editor') { ?>
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
						$avail_roles = hms_testimonials_roles();
						if ($avail_roles[$role] >= $avail_roles[$options['autoapprove']]) { ?>
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

function hms_testimonials_admin_view() {
	global $wpdb, $blog_id, $current_user;

	$options = get_option('hms_testimonials');
	$getrole = $current_user->roles;
	$role = array_shift($getrole);


	if (!isset($_GET['id'])||!is_numeric($_GET['id'])) $_GET['id'] = 0;
	$get_testimonial = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `id` = ".(int)$_GET['id']." AND `blog_id` = ".(int)$blog_id, ARRAY_A);


	if ($role != 'administrator' && $role != 'editor') {
		if ($get_testimonial['user_id'] != $current_user->ID) {
			die('You do not have access to this testimonial.');
		}
	}


	$get_groups = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials_groups` WHERE `blog_id` = ".(int)$blog_id, ARRAY_A);

	$groups = array();
	foreach($get_groups as $g)
		$groups[$g['id']] = $g['name'];

	$my_groups = array();
	$get_my_groups = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."hms_testimonials_group_meta` WHERE `testimonial_id` = ".(int)$get_testimonial['id'], ARRAY_A);

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
				//'display' => $display,
				'url' => $url
			);

			$avail_roles = hms_testimonials_roles();
			if ($avail_roles[$role] >= $avail_roles[$options['autoapprove']])
				$updates['display'] = $display;
			elseif ($options['resetapproval'] == 1 && $current_user->ID == $get_testimonial['user_id'])
				$updates['display'] = 0;
			else
				$updates['display'] = $get_testimonial['display'];

			$display = $get_testimonial['display'];


			$wpdb->update($wpdb->prefix."hms_testimonials", 
				$updates,
				array('id' => $get_testimonial['id']));

			if ($role != 'administrator' && $role != 'editor') {
				$message = $current_user->user_login.' has updated their testimonial on '.get_bloginfo('name')."\r\n\r\n";

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
			}


			if (isset($_POST['groups']) && is_array($_POST['groups'])) {

				$del_groups = $my_groups;
				foreach($_POST['groups'] as $gid) {

					if (isset($groups[$gid])) {
						
						if (!isset($my_groups[$gid])) {
							
							$wpdb->insert($wpdb->prefix."hms_testimonials_group_meta", array('testimonial_id' => $get_testimonial['id'], 'group_id' => $gid));
							$new_groups[$gid] = $gid;
							
						} else {
							$new_groups[$gid] = $gid;
							unset($del_groups[$gid]);
						}

					}
				}

				

				foreach($del_groups as $did)
					$wpdb->query("DELETE FROM `".$wpdb->prefix."hms_testimonials_group_meta` WHERE `testimonial_id` = ".(int)$get_testimonial['id']." AND `group_id` = ".(int)$did);
			} else {
				$wpdb->query("DELETE FROM `".$wpdb->prefix."hms_testimonials_group_meta` WHERE `testimonial_id` = ".(int)$get_testimonial['id']);
			}
			$my_groups = $new_groups;
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
					<?php if ($role == 'administrator' || $role == 'editor') { ?>
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
						<?php
						$avail_roles = hms_testimonials_roles();
						if ($avail_roles[$role] >= $avail_roles[$options['autoapprove']]) { ?>
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

function hms_testimonials_admin_delete() {
	global $wpdb, $blog_id;
	if (!isset($_GET['id'])||!is_numeric($_GET['id'])) $_GET['id'] = 0;
	$get_t = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `id` = ".(int)$_GET['id']." AND `blog_id` = ".(int)$blog_id,ARRAY_A);
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

	$wpdb->query("DELETE FROM `".$wpdb->prefix."hms_testimonials_group_meta` WHERE `testimonial_id` = ".$get_t['id']);
	$wpdb->query("DELETE FROM `".$wpdb->prefix."hms_testimonials` WHERE `id` = ".$get_t['id']);


	if (isset($_GET['noheader'])) {

			die(header('Location: '.admin_url('admin.php?page=hms-testimonials')));

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

function hms_testimonials_admin_deletefg() {
	global $wpdb, $blog_id;
	if (!isset($_GET['id'])||!is_numeric($_GET['id'])) $_GET['id'] = 0;
	if (!isset($_GET['group_id'])||!is_numeric($_GET['group_id'])) $_GET['group_id'] = 0;

	$get_t = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."hms_testimonials` WHERE `id` = ".(int)$_GET['id']." AND `blog_id` = ".(int)$blog_id,ARRAY_A);
	$group = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."hms_testimonials_groups` WHERE `id` = ".(int)$_GET['group_id']." AND `blog_id` = ".(int)$blog_id,ARRAY_A);
	if (count($get_t)<1 || count($group)<1) {

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

	$wpdb->query("DELETE FROM `".$wpdb->prefix."hms_testimonials_group_meta` WHERE `testimonial_id` = ".$get_t['id']." AND `group_id` = ".$group['id']);

	if (isset($_GET['noheader'])) {

			die(header('Location: '.admin_url('admin.php?page=hms-testimonials')));

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

function hms_testimonials_admin_groups() {
	global $wpdb, $blog_id;
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
				$get = $wpdb->get_results("SELECT *, (SELECT COUNT(id) FROM `".$wpdb->prefix."hms_testimonials_group_meta` WHERE `group_id` = g.id) AS `testimonials` FROM `".$wpdb->prefix."hms_testimonials_groups` AS g WHERE g.blog_id = ".(int)$blog_id." ORDER BY `name` ASC", ARRAY_A);

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

function hms_testimonials_admin_newgroup() {
	global $wpdb, $blog_id;

	if (isset($_POST) && (isset($_POST['name'])) && (trim(strip_tags($_POST['name']))!='')) {
		$_POST = stripslashes_deep($_POST);
		$_POST['name'] = str_replace('"', '', $_POST['name']);
		$_POST['name'] = str_replace("'", '', $_POST['name']);
		$wpdb->insert($wpdb->prefix."hms_testimonials_groups", array('blog_id' => $blog_id, 'name' => trim(strip_tags($_POST['name'])), 'created_at' => date('Y-m-d h:i:s')));
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
				</div>
			</div>
			</div>
			<input type="submit" name="save" value="Save Group" class="button-primary" />
		</form>
	</div>
	<?php
}

function hms_testimonials_admin_viewgroup() {
	global $wpdb, $blog_id;
	?>
	<div class="wrap">
		<div id="icon-users" class="icon32"></div>
		<?php
	if (!isset($_GET['id'])) {
		echo '<h2>No group found.</h2>';
	} else {

		$get_group = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."hms_testimonials_groups` WHERE `id` = ".(int)$_GET['id']." AND `blog_id` = ".(int)$blog_id, ARRAY_A);
		if (count($get_group)<1)
			echo '<h2>No Group found.</h2>';
		else {
			?>
			<h2>Group: <?php echo $get_group['name']; ?> <a href="<?php echo admin_url('admin.php?page=hms-testimonials-addnewgroup'); ?>" class="add-new-h2">Add New Group</a></h2>
			<br /><br />
			<h3 align="center">Shortcode: [hms_testimonials group="<?php echo $get_group['id']; ?>"]</h3>
			<br />
			<strong>Note:</strong> You can drag and drop to set the display order.<br />
			<form method="post" action="<?php echo admin_url('admin.php?page=hms-testimonials-sortsave&group='.$get_group['id'].'&noheader=true'); ?>" id="sort-update">
			<input type="hidden" name="type" value="group" />
			<table class="wp-list-table widefat" id="sortable">
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
					$get = $wpdb->get_results("SELECT t.* FROM `".$wpdb->prefix."hms_testimonials` AS t 
						INNER JOIN `".$wpdb->prefix."hms_testimonials_group_meta` AS m 
							ON m.testimonial_id = t.id 
						WHERE t.blog_id = ".(int)$blog_id." AND m.group_id = ".(int)$get_group['id']." ORDER BY m.display_order ASC", ARRAY_A);

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
			</form>

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
			<?php
		}
	}
	?>
	</div>
	<?php
}


function hms_testimonials_admin_deletegroup() {
	global $wpdb, $blog_id;

	$get_group = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."hms_testimonials_groups` WHERE `id` = ".(int)$_GET['groupid']." AND `blog_id` = ".(int)$blog_id,ARRAY_A);
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

	$wpdb->query("DELETE FROM `".$wpdb->prefix."hms_testimonials_group_meta` WHERE `group_id` = ".$get_group['id']);
	$wpdb->query("DELETE FROM `".$wpdb->prefix."hms_testimonials_groups` WHERE `id` = ".$get_group['id']);


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

function hms_testimonials_admin_sortsave() {
	global $wpdb, $blog_id;

	if (!isset($_POST['type'])) return true;
	if ($_POST['type'] == 'group' && !isset($_GET['group'])) return true;

	if ($_POST['type'] == 'group') {
		$get_group = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."hms_testimonials_groups` WHERE `id` = ".(int)$_GET['group']." AND `blog_id` = ".(int)$blog_id,ARRAY_A);
		if (count($get_group)<1) return true;

	}

	if (isset($_POST['sort']) && is_array($_POST['sort'])) {
		$counter = 0;
		foreach($_POST['sort'] as $id) {
			if ($_POST['type'] == 'testimonials') {
				$wpdb->update($wpdb->prefix."hms_testimonials", array('display_order' => $counter), array('id' => $id, 'blog_id' => $blog_id));
			} elseif ($_POST['type'] == 'group') {
				$wpdb->update($wpdb->prefix."hms_testimonials_group_meta", array('display_order' => $counter), array('testimonial_id' => $id, 'group_id' => $_GET['group']));
			}
			echo $wpdb->print_error();

			$counter++;
		}
	}

}

function hms_testimonials_admin_help() {
	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"></div>
		<h2>Documentation</h2>

		<p>This plugin allows you to add customer testimonials to your site in an easy to manage way. HMS Testimonials offers a shortcode with multiple options and 2 widgets.</p>

		<br /><br />

		<h3>Features</h3>

		<ol>
			<li>Drag and Drop to set the display order for all or even groups</li>
			<li>Hide items by unchecking their Display checkbox</li>
			<li>Add a testimonial to 1 or more groups</li>
			<li>Use our shortcode to show all, a group or just 1 testimonial</li>
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

		<p>Place these shortcodes in your posts or pages. If you prefer to stick them in your sidebar see below for the widgets we offer.</p>

		<br /><br />
		<h3>Widgets</h3>
		<p>We offer a standard widget called HMS Testimonials where you can display all, a group or a single testimonial. We also offer a rotating widget called 
			HMS Testimonial Rotator that will show 1 at a time of the entire list or a group and swap them out after x amount of seconds</p>

		<br /><br />
		<div align="center"><a href="http://hitmyserver.com" target="_blank">A HitMyServer production.</a></div>
	</div>
	<?php
}