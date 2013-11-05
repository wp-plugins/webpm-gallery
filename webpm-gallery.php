<?php
/*
Plugin Name: WebPM Gallery
Plugin URI: http://webplantmedia.com/wordpress/2012/03/webpm-gallery/
Description: Extends Image Gallery Manager.
Version: 1.2
Author: Chris Baldelomar
Author URI: http://webplantmedia.com
License: GPL2
*/

add_theme_support( 'post-thumbnails' );
add_image_size( 'fixedwidth-thumb', 135, 9999 );
add_image_size( 'webpm-thumb', 135, 135 );

// Add CSS file to Dashboard.
add_action('admin_head', 'webpm_gallery_my_admin_head' );

// Restrict Buttons on TinyMCE editor
add_filter('tiny_mce_before_init', 'webpm_base_custom_mce_format' );

function webpm_base_custom_mce_format($init) {
	$init['plugins'] = 'inlinepopups,spellchecker,tabfocus,paste,media,fullscreen,wordpress,wpeditimage,wplink,wpdialogs,wpfullscreen';

	return $init;
}

/*
 *Add CSS file to Dashboard
 */
function webpm_gallery_my_admin_head() {
	echo '<link rel="stylesheet" type="text/css" href="' .plugins_url( 'css/wp-admin.css' , __FILE__ ). '">';
}

function webpm_wp_upload_tabs ($tabs) {

	$newtab = array(
		'webpm' => 'Image Panel',
		'webpm_sort' => 'Sort Gallery',
		'webpm_find' => 'Find'
	);
 
    return array_merge($tabs,$newtab);
}
add_filter('media_upload_tabs', 'webpm_wp_upload_tabs');

function media_upload_webpm_find() {
	wp_enqueue_script('admin-gallery');

    $errors = false;

	if (isset ($_POST['send']) ) {
		media_insert_webpm_images();
	}
	else if (isset ($_POST['execute']) ) {
		if (isset ($_POST['peformaction']) ) {
			if ($_POST['peformaction'] == 'featured-gallery') {
				media_insert_webpm_featured_gallery();
			}
			else {
				echo "<div id='message' class='updated below-h2'>No Action Peformed</div>";
			}
		}
		else {
			echo "<div id='message' class='updated below-h2'>No Action Peformed</div>";
		}
	}

	return wp_iframe( 'media_upload_webpm_find_form', $errors );
}
add_action('media_upload_webpm_find', 'media_upload_webpm_find');

function media_upload_webpm_sort() {
	wp_enqueue_script('admin-gallery');

    $errors = false;

	if (isset ($_POST['sort']) ) {
		media_webpm_update_sort();
	}

	return wp_iframe( 'media_upload_webpm_sort_form', $errors );
}
add_action('media_upload_webpm_sort', 'media_upload_webpm_sort');

function media_upload_webpm() {
	
	wp_enqueue_script('admin-gallery');
    // Not in use
    $errors = false;
    
	// Generate TinyMCE HTML output
	if (isset ($_POST['execute']) ) {
		if (isset ($_POST['peformaction']) ) {
			if ($_POST['peformaction'] == 'exclude') {
				media_upload_webpm_exclude_images();
			}
			else if ($_POST['peformaction'] == 'include') {
				media_upload_webpm_include_images();
			}
			else if ($_POST['peformaction'] == 'featured-gallery') {
				media_insert_webpm_featured_gallery();
			}
			else if ($_POST['peformaction'] == 'delete') {
				media_delete_webpm_images();
			}
		}
		else {
			echo "<div id='message' class='updated below-h2'>No Action Peformed</div>";
		}
	}
	if ( isset($_POST['send']) ) {
		media_insert_webpm_images();
	}
		
	return wp_iframe( 'media_upload_webpm_form', $errors );
}
add_action('media_upload_webpm', 'media_upload_webpm');

