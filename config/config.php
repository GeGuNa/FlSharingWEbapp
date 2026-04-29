<?php
defined('CONFIG_LOADED') or define('CONFIG_LOADED', true);


define('DB_HOST', 'localhost');
define('DB_NAME', 'filevault');
define('DB_USER', 'root');
define('DB_PASS', '');


define('JWT_SECRET', 'CHANGE_THIS_TO_A_MINIMUM_32_CHAR_RANDOM_STRING');
define('JWT_EXPIRY', 86400); 

define('UPLOAD_MAX_SIZE', 104857600); 
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL', '/uploads');


define('ALLOWED_EXTENSIONS', serialize([
    'jpg','jpeg','png','gif','webp','svg','bmp','ico',
    'pdf','doc','docx','xls','xlsx','ppt','pptx','odt','ods',
    'zip','rar','7z','tar','gz',
    'txt','csv','json','xml','md','rtf',
    'mp3','wav','ogg','flac','aac',
    'mp4','avi','mov','wmv','mkv','webm','flv',
    'html','css','js','ts','py','java','cpp','c','sql','php','rb','go','rs'
]));


define('APP_NAME', 'FileVault');
define('APP_URL', 'http://localhost/filevault');
