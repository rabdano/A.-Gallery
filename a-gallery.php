<?php
/*
Plugin Name: A. Gallery
Plugin URI: http://rabdano.ru/wordpress-plugins/a-gallery/
Description: A. Gallery allow you attach and detach images to post in one click. Also included shortcode that provides a beautiful gallery.
Version: 1.0
Author: Sevastyan Rabdano
Author URI: http://rabdano.ru/
License: GPLv2 or later
*/

define( 'A_GALLERY_URL', plugin_dir_url( __FILE__ ) );
define( 'ACT_FILENAME', A_GALLERY_URL . 'actions.php' );

add_option( 'ag-admin-thumbnail-w', 125 );
add_option( 'ag-admin-thumbnail-h', 125 );
add_option( 'ag-default-count', 10 );
add_option( 'ag-default-border', 3 );
add_option( 'ag-default-border-color', '#ccc' );
add_option( 'ag-default-item-w', 100 );
add_option( 'ag-default-item-h', 100 );
add_option( 'ag-default-columns', 4 );
add_option( 'ag_timthumb_max_file_size', 100 );

load_plugin_textdomain( 'a-gallery', false, dirname( plugin_basename( __FILE__ ) ) . '/l10n/' );

/**
 * Delete post_parent ftom attachment.
 *
 * @uses $wpdb, get_posts()
 *
 * @param int $post_id Post ID.
 * @param string $att_value Relative path to the attached file.
 */
function ag_detach_image( $post_id, $att_value ) {
	global $wpdb;
	if ( ! empty( $post_id ) ) {
		$args = 'post_parent='.$post_id.'&post_type=attachment&guid='.$att_value.'&';
		$attachments = get_posts( $args );
		$attachment = array_shift( $attachments );
		$wpdb->update( $wpdb->posts, array( 'post_parent' => '' ), array( 'ID' => $attachment->ID ) );
	}
}

/**
 * Get and return post attachments by ID.
 *
 * @uses get_children(), get_posts()
 *
 * @param int $post_id Post ID.
 * @return array Contains attachments objects.
 */
function ag_get_attachments( $post_id ) {
	$attachments = get_children( array(
		'post_parent' => $post_id,
		'post_type' => 'attachment',
		'post_mime_type' => 'image',
		'orderby' => 'menu_order'
	) );
	return $attachments;
}

/**
 * Adds plugin settings page.
 *
 * @uses add_options_page()
 */
function ag_add_options_page() {	
	add_options_page( __( 'A. Gallery options', 'a-gallery' ), __( 'A. Gallery', 'a-gallery' ), 8, basename(__FILE__), 'ag_display_options' );
}
add_action( 'admin_menu', 'ag_add_options_page' );

/**
 * Return plugin settings page.
 * 
 * @uses get_option()
 */