function media_webpm_update_sort() {
	global $wpdb; 

	check_admin_referer('webpm-media-form');

	if (!current_user_can('administrator') && !current_user_can('editor')) {
		echo "<div id='message' class='updated below-h2'>You do not have permissions to peform this action</div>";
		return;
	}

	if (isset($_POST['post_id']) && $_POST['post_id'] != 0) {
		$menu_order = 1;
		if (!empty($_POST['gallerysort'])) {
			foreach($_POST['gallerysort'] as $attachment_id) {
				$sql = $wpdb->prepare("UPDATE $wpdb->posts SET menu_order=%d WHERE ID=%d", $menu_order, $attachment_id);
				$wpdb->query($sql);
				$menu_order++;
			}
			echo "<div id='message' class='updated below-h2'>Gallery Sort Updated</div>";
		}
		else {
			echo "<div id='message' class='updated below-h2'>Problem Sorting Gallery</div>";
		}

		if (!empty($_POST['webpmgallerysort'])) {
			$save['webpm'] = array();
			$save['featured'] = array();
			foreach($_POST['webpmgallerysort'] as $attachment_id) {
				$save['webpm'][] = $attachment_id;
				if (array_key_exists('webpm'.$attachment_id, $_POST))
					$save['featured'][$attachment_id] = $_POST['webpm'.$attachment_id];
			}
			$save = serialize($save);
			if (update_post_meta((int)$_POST['post_id'], '_webpm_gallery', $save))
				echo "<div id='message' class='updated below-h2'>webpm Gallery Sort Updated</div>";
		}
	}
}
function media_insert_webpm_images() {
	check_admin_referer('webpm-media-form');

	if (!current_user_can('administrator')) {
		echo "<div id='message' class='updated below-h2'>You do not have permissions to peform this action</div>";
		return;
	}

	if (isset($_POST['post_id']) && $_POST['post_id'] != 0) {
		if (array_key_exists('imagesize', $_POST) ) {
			$piece = explode(':', $_POST['imagesize']);
			$link = get_attachment_link( $piece[1] );
			$meta = wp_get_attachment_metadata($piece[1]);
			$post = get_post($piece[1]);
			$class = (isset($_POST['imageposition']) ? $_POST['imageposition'] : 'alignright');
			if ($piece[0] == "webpm-thumb") {
				$img = wp_get_attachment_image_src($piece[1], 'webpm-thumb');
				$html = '<a href="'.$link.'"><img src="'.$img[0].'" title="'.$post->post_title.'" alt="'.$post->post_content.'" width="'.$img[1].'" height="'.$img[2].'" class="'.$class.'"/></a>';
				return media_send_to_editor($html);
			}
			else if ($piece[0] == "medium") {
				$img = wp_get_attachment_image_src($piece[1], 'medium');
				$html = '<a href="'.$link.'"><img src="'.$img[0].'" title="'.$post->post_title.'" alt="'.$post->post_content.'" width="'.$img[1].'" height="'.$img[2].'" class="'.$class.'"/></a>';
				return media_send_to_editor($html);
			}
			else if ($piece[0] == "large") {
				$img = wp_get_attachment_image_src($piece[1], 'large');
				$html = '<a href="'.$link.'"><img src="'.$img[0].'" title="'.$post->post_title.'" alt="'.$post->post_content.'" width="'.$img[1].'" height="'.$img[2].'" class="'.$class.'"/></a>';
				return media_send_to_editor($html);
			}
		}
	}
	echo "<div id='message' class='updated below-h2'>Problem Sending Image</div>";
}
function media_insert_webpm_featured_gallery() {
	check_admin_referer('webpm-media-form');

	if (!current_user_can('administrator')) {
		echo "<div id='message' class='updated below-h2'>You do not have permissions to peform this action</div>";
		return;
	}

	if (isset($_POST['post_id']) && $_POST['post_id'] != 0) {
		if ( isset($_POST['selection']) && is_array($_POST['selection']) ) {
			$bbcode = '[gallery include="'.implode(',',$_POST['selection']).'"]';
			return media_send_to_editor($bbcode);
		}
	}
	echo "<div id='message' class='updated below-h2'>Problem Sending BBCode</div>";
}
function media_delete_webpm_images() {
	global $wpdb;
	
	check_admin_referer('webpm-media-form');

	if (!current_user_can('administrator')) {
		echo "<div id='message' class='updated below-h2'>You do not have permissions to peform this action</div>";
		return;
	}

	if (isset($_POST['post_id']) && $_POST['post_id'] != 0) {
		$post_id = $_POST['post_id'];

		if ( !empty($_POST['selection']) ) foreach ( $_POST['selection'] as $attachment_id ) {
			if (wp_delete_attachment( $attachment_id , true ) )
				echo "<div id='message' class='updated below-h2'>Picture $attachment_id is Deleted</div>";
			else 
				echo "<div id='message' class='updated below-h2'>There was a problem Deleting Picture $attachment_id. Contact Developer.</div>";
		}
		if ( !empty($_POST['hselection']) ) foreach ( $_POST['hselection'] as $attachment_id ) {
			if (wp_delete_attachment( $attachment_id , true ) )
				echo "<div id='message' class='updated below-h2'>Picture $attachment_id is Deleted</div>";
			else 
				echo "<div id='message' class='updated below-h2'>There was a problem Deleting Picture $attachment_id. Contact Developer.</div>";
		}
	}
}

