    // plays audio + Links to for each above ^^
    $(document).ready(function() {

        var audio = null;
        var currentFile = null;
        // var playlist = <?php echo get_play_queue(); ?>;
        var currentCount = 0;

        $('.play-media').on('click', function() {
            var el = $(this);
            var filename = el.attr('data-file');
            var count = el.attr('data-file')
            currentCount = parseInt(count);
            
            if(audio && currentFile === filename) {
                audio.currentFile = 0;
                audio.play();
            }else {
                if(audio) {
                    audio.pause();
                }
                audio = new Audio(filename);
                currentFile = filename;
                audio.play();
            }
            return false;

        });

        // Pauses audio + Selects audio button below the form
        $('#pause-button').on('click', function() {
            if(audio) {
                audio.pause();
            }
            return false;
        });

        // Plays audio from the start of the track
        $('#from-start').on('click', function() {
            if(audio) {
                audio.currentTime = 0;
                audio.play();
            }
            return false;
        })

        // Skips to the next song in the playlist (BROKEN)
        $('#next-button').on('click', function() {
            if(currentCount < playlist.length) {
                if(audio) {
                    audio.pause();
                }
                var index = currentCount +1;
                audio = new Audio(playlist[index]);
                audio.play();
                currentCount++;
            }
            return false;
        })

    });