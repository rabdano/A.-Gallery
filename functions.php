<?php
/**
 * File with functions of plugin.
 *
 * @since 1.1
 *
 */



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



/**
 * Enqueue javascript and css
 *
 * @uses wp_register_script(), wp_enqueue_script(), wp_register_style(), wp_enqueue_style()
 */
function ag_head_inserts() {
	// Add a-gallery
	wp_register_script( 'a-gallery-js', A_GALLERY_URL . 'js/a-gallery.js', array( 'jquery' ) );
	wp_register_style('a-gallery-css', A_GALLERY_URL . 'css/a-gallery.css' );
	wp_enqueue_script( 'a-gallery-js' );
	wp_enqueue_style( 'a-gallery-css');
	// Add mousewheel plugin (this is optional)
	wp_register_script( 'jquery-mousewheel-js', A_GALLERY_URL . 'js/jquery.mousewheel.pack.js', array( 'jquery' ) );
	wp_enqueue_script( 'jquery-mousewheel-js' );
	// Add fancyBox
	wp_register_script( 'jquery-fancybox-js', A_GALLERY_URL . 'js/fancybox/jquery.fancybox.pack.js', array( 'jquery' ) );
	wp_register_style( 'jquery-fancybox-css', A_GALLERY_URL . 'js/fancybox/jquery.fancybox.css' );
	wp_enqueue_script( 'jquery-fancybox-js' );
	wp_enqueue_style( 'jquery-fancybox-css' );
	// Add thumbnail helper
	wp_register_script( 'jquery-fancybox-thumbs-js', A_GALLERY_URL . 'js/fancybox/helpers/jquery.fancybox-thumbs.js',
						array( 'jquery-fancybox-js' ) );
	wp_register_style( 'jquery-fancybox-thumbs-css', A_GALLERY_URL . 'js/fancybox/helpers/jquery.fancybox-thumbs.css' );
	wp_enqueue_script( 'jquery-fancybox-thumbs-js' );
	wp_enqueue_style( 'jquery-fancybox-thumbs-css' );
}



/**
 * Resize and crop image.
 *
 * @uses get_post_meta(), WP_Error(), wp_get_attachment_url(), image_resize()
 *
 * @param int $attachment_id Post ID.
 * @param int $width Width of destination image.
 * @param int $height Height of destination image.
 * @return string Image url.
 */