function media_upload_webpm_include_images() {
	global $wpdb;
	
	check_admin_referer('webpm-media-form');

	if (!current_user_can('administrator') && !current_user_can('editor')) {
		echo "<div id='message' class='updated below-h2'>You do not have permissions to peform this action</div>";
		return;
	}
	
	if (isset($_POST['post_id']) && $_POST['post_id'] != 0) {
		$post_id = $_POST['post_id'];
		if ( !empty($_POST['hselection']) ) foreach ( $_POST['hselection'] as $attachment_id ) {
			$sql = $wpdb->prepare("UPDATE $wpdb->posts SET post_status='inherit' WHERE ID=%d", $attachment_id);
			if ($wpdb->query($sql))
				echo "<div id='message' class='updated below-h2'>Pictures Selected Now Included</div>";
			else
				echo "<div id='message' class='updated below-h2'>Problem Including Pictures</div>";
		}
		else {
			echo "<div id='message' class='updated below-h2'>No Pictures Selected to Include</div>";
		}
	}
}
function media_upload_webpm_exclude_images() {
	global $wpdb;
	
	check_admin_referer('webpm-media-form');

	if (!current_user_can('administrator') && !current_user_can('editor')) {
		echo "<div id='message' class='updated below-h2'>You do not have permissions to peform this action</div>";
		return;
	}
	
	if (isset($_POST['post_id']) && $_POST['post_id'] != 0) {
		$post_id = $_POST['post_id'];
		if ( !empty($_POST['selection']) ) foreach ( $_POST['selection'] as $attachment_id ) {
			$sql = $wpdb->prepare("UPDATE $wpdb->posts SET post_status='private' WHERE ID=%d", $attachment_id);
			if ($wpdb->query($sql))
				echo "<div id='message' class='updated below-h2'>Pictures Selected Now Excluded</div>";
			else
				echo "<div id='message' class='updated below-h2'>Problem Excluding Pictures</div>";
		}
		else {
			echo "<div id='message' class='updated below-h2'>No Pictures Selected to Exclude</div>";
		}
	}
}

function media_upload_webpm_sort_form($errors) {
	global $redir_tab, $type, $wpdb;

	$redir_tab = 'webpm_sort';
	media_upload_header();

	$post_id = intval($_REQUEST['post_id']);
	$form_action_url = admin_url("media-upload.php?type=$type&tab=webpm_sort&post_id=$post_id");

	//Get Attachments
	$attachments = array();
	if ( $post_id ) {
		$attachments = get_children( array( 'post_parent' => $post_id, 'post_status'=>'inherit', 'post_type' => 'attachment', 'orderby' => 'menu_order ID', 'order' => 'ASC') );
	} 
	?>
	<form enctype="multipart/form-data" method="post" action="<?php echo esc_attr($form_action_url); ?>" class="media-upload-form validate" id="gallery-form">
		<?php wp_nonce_field('webpm-media-form'); ?>
		<p class="ml-submit">
			<?php submit_button( __( 'Update Sort' ), 'button savebutton', 'sort', false, array( 'id' => 'sort' ) ); ?>
		</p>
		<div id="sortable-images">
			<?php foreach ( (array) $attachments as $id => $attachment ) : ?>
				<?php $meta = wp_get_attachment_metadata( $id, false ); ?>
				<?php $img = wp_get_attachment_image_src($id, 'webpm-thumb'); ?>
				<div class="image-wrapper">
					<img style="margin-top:<?php echo floor((135-$img[2])/2); ?>px;" src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" />
					<input name="gallerysort[]" type="hidden" value="<?php echo $id; ?>" />
				</div>
			<?php endforeach; ?>
			<div style="clear:both;"></div>
		</div>
		<p class="ml-submit">
			<?php submit_button( __( 'Update Sort' ), 'button savebutton', 'sort', false, array( 'id' => 'sort' ) ); ?>
		</p>
		<p class="ml-submit">
		<input type="hidden" name="post_id" id="post_id" value="<?php echo (int) $post_id; ?>" />
		<input type="hidden" name="type" value="<?php echo esc_attr( $GLOBALS['type'] ); ?>" />
		<input type="hidden" name="tab" value="<?php echo esc_attr( $GLOBALS['tab'] ); ?>" />
		</p>
	</form>
	<script type="text/javascript">
		jQuery(function($) {
			$('#sortable-images').sortable({ 
				tolerance: 'pointer',
				scrollSensitivity: 80
			});
		});
	</script>
	<?php
}

function webpm_display_all_posts($s) {
	echo '<select id="webpm_home_id" name="webpm_home_id">';
	echo '<option value="0">Select Post</option>';
	webpm_fetch_all_posts($s);
	echo '</select>';
}

function webpm_fetch_all_posts($s) {
	global $wpdb;

	$sql = $wpdb->prepare("SELECT ID, post_title FROM $wpdb->posts WHERE post_status IN ('publish', 'private') AND post_mime_type='' AND post_title LIKE '%s' ORDER BY post_title ASC", "%$s%" );
	$res = $wpdb->get_results($sql);
	if (!empty($res)) {
		foreach ($res as $home) {
			if (isset($_POST['s']) && ($_POST['s'] == $_POST['s_old']))
				$selected = ( (isset($_POST['webpm_home_id']) && ($_POST['webpm_home_id'] == $home->ID)) ? ' selected="selected"' : '');

			echo '<option value="'.$home->ID.'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;'.$home->post_title.'</option>';
		}
	}
}

