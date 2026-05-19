<?php
session_start();

$host   = 'localhost';
$user   = 'root';
$pass   = '';          // XAMPP তে password খালি থাকে
$dbname = 'garbage_system';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;padding:40px;color:red;'>
        <h2>❌ Database Connection Failed</h2>
        <p>" . $conn->connect_error . "</p>
        <p>XAMPP এ MySQL চালু আছে কিনা দেখুন।</p>
    </div>");
}

$conn->set_charset('utf8');

// Upload directory
if (!is_dir(__DIR__ . '/uploads')) {
    mkdir(__DIR__ . '/uploads', 0777, true);
}
?>