function ag_image( $attachment_id, $width, $height ) {
	$file = get_post_meta( $attachment_id, '_wp_attached_file', true );
	// If the file is relative, prepend upload dir
	if ( 0 !== strpos($file, '/') && !preg_match('|^.:\\\|', $file) && ( ($uploads = wp_upload_dir()) && false === $uploads['error'] ) )
		$file = $uploads['basedir'] . "/$file";
	// Get width and height of original image
	$size = getimagesize( $file  );
	if ( !$size )
		return new WP_Error( 'invalid_image', __( 'Could not read image size', 'a-gallery' ), $file );
	list( $orig_w, $orig_h, $orig_type ) = $size;
	// Calculate aspect ratios
	$orig_aspect_ratio = $orig_w / $orig_h;
	$aspect_ratio = $width / $height;
	// Prepare new image url to return
	$oldimageurl = wp_get_attachment_url( $attachment_id );
	$urlinfo = pathinfo( $oldimageurl );
	$imageurl = $urlinfo['dirname'] . "/" . $urlinfo['filename'] . "-{$width}x{$height}" . "." . $urlinfo['extension'];
	// If image exists return $imageurl
	$headers = get_headers( $imageurl );
	if ( (strpos( $headers[0], "200" ) === false) ) {
		if ( $orig_aspect_ratio >= $aspect_ratio ) {
			// resize
			$tmpfile = image_resize( $file, $height * $orig_aspect_ratio, $height, false, "a-gallery" );
			// crop
			$file = image_resize( $file, $width, $height, true, "{$width}x{$height}" );
			unlink( $tmpfile );
		}
		else {
			// resize
			$tmpfile = image_resize( $file, $width, $wigth * $orig_aspect_ratio, false, "a-gallery" );
			// crop
			$file = image_resize( $file, $width, $height, true, "{$width}x{$height}" );
			unlink( $tmpfile );
		}
	}
	return $imageurl;
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
 * Handle image uploads: update post meta.
 *
 * @param $post_ID int Post id.
 */
function ag_image_upload_handler( $attachment_ID ) {
	$post_ID = get_post( $attachment_ID )->post_parent;
	$order = array_keys( ag_get_attachments( $post_ID ) );
	if ( count( $order ) > 0 ) {
		delete_post_meta( $post_ID, 'ag_attached_images' );
		add_post_meta( $post_ID, 'ag_attached_images', implode( ",", $order ) );
	}
}



/**
 * Show and remove images.
 *
 * @param $post int Post id.
 * @param $save bool Save accepted order or not.
 * @param $order string String with attachments ids "1,2,3,4,5".
 *
 */
function ag_save_and_show( $post, $save = false, $order = "" ) {
	$post = get_post( $post );
	if ( $save ) {
		// Validate $order. It must be a string with comma separated numbers: "1,20,3,40"
		if ( preg_match( "/(\d{1,},){0,}\d{1,}/", $order ) ) {
			global $wpdb;
			$order = explode( ",", $order );
			$attached = array_keys( ag_get_attachments( $post->ID ) );
			$detach = array_diff( $attached, $order );
			$attach = array_diff( $order, $attached );
			// detach
			foreach ( $detach as $key => $id ) {
				$wpdb->update( $wpdb->posts, array( 'post_parent' => 0 ), array( 'ID' => intval( $id ) ) );
			}
			// attach
			foreach ( $attach as $key => $id ) {
				$wpdb->update( $wpdb->posts, array( 'post_parent' => $post->ID ), array( 'ID' => intval( $id ) ) );
			}
			// save new order
			delete_post_meta( $post->ID, 'ag_attached_images' );
			add_post_meta( $post->ID, 'ag_attached_images', implode( ",", $order ) );
		}
	} else {
		// load order from post meta
		$post_meta = get_post_meta( $post->ID, 'ag_attached_images' );
		$ids_string = array_shift( $post_meta );
		// validate $ids_string format
		if ( preg_match( "/(\d{1,},){0,}\d{1,}/", $ids_string ) ) {
			$order = explode( ",", $ids_string );
		} else {
			$order = array();
		}
	}	
	// show //
	$id_order = $order;
	$attachments = ag_get_attachments( $post->ID );
	// sort attachments in user specified order
	if ( count( $order ) > 0 ):
	foreach ( $order as $position => $attachment_id ) {
		if ( array_key_exists( $attachment_id, $attachments ) ) {
			$order[ $position ] = $attachments[ $attachment_id ];
		}
	}
	?>
	<ul id="ag-sortable" rel="<?php echo implode( ",", $id_order); ?>">
	<?php
	$count = 0;
	foreach ( $order as $key => $attachment ):
		$w = get_option( 'ag-admin-thumbnail-w' );
		$h = get_option( 'ag-admin-thumbnail-h' );
		$imagesrc = ag_image( $attachment->ID, $w, $h );
		?>
		<li class="item ui-state-default" id="<?php echo $attachment->ID; ?>" style="width:<?php echo $w + 3; ?>px;">
			<img src="<?php echo $imagesrc; ?>" />
			<a href="#" class="close"><?php _e( "Delete", "a-gallery" ); ?></a>
		</li>
		<?php
	endforeach;
	?>
	</ul>
	<?php endif; ?>
	<a href="#ag_detach_box" rel="<?php echo A_GALLERY_URL . "actions.php?ag_action=add&post_id={$post->ID}&order=" . implode( ",", $id_order ); ?>" class="button" id="ag-add-images"><?php _e( 'Add from library', 'a-gallery' ); ?></a>	
	<a href="#ag_detach_box" rel="<?php echo A_GALLERY_URL . "actions.php?ag_action=save&post_id={$post->ID}&order="; ?>" class="button" id="ag-save-state"><?php _e( 'Save', 'a-gallery' ); ?></a>
	<br /><br />
	<?php
}



/**
 * Attach images to post.
 *
 * @param $post int Post id.
 * @param $old string Attachments old order.
 *
 */
function ag_add( $post, $old ) {
	$post = get_post( $post );
	// Get all not attached images
	$args = array( 
		'post_type' => 'attachment',
		'numberposts' => -1,
		'post_status' => null,
		'post_parent' => 0 );
	$attachments = get_posts( $args );
	?>
	<a href="#ag_detach_box" rel="<?php echo A_GALLERY_URL . "actions.php?ag_action=save&post_id={$post->ID}&order="; ?>" class="button" id="ag-update-state"><?php _e( 'Save and update', 'a-gallery'); ?></a>
	<table id="ag-add" rel="<?php echo $old;?>">
	<?php foreach ( $attachments as $attachment ):
		$w = get_option( 'ag-admin-thumbnail-w' );
		$h = get_option( 'ag-admin-thumbnail-h' );
		$image_url = ag_image( $attachment->ID, $w, $h );
		$image_name = $attachment->post_title;
		$image_alt = $image_name;
		$image_date = $attachment->post_date;
		?>
		<tr id="ag-attachment-<?php echo $attachment->ID; ?>">
			<td class="ag-attachment-image">
				<img class="ag-library-image" src="<?php echo $image_url; ?>" alt="<?php echo $image_alt; ?>" />
			</td>
			<td class="ag-attachment-desription">
				<p><?php echo $image_name; ?></p>
				<p><?php _e( 'Uploaded:' ); ?>&nbsp;<?php echo $image_date; ?></p>
				<a class="button" id="<?php echo $attachment->ID; ?>" href="#ag_detach_box">+&nbsp;<?php _e( 'Add', 'a-gallery' ); ?></a>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
	<?php
}



/**
 * Display images box for sorting and detaching.
 *
 * @uses add_meta_box(), wp_nonce_field()
 */
function ag_add_detach_box() {
	global $post;
    add_meta_box(
    	'ag_detach_box',
    	__( 'Manage images', 'a-gallery' ),
    	'ag_detachbox_callback',
    	'post', 'advanced',
    	'default',
    	array( "post_id" => $post->ID )
    );
}



/**
 * Callback for add_meta_box().
 *
 * @param $post object Post object.
 * @param $metabox array Array with metabox id, title, callback, and args elements.
 *
 **/
function ag_detachbox_callback( $post, $metabox ) {
	$post_id = $metabox['args']['post_id'];
	ag_save_and_show( $post_id );
}



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
		'max_width' => get_option( 'ag-default-max-width' ),
		'max_height' => get_option( 'ag-default-max-height' ),
		'exclude' => ''
	), $attr) );
	
	$attachments = ag_get_attachments( $post_id );
	// exclude attachments
	$exclude = explode( ',', $exclude );
	foreach ( $exclude as $id ) {
		unset( $attachments[$id] );
	}
	// save sort order like in post edit page
	$post_meta = get_post_meta( $post->ID, 'ag_attached_images' );
	$ids_string = array_shift( $post_meta );
	$order = explode( ',', $ids_string );
	foreach ( $order as $position => $attachment_id ) {
		if ( array_key_exists( $attachment_id, $attachments ) ) {
			$order[ $position ] = $attachments[ $attachment_id ];
		}
	}
	
	if ( count( $order ) < $count ) $count = count( $order );
	$item_c_w = $item_w + $border * 2;
	$item_c_h = $item_h + $border * 2;
	$c_width = ( $item_c_w + 10 ) * $columns - 10;
	$s_width = ( $item_c_w + 10 ) * $count - 10;
	
	$html = "<div class='a-gallery' rel='{$post->ID}' style='width:{$c_width}px !important;height:{$item_c_h}px !important;'>\n";
	if ( $columns < $count ) $html .= "\t<div class='a-gallery-left' style='height:{$item_c_h}px !important;'></div>\n";
	$html .= "\t<div class='a-gallery-container' style='width:{$c_width}px !important;height:{$item_c_h}px !important;'>\n";
	if ( $columns < $count ) $html .= "\t\t<div class='slider' style='width:{$s_width}px !important;height:{$item_c_h}px !important;'>\n";
	
	$c = 0;
	foreach ( $order as $i ) {
		$imagelink = wp_get_attachment_url( $i->ID );
		$thumblink = ag_image( $i->ID, $item_w, $item_h );
		$c++;
		
		$last = '';
		if ( $c >= $count ) $last = "last";
		
		$html .= "
			<div class='image {$last}' style='width:{$item_c_w}px;height:{$item_c_h}px;'>
				<a class='fancybox' rel='{$post->ID}' href='{$imagelink}'>
					<img src='{$thumblink}' style='width:{$item_w}px;height:{$item_h}px;border:{$border}px solid {$border_color};' />
				</a>
			</div><!-- end of div.image -->\n";
		
		if ( $c >= $count ) break;
	}
	$html .= "\t\t</div><!-- end of div.slider -->\n";
	if ( $columns < $count ) $html .= "\t</div><!-- end of div.a-gallery-container -->\n";
	if ( $columns < $count ) $html .= "\t<div class='a-gallery-right' style='height:{$item_c_h}px !important; left:{$c_width}px;'></div>\n";
	$html .= "</div><!-- end of div.a-gallery -->\n";
	$max_right_count = $count - $columns;
	$item_c_w = $item_c_w + 10;
	$lightboxurl = A_GALLERY_URL;
	$html .= '<script type="text/javascript">
				<!--<![CDATA[-->
				jQuery(document).ready(function($) {
					$(".fancybox").fancybox({
						prevEffect	: "none",
						nextEffect	: "none",
						maxWidth    : "' . $max_width . '",
						maxHeight   : "' . $max_height . '",
						helpers	: {
							title	: {
								type: "outside"
							},
							overlay	: {
								opacity : 0.8,
								css : {
									"background-color" : "#000"
								}
							},
							thumbs	: {
								width	: 50,
								height	: 50
							}
						}
					});
					leftCount[ ' . $post->ID . ' ] = 0;
					rightCount[ ' . $post->ID . ' ] = ' . $max_right_count . ';
					itemCWidth[ ' . $post->ID . ' ] = ' . $item_c_w . ';
				});
				<!--]]>-->
				</script>';
	return $html;
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
				<tr valign="top">
					<th scope="row">
						<label for="ag-default-max-width"><?php _e( 'Default width of picture in gallery view.', 'a-gallery' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="ag-default-max-width" name="ag-default-max-width" value="<?php echo get_option( 'ag-default-max-width' ); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ag-default-max-height"><?php _e( 'Default height of picture in gallery view.', 'a-gallery' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="ag-default-max-height" name="ag-default-max-height" value="<?php echo get_option( 'ag-default-max-height' ); ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		<p><?php _e( "<sup>1</sup> — This values will be used when entered shortcode don't provides any attributes. For example, if post contains " . '"[a_gallery item_h="200"]"' . " then all values will be taken from here except item_h (thumbnail height).", 'a-gallery' ); ?></p>
		<br />
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="ag-admin-thumbnail-w,ag-admin-thumbnail-h,ag-default-count,ag-default-border,ag-default-border-color,ag-default-item-w,ag-default-item-h,ag-default-columns,ag-default-max-height,ag-default-max-width" />
		<input type="submit" id="submit" class="button-primary" name="update" value="<?php _e( 'Save changes', 'a-gallery' ); ?>" />
	</form>
	</div>
	<?php
}
?>