function media_upload_webpm_find_form($errors) {
	global $redir_tab, $type, $wpdb, $dn;

	$redir_tab = 'webpm_find';
	media_upload_header();

	$post_id = intval($_REQUEST['post_id']);
	$form_action_url = admin_url("media-upload.php?type=$type&tab=webpm_find&post_id=$post_id");

	?>

	<form enctype="multipart/form-data" method="post" action="<?php echo esc_attr($form_action_url); ?>" class="media-upload-form validate" id="gallery-form">
	<?php wp_nonce_field('webpm-media-form'); ?>
	<p id="media-search" class="search-box" style="display:block">
		<label class="screen-reader-text" for="media-search-input"><?php _e('Search Media');?>:</label>
		<?php $search = (isset($_POST['s']) ? esc_attr($_POST['s']) : '' ); ?>
		<input type="text" id="media-search-input" name="s" value="<?php echo $search; ?>" />
		<input type="hidden" name="s_old" value="<?php echo $search; ?>" />
		<?php submit_button( __( 'Search Media' ), 'button', '', false, array('id' => 'search_button') ); ?>
	</p>
	<p class="ml-submit" style="float:left;width:100%;">
	<?php if ( isset($_POST['s']) ): ?>
		<?php webpm_display_all_posts($_POST['s']); ?>
	<?php endif; ?>
	</p>

	<?php if ( isset($_POST['webpm_home_id']) && ($_POST['webpm_home_id'] != 0) ): ?>
		<?php if ( isset($_POST['s']) && ($_POST['s_old'] == $_POST['s']) ) : ?>
		<?php
		//Get Attachments
		$attachments = array();
		if ( $post_id ) {
			$attachments = get_children( array( 'post_parent' => $_POST['webpm_home_id'], 'post_status'=>'inherit', 'post_type' => 'attachment', 'orderby' => 'menu_order ID', 'order' => 'ASC') );
		} 
		?>
		<p>
		<select name="peformaction" id="peformaction">
			<option value="no-action">Select Action</option>
			<option value="featured-gallery">Insert Featured Gallery</option>
		</select>
		<?php submit_button( __( 'Execute' ), 'button savebutton', 'execute', false, array( 'id' => 'execute' ) ); ?>
		</p>
		<table id="active-media-panel" class="widefat media-panel" cellspacing="0">
			<thead><tr>
			<th><input type="checkbox" name="selectall" id="selectall" value="all" /></th>
			<th><?php _e('Media'); ?></th>
			<th><?php _e('Post Title'); ?></th>
			</tr></thead>
			<tbody>
			<?php 
				$live_f = array();
				$live_c = array();
				$upload_dir = wp_upload_dir();
				$po = get_post($post_id);
				preg_match_all("/\[gallery\s+include=\"(.*)\"\s*\]/", $po->post_content, $find);
				if (array_key_exists(1,$find)) {
					foreach ($find[1] as $string) {
						$ids = explode(',',$string);
						foreach($ids as $id)
							$live_f[$id] = $id;
					}
				}
				preg_match_all("/src=\"([^\s]+)\"/", $po->post_content, $find);
				if (array_key_exists(1,$find)) {
					foreach ($find[1] as $url) {
						$name = wp_basename(preg_replace('/\-\d{2,}x\d{2,}\./', '.', $url));
						$live_c[$name] = $name;
					}
				}
			?>
			<?php foreach ( (array) $attachments as $id => $attachment ) : ?>

				<?php 
					$featured_links = $featured_class = '';
					if ( array_key_exists($id, $live_f) )
						$featured_class .= ' used-as-gallery';
				?>
				<?php $sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_thumbnail_id' AND meta_value={$id}"; ?>
				<?php $featured_posts = $wpdb->get_col($sql); ?>
				<?php foreach ($featured_posts as $fp): ?> 
					<?php $featured_links .= '<div><span class="line-title">Featured:</span><a target="_blank" href="'.get_edit_post_link($fp).'">'.get_the_title($fp).'</a></div>'; ?>
					<?php $featured_class .= ' used-as-featured'; ?>
				<?php endforeach; ?>

				<?php $meta = wp_get_attachment_metadata( $id, false ); ?>
				<?php $img = wp_get_attachment_image_src($id, 'webpm-thumb'); ?>
				<?php $imglink = $upload_dir['url'].'/'.$meta['file']; ?>
				<tr class="media-row<?php echo $featured_class; ?>">
				<td width="20" class="center"><input name="selection[]" type="checkbox" value="<?php echo $id; ?>" /></td>
				<td width="135">
					<img class="filename" src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" />
					<?php $ajax_nonce = wp_create_nonce( "set_post_thumbnail-$post_id" ); ?>
					<a style="display:block;" id='wp-post-thumbnail-<?php echo $id; ?>' href='#' onclick='WPSetAsThumbnail("<?php echo $id; ?>", "<?php echo $ajax_nonce; ?>");return false;'>Use as Featured Image</a>
				</td>
				<td class="top">
					<b><?php echo $attachment->post_title; ?></b> (<?php echo $id; ?>)<br />
					<input type="radio" name="imagesize" value="webpm-thumb:<?php echo $id; ?>" /><span>Thumbnail</span>
					<input type="radio" name="imagesize" value="medium:<?php echo $id; ?>" /><span>Medium</span>
					<input type="radio" name="imagesize" value="large:<?php echo $id; ?>" /><span>Large</span><br />
					<input type="radio" name="imageposition" value="alignleft" /><span>Left</span>
					<input type="radio" name="imageposition" value="aligncenter" /><span>Center</span>
					<input type="radio" name="imageposition" value="alignright" /><span>Right</span>
					<input type="radio" name="imageposition" value="alignnone" /><span>None</span>
					<?php submit_button( __( 'Insert Into Post' ), 'button sendbutton', 'send', false, array( 'id' => 'send' ) ); ?><br />

					<?php if (!empty($meta['image_meta']['credit'])) : ?>
						<div><span class="line-title">Credit:</span><?php echo $meta['image_meta']['credit']; ?></div>
					<?php endif; ?>
					<?php if (!empty($meta['image_meta']['camera'])) : ?>
						<div><span class="line-title">Camera:</span><?php echo $meta['image_meta']['camera']; ?></div>
					<?php endif; ?>
					<?php if (!empty($meta['image_meta']['created_timestamp'])) : ?>
						<div><span class="line-title">Time:</span><?php echo date("F j, Y, g:i a", $meta['image_meta']['created_timestamp']); ?></div>
					<?php endif; ?>
					<?php if (!empty($meta['file'])) : ?>
						<?php 
							$file_class = '';
							$filename = wp_basename($meta['file']);
							if (array_key_exists($filename, $live_c))
								$file_class = ' used-file';
						?>
						<div class="dummy<?php echo $file_class; ?>">
							<span class="line-title">File:</span><?php echo $filename; ?>&nbsp;[<a href="<?php echo $imglink; ?>" target="_blank"><?php echo $meta['width'].'x'.$meta['height']; ?></a>]&nbsp;[<a href="<?php echo get_permalink($id); ?>" target="_blank">URL</a>]
						</div>
					<?php endif; ?>
					<?php if (array_key_exists('sizes', $meta)) : ?>
						<div><span class="line-title">Sizes:</span>
							<?php foreach ($meta['sizes'] as $size) : ?>
								<?php $imglink = str_replace(wp_basename($imglink), $size['file'], $imglink); ?>
								[<a href="<?php echo $imglink; ?>" target="_blank" ><?php echo $size['width'].'x'.$size['height']; ?></a>]
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php if (!empty($attachment->post_date)) : ?>
						<div><span class="line-title">Uploaded:</span><?php echo date("F j, Y, g:i a", strtotime($attachment->post_date)); ?></div>
					<?php endif; ?>
					<?php echo $featured_links; ?>
				</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	<?php endif; ?>

	<p class="ml-submit">
	<input type="hidden" name="post_id" id="post_id" value="<?php echo (int) $post_id; ?>" />
	<input type="hidden" name="type" value="<?php echo esc_attr( $GLOBALS['type'] ); ?>" />
	<input type="hidden" name="tab" value="<?php echo esc_attr( $GLOBALS['tab'] ); ?>" />
	</p>

	</form>

	<script type="text/javascript">
		jQuery(function ($) {
			$('#webpm_home_id').change(function () {
				this.form.submit();
			});
			$('#media-search-input').focus();
			$('#selectall').click(function () {
				$(this).parents('table:eq(0)').find(':checkbox').attr('checked', this.checked);
			});
		});
	</script>
	<?php
}

