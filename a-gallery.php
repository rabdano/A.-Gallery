<?php
/*
Plugin Name: A. Gallery
Plugin URI: http://rabdano.ru/wordpress-plugins/
Description: 
Version: 0.1
Author: Sevastyan Rabdano
Author URI: http://rabdano.ru/
License: GPLv2 or later
*/

define( 'A_GALLERY_URL', plugin_dir_url( __FILE__ ) );
define( 'ACT_FILENAME', A_GALLERY_URL . 'actions.php' );

/**
 * Удаляет файл из списка привязянных к посту.
 *
 * @since 0.1
 * @uses $wpdb, get_posts()
 *
 * @param int $post_id Post ID.
 * @param string $att_value Relative path to the attached file.
 */
function ag_detach_image( $post_id, $att_value ) {
	global $wpdb;
	if ( !empty( $post_id ) ) {
		$args = 'post_parent='.$post_id.'&post_type=attachment&guid='.$att_value.'&';
		$attachments = get_posts( $args );
		$attachment = array_shift( $attachments );
		$wpdb->update( $wpdb->posts, array( 'post_parent' => '' ), array( 'ID' => $attachment->ID ) );
	}
}

/**
 * Получает картинки, привязанные к посту и возваращает их.
 *
 * @since 0.1
 * @uses get_children(), get_posts()
 *
 * @param $post_id Post ID.
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
 * Добавляет страницу настройки плагина.
 *
 * @since 0.1
 *
 */
function ag_add_options_page() {	
	add_options_page( 'A. Gallery options', 'A. Gallery', 8, basename(__FILE__), 'ag_display_options' );
	add_option( 'ag-admin-thumbnail-w', 125 );
	add_option( 'ag-admin-thumbnail-h', 125 );
}
add_action( 'admin_menu', 'ag_add_options_page' );

/**
 * Выводит страницу настройки плагина.
 *
 * @since 0.1
 *
 */
function ag_display_options() {
	?>
	<div class="wrap">
	<h2>Настройки A-gallery</h2>
	<form method="post" action="options.php">
		<?php wp_nonce_field('update-options'); ?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="ag-admin-thumbnail-w">Ширина миниатюры картинки на страницах консоли.</label>
					</th>
					<td>
						<input type="text" class="regular-text" id="ag-admin-thumbnail-w" name="ag-admin-thumbnail-w" value="<?php echo get_option('ag-admin-thumbnail-w') ?>" size="5" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ag-admin-thumbnail-h">Высота миниатюры картинки на страницах консоли.</label>
					</th>
					<td>
						<input type="text" class="regular-text" id="ag-admin-thumbnail-h" name="ag-admin-thumbnail-h" value="<?php echo get_option('ag-admin-thumbnail-h') ?>" size="5" />
					</td>
				</tr>
			</tbody>
		</table>
		<br />
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="ag-admin-thumbnail-w,ag-admin-thumbnail-h" />
		<input type="submit" id="submit" class="button-primary" name="update" value="Сохранить изменения" />
	</form>
	</div>
	<?php
}

/**
 * Выводит ссылку для прикрепления картинки к посту.
 *
 * @since 0.1
 *
 */
function ag_add_fields_to_edit( $form_fields, $post ) {
	$request_url = ACT_FILENAME . "?ag_aid=$post->ID&ag_pid=" . $_REQUEST['post_id'];
	if ( $post->post_parent == 0 ) {
		$form_fields['_final'] = '<div style="width:100%;text-align:center;"><a href="' . $request_url . '">Attach to post</a>';
	}
	return $form_fields;
}
add_filter('attachment_fields_to_edit', 'ag_add_fields_to_edit', 10, 2);

/**
 * Выводит картинки для разъединения с постом.
 *
 * @uses add_meta_box(), wp_nonce_field()
 *
 * @since 0.1
 *
 */
function ag_add_detach_box() {
    add_meta_box( 'ag_detach_box', 'Detach images', 'ag_inner_custom_box', 'post' );
    
    // Выводит содержимое контейнера
	function ag_inner_custom_box() {
		global $post;
		wp_nonce_field( plugin_basename( __FILE__ ), 'ag_nname' );
		$attachments = ag_get_attachments( $post->ID );
		
		// Добавим сохранение сортировки, заданной в админке
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
		ksort($attachments);
		
		?>
		<ul id="ag-sortable">
		<?php
		$count = 0;
		foreach ( $attachments as $key => $attachment ):
			$imagelink = wp_get_attachment_url( $attachment->ID );
			$w = get_option( 'ag-admin-thumbnail-w' );
			$h = get_option( 'ag-admin-thumbnail-h' );
			?>
			<li class="item ui-state-default">
				<a class="close">x</a>
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
add_action('admin_init', 'ag_add_detach_box', 1);

/**
 * Разъединяет картинки с постом.
 *
 * @since 0.1
 *
 */
function ag_save_postdata( $post_id ) {
	// Проверка безопасности
	if ( !wp_verify_nonce( $_REQUEST['ag_nname'], plugin_basename(__FILE__) ) ) return $post_id;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
	if ( 'page' == $_REQUEST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) ) return $post_id;
	} else {
		if ( !current_user_can( 'edit_post', $post_id ) ) return $post_id;
	}
	// Проверка на подлинность поста
	$post_id = wp_is_post_revision( $post_id );
	
	// Сохранение оставшихся картинок
	$ag_images_to_save = $_REQUEST['ag_images_to_save'];
	
	$old_meta = get_post_meta( $post_id, 'ag_post_images' );
	delete_post_meta( $post_id, 'ag_post_images', $old_meta[0] );
	add_post_meta( $post_id, 'ag_post_images', $ag_images_to_save, true );
	
	// Убираем лишние картинки
	$attachments = ag_get_attachments( $post_id );
	foreach ( $attachments as $attachment ) {
		$image = wp_get_attachment_url( $attachment->ID );
		if ( !in_array( $image, $ag_images_to_save ) ) {
			ag_detach_image( $post_id, $image );
		}
	}
}
add_action('save_post', 'ag_save_postdata');

