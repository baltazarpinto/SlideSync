function SlideSinc_playVideo(videoType, videoUrl, timePoints, imageContainerSelector, videoContainerSelector) {
	var videoID = '';
	var youtubePlayer;
	var html5Player;

	if (videoType === 'youtube') {
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
		var currentPointIndex = -1;

		var imageContainerElement = document.querySelector(imageContainerSelector);
		var videoContainerElement = document.querySelector(videoContainerSelector);

		var videoContainerOffsetWidth=videoContainerElement.offsetWidth;

		function initializeVideoPlayer() {
			if (videoType === 'youtube') {
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
					onYouTubeIframeAPIReady();
				}
				// Destroy the HTML5 video element if it exists
				if (html5Player) {
					html5Player.pause();
					videoContainerElement.removeChild(html5Player);
					html5Player = null;
				}
			} else {
				// If the selected type is not YouTube, destroy the player
				if (youtubePlayer) {
					youtubePlayer.destroy();
					youtubePlayer = null;
				}
				// Create the HTML5 video element if it doesn't exist
				if (!html5Player) {
					html5Player = document.createElement('video');
					html5Player.setAttribute('controls', 'controls');
					html5Player.setAttribute('width', '100%');
					//HTML5Player.setAttribute('height', '360');
					videoContainerElement.appendChild(html5Player);
				}
				loadDirectVideo(videoUrl);    
			}
		}

		// Load YouTube player when the API is ready
		function onYouTubeIframeAPIReady() {
			// Parse video ID from URL
			var videoID = parseYouTubeVideoId(videoUrl);
			if (youtubePlayer) {
				youtubePlayer.destroy();
				youtubePlayer = null;
			}
			try {			
				youtubePlayer = new YT.Player(videoContainerElement, {
					//height: '360',
					//width: '100%',
					videoId: videoID,
					 playerVars: {
					  'autoplay': 1,
					  'controls': 1,
					  'showinfo': 0,
					  'rel': 0,
					  'modestbranding': 1
					},
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

			// Get the dimensions of the video
			var videoEmbedCode = youtubePlayer.getVideoEmbedCode();
			var videoWidth = videoEmbedCode.match(/width="(\d+)"/)[1];
			var videoHeight = videoEmbedCode.match(/height="(\d+)"/)[1];
			            // Calculate the aspect ratio
            var aspectRatio = videoHeight / videoWidth;

            // Get the dimensions of the player container
            var containerWidth = videoContainerOffsetWidth;

            // Set the width of the player to 100% of the container and the height based on the aspect ratio
            youtubePlayer.setSize(containerWidth, containerWidth * aspectRatio);
            event.target.playVideo();
        }

        // Update image when state changes to playing
        function onPlayerStateChange(event) {
            var playerState = event.data;
            if (playerState==1) {
                setInterval(updateDivContent, 1000);
            }
        }

        // Load the direct video and play it
        function loadDirectVideo(videoUrl) {
            if (html5Player) {
                html5Player.src = videoUrl;
                html5Player.load();
                html5Player.play();
                // Add event listener for timeupdate event of the video element
                html5Player.addEventListener('timeupdate', updateDivContent);
            }
        }

        // Update the image according to the time  of the video
        function updateDivContent() {
            var currentTime;
            if (youtubePlayer) {
                currentTime = youtubePlayer.getCurrentTime();
            } else if (html5Player) {
                currentTime = html5Player.currentTime;
            }
            var timeString = formatTime(currentTime);
            var point = getPointAtTime(timeString);
            if (point) {
                if (point.index !== currentPointIndex) {
                    // Update the image in the div
                    var imageUrl = point.image;
                    var img = imageContainerElement.querySelector('img');
                    img.src = imageUrl;
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
                        var img = imageContainerElement.querySelector('img');
                        img.src = lastImageUrl;
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


        initializeVideoPlayer();
    })
}