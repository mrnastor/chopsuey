<?php

define( 'WORKFOLDER_DIR', __DIR__ );
define( 'BASE_DIR', __DIR__ );

define( 'FINISHED_FOLDER', WORKFOLDER_DIR . "finished_jobs/" );
define( 'ONGOING_FOLDER', WORKFOLDER_DIR . "ongoing_jobs/" );
define( 'RAWIMAGE_FOLDER', WORKFOLDER_DIR . "raw_image/" );
define( 'ENC_IMG_FOLDER', BASE_DIR . "content_data/encoded_images/" );
define( 'SCRIPTS_FOLDER', BASE_DIR . "scripts/" );
define( 'SCRIPTS_LOG_FILE', BASE_DIR . "logs/scripts.log" );

define( 'ENCODE_COMMAND', SCRIPTS_FOLDER .  "encode_movie.sh");
define( 'DOWNLOAD_COMMAND', SCRIPTS_FOLDER .  "download_from_ftp.sh");
define( 'UPLOAD_COMMAND', SCRIPTS_FOLDER .  "upload_to_origin.sh");
define( 'RESIZE_IMG_COMMAND', SCRIPTS_FOLDER .  "encode_picture.sh");
define( 'PS_COMMAND', "ps ");
define( 'COPY_COMMAND', "cp ");

define( 'ENCODER_MAIL_FROMADDRESS', "test@from.com");
define( 'ENCODER_MAIL_CC_LIST', "test@email.com");

define( 'MAX_SIMULTANEOUS_ENCODE', 2);
define( 'MAX_SIMULTANEOUS_UPLOAD', 2);
define( 'MAX_SIMULTANEOUS_DOWNLOAD', 1);
define( 'MAX_SIMULTANEOUS_ENCODE_DOWNLOAD', 2);
define( 'MAX_SIMULTANEOUS_UPLOAD_DOWNLOAD', 2);
define( 'MAX_SIMULTANEOUS_ENCODE_UPLOAD', 3);

//REDIS KEYS
define( 'REDIS_WAITING', 		"waiting");
define( 'REDIS_DOWNLOADING', 	"downloading");
define( 'REDIS_ENCODE_WAITING',	"encode_waiting");
define( 'REDIS_ENCODING',		"encoding");
define( 'REDIS_UPLOAD_WAITING',	"upload_waiting");
define( 'REDIS_UPLOADING',		"uploading");


?>
