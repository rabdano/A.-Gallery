<?php
/**
 * Handle actions with images.
 *
 * @since 1.1
 *
 */

require_once('../../../wp-admin/admin.php');
require_once('functions.php');

$ag_action = $_REQUEST['ag_action'];
$post_id = $_REQUEST['post_id'];
$order = $_REQUEST['order'];

if ( $ag_action == "add" ) {
	ag_add( $post_id, $order );
}
if ( $ag_action == "save" ) {
	ag_save_and_show( $post_id, true, $order );
}
?>