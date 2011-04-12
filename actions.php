<?php
require_once('../../../wp-admin/admin.php');
global $wpdb;
/**
 * Обработка действий по ссылкам.
 *
 * @since 0.1
 *
 */

// Привязываем картинку к файлу
if ( !empty( $_GET['ag_pid'] ) && !empty( $_GET['ag_aid'] ) ) {
	$post_id = $_GET['ag_pid'];
	$ag_aid = $_GET['ag_aid'];
	$error = $wpdb->update( $wpdb->posts, array( 'post_parent' => $post_id ), array( 'ID' => $ag_aid ), array( '%d' ), array( '%d' ) );
	if ( $error == 1 ) {
		echo '<script type="text/javascript">parent.eval(\'tb_remove();location.reload(true);\')</script>';
	} else {
		echo '<h3 style="">An error was ocurred while processing your request.</h3>';
	}
}

?>