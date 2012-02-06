<?php
/*
Plugin Name: A. Gallery
Plugin URI: http://rabdano.ru/wordpress-plugins/a-gallery/
Description: A. Gallery allow you attach and detach images to post in one click. Also included shortcode that provides a beautiful gallery.
Version: 1.1
Author: Sevastyan Rabdano
Author URI: http://rabdano.ru/
License: GPLv2 or later
*/

define( 'A_GALLERY_URL', plugin_dir_url( __FILE__ ) );

// Load plugin functions file
require_once( 'functions.php' );

load_plugin_textdomain( 'a-gallery', false, dirname( plugin_basename( __FILE__ ) ) . '/l10n/' );

add_option( 'ag-admin-thumbnail-w', 125 );
add_option( 'ag-admin-thumbnail-h', 125 );
add_option( 'ag-default-count', 10 );
add_option( 'ag-default-border', 3 );
add_option( 'ag-default-border-color', '#ccc' );
add_option( 'ag-default-item-w', 100 );
add_option( 'ag-default-item-h', 100 );
add_option( 'ag-default-columns', 4 );
add_option( 'ag-default-max-width', 800 );
add_option( 'ag-default-max-height', 400 );

add_action( 'admin_print_scripts', 'ag_admin_head_inserts' );
add_action( 'add_attachment', 'ag_image_upload_handler' );
add_action( 'init', 'ag_head_inserts' );
add_action( 'admin_init', 'ag_add_detach_box', 1 );

add_shortcode( 'a_gallery', 'ag_shortcode' );
?>