function media_upload_webpm_form($errors) {
	global $redir_tab, $type, $wpdb;

	$redir_tab = 'webpm';
	media_upload_header();

	$post_id = intval($_REQUEST['post_id']);
	$form_action_url = admin_url("media-upload.php?type=$type&tab=webpm&post_id=$post_id");

	//Get Attachments
	$attachments = array();
	if ( $post_id ) {
		$attachments = get_children( array( 'post_parent' => $post_id, 'post_status'=>'inherit', 'post_type' => 'attachment', 'orderby' => 'menu_order ID', 'order' => 'ASC') );
	} 
	?>

	<form enctype="multipart/form-data" method="post" action="<?php echo esc_attr($form_action_url); ?>" class="media-upload-form validate" id="gallery-form">
	<?php wp_nonce_field('webpm-media-form'); ?>
	<p class="ml-submit">
	<select name="peformaction" id="peformaction">
		<option value="no-action">Select Action</option>
		<option value="exclude">Exclude Selected Pictures</option>
		<option value="include">Include Selected Pictures</option>
		<option value="featured-gallery">Insert Selected Gallery</option>
		<option value="delete">Delete Selected Pictures</option>
	</select>
	<?php submit_button( __( 'Execute' ), 'button savebutton', 'execute', false, array( 'id' => 'execute' ) ); ?>
	</p>
	<table id="active-media-panel" class="widefat media-panel" cellspacing="0">
		<thead><tr>
		<th><input type="checkbox" name="selectall" id="selectall" value="all" /></th>
		<th><?php _e('Media'); ?></th>
		<th><?php _e('Post Title'); ?></th>
		</tr></thead>
		<tbody>
		<?php 
			$live_f = array();
			$live_c = array();
			$upload_dir = wp_upload_dir();
			$po = get_post($post_id);
			preg_match_all("/\[featured\s+include=\"(.*)\"\s*\]/", $po->post_content, $find);
			if (array_key_exists(1,$find)) {
				foreach ($find[1] as $string) {
					$ids = explode(',',$string);
					foreach($ids as $id)
						$live_f[$id] = $id;
				}
			}
			preg_match_all("/src=\"([^\s]+)\"/", $po->post_content, $find);
			if (array_key_exists(1,$find)) {
				foreach ($find[1] as $url) {
					$name = wp_basename(preg_replace('/\-\d{2,}x\d{2,}\./', '.', $url));
					$live_c[$name] = $name;
				}
			}
		?>
		<?php foreach ( (array) $attachments as $id => $attachment ) : ?>

			<?php 
				$featured_links = $featured_class = '';
				if ( array_key_exists($id, $live_f) )
					$featured_class .= ' used-as-gallery';
			?>
			<?php $sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_thumbnail_id' AND meta_value={$id}"; ?>
			<?php $featured_posts = $wpdb->get_col($sql); ?>
			<?php foreach ($featured_posts as $fp): ?> 
				<?php $featured_links .= '<div><span class="line-title">Featured:</span><a target="_blank" href="'.get_edit_post_link($fp).'">'.get_the_title($fp).'</a></div>'; ?>
				<?php $featured_class .= ' used-as-featured'; ?>
			<?php endforeach; ?>

			<?php $meta = wp_get_attachment_metadata( $id, false ); ?>
			<?php $img = wp_get_attachment_image_src($id, 'webpm-thumb'); ?>
			<?php $imglink = $upload_dir['url'].'/'.$meta['file']; ?>
			<tr class="media-row<?php echo $featured_class; ?>">
			<td width="20" class="center"><input id="selection<?php echo $id; ?>" name="selection[]" type="checkbox" value="<?php echo $id; ?>" /><label class="bigcheck" for="selection<?php echo $id; ?>"></label></td>
			<td width="135">
				<img class="filename" src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" />
				<?php $ajax_nonce = wp_create_nonce( "set_post_thumbnail-$post_id" ); ?>
				<a style="display:block;" id='wp-post-thumbnail-<?php echo $id; ?>' href='#' onclick='WPSetAsThumbnail("<?php echo $id; ?>", "<?php echo $ajax_nonce; ?>");return false;'>Use as Featured Image</a>
			</td>
			<td class="top">
				<b><?php echo $attachment->post_title; ?></b> (<?php echo $id; ?>)<br />
				<input type="radio" name="imagesize" value="webpm-thumb:<?php echo $id; ?>" /><span>Thumbnail</span>
				<input type="radio" name="imagesize" value="medium:<?php echo $id; ?>" /><span>Medium</span>
				<input type="radio" name="imagesize" value="large:<?php echo $id; ?>" /><span>Large</span><br />
				<input type="radio" name="imageposition" value="alignleft" /><span>Left</span>
				<input type="radio" name="imageposition" value="aligncenter" /><span>Center</span>
				<input type="radio" name="imageposition" value="alignright" /><span>Right</span>
				<input type="radio" name="imageposition" value="alignnone" /><span>None</span>
				<?php submit_button( __( 'Insert Into Post' ), 'button sendbutton', 'send', false, array( 'id' => 'send' ) ); ?><br />

				<?php if (!empty($meta['image_meta']['credit'])) : ?>
					<div><span class="line-title">Credit:</span><?php echo $meta['image_meta']['credit']; ?></div>
				<?php endif; ?>
				<?php if (!empty($meta['image_meta']['camera'])) : ?>
					<div><span class="line-title">Camera:</span><?php echo $meta['image_meta']['camera']; ?></div>
				<?php endif; ?>
				<?php if (!empty($meta['image_meta']['created_timestamp'])) : ?>
					<div><span class="line-title">Time:</span><?php echo date("F j, Y, g:i a", $meta['image_meta']['created_timestamp']); ?></div>
				<?php endif; ?>
				<?php if (!empty($meta['file'])) : ?>
					<?php 
						$file_class = '';
						$filename = wp_basename($meta['file']);
						if (array_key_exists($filename, $live_c))
							$file_class = ' used-file';
					?>
					<div class="dummy<?php echo $file_class; ?>">
						<span class="line-title">File:</span><?php echo $filename; ?>&nbsp;[<a href="<?php echo $imglink; ?>" target="_blank"><?php echo $meta['width'].'x'.$meta['height']; ?></a>]&nbsp;[<a href="<?php echo get_permalink($id); ?>" target="_blank">URL</a>]
					</div>
				<?php endif; ?>
				<?php if (array_key_exists('sizes', $meta)) : ?>
					<div><span class="line-title">Sizes:</span>
						<?php foreach ($meta['sizes'] as $size) : ?>
							<?php $imglink = str_replace(wp_basename($imglink), $size['file'], $imglink); ?>
							[<a href="<?php echo $imglink; ?>" target="_blank" ><?php echo $size['width'].'x'.$size['height']; ?></a>]
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if (!empty($attachment->post_date)) : ?>
					<div><span class="line-title">Uploaded:</span><?php echo date("F j, Y, g:i a", strtotime($attachment->post_date)); ?></div>
				<?php endif; ?>
				<?php echo $featured_links; ?>
			</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
	if ( $post_id ) {
		$attachments = get_children( array( 'post_parent' => $post_id, 'post_status'=>'private', 'post_type' => 'attachment', 'orderby' => 'menu_order ID', 'order' => 'ASC') );
	} 
	?>
	<table class="widefat media-panel excluded" cellspacing="0">
	<thead><tr>
	<th><input type="checkbox" name="hselectall" id="hselectall" value="all" /></th>
	<th><?php _e('Hidden Media'); ?></th>
	<th><?php _e('Post Title'); ?></th>
	</tr></thead>
	<tbody>
	<?php foreach ( (array) $attachments as $id => $attachment ) : ?>
		<?php $meta = wp_get_attachment_metadata( $id, false ); ?>
		<?php $img = wp_get_attachment_image_src($id, 'webpm-thumb'); ?>
		<?php $imglink = $upload_dir['url'].'/'.$meta['file']; ?>
		<tr>
		<td width="20" class="center"><input id="hselection<?php echo $id; ?>" name="hselection[]" type="checkbox" value="<?php echo $id; ?>" /><label class="bigcheck" for="hselection<?php echo $id; ?>"></label></td>
		<td width="135"><img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" /></td>
		<td class="top">
			<b><?php echo $attachment->post_title.' ('.$id.')'; ?></b>
			<?php if (!empty($meta['image_meta']['credit'])) : ?>
				<div><span class="line-title">Credit:</span><?php echo $meta['image_meta']['credit']; ?></div>
			<?php endif; ?>
			<?php if (!empty($meta['image_meta']['camera'])) : ?>
				<div><span class="line-title">Camera:</span><?php echo $meta['image_meta']['camera']; ?></div>
			<?php endif; ?>
			<?php if (!empty($meta['image_meta']['created_timestamp'])) : ?>
				<div><span class="line-title">Time:</span><?php echo date("F j, Y, g:i a", $meta['image_meta']['created_timestamp']); ?></div>
			<?php endif; ?>
			<?php if (!empty($meta['file'])) : ?>
				<div><span class="line-title">File:</span><?php echo wp_basename($meta['file']); ?>&nbsp;[<a href="<?php echo $imglink; ?>" target="_blank"><?php echo $meta['width'].'x'.$meta['height']; ?></a>]&nbsp;[<a href="<?php echo get_permalink($id); ?>" target="_blank">URL</a>]</div>
			<?php endif; ?>
			<?php if (array_key_exists('sizes', $meta)) : ?>
				<div><span class="line-title">Sizes:</span>
					<?php foreach ($meta['sizes'] as $size) : ?>
						<?php $imglink = str_replace(wp_basename($imglink), $size['file'], $imglink); ?>
						[<a href="<?php echo $imglink; ?>" target="_blank" ><?php echo $size['width'].'x'.$size['height']; ?></a>]
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
	</table>

	<p class="ml-submit">
	<?php submit_button( __( 'Save all changes' ), 'button savebutton', 'save', false, array( 'id' => 'save-all' ) ); ?>
	<input type="hidden" name="post_id" id="post_id" value="<?php echo (int) $post_id; ?>" />
	<input type="hidden" name="type" value="<?php echo esc_attr( $GLOBALS['type'] ); ?>" />
	<input type="hidden" name="tab" value="<?php echo esc_attr( $GLOBALS['tab'] ); ?>" />
	</p>

	</form>

	<script type="text/javascript">
		jQuery(function ($) {
			$('#selectall, #hselectall').click(function () {
				$(this).parents('table:eq(0)').find(':checkbox').attr('checked', this.checked);
			});
		});
	</script>
	<?php
}

