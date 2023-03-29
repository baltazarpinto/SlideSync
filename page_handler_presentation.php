<?php
function page_handler_presentation()
{
    global $wpdb;
    // Set table name using the WordPress prefix.
    $table_name = $wpdb->prefix . 'presentation'; 

    // Initialize message and notice variables.
    $message = '';
    $notice = '';

    // Set default values for the presentation item.
    $default = array(
        'id' => 0,
        'name' => '',
        'type' => '',
        'url' => '',
    );

    // If an id is provided in the request, fetch the corresponding record.
    $item = $default;
    if (isset($_REQUEST['id'])) {
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
        if (!$item) {
            $item = $default;
            $notice = __( 'Record not found', 'slidesync');
        }
    }
    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php echo __( 'Presentation', 'slidesync' );?> playing at: <span class="current-time">0:00</span></h2>

        <?php
        // Display notice and message based on their values.
        $notice_css = (!empty($notice)) ? 'block' : 'none';
        $message_css = (!empty($message)) ? 'block' : 'none';
        ?>
        <div id="notice" class="error" style="display:<?php echo $notice_css;?>;"><p><?php echo $notice ?></p></div>
        <div id="message" class="updated" style="display:<?php echo $message_css;?>;"><p><?php echo $message ?></p></div>

        <form id="form-presentation" method="POST">
        
            <div class="presentation-container">
                <div class="list-of-images-div">
                    <div id="work-timers" class="work-timers" role="tablist">
                        <?php 
                        // Get presentation rows from the database and display them.
                        $table_name = $wpdb->prefix . 'presentation_row';
                        $query = $wpdb->prepare("
                            SELECT *
                            FROM $table_name
                            WHERE presentation_id = %d order by time
                        ", $item['id']);
                        $results = $wpdb->get_results($query);
                        $start_image = '';
                        if (!empty($results)) {
                            foreach ($results as $result) {
                                // Get the first image in the presentation.
                                if (strlen($start_image) == 0 && !empty($result->image)) {
                                    $image_attributes = wp_get_attachment_image_src($result->image, 'full');
                                    $start_image = $image_attributes[0];
                                }
                                
                                // Display the content of the presentation row.
                                echo __slidesync_displayContent($result->id, $result->image, $result->time);
                            }
                        }
                        // Set a default image if no image is available.
                        if (strlen($start_image) == 0) {
                            $start_image = plugin_dir_url( __FILE__ ) . 'images/no-image.png';    
                        }
                        ?>
                    </div>
                </div>

				<div class="main-content-div">
					<!-- Hidden inputs for form data -->
					<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__)) ?>"/>
					<input type="hidden" id="id" name="id" value="<?php echo $item['id'] ?>"/>
					<input type="hidden" name="action" value="__slidesync_saveData"/>

					<div id="post-body">
						<div id="post-body-content">
							<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
								<tbody>
									<!-- Presentation name input -->
									<tr class="form-field">
										<th valign="top" scope="row">
											<label for="name"><?php echo __( 'Name', 'slidesync' );?></label>
										</th>
										<td>
											<input id="name" name="name" type="text" value="<?php echo esc_attr($item['name'])?>" size="50" class="text" required>
										</td>
									</tr>
									<!-- Presentation type input -->
									<tr class="form-field">
										<th valign="top" scope="row">
											<label for="type"><?php echo __( 'Type', 'slidesync' );?></label>
										</th>
										<td>
											<select id="item-type" name="type">
												<option <?php if($item['type']=='direct') { echo 'selected ';}?>value="direct"><?php echo __( 'Direct', 'slidesync' );?></option>
												<option <?php if($item['type']=='youtube') { echo 'selected ';}?>value="youtube"><?php echo __( 'YouTube ', 'slidesync' );?></option>
											</select>
										</td>
									</tr>
									<!-- Presentation URL input -->
									<tr class="form-field">
										<th valign="top" scope="row">
											<label for="url"><?php echo __( 'Video URL', 'slidesync' );?></label>
										</th>
										<td>
											<input id="item-url" name="url" type="text" value="<?php echo esc_attr($item['url'])?>" size="200" class="text" required>
										</td>
									</tr>
								</tbody>
							</table>
							<!-- Add new row and save buttons -->
							<div class="button-holder">
								<input type="button" value="<?php echo __( 'Add new row', 'slidesync' );?>" id="add-new-row">
								<input type="button" value="<?php echo __( 'Save', 'slidesync' );?>" id="submit" disabled="disabled" class="button-primary" style="float: right;">
							</div>
							<!-- Work video container -->
							<div class="work-video">
								<div class="work-video-image image-container">
									<img src="<?php echo $start_image;?>">
								</div>
								<div id="work-video-play" class="work-video-play video-container"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>

	<style>
		/* Container for the presentation */
		.presentation-container {
			display: flex;
			flex-direction: row;
		}

		/* Container for the list of images */
		.list-of-images-div {
			width: 25%;
			display: flex;
			flex-direction: column;
			overflow-y: auto;
			border-right: 1px solid #ccc;
			padding-right: 20px;
			overflow: auto;
			max-height:80vh;
		}

		/* Container for the main content */
		.main-content-div {
			width: 75%;
			display: flex;
			flex-direction: column;
			padding-left: 20px;
		}

		/* Styling for the form table */
		.form-table {
			border-collapse: collapse;
		}

		.form-table th,
		.form-table td {
			padding: 10px;
			vertical-align: top;
		}

		.form-table label {
			font-weight: bold;
		}

		.group h3 {
			display: block;
			cursor: pointer;
			position: relative;
			margin: 2px 0 0 0;
			padding: 0.5em 0.5em 0.5em 0.7em;
			font-size: 100%;
		}
		.group .ui-accordion-content {
			padding: 1em 2.2em;
			border-top: 0;
			overflow: auto;
		}
		
		/* Styling for the button holder */
		.button-holder {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding-bottom: 10px;
		}

		/* Styling for slidesync preview */
		.slidesync_preview {
			height: 150px;
			width: 200px;
			background-size: contain;
			background-position: center;
			background-repeat: no-repeat;
			margin-bottom: 5px;
			border: 1px solid #ccc;
		}

		/* Styling for slidesync time */
		.slidesync_time {
			padding-top: 20px;
			padding-bottom: 20px;
		}

		/* Styling for title image and title time */
		.title-img,
		.title-time {
			padding-left: 20px;
			vertical-align: top;
		}

		/* Styling for work video container */
		.work-video {
			display: flex;
			flex-wrap: wrap;
			margin-top: 20px;
		}

		/* Styling for image container and video container */
		.image-container,
		.video-container {
			display: flex;
			justify-content: center;
			align-items: center;
			padding: 10px;
			box-sizing: border-box;
		}

		/* Styling for image container */
		.image-container {
			width: 60%;
		}
		.image-container img {
			width: 100%;
		}

		/* Styling for video container */
		.video-container {
			width: 40%;
		}
		.play-button { float: right;margin-left: 10px;cursor: pointer;}
 
	</style>