function ag_display_options() {
	?>
	<div class="wrap">
	<div id="ag-icon-options" class="icon32"><br></div>
	<h2><?php _e( 'A. Gallery settings', 'a-gallery' ); ?></h2>
	<form method="post" action="options.php">
		<?php wp_nonce_field('update-options'); ?>
		<h3><?php _e( 'Admin', 'a-gallery' ); ?></h3>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="ag-admin-thumbnail-w"><?php _e( 'Thumbnail width on post edit page', 'a-gallery' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="ag-admin-thumbnail-w" name="ag-admin-thumbnail-w" value="<?php echo get_option( 'ag-admin-thumbnail-w' ); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ag-admin-thumbnail-h"><?php _e( 'Thumbnail height on post edit page', 'a-gallery' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="ag-admin-thumbnail-h" name="ag-admin-thumbnail-h" value="<?php echo get_option( 'ag-admin-thumbnail-h' ); ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		<h3>Shortcode</h3>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="ag-default-count"><?php _e( 'Default<sup>1</sup> number of pictures in gallery', 'a-gallery' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="ag-default-count" name="ag-default-count" value="<?php echo get_option( 'ag-default-count' ); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ag-default-border"><?php _e( 'Default width of border', 'a-gallery' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="ag-default-border" name="ag-default-border" value="<?php echo get_option( 'ag-default-border' ); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ag-default-border-color"><?php _e( 'Default border color', 'a-gallery' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="ag-default-border-color" name="ag-default-border-color" value="<?php echo get_option( 'ag-default-border-color' ); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ag-default-item-w"><?php _e( 'Default gallery thumbnail width', 'a-gallery' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="ag-default-item-w" name="ag-default-item-w" value="<?php echo get_option( 'ag-default-item-w' ); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ag-default-item-h"><?php _e( 'Defaul gallery thumbnail height', 'a-gallery' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="ag-default-item-h" name="ag-default-item-h" value="<?php echo get_option( 'ag-default-item-h' ); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ag-default-columns"><?php _e( 'Default number of visible pictures (number of columns)', 'a-gallery' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="ag-default-columns" name="ag-default-columns" value="<?php echo get_option( 'ag-default-columns' ); ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		<p><?php _e( "<sup>1</sup> — This values will be used when entered shortcode don't provides any attributes. For example, if post contains " . '"[a_gallery item_h="200"]"' . " then all values will be taken from here except item_h (thumbnail height).", 'a-gallery' ); ?></p>
		<br />
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="ag-admin-thumbnail-w,ag-admin-thumbnail-h,ag-default-count,ag-default-border,ag-default-border-color,ag-default-item-w,ag-default-item-h,ag-default-columns" />
		<input type="submit" id="submit" class="button-primary" name="update" value="<?php _e( 'Save changes', 'a-gallery' ); ?>" />
	</form>
	</div>
	<?php
}

/**
 * Adds link to actions.php to media editing window. Attaches the image to post.
 *
 * @param array $form_fields Form fields.
 * @param mixed Post data.
 *
 * @return 
 */
function ag_add_fields_to_edit( $form_fields, $post ) {
	$request_url = ACT_FILENAME . "?ag_aid={$post->ID}&ag_pid=" . $_REQUEST['post_id'];
	if ( $post->post_parent == 0 ) {
		$form_fields['_final'] = '<div style="width:100%;text-align:center;"><a href="' . $request_url . '">' . __( 'Attach to post', 'a-gallery' ) . '</a>';
	}
	return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'ag_add_fields_to_edit', 10, 2 );

/**
 * Display images box for sorting and detaching.
 *
 * @uses add_meta_box(), wp_nonce_field()
 */
function ag_add_detach_box() {
    add_meta_box( 'ag_detach_box', __( 'Sort or detach images' ), 'ag_inner_custom_box', 'post' );
    
    // display box of images
	function ag_inner_custom_box() {
		global $post;
		wp_nonce_field( plugin_basename( __FILE__ ), 'ag_nname' );
		$attachments = ag_get_attachments( $post->ID );
		
		// save sort order like in post edit page
		$meta = get_post_meta( $post->ID, 'ag_post_images' );
		$meta = $meta[0];
		foreach ( $attachments as $place => $i ) {
			foreach ( $meta as $key => $value ) {
				if ( wp_get_attachment_url( $i->ID ) == $value ) {
					$attachments[$key] = $attachments[$place];
					unset( $attachments[$place] );
				}
			}
		}
		ksort( $attachments );
		
		?>
		<ul id="ag-sortable">
		<?php
		$count = 0;
		foreach ( $attachments as $key => $attachment ):
			$imagelink = wp_get_attachment_url( $attachment->ID );
			$w = get_option( 'ag-admin-thumbnail-w' );
			$h = get_option( 'ag-admin-thumbnail-h' );
			?>
			<li class="item ui-state-default" style="width:<?php echo $w + 3; ?>px;height:<?php echo $w + 33; ?>px;">
				<a class="close" style="left: <?php echo $w - 15; ?>px;">x</a>
				<img src="<?php echo A_GALLERY_URL . 'timthumb.php?src=' . $imagelink . '&w=' . $w . '&h=' . $h . '&zc=1'; ?>" />
				<input type="hidden" name="ag_images_to_save[<?php echo $count; ?>]" value="<?php echo $imagelink; ?>" rel="<?php echo $count; $count++; ?>" />
			</li>
			<?php
		endforeach;
		?>
		</ul>
		<?php
	}
}
add_action( 'admin_init', 'ag_add_detach_box', 1 );

/**
 * Detach images from post when running 'save_post' action.
 *
 * @uses wp_verify_nonce(), plugin_basename(), current_user_can(), wp_is_post_revision, *_post_meta(), wp_get_attachment_url()
 *
 * @param int $post_id Post ID.
 */
function ag_save_postdata( $post_id ) {
	if ( ! wp_verify_nonce( $_REQUEST['ag_nname'], plugin_basename( __FILE__ ) ) ) return $post_id;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
	if ( 'page' == $_REQUEST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) return $post_id;
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) ) return $post_id;
	}
	$post_id = wp_is_post_revision( $post_id );
	
	// save images from $_REQUEST variable
	$ag_images_to_save = $_REQUEST['ag_images_to_save'];
	
	$old_meta = get_post_meta( $post_id, 'ag_post_images' );
	delete_post_meta( $post_id, 'ag_post_images', $old_meta[0] );
	add_post_meta( $post_id, 'ag_post_images', $ag_images_to_save, true );
	
	// detach images that not needed any more
	$attachments = ag_get_attachments( $post_id );
	foreach ( $attachments as $attachment ) {
		$image = wp_get_attachment_url( $attachment->ID );
		if ( ! in_array( $image, $ag_images_to_save ) ) {
			ag_detach_image( $post_id, $image );
		}
	}
}
add_action( 'save_post', 'ag_save_postdata' );

