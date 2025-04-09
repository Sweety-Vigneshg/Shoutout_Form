<?php
define('DSN', 'mysql:host=localhost;dbname=shoutout_db;charset=utf8mb4');
define('DB_USER', 'root');
define('DB_PASS', '');
define('PDO_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);