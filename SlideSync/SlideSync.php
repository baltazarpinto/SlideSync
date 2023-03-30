<?php
/*
Plugin Name: SlideSync
Plugin URI: https://github.com/baltazarpinto/SlideSync
Description: SlideSync is a WordPress plugin designed to enhance the experience of online presentations by synchronizing images with the corresponding video.
Version: 1.0
Author: Baltazar Pinto
Author URI: https://github.com/baltazarpinto
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: slidesync
*/
global $slidesync_ver;
$slidesync_ver = '2.9';
slidesync_dbInstallUpgrade();
// CReate menu options
add_action('admin_menu', 'slidesync_admin_menu');
function slidesync_admin_menu()
{
    add_menu_page('SlideSync', 'SlideSync', 'manage_options', 'slidesync-presentations', 'table_handler_presentations', 'dashicons-media-interactive');
	add_submenu_page('slidesync-presentations', __( 'Presentations', 'slidesync' ), __( 'Presentations', 'slidesync' ), 'manage_options', 'slidesync-presentations', 'table_handler_presentations');
	add_submenu_page('slidesync-presentations', __( 'Presentation', 'slidesync' ), __( 'Presentation', 'slidesync' ), 'manage_options', 'slidesync-presentation', 'page_handler_presentation');
}

include plugin_dir_path( __FILE__ ) . "table_handler_presentations.php";
include plugin_dir_path( __FILE__ ) . "page_handler_presentation.php";
// Set up the database tables to handle presentations and synch times
function slidesync_dbInstallUpgrade() {
	register_activation_hook(__FILE__, 'slidesync_install');
	function slidesync_install()
	{
		global $wpdb, $slidesync_ver;		
		$installed_ver = get_option('slidesync_ver');
		if ($installed_ver != $slidesync_ver) {
			$charset_collate = $wpdb->get_charset_collate();
		
			$table_name = $wpdb->prefix . 'presentation'; 
			$sql = "CREATE TABLE " . $table_name . " (
				id int(11) NOT NULL AUTO_INCREMENT,
				name tinytext NOT NULL,
				type tinytext NOT NULL,
				url varchar(200) NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
			
			$table_name = $wpdb->prefix . 'presentation_row'; 
			$sql = "CREATE TABLE " . $table_name . " (
				id int(11) NOT NULL AUTO_INCREMENT,
				presentation_id int(11) NOT NULL,
				image tinytext NOT NULL,
				time tinytext NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
			
			update_option('slidesync_ver', $slidesync_ver);
		}
	}
	function slidesync_ver_update_db_check()
	{
		global $slidesync_ver;
		if (get_site_option('slidesync_ver') != $slidesync_ver) {
			slidesync_install();
		}
	}
	add_action('plugins_loaded', 'slidesync_ver_update_db_check');
}
function slidesync_frontend_enqueue_scripts() {
  wp_enqueue_script( 'slidesinc', plugin_dir_url( __FILE__ )  . 'js/SlideSinc.js', array(), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'slidesync_frontend_enqueue_scripts' );

function slidesync_admin_enqueue_scripts() {
	if ( ! did_action( 'wp_enqueue_media' ) ) { wp_enqueue_media();	}
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('jquery-ui-accordion');
	$wp_scripts = wp_scripts();
	wp_enqueue_style('plugin_name-admin-ui-css',
	'https://ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css',
	false,
	$wp_scripts->registered['jquery-ui-core']->ver,
	false);
}
add_action( 'admin_enqueue_scripts', 'slidesync_admin_enqueue_scripts' );

function SlideSync_display($atts) {
	global $wpdb;
	$id=$atts['id'];
	$table_name = $wpdb->prefix . 'presentation'; 
	$item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
	$table_name = $wpdb->prefix . 'presentation_row';
	$query = $wpdb->prepare("
		SELECT *
		FROM $table_name
		WHERE presentation_id = %d order by time
	", $id);
	$results = $wpdb->get_results($query);
	$start_image = '';
	$timepoints="";
	if (!empty($results)) {
		foreach ($results as $result) {
			// Get the first image in the presentation.
			if (strlen($start_image) == 0 && !empty($result->image)) {
				$image_attributes = wp_get_attachment_image_src($result->image, 'full');
				$start_image = $image_attributes[0];
			}
			$image_attributes = wp_get_attachment_image_src($result->image, 'full');
			$image = $image_attributes[0];
			if(strlen($timepoints)>0) {$timepoints=$timepoints.", ";}
			$timepoints=$timepoints."{time: '".$result->time."', image: '".$image."'}";
		}
	}
	// Set a default image if no image is available.
	if (strlen($start_image) == 0) {
		$start_image = plugin_dir_url( __FILE__ ) . 'images/no-image.png';    
	}
	$video_id=0;
	if ($item['type']=='youtube') {
		$pattern = '/[\\?\\&]v=([^\\?\\&]+)/';
		preg_match($pattern,$item['url'], $matches);
		$video_id = $matches[1];
	}

	return "
	<div class='SS_".$id."_work-video-image'>
		<img src='$start_image' style='width: 100%;height: auto;'>
	</div>
	<script type='text/javascript'>
		document.addEventListener('DOMContentLoaded', () => {
			var timepoints=[$timepoints];
			SlideSinc_playVideo('".esc_attr($item['type'])."', '".esc_attr($item['url'])."',  timepoints, '.SS_".$id."_work-video-image', '.SS_".$id."_work-video-play');
		});
	</script>";
}

function SlideSync_video($atts) {
$id=$atts['id'];
return "<div class='SS_".$id."_work-video-play' style='width:100%;'></div>";
}
add_shortcode( 'SlideSync_display', 'SlideSync_display' );
add_shortcode( 'SlideSync_video', 'SlideSync_video' );