add_filter( 'post_gallery', 'webpm_shortcode_post_gallery', 10, 2 );
	
function webpm_shortcode_post_gallery($none, $attr) {
	global $post;

	static $instance = 0;
	$instance++;

	// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	if ( isset( $attr['orderby'] ) ) {
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		if ( !$attr['orderby'] )
			unset( $attr['orderby'] );
	}

	extract(shortcode_atts(array(
		'order'      => 'ASC',
		'orderby'    => 'menu_order ID',
		'id'         => $post->ID,
		'itemtag'    => 'dl',
		'icontag'    => 'dt',
		'captiontag' => 'dd',
		'columns'    => 5,
		'size'       => 'large',
		'include'    => '',
		'exclude'    => '',
		'displaylarge'	 => 0,
		'skipfirst' => 0,
		'showcaption'=> 1,
		'permalink'    => 0,
		'current'	=> 0
	), $attr));
	$displaylarge = (bool)$displaylarge;
	$showcaption = (bool)$showcaption;
	$permalink = (bool)$permalink;
	$skipfirst = (bool)$skipfirst;

	$id = intval($id);
	if ( 'RAND' == $order )
		$orderby = 'none';

	if ( !empty($include) ) {
		$include = preg_replace( '/[^0-9,]+/', '', $include );
		$_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
		$sort = explode(',',$include);

		$attachments = array();
		foreach ( $sort as $_id ) {
			$attachments[$_id] = $_attachments[$_id];
		}
	} elseif ( !empty($exclude) ) {
		$exclude = preg_replace( '/[^0-9,]+/', '', $exclude );
		$attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	} else {
		$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	}

	if ( empty($attachments) )
		return '';

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment )
			$output .= wp_get_attachment_link($att_id, 'fixedwidth-thumb', true) . "\n";
		return $output;
	}

	$stack = $line = array();
	if ($columns == 4) {
		$stack[0] = $stack[1] = $stack[2] = $stack[3] = 0;
		$line[3] = 0; $line[2] = 143; $line[1] = 286; $line[0] = 429;
	}
	else {
		$stack[0] = $stack[1] = $stack[2] = $stack[3] = $stack[4] = 0;
		$line[4] = 0; $line[3] = 143; $line[2] = 286; $line[1] = 429; $line[0] = 572; 
	}
	$arrsize = sizeof($attachments);
	$skipfirst = ($arrsize <= 1 ? true : $skipfirst);

	$output = $leadimage = '';
	$index = 0;
	foreach ( $attachments as $id => $attachment ) {
		$metadata = wp_get_attachment_metadata($id, true);
		$original_file = wp_basename($metadata['file']);
		if ($displaylarge) {
			$link = wp_get_attachment_link($id, $size, $permalink, false);
			if (!$permalink)
				$link = str_replace($original_file, $metadata['sizes'][$size]['file'], $link);

			$leadimage = '<div class="dark-background lead-gallery-image">'.$link.'</div>';
			if ($showcaption) {
				if (isset($post->sig_nhood_id) && (!empty($post->sig_nhood_id) ) )
					$leadimage .= '<div class="large-display-caption">'.get_the_title($post->sig_nhood_id).' Real Estate</div>';
				else
					$leadimage .= '<div class="large-display-caption">'.get_the_title($post->ID).' Gallery</div>';
			}
			$displaylarge = false;
			if ($skipfirst)
				continue;
		}

		if (array_key_exists('sizes', $metadata))
			$height = $metadata['sizes']['fixedwidth-thumb']['height'];
		else
			$height = $metadata['height'];
		$min = min($stack);
		$column = array_search($min, $stack);
		$link = wp_get_attachment_link($id, 'fixedwidth-thumb', $permalink, false);
		if (!$permalink)
			if (array_key_exists($size, $metadata['sizes']))
				$link = str_replace($original_file, $metadata['sizes'][$size]['file'], $link);

		$current_photo = ($current == $index ? ' current-photo' : '');
		$output .= '<div style="top:'.$stack[$column].'px;left:'.$line[$column].'px;position:absolute;" class="gallery-icon'.$current_photo.'"><div class="arrow"></div>'.$link.'</div>';
		$stack[$column] += $height + 8; 
		$index++;
	}

	$max = max($stack);
	$output = '<div class="clearfix"></div><a name="gallery"></a>'.$leadimage.'<div id="thumb-gallery" style="height:'.$max.'px;position:relative;" class="image-scroll-wrapper">'.$output.'</div>';

	return $output;
}