function ag_shortcode( $attr ) {
	global $post;
	
	$attr = apply_filters( 'post_a_gallery', $attr );
	
	extract(shortcode_atts(array(
		'post_id' => $post->ID,
		'count' => 10,
		'border' => 3,
		'border_color' => '#ccc',
		'item_w' => 100,
		'item_h' => 100,
		'columns' => 4,
		'height' => 125,
		'exclude' => ''
	), $attr));
	
	$r = ag_get_attachments( $post_id );
	$exclude = explode( ',', $exclude );
	foreach ( $exclude as $ex ) {
		unset($r[$ex]);
	}
	
	// Добавим сохранение сортировки, заданной в админке
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
	ksort($r);
	
	if ( count( $r ) < $count ) $count = count( $r );
	$item_c_w = $item_w + $border * 2;
	$item_c_h = $item_h + $border * 2;
	$c_width = ( $item_c_w + 10 ) * $columns - 10;
	
	$html = "<div id='a-gallery' style='width:{$c_width}px !important;height:{$item_c_h}px !important;'>\n";
	if ( $columns < $count ) $html .= "\t<div id='a-gallery-left' style='height:{$item_c_h}px !important;'></div>\n";
	$html .= "\t<div id='a-gallery-container' style='width:{$c_width}px !important;height:{$item_c_h}px !important;'>\n";
	if ( $columns < $count ) $html .= "\t<div id='slider' style='width:{$c_width}px !important;height:{$item_c_h}px !important;'>\n";
	
	$c = 0;
	foreach ( $r as $i ) {
		$imagelink = wp_get_attachment_url( $i->ID );
		$thumblink = A_GALLERY_URL . 'timthumb.php?src=' . $imagelink . '&w=' . $item_w . '&h=' . $item_h . '&zc=1';
		$c++;
		
		$last = '';
		if ( $c >= $count ) $last = "last";
		
		$html .= "\t<div class='image {$last}' style='width:{$item_c_w}px;height:{$item_c_h}px;'>\n";
		$html .= "\t\t<a href='{$imagelink}'><img src='{$thumblink}' style='width:{$item_w}px;height:{$item_h}px;border:{$border}px solid {$border_color};' /></a>\n";
		$html .= "\t</div>\n";
		
		if ( $c >= $count ) break;
	}
	$html .= "\t</div>";
	if ( $columns < $count ) $html .= "\t</div>";
	if ( $columns < $count ) $html .= "\t<div id='a-gallery-right' style='height:{$item_c_h}px !important; left:{$c_width}px;'></div>\n";
	$html .= "</div>\n";
	$max_right_count = $count - $columns;
	$item_c_w = $item_c_w + 10;
	$lightboxurl = A_GALLERY_URL . 'jquery-lightbox/';
	$html .= "<script type='text/javascript'>
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
				</script>";
	
	return $html;
}
add_shortcode( 'a_gallery', 'ag_shortcode' );

/**
 * Добавляет javascript и css в <head> консоли.
 *
 * @since 0.1
 *
 */
function ag_admin_head_inserts() {
	wp_register_script( 'a-gallery-admin-js', A_GALLERY_URL . 'a-gallery-admin.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ) );
	wp_enqueue_script( 'a-gallery-admin-js' );
	wp_register_style('a-gallery-admin-css', A_GALLERY_URL . 'a-gallery-admin.css' );
	wp_enqueue_style( 'a-gallery-admin-css');
}
add_action('admin_print_scripts', 'ag_admin_head_inserts' );

/**
 * Добавляет javascript и css в <head>.
 *
 * @since 0.1
 *
 */
function ag_head_inserts() {
	wp_register_script( 'jquery-lightbox-js', A_GALLERY_URL . 'jquery-lightbox/js/jquery-lightbox.js' );
	wp_enqueue_script( 'jquery-lightbox-js' );
	wp_register_style( 'jquery-lightbox-css', A_GALLERY_URL . 'jquery-lightbox/css/jquery-lightbox.css' );
	wp_enqueue_style( 'jquery-lightbox-css');
	wp_register_script( 'a-gallery-js', A_GALLERY_URL . 'a-gallery.js' );
	wp_enqueue_script( 'a-gallery-js' );
	wp_register_style('a-gallery-css', A_GALLERY_URL . 'a-gallery.css' );
	wp_enqueue_style( 'a-gallery-css');
}
add_action('init', 'ag_head_inserts');


?>