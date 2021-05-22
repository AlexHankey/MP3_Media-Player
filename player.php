<!-- SQL TO CREATE TABLES -->

<!-- MEDIA -->


<!-- MEDIA_PLAYLIST -->


<!-- MEDIA_PLAYLIST_FILES -->
<!-- CREATE TABLE `media-playlist_files` (
        `media` int(11) NOT NULL,
        `playlist` int(11) NOT NULL
    ) 
-->


<?php 

// Connection to the database
    function get_connection() {
        $dsn = "mysql:host=localhost;dbname=mp3phptest";
        $user = "root";
        $password = "";
        $conn = new PDO($dsn, $user, $password);
        return $conn;
    }

// During file upload. This function randomises the files name 
    function get_random_name($num=6) {
        $characters = 'abcdefghijklmnopqrstuvwxyz123456789';
        $string = '';
        $max = strlen($characters) -1;
        for ($i = 0; $i < $num; $i++) {
            $string .= $characters[mt_rand(0, $max)];
        }
        return $string;
    }

// Saves the files to the database and inserts it into a table
    function save_media($filename, $description) {
        $conn = get_connection();
        $sql = "INSERT INTO media(`file`, `description`) VALUES (?,?)";
        $query = $conn -> prepare($sql);
        $query->execute([$filename, $description]);
    }

// Saves songs to a playlist
    function save_playlist($name) {
        $conn = get_connection();
        $sql = "INSERT INTO media_playlist(`name`) VALUES (?)";
        $query = $conn -> prepare($sql);
        $query->execute([$name]);
    }

    function save_to_playlist($mediaId, $playlistId) {
        $conn = get_connection();
        $sql = "INSERT INTO media_playlist_files(`media`, `playlist`) VALUES (?,?)";
        $query = $conn->prepare($sql);
        $query->execute([$mediaId, $playlistId]);
    }

    function get_media() {
        $pl = isset($_GET['playlist']) ? $_GET['playlist'] : 'all';
        $results = [];
        try {
            $conn = get_connection();

            // Getting songs from selected playlist query 
            if($pl && $pl != "all") {
                $query = $conn->prepare("SELECT * from media 
                    WHERE id IN (SELECT media from media_playlist_files WHERE playlist=?)");
                $query->execute([$pl]);
                $results = $query->fetchAll();

            }else {
                $results = $conn->query("SELECT * from media");
            }

        }catch(Exception $e) {
            echo $e->getMessage();
        }
        return $results;
    }

    function get_playlists() {
        $results = [];
        try {
            $conn = get_connection();
            $results = $conn->query("SELECT * from media_playlist");
        }catch(Exception $e) {
            echo $e->getMessage();
        }
        return $results;
    }

    function get_play_queue() {
        $mediaFiles = get_media();
        $queue = [];
        foreach($mediaFiles as $media) {
            $queue[] = './uploads/' . $media['file'];
        }
        return json_encode($queue);
    }



// Handles the files POST request && does a check if the files exists
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save-media'])) {
        $uploadDir = "./uploads/";
        if(isset($_FILES['file']) && $_FILES['file']['error'] ==0) {

            $filename = $_FILES['file']['name'];
            $filetype = $_FILES['file']['type'];
            $filesize = $_FILES['file']['size'];
            $newFileName = get_random_name() . "." . pathinfo($filename, PATHINFO_EXTENSION);

            if(file_exists($uploadDir . $newFileName)) {
                echo $filename . 'Already exists';
            }else {
                move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $newFileName);
                save_media($newFileName, $filename);
                echo "File uploaded";
            }

        }
    }


// Handles the saving of a playlist from the playlist form
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save-playlist'])) {

        $playlist = isset($_POST['playlist']) ? $_POST['playlist'] : null;
        if($playlist) {
            save_playlist($playlist);
            echo "Playlist added!";
        }

    }

// Handles the saving a song to a playlist
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add-to-playlist'])) {

        $media = isset($_POST['media']) ? $_POST['media'] : [];
        $playlistId = isset($_POST['addtoplaylist']) ? $_POST['addtoplaylist'] : null;
        if($playlistId) {
            if(count($media) > 0) {
                foreach ($media as $mid) {
                    save_to_playlist($mid, $playlistId);
                }
            }
        }

    }


?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media player</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
<!-- GO to https://code.jquery.com/ and copy cdn code + ONCE YOU'VE STARTED WRITING FOR EACH CODE-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
</head>
<body>


    <div class="container">
        <h1>MP3 Player</h1>

        <form method="POST" enctype="multipart/form-data">

            <input type="file" name="file">
            <button type="submit" name="save-media">Save file</button>

        </form>

        <h3>Create Playlist</h3>
        <form method="POST">

            <input type="text" name="playlist">
            <button type="submit" name="save-playlist">Save Playlist</button>

        </form>

        <br>
        <form method="get">
        Playlists: 
            <select name="playlist">

                <option selected value>--Select a playlist--</option>
                <option value="all">All songs</option>
                <?php foreach(get_playlists() as $prow): ?>

                    <option value="<?php echo $prow["id"]; ?>">
                        <?php echo $prow["name"]; ?>
                    </option>

                <?php endforeach; ?>

            </select>
            <button>Select Playlist</button>
        </form>
            
        <br><br>

        <button id="pause-button">Pause</button>
        <button id="from-start">From Start</button>
        <button id="next-button">Next</button>

        <form method="POST">
            <select name="addtoplaylist">
            <option selected value>--Select a playlist--</option>
            <?php foreach(get_playlists() as $prow): ?>

                <option value="<?php echo $prow["id"]; ?>">
                    <?php echo $prow["name"]; ?>
                </option>

            <?php endforeach; ?>

        </select>
            <button type="submit" name="add-to-playlist">Add to Playlist!</button>
            <!-- A foreach that loops through the database and returns all audio files in media able  -->
            <ul>
                <?php $count=0; foreach(get_media() as $media): ?>
                    <li><input type="checkbox" name="media[]" value="<?php echo $media["id"]; ?>"/>
                        <a data-count="<?php echo $count; ?>" class="play-media" href="javascript:void(0);" data-file="./uploads/<?php echo $media["file"]; ?>">
                            <?php echo $media['description']; ?>
                        </a>
                    </li>
                <?php $count++; endforeach;?>
            </ul>
        </form>
    </div>
    

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js" integrity="sha384-+YQ4JLhjyBLPDQt//I+STsc9iw4uQqACwlvpslubQzn4u2UU2UFM80nGisd026JF" crossorigin="anonymous"></script>
<script>
    // plays audio + Links to for each above ^^
    $(document).ready(function() {

        var audio = null;
        var currentFile = null;
        var playlist = <?php echo get_play_queue(); ?>;
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
</script>
</html>