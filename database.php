<?php
// database.php
// Centralized DB connection for YoChat
function get_db_connection()
{
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'yochat_db';
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
    return $conn;
}
