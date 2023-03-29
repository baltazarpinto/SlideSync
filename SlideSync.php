<?php
/*
Plugin Name: SlideSync
Description: A plugin to adjust a set of images in synch with a video
Author: Baltazar
Version: 1.0
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
		var SS_".$id."_videoType = '".esc_attr($item['type'])."';
		var SS_".$id."_videoUrl = '".esc_attr($item['url'])."';
		var SS_".$id."_videoID = '$video_id';
		var SS_".$id."_YoutubePlayer;
		var SS_".$id."_HTML5Player;

		if (SS_".$id."_videoType === 'youtube') {
			// Check if the script is already loaded
			if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
				try {
					var tag = document.createElement('script');
					tag.src = 'https://www.youtube.com/iframe_api';
					var firstScriptTag = document.getElementsByTagName('script')[0];
					firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
				} catch (e) {
					console.error('Error loading YouTube IFrame Player API:', e);
				}
			}
		}
			

		
	window.addEventListener('load', function() {
		var SS_".$id."_timePoints = [$timepoints];
		var SS_".$id."_currentPointIndex = -1;
		
		var SS_".$id."_imageContainer = document.querySelector('.SS_".$id."_work-video-image');
		var SS_".$id."_videoContainer = document.querySelector('.SS_".$id."_work-video-play');

		var SS_".$id."_videoContainer_offsetWidth=SS_".$id."_videoContainer.offsetWidth;
		
		function SS_".$id."_initializeVideoPlayer() {
			if (SS_".$id."_videoType === 'youtube') {
				// Check if the script is already loaded
				if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
					try {
						var tag = document.createElement('script');
						tag.src = 'https://www.youtube.com/iframe_api';
						var firstScriptTag = document.getElementsByTagName('script')[0];
						firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
					} catch (e) {
						console.error('Error loading YouTube IFrame Player API:', e);
					}
				} else {
					// If the script is already loaded, initialize the player immediately
					SS_".$id."_onYouTubeIframeAPIReady();
				}
				// Destroy the HTML5 video element if it exists
				if (SS_".$id."_HTML5Player) {
					SS_".$id."_HTML5Player.pause();
					SS_".$id."_videoContainer.removeChild(SS_".$id."_HTML5Player);
					SS_".$id."_HTML5Player = null;
				}
			} else {
				// If the selected type is not YouTube, destroy the player
				if (SS_".$id."_YoutubePlayer) {
					SS_".$id."_YoutubePlayer.destroy();
					SS_".$id."_YoutubePlayer = null;
				}
				// Create the HTML5 video element if it doesn't exist
				if (!SS_".$id."_HTML5Player) {
					SS_".$id."_HTML5Player = document.createElement('video');
					SS_".$id."_HTML5Player.setAttribute('controls', 'controls');
					SS_".$id."_HTML5Player.setAttribute('width', '100%');
					//HTML5Player.setAttribute('height', '360');
					SS_".$id."_videoContainer.appendChild(SS_".$id."_HTML5Player);
				}
				SS_".$id."_loadDirectVideo(SS_".$id."_videoUrl);	
			}
		}

		// Load YouTube player when the API is ready
		function SS_".$id."_onYouTubeIframeAPIReady() {
			// Parse video ID from URL
			if (SS_".$id."_YoutubePlayer) {
				SS_".$id."_YoutubePlayer.destroy();
				SS_".$id."_YoutubePlayer = null;
			}
			try {
				SS_".$id."_YoutubePlayer = new YT.Player(SS_".$id."_videoContainer, {
					//height: '360',
					//width: '100%',
					
					videoId: SS_".$id."_videoID,
					 playerVars: {
					  'autoplay': 1,
					  'controls': 1,
					  'showinfo': 0,
					  'rel': 0,
					  'modestbranding': 1
					},
					events: {
						'onReady': SS_".$id."_onPlayerReady,
						'onStateChange': SS_".$id."_onPlayerStateChange
					}
				});
			} catch (e) {
				console.error('Error creating YouTube player:', e);
			}
		}
		// Play video when ready
		function SS_".$id."_onPlayerReady(event) {

			 // Get the dimensions of the video
			var videoEmbedCode = SS_".$id."_YoutubePlayer.getVideoEmbedCode();
			var videoWidth = videoEmbedCode.match(/width=\"(\d+)\"/)[1];
			var videoHeight = videoEmbedCode.match(/height=\"(\d+)\"/)[1];
  
			// Calculate the aspect ratio
			var aspectRatio = videoHeight / videoWidth;

			// Get the dimensions of the player container
			var containerWidth = SS_".$id."_videoContainer_offsetWidth;

			// Set the width of the player to 100% of the container and the height based on the aspect ratio
			SS_".$id."_YoutubePlayer.setSize(containerWidth, containerWidth * aspectRatio);
			event.target.playVideo();
		}

		// Update image when state changes to playing
		function SS_".$id."_onPlayerStateChange(event) {
			var playerState = event.data;
			if (playerState==1) {
				setInterval(SS_".$id."_updateDivContent, 1000);
			}
		}

		// Load the direct video and play it
		function SS_".$id."_loadDirectVideo(videoUrl) {
			if (SS_".$id."_HTML5Player) {
				SS_".$id."_HTML5Player.src = videoUrl;
				SS_".$id."_HTML5Player.load();
				SS_".$id."_HTML5Player.play();
				// Add event listener for timeupdate event of the video element
				SS_".$id."_HTML5Player.addEventListener('timeupdate', SS_".$id."_updateDivContent);
			}
		}

		// Update the image according to the time  of the video
		function SS_".$id."_updateDivContent() {
			var currentTime;
			if (SS_".$id."_YoutubePlayer) {
				currentTime = SS_".$id."_YoutubePlayer.getCurrentTime();
			} else if (SS_".$id."_HTML5Player) {
				currentTime = SS_".$id."_HTML5Player.currentTime;
			}
			var timeString = SS_".$id."_formatTime(currentTime);
			var point = SS_".$id."_getPointAtTime(timeString);
			if (point) {
				if (point.index !== SS_".$id."_currentPointIndex) {
					// Update the image in the div
					var imageUrl = point.image;
					img = SS_".$id."_imageContainer.querySelector('img');
					img.src = imageUrl;
					SS_".$id."_currentPointIndex = point.index;
				}
			} else {
				if (SS_".$id."_timePoints.length>0) {
					// No matching time point found, check if current time is after last time point
					var lastPoint = SS_".$id."_timePoints[SS_".$id."_timePoints.length - 1];
					var lastPointTime = SS_".$id."_getTimeInSeconds(lastPoint.time);
					if (currentTime >= lastPointTime && SS_".$id."_currentPointIndex !== SS_".$id."_timePoints.length - 1) {
						// Update the image to the last time point's image
						var lastImageUrl = lastPoint.image;
						img = SS_".$id."_imageContainer.querySelector('img');
						img.src = lastImageUrl;
						SS_".$id."_currentPointIndex = SS_".$id."_timePoints.length - 1;
					}
				}
			}
		}

		function SS_".$id."_formatTime(time) {
			var minutes = Math.floor(time / 60);
			var seconds = Math.floor(time % 60);
			return minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
		}

		function SS_".$id."_getPointAtTime(timeString) {
			var currentTimeSeconds = SS_".$id."_getTimeInSeconds(timeString);
			for (var i = 0; i < SS_".$id."_timePoints.length; i++) {
				var pointTime = SS_".$id."_getTimeInSeconds(SS_".$id."_timePoints[i].time);
				if (currentTimeSeconds < pointTime) {
					if (i === 0) {
						return null;
					} else {
						return { index: i - 1, image: SS_".$id."_timePoints[i - 1].image };
					}
				}
			}
			return null;
		}

		function SS_".$id."_getTimeInSeconds(timeString) {
			var timeParts = timeString.split(':');
			var minutes = parseInt(timeParts[0]);
			var seconds = parseInt(timeParts[1]);
			return minutes * 60 + seconds;
		}
		
			SS_".$id."_initializeVideoPlayer();
		})
	</script>";
}

function SlideSync_video($atts) {
$id=$atts['id'];
return "<div class='SS_".$id."_work-video-play' style='width:100%;'></div>";
}
add_shortcode( 'SlideSync_display', 'SlideSync_display' );
add_shortcode( 'SlideSync_video', 'SlideSync_video' );