<script>
//https://www.youtube.com/watch?v=Vc0HOvhk4qM&ab_channel=D1stinct
//http://localhost/video.mp4

</script>
	
<script type="text/javascript">
var timePoints = [];
var currentPointIndex = -1;
var YoutubePlayer;
var HTML5Player;
var videoContainer = document.querySelector('.work-video-play');
var videoTypeSelect = document.getElementById('item-type');
var videoUrlInput = document.getElementById('item-url');
var unsavedChanges = false;

window.addEventListener('beforeunload', function(event) {
	// Check if there are unsaved changes on the page
	if (unsavedChanges) {
		// Display a warning message to the user
		event.preventDefault();
		event.returnValue = ''; // This is needed for some browsers
	}
});
function notsaved(status) {
	unsavedChanges = status;
	if (status) {
		jQuery('#submit').removeAttr('disabled');	
	} else {
		jQuery('#submit').attr('disabled','disabled');
	}
}
function initializeVideoPlayer() {
	var videoType = videoTypeSelect.value;
	if (videoType === 'youtube') {
		// Check if the script is already loaded
		if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
			try {
				var tag = document.createElement('script');
				tag.src = "https://www.youtube.com/iframe_api";
				var firstScriptTag = document.getElementsByTagName('script')[0];
				firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
			} catch (e) {
				console.error('Error loading YouTube IFrame Player API:', e);
			}
		} else {
			// If the script is already loaded, initialize the player immediately
			onYouTubeIframeAPIReady();
		}
		// Destroy the HTML5 video element if it exists
		if (HTML5Player) {
			HTML5Player.pause();
			videoContainer.removeChild(HTML5Player);
			HTML5Player = null;
		}
	} else {
		// If the selected type is not YouTube, destroy the player
		if (YoutubePlayer) {
			YoutubePlayer.destroy();
			YoutubePlayer = null;
		}
		// Create the HTML5 video element if it doesn't exist
		if (!HTML5Player) {
			HTML5Player = document.createElement('video');
			HTML5Player.setAttribute('controls', 'controls');
			//HTML5Player.setAttribute('width', '640');
			//HTML5Player.setAttribute('height', '360');
			videoContainer.appendChild(HTML5Player);
		}
		loadDirectVideo(document.getElementById('item-url').value);	
	}
}