/**
 * Process shortcode [a_gallery].
 *
 * @uses apply_filters(), get_option(), get_post_meta(), wp_get_attachment_url()
 *
 * @param string $attr Shortcode attributes.
 * @return string $html Processed html.
 */
function ag_shortcode( $attr ) {
	global $post;
	
	$attr = apply_filters( 'post_a_gallery', $attr );
	
	extract( shortcode_atts( array(
		'post_id' => $post->ID,
		'count' => get_option( 'ag-default-count' ),
		'border' => get_option( 'ag-default-border' ),
		'border_color' => get_option( 'ag-default-border-color' ),
		'item_w' => get_option( 'ag-default-item-w' ),
		'item_h' => get_option( 'ag-default-item-h' ),
		'columns' => get_option( 'ag-default-columns' ),
		'exclude' => ''
	), $attr) );
	
	$r = ag_get_attachments( $post_id );
	$exclude = explode( ',', $exclude );
	foreach ( $exclude as $ex ) {
		unset( $r[$ex] );
	}
	
	// save sort order like in post edit page
	$meta = get_post_meta( $post_id, 'ag_post_images' );
	$meta = $meta[0];
	foreach ( $r as $place => $i ) {
		foreach ( $meta as $key => $value ) {
			if ( wp_get_attachment_url( $i->ID ) == $value ) {
				$r[$key] = $r[$place];
				unset( $r[$place] );
			}
		}
	}
	ksort( $r );
	
	if ( count( $r ) < $count ) $count = count( $r );
	$item_c_w = $item_w + $border * 2;
	$item_c_h = $item_h + $border * 2;
	$c_width = ( $item_c_w + 10 ) * $columns - 10;
	
	$html = "<div id='a-gallery' style='width:{$c_width}px !important;height:{$item_c_h}px !important;'>\n";
	if ( $columns < $count ) $html .= "\t<div id='a-gallery-left' style='height:{$item_c_h}px !important;'></div>\n";
	$html .= "\t<div id='a-gallery-container' style='width:{$c_width}px !important;height:{$item_c_h}px !important;'>\n";
	if ( $columns < $count ) $html .= "\t\t<div id='slider' style='width:{$c_width}px !important;height:{$item_c_h}px !important;'>\n";
	
	$c = 0;
	foreach ( $r as $i ) {
		$imagelink = wp_get_attachment_url( $i->ID );
		$thumblink = A_GALLERY_URL . 'timthumb.php?src=' . $imagelink . '&w=' . $item_w . '&h=' . $item_h . '&zc=1';
		$c++;
		
		$last = '';
		if ( $c >= $count ) $last = "last";
		
		$html .= "\t\t\t<div class='image {$last}' style='width:{$item_c_w}px;height:{$item_c_h}px;'>\n";
		$html .= "\t\t\t\t<a href='{$imagelink}'><img src='{$thumblink}' style='width:{$item_w}px;height:{$item_h}px;border:{$border}px solid {$border_color};' /></a>\n";
		$html .= "\t\t\t</div><!-- end of div#image -->\n";
		
		if ( $c >= $count ) break;
	}
	$html .= "\t\t</div><!-- end of div#slider -->\n";
	if ( $columns < $count ) $html .= "\t</div><!-- end of div#a-gallery-container -->\n";
	if ( $columns < $count ) $html .= "\t<div id='a-gallery-right' style='height:{$item_c_h}px !important; left:{$c_width}px;'></div>\n";
	$html .= "</div><!-- end of div#a-gallery -->\n";
	$max_right_count = $count - $columns;
	$item_c_w =  esc_attr( $item_c_w + 10 );
	$lightboxurl = A_GALLERY_URL;
	$html .= "<script type='text/javascript'>
				<!--<![CDATA[-->
				jQuery(document).ready(function($) {
					
					var leftCount = 0;
					var rightCount = {$max_right_count};
					
					$('#a-gallery #a-gallery-left').click(function(){
						if (leftCount > 0) {
							$('#a-gallery-container #slider').animate({\"left\": \"+={$item_c_w}px\"}, \"normal\");
							rightCount = rightCount + 1;
							leftCount = leftCount - 1;
						}
					});

					$('#a-gallery #a-gallery-right').click(function(){
						if (rightCount > 0) {
							$('#a-gallery-container #slider').animate({\"left\": \"-={$item_c_w}px\"}, \"normal\");
							rightCount = rightCount - 1;
							leftCount = leftCount + 1;
						}
					});
					
					$(function() {
					   $('#a-gallery a').lightBox({
						overlayBgColor: '#FFF',
						overlayOpacity: 0.6,
						imageLoading: '{$lightboxurl}images/loading.gif',
						imageBtnClose: '{$lightboxurl}images/close.gif',
						imageBtnPrev: '{$lightboxurl}images/prev.gif',
						imageBtnNext: '{$lightboxurl}images/next.gif',
						containerResizeSpeed: 350,
						maxHeight: screen.height * 0.8,
						maxWidth: screen.width * 0.8
					   });
					});
				});
				<!--]]>-->
				</script>";
	
	return $html;
}
add_shortcode( 'a_gallery', 'ag_shortcode' );

/**
 * Enqueue javascript and css for administration pages
 *
 * @uses wp_register_script(), wp_enqueue_script(), wp_register_style(), wp_enqueue_style()
 */
function ag_admin_head_inserts() {
	wp_register_script( 'a-gallery-admin-js', A_GALLERY_URL . 'js/a-gallery-admin.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ) );
	wp_enqueue_script( 'a-gallery-admin-js' );
	wp_register_style('a-gallery-admin-css', A_GALLERY_URL . 'css/a-gallery-admin.css' );
	wp_enqueue_style( 'a-gallery-admin-css');
}
add_action('admin_print_scripts', 'ag_admin_head_inserts' );

/**
 * Enqueue javascript and css
 *
 * @uses wp_register_script(), wp_enqueue_script(), wp_register_style(), wp_enqueue_style()
 */
function ag_head_inserts() {
	wp_register_script( 'jquery-lightbox-js', A_GALLERY_URL . 'js/jquery-lightbox.js', array( 'jquery' ) );
	wp_enqueue_script( 'jquery-lightbox-js' );
	wp_register_style( 'jquery-lightbox-css', A_GALLERY_URL . 'css/jquery-lightbox.css' );
	wp_enqueue_style( 'jquery-lightbox-css');
	wp_register_style('a-gallery-css', A_GALLERY_URL . 'css/a-gallery.css' );
	wp_enqueue_style( 'a-gallery-css');
}
add_action('init', 'ag_head_inserts');


?>