// Event listener for the video type select element
videoTypeSelect.addEventListener('change', function() {
	notsaved(true);
	initializeVideoPlayer();
});
// Event listener for the video url input element
videoUrlInput.addEventListener('input', function() {
	notsaved(true);
	initializeVideoPlayer();
});

// Parse the YouTube video ID from a URL
function parseYouTubeVideoId(url) {
	var regex = /^.*(youtu\.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
	var match = url.match(regex);
	if (match && match[2].length === 11) {
		return match[2];
	} else {
		return null;
	}
}

// Load YouTube player when the API is ready
function onYouTubeIframeAPIReady() {
	// Parse video ID from URL
	var videoUrl = document.getElementById('item-url').value;
	var videoId = parseYouTubeVideoId(videoUrl);
	if (YoutubePlayer) {
		YoutubePlayer.destroy();
		YoutubePlayer = null;
	}
	try {
		YoutubePlayer = new YT.Player(videoContainer, {
			//height: '360',
			//width: '640',
			videoId: videoId,
			events: {
				'onReady': onPlayerReady,
				'onStateChange': onPlayerStateChange
			}
		});
	} catch (e) {
		console.error('Error creating YouTube player:', e);
	}
}

// Play video when ready
function onPlayerReady(event) {
	event.target.playVideo();
}

// Update image when state changes to playing
function onPlayerStateChange(event) {
	if (event.data === YT.PlayerState.PLAYING) {
		setInterval(updateDivContent, 1000);
	}
}

// Load the direct video and play it
function loadDirectVideo(videoUrl) {
	if (HTML5Player) {
		HTML5Player.src = videoUrl;
		HTML5Player.load();
		HTML5Player.play();
		// Add event listener for timeupdate event of the video element
		HTML5Player.addEventListener('timeupdate', updateDivContent);
	}
}

// Update the image according to the time  of the video
function updateDivContent() {
   var currentTime;
	if (YoutubePlayer) {
		currentTime = YoutubePlayer.getCurrentTime();
	} else if (HTML5Player) {
		currentTime = HTML5Player.currentTime;
	}
	var timeString = formatTime(currentTime);
	jQuery('.current-time').html(timeString);
	var point = getPointAtTime(timeString);
    if (point) {
		if (point.index !== currentPointIndex) {
			// Update the image in the div
			var imageUrl = point.image;
			jQuery('.work-video-image img').attr("src",imageUrl);
			currentPointIndex = point.index;
		}
	} else {
		if (timePoints.length>0) {
			// No matching time point found, check if current time is after last time point
			var lastPoint = timePoints[timePoints.length - 1];
			var lastPointTime = getTimeInSeconds(lastPoint.time);
			if (currentTime >= lastPointTime && currentPointIndex !== timePoints.length - 1) {
				// Update the image to the last time point's image
				var lastImageUrl = lastPoint.image;
				jQuery('.work-video-image img').attr("src",lastImageUrl);
				currentPointIndex = timePoints.length - 1;
			}
		}
	}
}


function formatTime(time) {
	var minutes = Math.floor(time / 60);
	var seconds = Math.floor(time % 60);
	return minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
}

function getPointAtTime(timeString) {
	var currentTimeSeconds = getTimeInSeconds(timeString);
    for (var i = 0; i < timePoints.length; i++) {
		var pointTime = getTimeInSeconds(timePoints[i].time);
		if (currentTimeSeconds < pointTime) {
			if (i === 0) {
				return null;
			} else {
				return { index: i - 1, image: timePoints[i - 1].image };
			}
		}
	}
	return null;
}

function getTimeInSeconds(timeString) {
	var timeParts = timeString.split(':');
	var minutes = parseInt(timeParts[0]);
	var seconds = parseInt(timeParts[1]);
	return minutes * 60 + seconds;
}
jQuery(document).ready( function($) {
	initializeVideoPlayer();
})
</script>
<script type="text/javascript">


jQuery(document).ready( function($) {
	 $( '.work-timers' ).accordion({
		header: '> div > h3',
		collapsible:true,
		active: false,
		heightStyle: 'content'

	})
	
	 function sortGroups() {
	 
		// Get the index of the currently active panel
		var activeIndex = $('#work-timers .ui-accordion-header').index($('#work-timers .ui-state-active'));

        // Destroy the current accordion
        $('#work-timers').accordion('destroy');

        var $groups = $('#work-timers .group');
        $groups.sort(function (a, b) {
            var timeA = $(a).find('.time-edit').val();
            var timeB = $(b).find('.time-edit').val();
            return timeA.localeCompare(timeB);
        });

        // Append sorted groups and recreate the accordion
        $('#work-timers').empty().append($groups);
        $( '.work-timers' ).accordion({
			header: '> div > h3',
			collapsible:true,
			active: activeIndex,
			heightStyle: 'content'

		})
    }

	// Sort groups when a time-edit input loses focus
    $('#work-timers').on('blur', '.time-edit', function () {
        sortGroups();
		RefreshRowEdit();
    });
	
	
	
	jQuery(document).on('change ,  keyup' , '#name' ,function(){notsaved(true);});
		
	
	$('#submit').click(function(ev) {
		var postdata=$("#form-presentation").serialize();
		$.post(ajaxurl, postdata, function(response) {
			response=JSON.parse(response);
			if (response.success) {
				$('#id').val(response.id);
				var content_ids=response.content_ids;
				const inputFields = document.querySelectorAll('.work-timers .content_id'); 

				const valuesArray = content_ids.split(','); 
				  
				inputFields.forEach((input, index) => {
					input.value = valuesArray[index].trim();
				});
				notsaved(false);
			} else {
			}
		});
		ev.stopPropagation();
		ev.stopImmediatePropagation();
	});
	
	$('#add-new-row').click(function(e) {
		e.preventDefault();
		var data = { 'action': '__slidesync_addRow'};
		$.post(ajaxurl, data, function(response) {
			response=JSON.parse(response);
			if (response.success) {
				$('.work-timers').append(response.content);
				$('.work-timers').accordion('refresh');
				$('.work-timers').accordion( 'option', 'active', -1);
				notsaved(true);
				RefreshRowEdit();
			}
		});
	});
	
	function RefreshRowEdit() {
		
		$('.play-button').click(function(event) {
			event.preventDefault();
			event.stopImmediatePropagation();
			// Get the time and image URL from the clicked row
			var time = $(this).parent().parent().find('.time-edit').val();
			if (YoutubePlayer) {
				YoutubePlayer.seekTo(getTimeInSeconds(time), true);
			} else if (HTML5Player) {
				HTML5Player.currentTime = getTimeInSeconds(time);
			}
		});
	
		$('.edicao-elimina-conteudo').click(function(event) {
			event.stopPropagation();
			event.stopImmediatePropagation();
			if (confirm('<?php echo __( 'Are you sure you want to delete this item?', 'slidesync' );?>')) {
				notsaved(true);
				$(this).parents('div.group').remove();
				updTimePoints();
			}
		});	
		
		jQuery(document).on('change ,  keyup' , '.time-edit' ,function(){
			notsaved(true);
			$(this).parent().parent().parent().children('h3').children('.title-time').html($(this).val());
			updTimePoints();
		});
		updTimePoints();
	}
	RefreshRowEdit();
	
	
	
	function updTimePoints() {
		// Get the container div
		const timePointsContainer = document.getElementById('work-timers');

		// Get all the timepoint divs
		const timePointDivs = timePointsContainer.querySelectorAll('.group');

		// Create an empty array to store the updated timepoints
		const updatedTimePoints = [];

		// Loop through each timepoint div
		timePointDivs.forEach((timePointDiv) => {
		  // Get the time and image values from the input elements
		  const timeInput = timePointDiv.querySelector('.time-edit');
		  const imageInput = timePointDiv.querySelector('.title-img img');
		  
		  
		  
		  const time = timeInput.value;
		  const image = imageInput.src;

		  // Create a new timepoint object with the retrieved values
		  const timePoint = { time, image };

		  // Add the new timepoint object to the updatedTimePoints array
		  updatedTimePoints.push(timePoint);
		});

		// Replace the old timePoints array with the updatedTimePoints array
		timePoints = updatedTimePoints;
	}
	updTimePoints();
	
	


	$( 'body' ).on( 'click', '.upload_image_button', function( event ){
		notsaved(true);
		event.preventDefault(); // prevent default link click and page refresh
		
		const button = $(this)
		const imageId = button.parent().parent().find('.image_field').val();
		
		const custom_uploader = wp.media({
			//title: 'Insert image', // modal window title
			library : {
				// uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
				type : 'image'
			},
			multiple: false
		}).on( 'select', function() { // it also has "open" and "close" events
			const attachment = custom_uploader.state().get( 'selection' ).first().toJSON();
			objInputHolder=button.parent().find('.image_field');
			var if_src = objInputHolder.data('if_src');
			if (typeof if_src != 'undefined') {
				objInputHolder.data('if_src', attachment.url);				
			}
			
			
			objMdiaPreview=button.parent().parent().parent().parent().find('.slidesync_preview');
			objMdiaPreview.css("background-image", "url(" + attachment.url + ")");
			objMdiaPreview=button.parent().parent().parent().parent().find('.title-img img');
			objMdiaPreview.attr("src",attachment.url);

			objInputHolder.val(attachment.id).trigger('change');			
			
		})
		
		// already selected images
		custom_uploader.on( 'open', function() {

			if( imageId ) {
			  const selection = custom_uploader.state().get( 'selection' )
			  attachment = wp.media.attachment( imageId );
			  attachment.fetch();
			  selection.add( attachment ? [attachment] : [] );
			}
			
		})

		custom_uploader.open()
	
	});
	
	
	
});
</script>

<?php } 

add_action( 'wp_ajax___slidesync_addRow', '__slidesync_addRow' );
function __slidesync_addRow() {
	$content=__slidesync_displayContent(0,'','');
	echo json_encode(array('success' => true,'content'=>$content));
	wp_die();
}
add_action( 'wp_ajax___slidesync_saveData', '__slidesync_saveData' );
function __slidesync_saveData() {
	$postdata = $_POST;
	// Sanitize the data
	$id = sanitize_text_field( $postdata['id'] );
	$name = sanitize_text_field( $postdata['name'] );
	$type = sanitize_text_field( $postdata['type'] );
	$url = sanitize_text_field( $postdata['url'] );
	// Connect to the database
	global $wpdb;
	$table_name = $wpdb->prefix . 'presentation';

	if ( $id > 0 ) {
		// Update the existing data
		$wpdb->update(
			$table_name,
			array( 'name' => $name, 'type' => $type, 'url' => $url ),
			array( 'id' => $id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	} else {
		// Insert new data
		$wpdb->insert(
			$table_name,
			array( 'name' => $name, 'type' => $type, 'url' => $url ),
			array( '%s', '%s', '%s' )
		);
		$id = $wpdb->insert_id;
	}

	$content_ids = array();
	$table_name = $wpdb->prefix . 'presentation_row';

	foreach ( $_POST['content_id'] as $key => $content_id ) {
		$content_img = sanitize_text_field( $_POST['content_img'][ $key ] );
		$content_time = sanitize_text_field( $_POST['content_time'][ $key ] );

		if ( $content_id > 0 ) {
			// Update the existing data
			$wpdb->update(
				$table_name,
				array( 'image' => $content_img, 'time' => $content_time ),
				array( 'id' => $content_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		} else {
			// Insert new data
			$wpdb->insert(
				$table_name,
				array( 'presentation_id' => $id, 'image' => $content_img, 'time' => $content_time ),
				array( '%d', '%s', '%s' )
			);
			$content_id = $wpdb->insert_id;
		}
		$content_ids[] = $content_id;
	}

	// Delete rows not found in $_POST['content_id']
	if ( ! empty( $content_ids ) ) {
		$content_ids_string = implode( ',', $content_ids );
		$delete_query = "DELETE FROM $table_name WHERE id NOT IN ($content_ids_string)";
		$wpdb->query( $delete_query );
	}

	echo json_encode( array( 'success' => true, 'id' => $id, 'content_ids' => implode( ',', $content_ids )));
	wp_die();
}

function __slidesync_displayContent($id,$image,$time) {
    $default_image = plugin_dir_url( __FILE__ ).'images/no-image.png';

    if ( !empty( $image ) ) {
        $image_attributes = wp_get_attachment_image_src( $image,'full' );
        $src = $image_attributes[0];
    } else {
        $src = $default_image;
    }
	$retval='<div class="group">
		<h3 role="tab"><span class="title-img"><img src="'.$src.'" height="50"></span><span class="title-time">'.$time.'</span></h3>
		<div>
			<input class="content_id" type="hidden" name="content_id[]" value="'.$id.'">
			<div class="slidesync_mediaview">
				<div class="slidesync_preview" style="background-image: url('.$src.')"></div>
				<div class="actions">
					<input type="hidden" name="content_img[]" class="image_field" value="'.$image.'">
					<button type="button" class="upload_image_button button upload-button upload-custom-img">'.__( 'Change image', 'slidesync' ).'</button>
				</div>
			</div>
			<div class="slidesync_time"><input type="time" class="text time-edit" name="content_time[]" value="'.$time.'"></div>
			<div class="slidesync_option"><hr><button type="button" class="left button button-info button-small edicao-elimina-conteudo">'.__( 'Remove', 'slidesync' ).'</button><button class="play-button"><span class="dashicons dashicons-controls-play"></span></button></div>
		</div>
	</div>';
	return $retval;
}
?>